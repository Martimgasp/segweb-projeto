<?php
// Cifra de campos sensíveis (NIF, IBAN) com AES-256-GCM.
// O "contexto" liga o valor à coluna e ao dono: copiado para outra linha, não decifra.

function chave_dados(): string
{
    $k = base64_decode((string)(app_config()['app']['chave_dados'] ?? ''), true);
    if ($k === false || strlen($k) !== 32) {
        throw new RuntimeException('Chave de cifra inválida.');
    }
    return $k;
}

function cifrar(?string $claro, string $contexto): ?string
{
    if ($claro === null || $claro === '') {
        return null;
    }
    $iv  = random_bytes(12);   // IV novo por valor
    $tag = '';
    $c   = openssl_encrypt($claro, 'aes-256-gcm', chave_dados(), OPENSSL_RAW_DATA, $iv, $tag, $contexto, 16);
    if ($c === false) {
        throw new RuntimeException('Falha ao cifrar.');
    }
    return 'v1:' . base64_encode($iv . $tag . $c);   // v1 = versão do formato
}

// Devolve null se o valor foi adulterado, está noutro contexto ou não está cifrado
function decifrar(?string $guardado, string $contexto): ?string
{
    if ($guardado === null || !str_starts_with($guardado, 'v1:')) {
        return null;
    }
    $bin = base64_decode(substr($guardado, 3), true);
    if ($bin === false || strlen($bin) < 29) {
        return null;
    }
    $claro = openssl_decrypt(substr($bin, 28), 'aes-256-gcm', chave_dados(), OPENSSL_RAW_DATA,
                             substr($bin, 0, 12), substr($bin, 12, 16), $contexto);
    return $claro === false ? null : $claro;
}

// Atalhos: o contexto fica num só sítio ($dono = id do candidato)
function iban_cifrar(string $iban, int $dono): ?string { return cifrar($iban, 'iban:' . $dono); }
function iban_ler(?string $guardado, int $dono): string { return decifrar($guardado, 'iban:' . $dono) ?? ''; }
function nif_cifrar(string $nif, int $dono): ?string   { return cifrar($nif, 'nif:' . $dono); }
function nif_ler(?string $guardado, int $dono): string  { return decifrar($guardado, 'nif:' . $dono) ?? ''; }

// PT50 **** **** **** **** 4154
function mascarar_iban(string $iban): string
{
    return strlen($iban) < 8 ? '—' : substr($iban, 0, 4) . ' **** **** **** **** ' . substr($iban, -4);
}
