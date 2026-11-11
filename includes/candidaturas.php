<?php
// Regras e consultas das candidaturas (lado do candidato)

const ESTADOS = [
    'submetida'               => 'Submetida',
    'em_analise'              => 'Em análise',
    'aprovada'                => 'Aprovada',
    'rejeitada'               => 'Rejeitada',
    'a_aguardar_documentacao' => 'A aguardar documentação',
    'cancelada'               => 'Cancelada',
];

function estado_label(string $estado): string
{
    return ESTADOS[$estado] ?? $estado;
}

// Etiqueta HTML do estado (valores escapados)
function estado_badge(string $estado): string
{
    return '<span class="estado estado-' . e($estado) . '">' . e(estado_label($estado)) . '</span>';
}

// Devolve a candidatura SÓ se pertencer ao utilizador (senão null)
function candidatura_do_candidato(int $id, int $userId): ?array
{
    $s = db()->prepare(
        'SELECT c.*, b.designacao AS bolsa, b.montante, b.prazo_candidatura
         FROM candidaturas c JOIN bolsas b ON b.id = c.bolsa_id
         WHERE c.id = :id AND c.utilizador_id = :u'
    );
    $s->execute([':id' => $id, ':u' => $userId]);
    return $s->fetch() ?: null;
}

// Edição e cancelamento só antes de entrar em análise
function pode_alterar(array $c): bool
{
    return $c['estado'] === 'submetida';
}

function registar_historico(PDO $pdo, int $candId, ?string $anterior, string $novo, int $porUserId): void
{
    $pdo->prepare(
        'INSERT INTO historico_estados (candidatura_id, estado_anterior, estado_novo, alterado_por)
         VALUES (:c, :a, :n, :u)'
    )->execute([':c' => $candId, ':a' => $anterior, ':n' => $novo, ':u' => $porUserId]);
}

// Técnico com menos candidaturas em aberto (null se não existir nenhum)
function atribuir_tecnico(PDO $pdo): ?int
{
    $id = $pdo->query(
        "SELECT u.id FROM utilizadores u
         LEFT JOIN candidaturas c ON c.tecnico_id = u.id
              AND c.estado IN ('submetida','em_analise','a_aguardar_documentacao')
         WHERE u.perfil = 'tecnico'
         GROUP BY u.id ORDER BY COUNT(c.id) ASC, u.id ASC LIMIT 1"
    )->fetchColumn();
    return $id === false ? null : (int)$id;
}

// Lê e valida os campos do formulário de candidatura. Devolve [dados, erros].
function validar_dados_candidatura(): array
{
    $d = [
        'instituicao' => post_str('instituicao'),
        'curso'       => post_str('curso'),
        'rendimento'  => post_str('rendimento'),
        'iban'        => strtoupper((string)preg_replace('/\s+/', '', post_str('iban'))),
    ];
    $erros = [];
    foreach ([
        validar_texto($d['instituicao'], 'A instituição de ensino', 2, 150),
        validar_texto($d['curso'], 'O curso', 2, 150),
    ] as $m) {
        if ($m !== null) {
            $erros[] = $m;
        }
    }
    if (preg_match('/^\d{1,8}([.,]\d{1,2})?$/', $d['rendimento']) !== 1) {
        $erros[] = 'O rendimento do agregado deve ser um valor positivo (ex.: 12500,50).';
    }
    if (!iban_valido($d['iban'])) {
        $erros[] = 'IBAN inválido (formato PT50 seguido de 21 dígitos).';
    }
    return [$d, $erros];
}

// ---- Lado do técnico ----

// Transições de estado permitidas ao técnico (aprovada, rejeitada e cancelada são finais)
const TRANSICOES = [
    'submetida'               => ['em_analise'],
    'em_analise'              => ['aprovada', 'rejeitada', 'a_aguardar_documentacao'],
    'a_aguardar_documentacao' => ['em_analise'],
];

// Candidatura SÓ se estiver atribuída a este técnico (senão null)
function candidatura_do_tecnico(int $id, int $tecnicoId): ?array
{
    $s = db()->prepare(
        'SELECT c.*, b.designacao AS bolsa, b.montante,
                u.nome AS candidato, u.email, u.morada, u.telefone, u.nif
         FROM candidaturas c
         JOIN bolsas b ON b.id = c.bolsa_id
         JOIN utilizadores u ON u.id = c.utilizador_id
         WHERE c.id = :id AND c.tecnico_id = :t'
    );
    $s->execute([':id' => $id, ':t' => $tecnicoId]);
    return $s->fetch() ?: null;
}

function data_valida(string $v): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return $d !== false && $d->format('Y-m-d') === $v;
}
