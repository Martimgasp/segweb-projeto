<?php
// Detalhe da candidatura (técnico): dados, documentos, estado, observações e esclarecimentos
require_once __DIR__ . '/../../includes/bootstrap.php';

$u   = exigir_perfil('tecnico');
$pdo = db();

$id = get_id('id');
$c  = $id !== null ? candidatura_do_tecnico($id, $u['id']) : null;
if ($c === null) {
    erro_http(404, 'Candidatura não encontrada');   // inclui as de outros técnicos
}
$destino = url('tecnico/candidatura.php?id=' . (int)$c['id']);
$erros   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $acao = post_str('acao');
    $texto = post_str('texto');
    $textoOk = $texto !== '' && mb_strlen($texto) <= 2000 && mb_check_encoding($texto, 'UTF-8');

    try {
        $pdo->beginTransaction();
        // Bloqueia a linha e relê o estado (só se continuar atribuída a este técnico)
        $s = $pdo->prepare('SELECT estado FROM candidaturas WHERE id = :id AND tecnico_id = :t FOR UPDATE');
        $s->execute([':id' => $c['id'], ':t' => $u['id']]);
        $atual = $s->fetchColumn();
        if ($atual === false) {
            throw new RuntimeException('Candidatura já não atribuída');
        }

        if ($acao === 'estado') {
            $novo = post_str('novo_estado');
            if (!in_array($novo, TRANSICOES[$atual] ?? [], true)) {
                $erros[] = 'Transição de estado não permitida (' . estado_label($atual) . ' → ' . estado_label($novo) . ').';
            } else {
                $pdo->prepare('UPDATE candidaturas SET estado = :e WHERE id = :id')
                    ->execute([':e' => $novo, ':id' => $c['id']]);
                registar_historico($pdo, (int)$c['id'], $atual, $novo, $u['id']);
                flash_add('ok', 'Estado atualizado para «' . estado_label($novo) . '».');
            }
        } elseif ($acao === 'observacao') {
            if (!$textoOk) {
                $erros[] = 'A observação deve ter entre 1 e 2000 caracteres.';
            } else {
                $pdo->prepare('INSERT INTO observacoes_internas (candidatura_id, tecnico_id, texto) VALUES (:c, :t, :x)')
                    ->execute([':c' => $c['id'], ':t' => $u['id'], ':x' => $texto]);
                flash_add('ok', 'Observação registada.');
            }
        } elseif ($acao === 'esclarecimento') {
            if (!$textoOk) {
                $erros[] = 'O pedido deve ter entre 1 e 2000 caracteres.';
            } elseif ($atual !== 'em_analise') {
                $erros[] = 'Só é possível pedir esclarecimentos com a candidatura em análise.';
            } else {
                $pdo->prepare("INSERT INTO esclarecimentos (candidatura_id, autor_id, tipo, mensagem) VALUES (:c, :a, 'pedido', :m)")
                    ->execute([':c' => $c['id'], ':a' => $u['id'], ':m' => $texto]);
                $pdo->prepare("UPDATE candidaturas SET estado = 'a_aguardar_documentacao' WHERE id = :id")
                    ->execute([':id' => $c['id']]);
                registar_historico($pdo, (int)$c['id'], $atual, 'a_aguardar_documentacao', $u['id']);
                flash_add('ok', 'Pedido enviado ao candidato.');
            }
        } else {
            $erros[] = 'Ação inválida.';
        }

        if ($erros) {
            $pdo->rollBack();
        } else {
            $pdo->commit();
            redirect($destino);
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro (técnico): ' . $ex->getMessage());
        $erros[] = 'Erro interno. Tente novamente mais tarde.';
    }
    $c = candidatura_do_tecnico((int)$c['id'], $u['id']) ?? $c;   // estado atualizado
}

$q = function (string $sql) use ($pdo, $c): array {
    $s = $pdo->prepare($sql);
    $s->execute([':c' => $c['id']]);
    return $s->fetchAll();
};
$documentos = $q('SELECT id, nome_original, tamanho_bytes, enviado_em FROM documentos WHERE candidatura_id = :c ORDER BY enviado_em, id');
$escl       = $q('SELECT e.tipo, e.mensagem, e.criado_em, x.nome AS autor FROM esclarecimentos e JOIN utilizadores x ON x.id = e.autor_id WHERE e.candidatura_id = :c ORDER BY e.id');
$obs        = $q('SELECT o.texto, o.criado_em, x.nome AS tecnico FROM observacoes_internas o JOIN utilizadores x ON x.id = o.tecnico_id WHERE o.candidatura_id = :c ORDER BY o.id DESC');
$hist       = $q('SELECT h.estado_anterior, h.estado_novo, h.alterado_em, x.nome AS responsavel FROM historico_estados h JOIN utilizadores x ON x.id = h.alterado_por WHERE h.candidatura_id = :c ORDER BY h.id');
$proximos   = TRANSICOES[$c['estado']] ?? [];

$titulo = 'Candidatura';
require __DIR__ . '/../../includes/header.php';
?>
<p><a href="index.php">&larr; Voltar à lista</a></p>
<h1><?= e($c['bolsa']) ?> · <?= e($c['candidato']) ?></h1>

