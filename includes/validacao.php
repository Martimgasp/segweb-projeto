<?php
// Validação de dados de entrada no servidor (nunca confiar só no HTML/JS).
// Cada função devolve null se o valor é válido, ou a mensagem de erro.

function validar_username(string $v): ?string
{
    if (preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $v) !== 1) {
        return 'O username deve ter 3 a 30 caracteres (letras, números, _ . -).';
    }
    return null;
}

function validar_email(string $v): ?string
{
    if (strlen($v) > 255 || filter_var($v, FILTER_VALIDATE_EMAIL) === false) {
        return 'O email indicado não é válido.';
    }
    return null;
}

function validar_nome(string $v): ?string
{
    if (!mb_check_encoding($v, 'UTF-8')) {
        return 'O nome contém caracteres inválidos.';
    }
    $len = mb_strlen($v);
    if ($len < 2 || $len > 150) {
        return 'O nome deve ter entre 2 e 150 caracteres.';
    }
    if (preg_match('/[\x00-\x1F\x7F<>]/', $v) !== 0) {
        return 'O nome contém caracteres inválidos.';
    }
    return null;
}

// Mínimo 10 caracteres, com minúscula, maiúscula e dígito.
// Máximo 72 bytes: limite do bcrypt (acima disso seria truncada em silêncio).
function validar_password(string $v): ?string
{
    if (strlen($v) < 8) {
        return 'A palavra-passe deve ter pelo menos 8 caracteres.';
    }
    if (strlen($v) > 72) {
        return 'A palavra-passe não pode exceder 72 bytes.';
    }
    if (preg_match('/[a-z]/', $v) !== 1 || preg_match('/[A-Z]/', $v) !== 1 || preg_match('/\d/', $v) !== 1) {
        return 'A palavra-passe deve ter minúsculas, maiúsculas e pelo menos um número.';
    }
    return null;
}

// Texto livre de uma linha (sem caracteres de controlo)
function validar_texto(string $v, string $rotulo, int $min, int $max): ?string
{
    if (!mb_check_encoding($v, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $v) !== 0) {
        return "$rotulo contém caracteres inválidos.";
    }
    $len = mb_strlen($v);
    if ($len < $min || $len > $max) {
        return "$rotulo deve ter entre $min e $max caracteres.";
    }
    return null;
}

// NIF português: 9 dígitos + dígito de controlo (módulo 11)
function validar_nif(string $v): ?string
{
    if (preg_match('/^\d{9}$/', $v) !== 1) {
        return 'O NIF deve ter 9 dígitos.';
    }
    $soma = 0;
    for ($i = 0; $i < 8; $i++) {
        $soma += (int)$v[$i] * (9 - $i);
    }
    $ctrl = 11 - ($soma % 11);
    if ($ctrl >= 10) {
        $ctrl = 0;
    }
    return $ctrl === (int)$v[8] ? null : 'O NIF indicado não é válido.';
}

// Telefone: 9 dígitos, com ou sem +351 (espaços já removidos)
function validar_telefone(string $v): ?string
{
    return preg_match('/^(\+351)?\d{9}$/', $v) === 1 ? null : 'O telefone deve ter 9 dígitos (ex.: 912345678).';
}

// IBAN português (PT + 23 dígitos) com verificação módulo 97
function iban_valido(string $iban): bool
{
    if (preg_match('/^PT\d{23}$/', $iban) !== 1) {
        return false;
    }
    $num   = strtr(substr($iban, 4) . substr($iban, 0, 4), ['P' => '25', 'T' => '29']);
    $resto = 0;
    foreach (str_split($num, 7) as $bloco) {
        $resto = (int)($resto . $bloco) % 97;
    }
    return $resto === 1;
}
