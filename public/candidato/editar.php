<?php
// Editar candidatura (só enquanto estiver "submetida")
require_once __DIR__ . '/../../includes/bootstrap.php';

$u   = exigir_perfil('candidato');
$pdo = db();

$id = get_id('id');
$c  = $id !== null ? candidatura_do_candidato($id, $u['id']) : null;
if ($c === null) {
    erro_http(404, 'Candidatura não encontrada');
}
if (!pode_alterar($c)) {
    flash_add('erro', 'Esta candidatura já não pode ser editada.');
    redirect(url('candidato/candidatura.php?id=' . (int)$c['id']));
}

$d = [
    'instituicao' => $c['instituicao'],
    'curso'       => $c['curso'],
    'rendimento'  => number_format((float)$c['rendimento_agregado'], 2, ',', ''),
    'iban'        => $c['iban'],
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    [$d, $erros] = validar_dados_candidatura();
    $s = $pdo->prepare('SELECT COUNT(*) FROM documentos WHERE candidatura_id = :c');
    $s->execute([':c' => $c['id']]);
    [$ficheiros, $errosUp] = validar_uploads('documentos', (int)$s->fetchColumn());
    $erros = array_merge($erros, $errosUp);

    if (!$erros) {
        $criados = [];
        try {
            $pdo->beginTransaction();
            // Revalida o estado com a linha bloqueada
            $s = $pdo->prepare('SELECT estado FROM candidaturas WHERE id = :id AND utilizador_id = :u FOR UPDATE');
            $s->execute([':id' => $c['id'], ':u' => $u['id']]);
            if ($s->fetchColumn() !== 'submetida') {
                $pdo->rollBack();
                flash_add('erro', 'Esta candidatura já não pode ser editada.');
                redirect(url('candidato/candidatura.php?id=' . (int)$c['id']));
            }
            $pdo->prepare(
                'UPDATE candidaturas SET instituicao = :i, curso = :c, rendimento_agregado = :r, iban = :iban WHERE id = :id'
            )->execute([
                ':i' => $d['instituicao'], ':c' => $d['curso'],
                ':r' => (float)str_replace(',', '.', $d['rendimento']), ':iban' => $d['iban'], ':id' => $c['id'],
            ]);
            guardar_documentos($ficheiros, (int)$c['id'], $u['id'], null, $criados);
            $pdo->commit();
            flash_add('ok', 'Candidatura atualizada.');
            redirect(url('candidato/candidatura.php?id=' . (int)$c['id']));
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            apagar_ficheiros($criados);
            error_log('Erro ao editar candidatura: ' . $ex->getMessage());
            $erros[] = 'Erro interno. Tente novamente mais tarde.';
        }
    }
}

$titulo = 'Editar candidatura';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1>Editar candidatura: <?= e($c['bolsa']) ?></h1>
<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert"><ul>
        <?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>
<?php
$acao  = 'editar.php?id=' . (int)$c['id'];
$botao = 'Guardar alterações';
require __DIR__ . '/../../includes/form_candidatura.php';
?>
<p><a href="candidatura.php?id=<?= (int)$c['id'] ?>">&larr; Voltar à candidatura</a></p>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
