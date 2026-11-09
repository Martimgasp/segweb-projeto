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

// Mínimo 8 caracteres, com minúscula, maiúscula e dígito.
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
