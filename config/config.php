<?php
// Configuração da aplicação. Fica FORA da pasta pública (public/).
// Em produção, não versionar este ficheiro com credenciais reais.

return [
    'db' => [
        'host'    => '127.0.0.1',
        'name'    => 'bolsas',
        'user'    => 'bolsas_app',
        // Tem de ser IGUAL à password do CREATE USER no final de database/schema.sql
        'pass'    => 'TrocaEstaPassword!2026',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'nome'        => 'Bolsas e Apoios',
        // Em desenvolvimento local sem HTTPS (php -S) põe false; na entrega deixa true
        'force_https' => true,
        // Host canónico usado no redirecionamento HTTP -> HTTPS (evita Host header injection)
        'host'        => 'localhost',
        // Pasta de uploads, fora da pasta pública
        'upload_dir'  => __DIR__ . '/../storage/uploads',
    ],
];
