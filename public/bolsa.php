<?php
// Área pública: detalhe de uma bolsa (requisitos, montante e prazo)
require_once __DIR__ . '/../includes/bootstrap.php';

$id = get_id('id');
if ($id === null) {
    erro_http(404, 'Bolsa não encontrada');
}

$stmt = db()->prepare(
    'SELECT id, designacao, tipo_apoio, area_estudo, descricao, requisitos, montante, prazo_candidatura
     FROM bolsas WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$bolsa = $stmt->fetch();

if (!$bolsa) {
    erro_http(404, 'Bolsa não encontrada');
}

$encerrada = $bolsa['prazo_candidatura'] < date('Y-m-d');
$titulo    = $bolsa['designacao'];
require __DIR__ . '/../includes/header.php';
?>
<p><a href="index.php">&larr; Voltar à lista</a></p>
<h1><?= e($bolsa['designacao']) ?></h1>

<dl class="detalhes">
    <dt>Tipo de apoio</dt>
    <dd><?= e($bolsa['tipo_apoio']) ?></dd>
    <dt>Área de estudo</dt>
    <dd><?= e($bolsa['area_estudo']) ?></dd>
    <dt>Montante</dt>
    <dd><?= e(formatar_eur($bolsa['montante'])) ?></dd>
    <dt>Prazo de candidatura</dt>
    <dd>
        <?= e(formatar_data($bolsa['prazo_candidatura'])) ?>
        <?php if ($encerrada): ?><span class="etiqueta">Encerrada</span><?php endif; ?>
    </dd>
</dl>

<h2>Descrição</h2>
<p><?= nl2br(e($bolsa['descricao'])) ?></p>

<h2>Requisitos</h2>
<p><?= nl2br(e($bolsa['requisitos'])) ?></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
