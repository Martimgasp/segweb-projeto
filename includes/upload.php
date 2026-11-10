<?php
// Upload seguro de documentos

const UPLOAD_MAX_BYTES    = 2 * 1024 * 1024;   // 2 MB por ficheiro (limite do php.ini no XAMPP)
const UPLOAD_MAX_POR_VEZ  = 5;
const UPLOAD_MAX_TOTAL    = 15;                // por candidatura
// Tipos permitidos, decididos pelo CONTEÚDO do ficheiro (não pela extensão)
const UPLOAD_TIPOS = ['application/pdf', 'image/jpeg', 'image/png'];

function nome_ficheiro_seguro(string $nome): string
{
    $nome = basename(str_replace('\\', '/', $nome));
    $nome = (string)preg_replace('/[\x00-\x1F\x7F]/', '', $nome);
    $nome = mb_check_encoding($nome, 'UTF-8') ? mb_substr($nome, 0, 200) : '';
    return $nome !== '' ? $nome : 'documento';
}

function pasta_uploads(): string
{
    $dir = rtrim(app_config()['app']['upload_dir'], '/\\');
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir;
}

// Caminho no disco de um documento (nome_armazenado tem de ser 64 hex)
function caminho_documento(string $nomeArmazenado): ?string
{
    if (preg_match('/^[a-f0-9]{64}$/', $nomeArmazenado) !== 1) {
        return null;
    }
    return pasta_uploads() . DIRECTORY_SEPARATOR . $nomeArmazenado;
}

// Valida $_FILES[$campo] (input múltiplo). Devolve [ficheiros_validos, erros].
function validar_uploads(string $campo, int $jaExistentes = 0): array
{
    $f = $_FILES[$campo] ?? null;
    if (!is_array($f) || !is_array($f['name'] ?? null)) {
        return [[], []];
    }
    $validos = [];
    $erros   = [];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);

    foreach ($f['name'] as $i => $nomeOriginal) {
        $erro = $f['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $nome = nome_ficheiro_seguro((string)$nomeOriginal);

        if (count($validos) >= UPLOAD_MAX_POR_VEZ || $jaExistentes + count($validos) >= UPLOAD_MAX_TOTAL) {
            $erros[] = 'Foi atingido o número máximo de documentos.';
            break;
        }
        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            $erros[] = "«{$nome}» excede o tamanho máximo de " . formatar_tamanho(UPLOAD_MAX_BYTES) . '.';
            continue;
        }
        $tmp = (string)($f['tmp_name'][$i] ?? '');
        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            $erros[] = "Não foi possível receber «{$nome}».";
            continue;
        }
        $tamanho = (int)filesize($tmp);
        if ($tamanho <= 0 || $tamanho > UPLOAD_MAX_BYTES) {
            $erros[] = "«{$nome}» está vazio ou excede " . formatar_tamanho(UPLOAD_MAX_BYTES) . '.';
            continue;
        }
        $mime = (string)$finfo->file($tmp);
        if (!in_array($mime, UPLOAD_TIPOS, true)) {
            $erros[] = "«{$nome}» não é permitido (apenas PDF, JPG ou PNG).";
            continue;
        }
        $validos[] = ['tmp' => $tmp, 'nome' => $nome, 'mime' => $mime, 'tamanho' => $tamanho];
    }
    return [$validos, $erros];
}

// Move os ficheiros para storage/ e regista-os na BD (chamar dentro de uma transação).
// $criados recebe os caminhos já escritos, para o chamador os apagar se houver rollback.
function guardar_documentos(array $ficheiros, int $candId, int $userId, ?int $esclId, array &$criados): void
{
    $dir  = pasta_uploads();
    $stmt = db()->prepare(
        'INSERT INTO documentos (candidatura_id, esclarecimento_id, enviado_por, nome_original,
                                 nome_armazenado, tipo_mime, tamanho_bytes, hash_sha256)
         VALUES (:c, :e, :u, :no, :na, :m, :t, :h)'
    );
    foreach ($ficheiros as $f) {
        $armazenado = bin2hex(random_bytes(32));          // nome aleatório, sem extensão
        $destino    = $dir . DIRECTORY_SEPARATOR . $armazenado;
        if (!move_uploaded_file($f['tmp'], $destino)) {
            throw new RuntimeException('Falha ao guardar o ficheiro.');
        }
        $criados[] = $destino;
        @chmod($destino, 0640);
        $stmt->execute([
            ':c' => $candId, ':e' => $esclId, ':u' => $userId, ':no' => $f['nome'],
            ':na' => $armazenado, ':m' => $f['mime'], ':t' => $f['tamanho'],
            ':h' => hash_file('sha256', $destino),
        ]);
    }
}

function apagar_ficheiros(array $caminhos): void
{
    foreach ($caminhos as $c) {
        @unlink($c);
    }
}
