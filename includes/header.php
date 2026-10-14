<?php
// Cabeçalho comum. Opcionalmente definir $titulo antes de incluir.
$tituloPagina = isset($titulo) ? $titulo . ' · ' : '';
$nomeApp = app_config()['app']['nome'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina . $nomeApp) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topo">
    <div class="container">
        <a class="marca" href="index.php"><?= e($nomeApp) ?></a>
        <nav>
            <a href="index.php">Bolsas</a>
        </nav>
    </div>
</header>
<main class="container">
