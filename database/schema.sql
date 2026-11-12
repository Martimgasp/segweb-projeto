-- =====================================================================
-- Plataforma de Gestão de Candidaturas a Bolsas — Segurança na Web
-- SCRIPT ÚNICO: corre tudo de uma vez, pela ordem correta.
-- Compatível com MySQL 8 / MariaDB 10.4+
--
-- ATENÇÃO: apaga e recria a base de dados `bolsas` (só para desenvolvimento).
-- Tem de ser executado com uma ligação com privilégios (root), porque
-- cria a base de dados e o utilizador da aplicação.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 0. Recriar a base de dados do zero
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS bolsas;

CREATE DATABASE bolsas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bolsas;

-- ---------------------------------------------------------------------
-- 1. TABELAS (ordem: primeiro as que não dependem de nenhuma outra)
-- ---------------------------------------------------------------------

-- 1.1 UTILIZADORES (candidatos e técnicos)
--     Técnicos NÃO se registam: são inseridos à mão (ver secção 2).
--     A password nunca é guardada em claro: só o hash de password_hash().
CREATE TABLE utilizadores (
                              id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
                              perfil              ENUM('candidato','tecnico') NOT NULL DEFAULT 'candidato',
                              username            VARCHAR(50)  NOT NULL,
                              email               VARCHAR(255) NOT NULL,
                              password_hash       VARCHAR(255) NOT NULL,
                              nome                VARCHAR(150) NOT NULL,
                              morada              VARCHAR(255) NULL,
                              telefone            VARCHAR(20)  NULL,
                              nif                 VARCHAR(255) NULL,      -- cifrado (AES-256-GCM)
                              tentativas_falhadas TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- anti força bruta
                              bloqueado_ate       DATETIME     NULL,
                              criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (id),
                              UNIQUE KEY uq_utilizadores_username (username),
                              UNIQUE KEY uq_utilizadores_email (email)
) ENGINE=InnoDB;

-- 1.2 BOLSAS (públicas)
CREATE TABLE bolsas (
                        id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                        designacao        VARCHAR(150)  NOT NULL,
                        tipo_apoio        VARCHAR(80)   NOT NULL,
                        area_estudo       VARCHAR(80)   NOT NULL,
                        descricao         TEXT          NULL,
                        requisitos        TEXT          NOT NULL,
                        montante          DECIMAL(10,2) NOT NULL,
                        prazo_candidatura DATE          NOT NULL,
                        PRIMARY KEY (id),
                        KEY idx_bolsas_tipo (tipo_apoio),
                        KEY idx_bolsas_area (area_estudo)
) ENGINE=InnoDB;

-- 1.3 CANDIDATURAS (depende de utilizadores e bolsas)
--     UNIQUE(utilizador_id, bolsa_id) garante "máx. 1 candidatura por bolsa"
--     na própria BD, mesmo que o código PHP falhe.
CREATE TABLE candidaturas (
                              id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                              utilizador_id       INT UNSIGNED  NOT NULL,
                              bolsa_id            INT UNSIGNED  NOT NULL,
                              tecnico_id          INT UNSIGNED  NULL,              -- técnico atribuído
                              instituicao         VARCHAR(150)  NOT NULL,
                              curso               VARCHAR(150)  NOT NULL,
                              rendimento_agregado DECIMAL(10,2) NOT NULL,
                              iban                VARCHAR(255)  NOT NULL, -- cifrado (AES-256-GCM)
                              estado              ENUM('submetida','em_analise','aprovada','rejeitada',
                                  'a_aguardar_documentacao','cancelada')
                                                                NOT NULL DEFAULT 'submetida',
                              data_submissao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              data_atualizacao    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
                              PRIMARY KEY (id),
                              UNIQUE KEY uq_candidatura_user_bolsa (utilizador_id, bolsa_id),
                              KEY idx_cand_estado (estado),
                              KEY idx_cand_tecnico (tecnico_id),
                              CONSTRAINT fk_cand_user    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id),
                              CONSTRAINT fk_cand_bolsa   FOREIGN KEY (bolsa_id)      REFERENCES bolsas(id),
                              CONSTRAINT fk_cand_tecnico FOREIGN KEY (tecnico_id)    REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- 1.4 ESCLARECIMENTOS (pedido do técnico / resposta do candidato)
