<?php
// Área do candidato (só perfil "candidato"). Página inicial provisória.
require_once __DIR__ . '/../../includes/bootstrap.php';

$u = exigir_perfil('candidato');

$titulo = 'Área do candidato';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Área do candidato</h1>
<div class="cartao">
    <p>Olá, <strong><?= e($u['nome']) ?></strong>.</p>
    <p>Em construção: perfil, candidaturas, documentos e esclarecimentos.</p>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
