<?php
// Autenticação, sessão e controlo de acesso por perfil.

const LOGIN_MAX_TENTATIVAS   = 5;      // falhas seguidas até bloquear a conta
const LOGIN_BLOQUEIO_MINUTOS = 15;     // duração do bloqueio
const SESSAO_INATIVIDADE_SEG = 1800;   // 30 min sem atividade termina a sessão
const BCRYPT_CUSTO           = 12;

// Hash válido (de uma password qualquer), usado só para que o tempo de resposta
// seja o mesmo quando o username não existe (evita enumeração de utilizadores).
const HASH_FALSO = '$2y$12$qLzeldsoOv5deD7asJqmN.luKiCQSQG27eCsjKOGWqH3f/rQ4eOSW';

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_CUSTO]);
}

// Esvazia a sessão e muda o ID (invalida o anterior)
function sessao_limpar(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// Chamar em cada pedido: termina sessões inativas e renova o relógio
function sessao_verificar_inatividade(): void
{
    if (!isset($_SESSION['utilizador_id'])) {
        return;
    }
    $agora = time();
    if ($agora - (int)($_SESSION['ultima_atividade'] ?? 0) > SESSAO_INATIVIDADE_SEG) {
        sessao_limpar();
        flash_add('erro', 'A sessão expirou por inatividade. Inicie sessão novamente.');
        return;
    }
    $_SESSION['ultima_atividade'] = $agora;
}

// Dados do utilizador autenticado, ou null
function utilizador_atual(): ?array
{
    if (!isset($_SESSION['utilizador_id'])) {
        return null;
    }
    return [
        'id'     => (int)$_SESSION['utilizador_id'],
        'perfil' => (string)$_SESSION['perfil'],
        'nome'   => (string)$_SESSION['nome'],
    ];
}

// Página inicial da área de cada perfil
function url_area(string $perfil): string
{
    return $perfil === 'tecnico' ? url('tecnico/index.php') : url('candidato/index.php');
}

// Exige sessão iniciada (senão envia para o login)
function exigir_login(): array
{
    $u = utilizador_atual();
    if ($u === null) {
        flash_add('erro', 'Tem de iniciar sessão para aceder a essa página.');
        redirect(url('login.php'));
    }
    return $u;
}

// Exige sessão iniciada E um perfil específico ('candidato' ou 'tecnico')
function exigir_perfil(string $perfil): array
{
    $u = exigir_login();
    if ($u['perfil'] !== $perfil) {
        erro_http(403, 'Acesso negado');
    }
    return $u;
}

// Verifica as credenciais. Em caso de sucesso inicia a sessão e devolve true.
// Mensagem de erro igual para "utilizador inexistente", "password errada" e "bloqueado".
function tentar_login(string $username, string $password): bool
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, perfil, nome, password_hash, tentativas_falhadas,
                (bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()) AS bloqueado
         FROM utilizadores WHERE username = :u'
    );
    $stmt->execute([':u' => $username]);
    $u = $stmt->fetch();

    // password_verify corre SEMPRE (mesmo sem utilizador) para igualar tempos
    $ok = password_verify($password, $u ? $u['password_hash'] : HASH_FALSO);

    if (!$u) {
        error_log('Login falhado (utilizador inexistente) de ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        return false;
    }
    if ((int)$u['bloqueado'] === 1) {
        error_log('Login recusado (conta bloqueada) id=' . $u['id']);
        return false;
    }

    if (!$ok) {
        $tentativas = (int)$u['tentativas_falhadas'] + 1;
        if ($tentativas >= LOGIN_MAX_TENTATIVAS) {
            $pdo->prepare(
                'UPDATE utilizadores
                 SET tentativas_falhadas = 0,
                     bloqueado_ate = DATE_ADD(NOW(), INTERVAL ' . (int)LOGIN_BLOQUEIO_MINUTOS . ' MINUTE)
                 WHERE id = :id'
            )->execute([':id' => $u['id']]);
            error_log('Conta bloqueada por tentativas falhadas id=' . $u['id']);
        } else {
            $pdo->prepare('UPDATE utilizadores SET tentativas_falhadas = :t WHERE id = :id')
                ->execute([':t' => $tentativas, ':id' => $u['id']]);
        }
        return false;
    }

    // Sucesso: limpa contadores e atualiza o hash se o custo mudou
    $pdo->prepare('UPDATE utilizadores SET tentativas_falhadas = 0, bloqueado_ate = NULL WHERE id = :id')
        ->execute([':id' => $u['id']]);
    if (password_needs_rehash($u['password_hash'], PASSWORD_BCRYPT, ['cost' => BCRYPT_CUSTO])) {
        $pdo->prepare('UPDATE utilizadores SET password_hash = :h WHERE id = :id')
            ->execute([':h' => hash_password($password), ':id' => $u['id']]);
    }

    // Novo ID de sessão no login (contra session fixation); também renova o token CSRF
    session_regenerate_id(true);
    $_SESSION = [
        'utilizador_id'    => (int)$u['id'],
        'perfil'           => $u['perfil'],
        'nome'             => $u['nome'],
        'ultima_atividade' => time(),
    ];
    return true;
}
