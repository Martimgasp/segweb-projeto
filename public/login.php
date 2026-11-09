<?php
// Área pública: autenticação (username + password) para candidatos e técnicos.
require_once __DIR__ . '/../includes/bootstrap.php';

if (($atual = utilizador_atual()) !== null) {
    redirect(url_area($atual['perfil']));
}

$erro     = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $username = post_str('username');
    $password = post_str('password', false);

    if ($username === '' || $password === '' || strlen($username) > 50 || strlen($password) > 1000) {
        $erro = 'Credenciais inválidas.';
    } elseif (tentar_login($username, $password)) {
        redirect(url_area($_SESSION['perfil']));
    } else {
        // Mensagem única: não revela se o utilizador existe nem se está bloqueado
        $erro = 'Credenciais inválidas ou conta temporariamente bloqueada.';
    }
}

$titulo = 'Entrar';
require __DIR__ . '/../includes/header.php';
?>
<h1>Entrar</h1>

<?php if ($erro !== null): ?>
    <div class="alerta alerta-erro" role="alert"><?= e($erro) ?></div>
<?php endif; ?>

<form method="post" action="login.php" class="form">
    <?= csrf_field() ?>
    <label>Username
        <input type="text" name="username" value="<?= e($username) ?>" maxlength="50" required autocomplete="username">
    </label>
    <label>Palavra-passe
        <input type="password" name="password" maxlength="1000" required autocomplete="current-password">
    </label>
    <button type="submit">Entrar</button>
</form>
<p>Ainda não tem conta? <a href="registo.php">Registar</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
