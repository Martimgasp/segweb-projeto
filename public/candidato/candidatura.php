<?php
// Detalhe de uma candidatura, documentos e esclarecimentos
require_once __DIR__ . '/../../includes/bootstrap.php';

$u   = exigir_perfil('candidato');
$pdo = db();

$id = get_id('id');
$c  = $id !== null ? candidatura_do_candidato($id, $u['id']) : null;
if ($c === null) {
    erro_http(404, 'Candidatura não encontrada');   // 404 também para candidaturas de outros
}

$erros = [];

// Responder a um pedido de esclarecimento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $mensagem = post_str('mensagem');
    if ($mensagem === '' || mb_strlen($mensagem) > 2000 || !mb_check_encoding($mensagem, 'UTF-8')) {
        $erros[] = 'A resposta deve ter entre 1 e 2000 caracteres.';
    }
    $s = $pdo->prepare('SELECT COUNT(*) FROM documentos WHERE candidatura_id = :c');
    $s->execute([':c' => $c['id']]);
    [$ficheiros, $errosUp] = validar_uploads('documentos', (int)$s->fetchColumn());
    $erros = array_merge($erros, $errosUp);

    if (!$erros) {
        $criados = [];
        try {
            $pdo->beginTransaction();
            // Bloqueia a linha e revalida o estado (o técnico pode ter mudado entretanto)
            $s = $pdo->prepare('SELECT estado FROM candidaturas WHERE id = :id AND utilizador_id = :u FOR UPDATE');
            $s->execute([':id' => $c['id'], ':u' => $u['id']]);
            $estado = (string)$s->fetchColumn();

            if ($estado !== 'a_aguardar_documentacao') {
                $pdo->rollBack();
                $erros[] = 'A candidatura não está a aguardar documentação.';
            } else {
                $pdo->prepare(
                    "INSERT INTO esclarecimentos (candidatura_id, autor_id, tipo, mensagem) VALUES (:c, :a, 'resposta', :m)"
                )->execute([':c' => $c['id'], ':a' => $u['id'], ':m' => $mensagem]);
                $esclId = (int)$pdo->lastInsertId();
                guardar_documentos($ficheiros, (int)$c['id'], $u['id'], $esclId, $criados);

                // Resposta entregue: volta a "em análise"
                if ($estado === 'a_aguardar_documentacao') {
                    $pdo->prepare("UPDATE candidaturas SET estado = 'em_analise' WHERE id = :id")
                        ->execute([':id' => $c['id']]);
                    registar_historico($pdo, (int)$c['id'], $estado, 'em_analise', $u['id']);
                }
                $pdo->commit();
                flash_add('ok', 'Resposta enviada.');
                redirect(url('candidato/candidatura.php?id=' . (int)$c['id']));
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            apagar_ficheiros($criados);
            error_log('Erro ao responder: ' . $ex->getMessage());
            $erros[] = 'Erro interno. Tente novamente mais tarde.';
        }
    }
    $c = candidatura_do_candidato((int)$c['id'], $u['id']);   // estado atualizado
}

$s = $pdo->prepare(
    'SELECT d.id, d.nome_original, d.tamanho_bytes, d.enviado_em, d.esclarecimento_id
     FROM documentos d WHERE d.candidatura_id = :c ORDER BY d.enviado_em, d.id'
);
$s->execute([':c' => $c['id']]);
$documentos = $s->fetchAll();

$s = $pdo->prepare(
    'SELECT e.tipo, e.mensagem, e.criado_em, u.nome AS autor
     FROM esclarecimentos e JOIN utilizadores u ON u.id = e.autor_id
     WHERE e.candidatura_id = :c ORDER BY e.id'
);
$s->execute([':c' => $c['id']]);
$esclarecimentos = $s->fetchAll();

// Pode responder e anexar documentos sempre que a candidatura aguarda documentação
$podeResponder = $c['estado'] === 'a_aguardar_documentacao';

$titulo = 'Candidatura';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1><?= e($c['bolsa']) ?></h1>

<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert"><ul>
        <?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<dl class="detalhes">
    <dt>Estado</dt><dd><?= estado_badge($c['estado']) ?></dd>
    <dt>Instituição</dt><dd><?= e($c['instituicao']) ?></dd>
    <dt>Curso</dt><dd><?= e($c['curso']) ?></dd>
    <dt>Rendimento do agregado</dt><dd><?= e(formatar_eur($c['rendimento_agregado'])) ?></dd>
    <dt>IBAN</dt><dd><?= e(mascarar_iban(iban_ler($c['iban'], $u['id']))) ?></dd>
    <dt>Montante da bolsa</dt><dd><?= e(formatar_eur($c['montante'])) ?></dd>
    <dt>Submetida em</dt><dd><?= e(formatar_datahora($c['data_submissao'])) ?></dd>
    <dt>Última atualização</dt><dd><?= e(formatar_datahora($c['data_atualizacao'])) ?></dd>
</dl>

<?php if (pode_alterar($c)): ?>
    <p><a href="editar.php?id=<?= (int)$c['id'] ?>">Editar candidatura</a></p>
<?php endif; ?>

<h2>Documentos</h2>
<?php if (!$documentos): ?>
    <p>Sem documentos anexados.</p>
<?php else: ?>
<table class="tabela">
    <thead><tr><th>Ficheiro</th><th>Tamanho</th><th>Enviado em</th></tr></thead>
    <tbody>
    <?php foreach ($documentos as $doc): ?>
        <tr>
            <td><a href="../download.php?id=<?= (int)$doc['id'] ?>"><?= e($doc['nome_original']) ?></a></td>
            <td><?= e(formatar_tamanho((int)$doc['tamanho_bytes'])) ?></td>
            <td><?= e(formatar_datahora($doc['enviado_em'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>Esclarecimentos</h2>
<?php if (!$esclarecimentos): ?>
    <p>Sem pedidos de esclarecimento.</p>
<?php endif; ?>
<?php foreach ($esclarecimentos as $esc): ?>
    <div class="cartao">
        <p class="ajuda"><?= $esc['tipo'] === 'pedido' ? 'Pedido do técnico' : 'A sua resposta' ?>
            · <?= e($esc['autor']) ?> · <?= e(formatar_datahora($esc['criado_em'])) ?></p>
        <p><?= nl2br(e($esc['mensagem'])) ?></p>
    </div>
<?php endforeach; ?>

<?php if ($podeResponder): ?>
    <p class="alerta alerta-erro">O técnico precisa de documentação adicional. Envie uma mensagem e os documentos.</p>
    <form method="post" action="candidatura.php?id=<?= (int)$c['id'] ?>" enctype="multipart/form-data" class="form form-largo">
        <?= csrf_field() ?>
        <label>Mensagem para o técnico
            <textarea name="mensagem" rows="5" maxlength="2000" required></textarea>
        </label>
        <label>Documentos adicionais (opcional)
            <input type="file" name="documentos[]" multiple accept=".pdf,.jpg,.jpeg,.png">
            <span class="ajuda">PDF, JPG ou PNG, até <?= e(formatar_tamanho(UPLOAD_MAX_BYTES)) ?> cada.</span>
        </label>
        <button type="submit">Enviar resposta</button>
    </form>
<?php endif; ?>

<?php if (pode_alterar($c)): ?>
    <h2>Cancelar candidatura</h2>
    <form method="post" action="cancelar.php" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <label class="linha"><input type="checkbox" name="confirmo" value="1" required>
            Confirmo que quero cancelar esta candidatura</label>
        <button type="submit" class="perigo">Cancelar candidatura</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
