<?php
// Funções auxiliares

function app_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/config.php';
    }
    return $cfg;
}

function is_https(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

// Escape de TODO o output dinâmico em HTML (proteção contra XSS)
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $caminho): never
{
    header('Location: ' . $caminho);
    exit;
}

function formatar_eur(float|string $valor): string
{
    return number_format((float)$valor, 2, ',', ' ') . ' €';
}

function formatar_data(string $data): string
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d ? $d->format('d/m/Y') : '';
}

// Lê um inteiro positivo de $_GET (ou null se inválido)
function get_id(string $chave): ?int
{
    $v = filter_input(INPUT_GET, $chave, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $v === false ? null : $v;
}

function erro_http(int $codigo, string $mensagem): never
{
    http_response_code($codigo);
    $titulo = $mensagem;
    require __DIR__ . '/header.php';
    echo '<h1>' . e($mensagem) . '</h1>';
    echo '<p><a href="index.php">Voltar ao início</a></p>';
    require __DIR__ . '/footer.php';
    exit;
}
