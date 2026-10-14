<?php
// Área pública: lista e pesquisa de bolsas (não requer autenticação)
require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

// Filtros vindos do GET: tratados como texto e passados sempre por parâmetros
$q    = trim((string)($_GET['q'] ?? ''));
$tipo = trim((string)($_GET['tipo'] ?? ''));
$area = trim((string)($_GET['area'] ?? ''));

// Limitar tamanho para evitar abusos
$q    = mb_substr($q, 0, 100);
$tipo = mb_substr($tipo, 0, 60);
$area = mb_substr($area, 0, 80);

$sql    = 'SELECT id, designacao, tipo_apoio, area_estudo, montante, prazo_candidatura FROM bolsas WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND designacao LIKE :q';
    // Escapar os curingas do LIKE para que o utilizador pesquise texto literal
    $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
}
if ($tipo !== '') {
    $sql .= ' AND tipo_apoio = :tipo';
    $params[':tipo'] = $tipo;
}
if ($area !== '') {
    $sql .= ' AND area_estudo = :area';
    $params[':area'] = $area;
}
$sql .= ' ORDER BY prazo_candidatura ASC, designacao ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bolsas = $stmt->fetchAll();

// Valores para as listas de seleção
$tipos = $pdo->query('SELECT DISTINCT tipo_apoio FROM bolsas ORDER BY tipo_apoio')->fetchAll(PDO::FETCH_COLUMN);
$areas = $pdo->query('SELECT DISTINCT area_estudo FROM bolsas ORDER BY area_estudo')->fetchAll(PDO::FETCH_COLUMN);

$hoje   = date('Y-m-d');
$titulo = 'Bolsas disponíveis';
require __DIR__ . '/../includes/header.php';
?>
<h1>Bolsas e apoios financeiros</h1>

<form method="get" action="index.php" class="pesquisa">
    <label>Designação
        <input type="text" name="q" value="<?= e($q) ?>" maxlength="100">
    </label>
    <label>Tipo de apoio
        <select name="tipo">
            <option value="">Todos</option>
            <?php foreach ($tipos as $t): ?>
                <option value="<?= e($t) ?>" <?= $t === $tipo ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Área de estudo
        <select name="area">
            <option value="">Todas</option>
            <?php foreach ($areas as $a): ?>
                <option value="<?= e($a) ?>" <?= $a === $area ? 'selected' : '' ?>><?= e($a) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Pesquisar</button>
</form>

<?php if (!$bolsas): ?>
    <p>Não foram encontradas bolsas com esses critérios.</p>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Designação</th>
                <th>Tipo</th>
                <th>Área</th>
                <th>Montante</th>
                <th>Prazo</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bolsas as $b): ?>
            <tr>
                <td><a href="bolsa.php?id=<?= (int)$b['id'] ?>"><?= e($b['designacao']) ?></a></td>
                <td><?= e($b['tipo_apoio']) ?></td>
                <td><?= e($b['area_estudo']) ?></td>
                <td><?= e(formatar_eur($b['montante'])) ?></td>
                <td>
                    <?= e(formatar_data($b['prazo_candidatura'])) ?>
                    <?php if ($b['prazo_candidatura'] < $hoje): ?>
                        <span class="etiqueta">Encerrada</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
