<?php
// Área do candidato: página inicial
require_once __DIR__ . '/../../includes/bootstrap.php';

$u = exigir_perfil('candidato');

$s = db()->prepare("SELECT COUNT(*) FROM candidaturas WHERE utilizador_id = :u AND estado = 'a_aguardar_documentacao'");
$s->execute([':u' => $u['id']]);
$pendentes = (int)$s->fetchColumn();

$titulo = 'Área do candidato';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1>Área do candidato</h1>
<div class="cartao">
    <p>Olá, <strong><?= e($u['nome']) ?></strong>.</p>
    <?php if ($pendentes > 0): ?>
        <p class="alerta alerta-erro">Tem <?= $pendentes ?> candidatura(s) a aguardar documentação.
            <a href="candidaturas.php">Ver candidaturas</a></p>
    <?php endif; ?>
    <p>Consulte as <a href="../index.php">bolsas disponíveis</a> para se candidatar.</p>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
