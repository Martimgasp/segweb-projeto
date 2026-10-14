<?php
// Ligação à base de dados via PDO.
// - Erros como exceções
// - Prepared statements reais (EMULATE_PREPARES = false) contra SQL injection
// - Detalhes do erro vão para o log, nunca para o utilizador

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = app_config()['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['name'], $cfg['charset']);

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Falha na ligação à BD: ' . $e->getMessage());
        http_response_code(500);
        exit('Erro interno. Tente novamente mais tarde.');
    }

    return $pdo;
}
