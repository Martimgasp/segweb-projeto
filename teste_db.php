<?php

$c = require __DIR__ . '/config/config.php';
$d = $c['db'];
try {
    new PDO("mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}",
        $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Ligação OK\n";
} catch (PDOException $e) {
    echo $e->getMessage() . "\n";
}