<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert"><ul>
        <?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<h2>Dados pessoais</h2>
<dl class="detalhes">
    <dt>Nome</dt><dd><?= e($c['candidato']) ?></dd>
    <dt>Email</dt><dd><?= e($c['email']) ?></dd>
    <dt>Morada</dt><dd><?= e($c['morada'] ?? '—') ?></dd>
    <dt>Telefone</dt><dd><?= e($c['telefone'] ?? '—') ?></dd>
    <dt>NIF</dt><dd><?= e(nif_ler($c['nif'], (int)$c['utilizador_id']) ?: '—') ?></dd>
</dl>

<h2>Candidatura</h2>
<dl class="detalhes">
    <dt>Estado</dt><dd><?= estado_badge($c['estado']) ?></dd>
    <dt>Instituição</dt><dd><?= e($c['instituicao']) ?></dd>
    <dt>Curso</dt><dd><?= e($c['curso']) ?></dd>
    <dt>Rendimento do agregado</dt><dd><?= e(formatar_eur($c['rendimento_agregado'])) ?></dd>
    <dt>IBAN</dt><dd><?= e(iban_ler($c['iban'], (int)$c['utilizador_id']) ?: '—') ?></dd>
    <dt>Montante da bolsa</dt><dd><?= e(formatar_eur($c['montante'])) ?></dd>
    <dt>Submetida em</dt><dd><?= e(formatar_datahora($c['data_submissao'])) ?></dd>
</dl>

<h2>Documentos</h2>
<?php if (!$documentos): ?>
    <p>Sem documentos anexados.</p>
<?php else: ?>
<table class="tabela">
    <thead><tr><th>Ficheiro</th><th>Tamanho</th><th>Enviado em</th></tr></thead>
    <tbody>
    <?php foreach ($documentos as $d): ?>
        <tr>
            <td><a href="../download.php?id=<?= (int)$d['id'] ?>"><?= e($d['nome_original']) ?></a></td>
            <td><?= e(formatar_tamanho((int)$d['tamanho_bytes'])) ?></td>
            <td><?= e(formatar_datahora($d['enviado_em'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>Alterar estado</h2>
<?php if (!$proximos): ?>
    <p>Esta candidatura está num estado final (<?= e(estado_label($c['estado'])) ?>).</p>
<?php else: ?>
    <form method="post" action="candidatura.php?id=<?= (int)$c['id'] ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="estado">
        <label>Novo estado
            <select name="novo_estado" required>
                <?php foreach ($proximos as $p): ?>
                    <option value="<?= e($p) ?>"><?= e(estado_label($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Atualizar estado</button>
    </form>
<?php endif; ?>

<h2>Esclarecimentos</h2>
<?php if (!$escl): ?><p>Sem pedidos de esclarecimento.</p><?php endif; ?>
<?php foreach ($escl as $x): ?>
    <div class="cartao">
        <p class="ajuda"><?= $x['tipo'] === 'pedido' ? 'Pedido' : 'Resposta do candidato' ?>
            · <?= e($x['autor']) ?> · <?= e(formatar_datahora($x['criado_em'])) ?></p>
        <p><?= nl2br(e($x['mensagem'])) ?></p>
    </div>
<?php endforeach; ?>
<?php if ($c['estado'] === 'em_analise'): ?>
    <form method="post" action="candidatura.php?id=<?= (int)$c['id'] ?>" class="form form-largo">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="esclarecimento">
        <label>Pedir esclarecimento ou documentos adicionais
            <textarea name="texto" rows="4" maxlength="2000" required></textarea>
            <span class="ajuda">A candidatura passa a «A aguardar documentação».</span>
        </label>
        <button type="submit">Enviar pedido</button>
    </form>
<?php endif; ?>

<h2>Observações internas</h2>
<p class="ajuda">Visíveis apenas para técnicos; o candidato não as vê.</p>
<?php foreach ($obs as $o): ?>
    <div class="cartao">
        <p class="ajuda"><?= e($o['tecnico']) ?> · <?= e(formatar_datahora($o['criado_em'])) ?></p>
        <p><?= nl2br(e($o['texto'])) ?></p>
    </div>
<?php endforeach; ?>
<form method="post" action="candidatura.php?id=<?= (int)$c['id'] ?>" class="form form-largo">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="observacao">
    <label>Nova observação
        <textarea name="texto" rows="3" maxlength="2000" required></textarea>
    </label>
    <button type="submit">Registar observação</button>
</form>

<h2>Histórico de estados</h2>
<table class="tabela">
    <thead><tr><th>Data</th><th>De</th><th>Para</th><th>Responsável</th></tr></thead>
    <tbody>
    <?php foreach ($hist as $h): ?>
        <tr>
            <td><?= e(formatar_datahora($h['alterado_em'])) ?></td>
            <td><?= $h['estado_anterior'] !== null ? e(estado_label($h['estado_anterior'])) : '—' ?></td>
            <td><?= e(estado_label($h['estado_novo'])) ?></td>
            <td><?= e($h['responsavel']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
