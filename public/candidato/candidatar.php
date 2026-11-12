<?php
// Submeter candidatura a uma bolsa
require_once __DIR__ . '/../../includes/bootstrap.php';

$u   = exigir_perfil('candidato');
$pdo = db();

$bolsaId = get_id('bolsa');
if ($bolsaId === null) {
    erro_http(404, 'Bolsa não encontrada');
}
$s = $pdo->prepare(
    'SELECT id, designacao, requisitos, montante, prazo_candidatura, (prazo_candidatura >= CURDATE()) AS aberta
     FROM bolsas WHERE id = :id'
);
$s->execute([':id' => $bolsaId]);
$bolsa = $s->fetch();
if (!$bolsa) {
    erro_http(404, 'Bolsa não encontrada');
}

// Uma candidatura por bolsa
$s = $pdo->prepare('SELECT id FROM candidaturas WHERE utilizador_id = :u AND bolsa_id = :b');
$s->execute([':u' => $u['id'], ':b' => $bolsaId]);
if (($existente = $s->fetchColumn()) !== false) {
    flash_add('erro', 'Já submeteu uma candidatura a esta bolsa.');
    redirect(url('candidato/candidatura.php?id=' . (int)$existente));
}
// Dentro do prazo
if (!(int)$bolsa['aberta']) {
    flash_add('erro', 'O prazo de candidatura desta bolsa terminou.');
    redirect(url('bolsa.php?id=' . $bolsaId));
}

$d     = ['instituicao' => '', 'curso' => '', 'rendimento' => '', 'iban' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    [$d, $erros] = validar_dados_candidatura();
    [$ficheiros, $errosUp] = validar_uploads('documentos');
    $erros = array_merge($erros, $errosUp);

    if (!$erros) {
        $criados = [];
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                'INSERT INTO candidaturas (utilizador_id, bolsa_id, tecnico_id, instituicao, curso, rendimento_agregado, iban)
                 VALUES (:u, :b, :t, :i, :c, :r, :iban)'
            )->execute([
                ':u' => $u['id'], ':b' => $bolsaId, ':t' => atribuir_tecnico($pdo),
                ':i' => $d['instituicao'], ':c' => $d['curso'],
                ':r' => (float)str_replace(',', '.', $d['rendimento']), ':iban' => iban_cifrar($d['iban'], $u['id']),
            ]);
            $candId = (int)$pdo->lastInsertId();
            registar_historico($pdo, $candId, null, 'submetida', $u['id']);
            guardar_documentos($ficheiros, $candId, $u['id'], null, $criados);
            $pdo->commit();

            flash_add('ok', 'Candidatura submetida com sucesso.');
            redirect(url('candidato/candidatura.php?id=' . $candId));
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            apagar_ficheiros($criados);
            if ($ex instanceof PDOException && $ex->getCode() === '23000') {
                $erros[] = 'Já submeteu uma candidatura a esta bolsa.';
            } else {
                error_log('Erro ao submeter candidatura: ' . $ex->getMessage());
                $erros[] = 'Erro interno. Tente novamente mais tarde.';
            }
        }
    }
}

$titulo = 'Candidatar';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1>Candidatura: <?= e($bolsa['designacao']) ?></h1>
<div class="cartao">
    <p><strong>Montante:</strong> <?= e(formatar_eur($bolsa['montante'])) ?>
        · <strong>Prazo:</strong> <?= e(formatar_data($bolsa['prazo_candidatura'])) ?></p>
    <p><strong>Requisitos:</strong> <?= e($bolsa['requisitos']) ?></p>
</div>

<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert"><ul>
        <?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<?php
$acao  = 'candidatar.php?bolsa=' . $bolsaId;
$botao = 'Submeter candidatura';
require __DIR__ . '/../../includes/form_candidatura.php';
require __DIR__ . '/../../includes/footer.php';
