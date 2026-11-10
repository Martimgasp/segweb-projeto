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

// Caminho relativo à raiz pública (public/), funciona em qualquer subpasta
function url(string $caminho): string
{
    return (defined('RAIZ') ? RAIZ : '') . ltrim($caminho, '/');
}

// Só deve receber URLs internas geradas por url() (nunca valores do utilizador)
function redirect(string $destino): never
{
    header('Location: ' . $destino);
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

// Lê um campo de texto de $_POST (devolve '' se ausente ou se for array)
function post_str(string $chave, bool $trim = true): string
{
    $v = $_POST[$chave] ?? '';
    if (!is_string($v)) {
        return '';
    }
    return $trim ? trim($v) : $v;
}

// Mensagens de uma só utilização (mostradas no cabeçalho da página seguinte)
function flash_add(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $mensagem];
}

function flash_obter(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function erro_http(int $codigo, string $mensagem): never
{
    http_response_code($codigo);
    $titulo = $mensagem;
    require __DIR__ . '/header.php';
    echo '<h1>' . e($mensagem) . '</h1>';
    echo '<p><a href="' . e(url('index.php')) . '">Voltar ao início</a></p>';
    require __DIR__ . '/footer.php';
    exit;
}

// Inteiro positivo vindo de $_POST (ou null)
function post_id(string $chave): ?int
{
    $v = filter_var($_POST[$chave] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $v === false ? null : $v;
}

function formatar_datahora(string $dt): string
{
    $t = strtotime($dt);
    return $t ? date('d/m/Y H:i', $t) : '';
}

function formatar_tamanho(int $bytes): string
{
    return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '') . ' MB'
                             : max(1, (int)round($bytes / 1024)) . ' KB';
}
