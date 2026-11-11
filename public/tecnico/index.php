<?php
// Área do técnico: candidaturas atribuídas, com pesquisa e filtros
require_once __DIR__ . '/../../includes/bootstrap.php';

$u   = exigir_perfil('tecnico');
$pdo = db();

// Filtros (GET), todos validados
$nome   = trim((string)($_GET['nome'] ?? ''));
$bolsa  = get_id('bolsa');
$estado = (string)($_GET['estado'] ?? '');
$de     = (string)($_GET['de'] ?? '');
$ate    = (string)($_GET['ate'] ?? '');
if (!array_key_exists($estado, ESTADOS)) { $estado = ''; }
if (!data_valida($de))  { $de = ''; }
if (!data_valida($ate)) { $ate = ''; }
$nome = mb_substr($nome, 0, 100);

// Sempre limitado às candidaturas deste técnico
$where  = ['c.tecnico_id = :t'];
$params = [':t' => $u['id']];
if ($nome !== '') {
    $where[] = 'ca.nome LIKE :nome';
    $params[':nome'] = '%' . addcslashes($nome, '%_\\') . '%';   // % e _ tratados como texto
}
if ($bolsa !== null) { $where[] = 'c.bolsa_id = :bolsa'; $params[':bolsa'] = $bolsa; }
if ($estado !== '')  { $where[] = 'c.estado = :estado';   $params[':estado'] = $estado; }
if ($de !== '')      { $where[] = 'DATE(c.data_submissao) >= :de';  $params[':de'] = $de; }
if ($ate !== '')     { $where[] = 'DATE(c.data_submissao) <= :ate'; $params[':ate'] = $ate; }

$s = $pdo->prepare(
    'SELECT c.id, c.estado, c.data_submissao, b.designacao, ca.nome AS candidato
     FROM candidaturas c
     JOIN bolsas b ON b.id = c.bolsa_id
     JOIN utilizadores ca ON ca.id = c.utilizador_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY c.data_submissao DESC LIMIT 200'
);
$s->execute($params);
$lista = $s->fetchAll();

// Bolsas para o filtro (só as das minhas candidaturas)
$s = $pdo->prepare(
    'SELECT DISTINCT b.id, b.designacao FROM candidaturas c JOIN bolsas b ON b.id = c.bolsa_id
     WHERE c.tecnico_id = :t ORDER BY b.designacao'
);
$s->execute([':t' => $u['id']]);
$bolsas = $s->fetchAll();

$titulo = 'Candidaturas atribuídas';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Candidaturas atribuídas</h1>

<form method="get" action="index.php" class="pesquisa">
    <label>Candidato
        <input type="text" name="nome" value="<?= e($nome) ?>" maxlength="100">
    </label>
    <label>Bolsa
        <select name="bolsa">
            <option value="">Todas</option>
            <?php foreach ($bolsas as $b): ?>
                <option value="<?= (int)$b['id'] ?>" <?= $bolsa === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['designacao']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Estado
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach (ESTADOS as $k => $rot): ?>
                <option value="<?= e($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= e($rot) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Submetida de <input type="date" name="de" value="<?= e($de) ?>"></label>
    <label>até <input type="date" name="ate" value="<?= e($ate) ?>"></label>
    <button type="submit">Filtrar</button>
    <a href="index.php">Limpar</a>
</form>

<?php if (!$lista): ?>
    <p>Sem candidaturas para mostrar.</p>
<?php else: ?>
<table class="tabela">
    <thead><tr><th>Candidato</th><th>Bolsa</th><th>Submetida em</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
        <tr>
            <td><?= e($c['candidato']) ?></td>
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
