<?php
// Área do técnico de avaliação (só perfil "tecnico"). Página inicial provisória.
require_once __DIR__ . '/../../includes/bootstrap.php';

$u = exigir_perfil('tecnico');

$titulo = 'Área do técnico';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Área do técnico de avaliação</h1>
<div class="cartao">
    <p>Olá, <strong><?= e($u['nome']) ?></strong>.</p>
    <p>Em construção: candidaturas atribuídas, estados, observações e esclarecimentos.</p>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
