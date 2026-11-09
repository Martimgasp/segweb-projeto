<?php
// Cabeçalho comum. Opcionalmente definir $titulo antes de incluir.
$tituloPagina = isset($titulo) ? $titulo . ' · ' : '';
$nomeApp = app_config()['app']['nome'];
$utilizadorNav = utilizador_atual();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina . $nomeApp) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<header class="topo">
    <div class="container">
        <a class="marca" href="<?= e(url('index.php')) ?>"><?= e($nomeApp) ?></a>
        <nav>
            <a href="<?= e(url('index.php')) ?>">Bolsas</a>
            <?php if ($utilizadorNav): ?>
                <a href="<?= e(url_area($utilizadorNav['perfil'])) ?>">A minha área</a>
                <form method="post" action="<?= e(url('logout.php')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-link">Sair (<?= e($utilizadorNav['nome']) ?>)</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Entrar</a>
                <a href="<?= e(url('registo.php')) ?>">Registar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php foreach (flash_obter() as $f): ?>
    <div class="alerta alerta-<?= $f['tipo'] === 'ok' ? 'ok' : 'erro' ?>" role="alert"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
