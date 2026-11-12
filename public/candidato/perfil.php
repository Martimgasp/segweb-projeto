<?php
// Ver e editar o perfil pessoal
require_once __DIR__ . '/../../includes/bootstrap.php';

$u   = exigir_perfil('candidato');
$pdo = db();

$s = $pdo->prepare('SELECT username, nome, email, morada, telefone, nif FROM utilizadores WHERE id = :id');
$s->execute([':id' => $u['id']]);
$perfil = $s->fetch();

$d = [
    'nome'     => $perfil['nome'],
    'email'    => $perfil['email'],
    'morada'   => (string)$perfil['morada'],
    'telefone' => (string)$perfil['telefone'],
    'nif'      => nif_ler($perfil['nif'], $u['id']),
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $d['nome']     = post_str('nome');
    $d['email']    = mb_strtolower(post_str('email'));
    $d['morada']   = post_str('morada');
    $d['telefone'] = (string)preg_replace('/\s+/', '', post_str('telefone'));
    $d['nif']      = post_str('nif');

    $erros = array_filter([
        validar_nome($d['nome']),
        validar_email($d['email']),
        $d['morada']   !== '' ? validar_texto($d['morada'], 'A morada', 5, 255) : null,
        $d['telefone'] !== '' ? validar_telefone($d['telefone']) : null,
        $d['nif']      !== '' ? validar_nif($d['nif']) : null,
    ]);

    if (!$erros) {
        try {
            $pdo->prepare(
                'UPDATE utilizadores SET nome = :n, email = :e, morada = :m, telefone = :t, nif = :nif WHERE id = :id'
            )->execute([
                ':n' => $d['nome'], ':e' => $d['email'],
                ':m' => $d['morada'] ?: null, ':t' => $d['telefone'] ?: null, ':nif' => nif_cifrar($d['nif'], $u['id']),
                ':id' => $u['id'],
            ]);
            $_SESSION['nome'] = $d['nome'];
            flash_add('ok', 'Perfil atualizado.');
            redirect(url('candidato/perfil.php'));
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $erros[] = 'Esse email já está em uso.';
            } else {
                error_log('Erro no perfil: ' . $ex->getMessage());
                $erros[] = 'Erro interno. Tente novamente mais tarde.';
            }
        }
    }
}

$titulo = 'Perfil';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/menu_candidato.php';
?>
<h1>O meu perfil</h1>
<?php if ($erros): ?>
    <div class="alerta alerta-erro" role="alert"><ul>
        <?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form method="post" action="perfil.php" class="form form-largo">
    <?= csrf_field() ?>
    <p>Username: <strong><?= e($perfil['username']) ?></strong></p>
    <label>Nome completo
        <input type="text" name="nome" value="<?= e($d['nome']) ?>" maxlength="150" required>
    </label>
    <label>Email
        <input type="email" name="email" value="<?= e($d['email']) ?>" maxlength="255" required>
    </label>
    <label>Morada
        <input type="text" name="morada" value="<?= e($d['morada']) ?>" maxlength="255">
    </label>
    <label>Telefone
        <input type="text" name="telefone" value="<?= e($d['telefone']) ?>" maxlength="20" inputmode="tel">
    </label>
    <label>NIF
        <input type="text" name="nif" value="<?= e($d['nif']) ?>" maxlength="9" inputmode="numeric">
    </label>
    <button type="submit">Guardar</button>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
