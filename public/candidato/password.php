<?php
// Alterar a palavra-passe
require_once __DIR__ . '/../../includes/bootstrap.php';

$u     = exigir_perfil('candidato');
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $atual     = post_str('atual', false);
    $nova      = post_str('nova', false);
    $confirmar = post_str('confirmar', false);

    $s = db()->prepare('SELECT password_hash FROM utilizadores WHERE id = :id');
    $s->execute([':id' => $u['id']]);
    $hash = (string)$s->fetchColumn();

    if (strlen($atual) > 1000 || !password_verify($atual, $hash)) {
        $erros[] = 'A palavra-passe atual está incorreta.';
    }
    if (($m = validar_password($nova)) !== null) {
        $erros[] = $m;
    }
    if ($nova !== $confirmar) {
        $erros[] = 'As palavras-passe novas não coincidem.';
    }
    if (!$erros && $nova === $atual) {
        $erros[] = 'A nova palavra-passe tem de ser diferente da atual.';
    }

    if (!$erros) {
        db()->prepare('UPDATE utilizadores SET password_hash = :h WHERE id = :id')
            ->execute([':h' => hash_password($nova), ':id' => $u['id']]);
        session_regenerate_id(true);   // novo ID de sessão após mudança de credenciais
        flash_add('ok', 'Palavra-passe alterada com sucesso.');
        redirect(url('candidato/password.php'));
    }
}

$titulo = 'Alterar palavra-passe';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1>Alterar palavra-passe</h1>
<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert"><ul>
        <?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form method="post" action="password.php" class="form" autocomplete="off">
    <?= csrf_field() ?>
    <label>Palavra-passe atual
        <input type="password" name="atual" maxlength="1000" required autocomplete="current-password">
    </label>
    <label>Nova palavra-passe
        <input type="password" name="nova" maxlength="72" required autocomplete="new-password">
        <span class="ajuda">Mínimo 10 caracteres, com minúsculas, maiúsculas e números.</span>
    </label>
    <label>Confirmar nova palavra-passe
        <input type="password" name="confirmar" maxlength="72" required autocomplete="new-password">
    </label>
    <button type="submit">Alterar</button>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