CREATE TABLE esclarecimentos (
                                 id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                 candidatura_id INT UNSIGNED NOT NULL,
                                 autor_id       INT UNSIGNED NOT NULL,
                                 tipo           ENUM('pedido','resposta') NOT NULL,
                                 mensagem       TEXT NOT NULL,
                                 criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 PRIMARY KEY (id),
                                 KEY idx_escl_cand (candidatura_id),
                                 CONSTRAINT fk_escl_cand  FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
                                 CONSTRAINT fk_escl_autor FOREIGN KEY (autor_id)       REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- 1.5 DOCUMENTOS (depende de candidaturas e esclarecimentos)
--     Só guarda metadados; o ficheiro fica FORA da pasta pública, com nome
--     aleatório — nunca acessível por URL direto.
CREATE TABLE documentos (
                            id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            candidatura_id    INT UNSIGNED NOT NULL,
                            esclarecimento_id INT UNSIGNED NULL,
                            enviado_por       INT UNSIGNED NOT NULL,
                            nome_original     VARCHAR(255) NOT NULL,             -- só para mostrar ao utilizador
                            nome_armazenado   CHAR(64)     NOT NULL,             -- aleatório, sem extensão
                            tipo_mime         VARCHAR(100) NOT NULL,
                            tamanho_bytes     INT UNSIGNED NOT NULL,
                            hash_sha256       CHAR(64)     NOT NULL,
                            enviado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (id),
                            UNIQUE KEY uq_doc_armazenado (nome_armazenado),
                            KEY idx_doc_cand (candidatura_id),
                            CONSTRAINT fk_doc_cand FOREIGN KEY (candidatura_id)    REFERENCES candidaturas(id)   ON DELETE CASCADE,
                            CONSTRAINT fk_doc_escl FOREIGN KEY (esclarecimento_id) REFERENCES esclarecimentos(id) ON DELETE SET NULL,
                            CONSTRAINT fk_doc_user FOREIGN KEY (enviado_por)       REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- 1.6 OBSERVAÇÕES INTERNAS (só técnicos; invisíveis ao candidato)
CREATE TABLE observacoes_internas (
                                      id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                      candidatura_id INT UNSIGNED NOT NULL,
                                      tecnico_id     INT UNSIGNED NOT NULL,
                                      texto          TEXT NOT NULL,
                                      criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      PRIMARY KEY (id),
                                      KEY idx_obs_cand (candidatura_id),
                                      CONSTRAINT fk_obs_cand    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
                                      CONSTRAINT fk_obs_tecnico FOREIGN KEY (tecnico_id)     REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- 1.7 HISTÓRICO DE ESTADOS (data + responsável de cada alteração)
CREATE TABLE historico_estados (
                                   id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                   candidatura_id  INT UNSIGNED NOT NULL,
                                   estado_anterior VARCHAR(30)  NULL,
                                   estado_novo     VARCHAR(30)  NOT NULL,
                                   alterado_por    INT UNSIGNED NOT NULL,
                                   alterado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   PRIMARY KEY (id),
                                   KEY idx_hist_cand (candidatura_id),
                                   CONSTRAINT fk_hist_cand FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
                                   CONSTRAINT fk_hist_user FOREIGN KEY (alterado_por)   REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. DADOS DE TESTE (todos fictícios)
-- ---------------------------------------------------------------------

-- 2.1 Bolsas. A última tem o prazo já ultrapassado, para testar essa regra.
INSERT INTO bolsas
(designacao, tipo_apoio, area_estudo, descricao, requisitos, montante, prazo_candidatura)
VALUES
    ('Bolsa de Mérito Académico', 'Bolsa de estudo', 'Informática',
     'Apoio a estudantes com excelente aproveitamento escolar.',
     'Média final igual ou superior a 16 valores; matrícula ativa.', 1500.00, '2026-12-15'),
    ('Apoio Social Escolar', 'Apoio social', 'Qualquer área',
     'Apoio a estudantes de agregados com baixos rendimentos.',
     'Rendimento per capita inferior ao limite definido; matrícula ativa.', 1200.00, '2027-01-31'),
    ('Bolsa Mobilidade Insular', 'Apoio à mobilidade', 'Engenharia',
     'Comparticipação de deslocações para estudantes das ilhas.',
     'Residência fiscal numa ilha diferente da instituição de ensino.', 900.00, '2026-12-01'),
    ('Apoio a Material Didático', 'Apoio material', 'Design',
     'Comparticipação na compra de equipamento e material de estudo.',
     'Comprovativo de matrícula e de despesa.', 400.00, '2027-02-28'),
    ('Bolsa Investigação Júnior', 'Bolsa de estudo', 'Ciências',
     'Integração de estudantes em projetos de investigação.',
     'Carta de motivação; média igual ou superior a 14 valores.', 2000.00, '2026-09-30');

-- 2.2 Contas de teste (fictícias). Password de ambas: Teste!2026Bolsas
--     (hash bcrypt, compatível com password_verify() do PHP).
--     O técnico é criado aqui porque não existe registo público para esse perfil.
--     Muda ou remove estas contas antes da entrega final.
INSERT INTO utilizadores (perfil, username, email, password_hash, nome, morada, telefone, nif) VALUES
                                                                                                   ('tecnico',   'tecnico1',   'tecnico1@exemplo.pt',
                                                                                                    '$2y$12$qLzeldsoOv5deD7asJqmN.luKiCQSQG27eCsjKOGWqH3f/rQ4eOSW',
                                                                                                    'Técnico Teste', NULL, NULL, NULL),
                                                                                           ('candidato', 'candidato1', 'candidato1@exemplo.pt',
                                                                                                    '$2y$12$qLzeldsoOv5deD7asJqmN.luKiCQSQG27eCsjKOGWqH3f/rQ4eOSW',
                                                                                                    'Candidato Teste', 'Rua Fictícia 1, Ponta Delgada', '912345678', NULL);   -- NIF a NULL: é cifrado pela aplicação (preencher no Perfil)

-- ---------------------------------------------------------------------
-- 3. UTILIZADOR DA APLICAÇÃO (princípio do menor privilégio)
--    Sem CREATE / DROP / ALTER: mesmo que a app seja explorada, não
--    consegue alterar a estrutura da base de dados.
--    >>> Muda a password abaixo e usa a MESMA em config/config.php <<<
-- ---------------------------------------------------------------------
DROP USER IF EXISTS 'bolsas_app'@'localhost';
CREATE USER 'bolsas_app'@'localhost' IDENTIFIED BY 'TrocaEstaPassword!2026';
GRANT SELECT, INSERT, UPDATE, DELETE ON bolsas.* TO 'bolsas_app'@'localhost';
FLUSH PRIVILEGES;
