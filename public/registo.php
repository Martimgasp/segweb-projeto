<?php
// Área pública: registo de uma conta de CANDIDATO.
// O perfil é fixado no servidor: nunca é lido do formulário (técnicos não se registam).
require_once __DIR__ . '/../includes/bootstrap.php';

if (($atual = utilizador_atual()) !== null) {
    redirect(url_area($atual['perfil']));
}

$dados = ['username' => '', 'nome' => '', 'email' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $dados['username'] = post_str('username');
    $dados['nome']     = post_str('nome');
    $dados['email']    = mb_strtolower(post_str('email'));
    $password          = post_str('password', false);
    $confirmar         = post_str('confirmar', false);

    foreach ([
        validar_username($dados['username']),
        validar_nome($dados['nome']),
        validar_email($dados['email']),
        validar_password($password),
    ] as $msg) {
        if ($msg !== null) {
            $erros[] = $msg;
        }
    }
    if ($password !== $confirmar) {
        $erros[] = 'As palavras-passe não coincidem.';
    }

    if (!$erros) {
        try {
            db()->prepare(
                "INSERT INTO utilizadores (perfil, username, email, password_hash, nome)
                 VALUES ('candidato', :username, :email, :hash, :nome)"
            )->execute([
                ':username' => $dados['username'],
                ':email'    => $dados['email'],
                ':hash'     => hash_password($password),
                ':nome'     => $dados['nome'],
            ]);
            flash_add('ok', 'Conta criada com sucesso. Já pode iniciar sessão.');
            redirect(url('login.php'));
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { // violação de UNIQUE (username ou email)
                $erros[] = 'Não foi possível criar a conta: username ou email já em uso.';
            } else {
                error_log('Erro no registo: ' . $ex->getMessage());
                $erros[] = 'Erro interno. Tente novamente mais tarde.';
            }
        }
    }
}

$titulo = 'Criar conta';
require __DIR__ . '/../includes/header.php';
?>
<h1>Criar conta de candidato</h1>

<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert">
        <ul>
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="registo.php" class="form" autocomplete="off">
    <?= csrf_field() ?>
    <label>Nome completo
        <input type="text" name="nome" value="<?= e($dados['nome']) ?>" maxlength="150" required autocomplete="name">
    </label>
    <label>Username
        <input type="text" name="username" value="<?= e($dados['username']) ?>" maxlength="30" required autocomplete="username">
    </label>
    <label>Email
        <input type="email" name="email" value="<?= e($dados['email']) ?>" maxlength="255" required autocomplete="email">
    </label>
    <label>Palavra-passe
        <input type="password" name="password" maxlength="72" required autocomplete="new-password">
        <span class="ajuda">Mínimo 10 caracteres, com minúsculas, maiúsculas e números.</span>
    </label>
    <label>Confirmar palavra-passe
        <input type="password" name="confirmar" maxlength="72" required autocomplete="new-password">
    </label>
    <button type="submit">Registar</button>
</form>
<p>Já tem conta? <a href="login.php">Entrar</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
