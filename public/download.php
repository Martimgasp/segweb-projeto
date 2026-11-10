<?php
// Entrega de documentos: só ao dono da candidatura ou ao técnico atribuído
require_once __DIR__ . '/../includes/bootstrap.php';

$u  = exigir_login();
$id = get_id('id');
if ($id === null) {
    erro_http(404, 'Documento não encontrado');
}

$s = db()->prepare(
    'SELECT d.nome_original, d.nome_armazenado, d.tipo_mime, d.tamanho_bytes, c.utilizador_id, c.tecnico_id
     FROM documentos d JOIN candidaturas c ON c.id = d.candidatura_id WHERE d.id = :id'
);
$s->execute([':id' => $id]);
$doc = $s->fetch();

$autorizado = $doc && (
    ($u['perfil'] === 'candidato' && (int)$doc['utilizador_id'] === $u['id']) ||
    ($u['perfil'] === 'tecnico'   && (int)$doc['tecnico_id'] === $u['id'])
);
$caminho = $autorizado ? caminho_documento($doc['nome_armazenado']) : null;

// 404 igual para "não existe" e "sem permissão"
if ($caminho === null || !is_file($caminho)) {
    erro_http(404, 'Documento não encontrado');
}

$nome = nome_ficheiro_seguro($doc['nome_original']);
header('Content-Type: ' . $doc['tipo_mime']);
header('Content-Length: ' . filesize($caminho));
header("Content-Disposition: attachment; filename=\"documento\"; filename*=UTF-8''" . rawurlencode($nome));
header('Cache-Control: private, no-store');
session_write_close();
readfile($caminho);
