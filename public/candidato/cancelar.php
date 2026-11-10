<?php
// Cancelar candidatura (só POST, só enquanto "submetida")
require_once __DIR__ . '/../../includes/bootstrap.php';

$u = exigir_perfil('candidato');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('candidato/candidaturas.php'));
}
csrf_verificar();

$id = post_id('id');
$c  = $id !== null ? candidatura_do_candidato($id, $u['id']) : null;
if ($c === null) {
    erro_http(404, 'Candidatura não encontrada');
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $s = $pdo->prepare('SELECT estado FROM candidaturas WHERE id = :id AND utilizador_id = :u FOR UPDATE');
    $s->execute([':id' => $c['id'], ':u' => $u['id']]);
    if ($s->fetchColumn() !== 'submetida' || post_str('confirmo') !== '1') {
        $pdo->rollBack();
        flash_add('erro', 'Esta candidatura já não pode ser cancelada.');
    } else {
        $pdo->prepare("UPDATE candidaturas SET estado = 'cancelada' WHERE id = :id")->execute([':id' => $c['id']]);
        registar_historico($pdo, (int)$c['id'], 'submetida', 'cancelada', $u['id']);
        $pdo->commit();
        flash_add('ok', 'Candidatura cancelada.');
    }
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao cancelar: ' . $ex->getMessage());
    flash_add('erro', 'Erro interno. Tente novamente mais tarde.');
}
redirect(url('candidato/candidatura.php?id=' . (int)$c['id']));
