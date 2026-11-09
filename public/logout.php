<?php
// Termina a sessão. Só aceita POST com token CSRF (um simples link não pode fazer logout).
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('index.php'));
}
csrf_verificar();

sessao_limpar();
flash_add('ok', 'Sessão terminada.');
redirect(url('index.php'));
