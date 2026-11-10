<?php
// Lista das minhas candidaturas
require_once __DIR__ . '/../../includes/bootstrap.php';

$u = exigir_perfil('candidato');

$s = db()->prepare(
    'SELECT c.id, c.estado, c.data_submissao, b.designacao
     FROM candidaturas c JOIN bolsas b ON b.id = c.bolsa_id
     WHERE c.utilizador_id = :u ORDER BY c.data_submissao DESC'
);
$s->execute([':u' => $u['id']]);
$lista = $s->fetchAll();

$titulo = 'As minhas candidaturas';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1>As minhas candidaturas</h1>
<?php if (!$lista): ?>
    <p>Ainda não submeteu candidaturas. Veja as <a href="../index.php">bolsas disponíveis</a>.</p>
<?php else: ?>
<table class="tabela">
    <thead><tr><th>Bolsa</th><th>Submetida em</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
        <tr>
            <td><?= e($c['designacao']) ?></td>
            <td><?= e(formatar_datahora($c['data_submissao'])) ?></td>
            <td><?= estado_badge($c['estado']) ?></td>
            <td><a href="candidatura.php?id=<?= (int)$c['id'] ?>">Ver detalhes</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
