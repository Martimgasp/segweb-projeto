<?php
// Inicialização comum a todas as páginas. Incluir no topo de cada script público.

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$cfg = app_config();

// Erros: nunca mostrar detalhes ao utilizador; registar em log
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// RAIZ: prefixo relativo até public/ (vazio na raiz, "../" numa subpasta, ...)
// Permite que header, links e redirecionamentos funcionem em qualquer pasta.
$_publico = realpath(__DIR__ . '/../public');
$_script  = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
$_nivel   = 0;
if ($_publico !== false && $_script !== false && str_starts_with($_script, $_publico)) {
    $_rel   = ltrim(str_replace('\\', '/', substr($_script, strlen($_publico))), '/');
    $_nivel = substr_count($_rel, '/');
}
define('RAIZ', str_repeat('../', $_nivel));
unset($_publico, $_script, $_nivel, $_rel);

// HTTPS obrigatório: redireciona HTTP -> HTTPS (host fixo da configuração)
if ($cfg['app']['force_https'] && !is_https()) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $cfg['app']['host'] . $uri, true, 301);
    exit;
}

// Cabeçalhos de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
if (is_https()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Sessão com cookie endurecido
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_name('BOLSASSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/candidaturas.php';
require_once __DIR__ . '/upload.php';

sessao_verificar_inatividade();
