-- Plataforma de Gestão de Candidaturas a Bolsas e Apoios Financeiros
-- Esquema da base de dados (MySQL 8 / MariaDB 10.4+)
-- Todos os dados de teste são fictícios.

CREATE DATABASE IF NOT EXISTS bolsas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bolsas;

-- ---------------------------------------------------------------
-- Utilizadores (candidatos e técnicos de avaliação)
-- password_hash guarda o resultado de password_hash() (bcrypt/argon2), nunca a password
-- ---------------------------------------------------------------
CREATE TABLE utilizadores (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(50)  NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  perfil         ENUM('candidato','tecnico') NOT NULL DEFAULT 'candidato',
  nome           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  telefone       VARCHAR(20)  NULL,
  morada         VARCHAR(255) NULL,
  nif            CHAR(9)      NULL,
  tentativas_falhadas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_ate  DATETIME     NULL,
  criado_em      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_utilizadores_username (username),
  UNIQUE KEY uq_utilizadores_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Bolsas e apoios (informação pública)
-- ---------------------------------------------------------------
CREATE TABLE bolsas (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  designacao        VARCHAR(150) NOT NULL,
  tipo_apoio        VARCHAR(60)  NOT NULL,
  area_estudo       VARCHAR(80)  NOT NULL,
  descricao         TEXT         NOT NULL,
  requisitos        TEXT         NOT NULL,
  montante          DECIMAL(10,2) NOT NULL,
  prazo_candidatura DATE         NOT NULL,
  PRIMARY KEY (id),
  KEY idx_bolsas_tipo (tipo_apoio),
  KEY idx_bolsas_area (area_estudo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Candidaturas
-- UNIQUE (utilizador_id, bolsa_id): no máximo uma candidatura por bolsa
-- tecnico_id: técnico a quem a candidatura foi atribuída
-- ---------------------------------------------------------------
CREATE TABLE candidaturas (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  utilizador_id       INT UNSIGNED NOT NULL,
  bolsa_id            INT UNSIGNED NOT NULL,
  tecnico_id          INT UNSIGNED NULL,
  instituicao_ensino  VARCHAR(150) NOT NULL,
  curso               VARCHAR(150) NOT NULL,
  rendimento_agregado DECIMAL(10,2) NOT NULL,
  iban                VARCHAR(34)  NOT NULL,
  estado              ENUM('submetida','em_analise','aprovada','rejeitada','a_aguardar_documentacao','cancelada')
                      NOT NULL DEFAULT 'submetida',
  criado_em           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_candidatura_utilizador_bolsa (utilizador_id, bolsa_id),
  KEY idx_candidaturas_tecnico (tecnico_id),
  KEY idx_candidaturas_estado (estado),
  CONSTRAINT fk_cand_utilizador FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
  CONSTRAINT fk_cand_bolsa      FOREIGN KEY (bolsa_id)      REFERENCES bolsas(id),
  CONSTRAINT fk_cand_tecnico    FOREIGN KEY (tecnico_id)    REFERENCES utilizadores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Documentos anexados (ficheiros guardados FORA da pasta pública)
-- nome_armazenado: nome aleatório no disco; nome_original só para apresentação
-- ---------------------------------------------------------------
CREATE TABLE documentos (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidatura_id   INT UNSIGNED NOT NULL,
  nome_original    VARCHAR(255) NOT NULL,
  nome_armazenado  CHAR(64)     NOT NULL,
  mime_type        VARCHAR(100) NOT NULL,
  tamanho_bytes    INT UNSIGNED NOT NULL,
  sha256           CHAR(64)     NOT NULL,
  enviado_por      INT UNSIGNED NOT NULL,
  criado_em        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_documentos_armazenado (nome_armazenado),
  KEY idx_documentos_candidatura (candidatura_id),
  CONSTRAINT fk_doc_candidatura FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
  CONSTRAINT fk_doc_enviado_por FOREIGN KEY (enviado_por)    REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Pedidos de esclarecimento (técnico -> candidato) e respostas
-- ---------------------------------------------------------------
CREATE TABLE esclarecimentos (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidatura_id INT UNSIGNED NOT NULL,
  autor_id       INT UNSIGNED NOT NULL,
  mensagem       TEXT         NOT NULL,
  criado_em      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_escl_candidatura (candidatura_id),
  CONSTRAINT fk_escl_candidatura FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
  CONSTRAINT fk_escl_autor       FOREIGN KEY (autor_id)       REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Observações internas (nunca visíveis ao candidato)
-- ---------------------------------------------------------------
CREATE TABLE observacoes_internas (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidatura_id INT UNSIGNED NOT NULL,
  tecnico_id     INT UNSIGNED NOT NULL,
  observacao     TEXT         NOT NULL,
  criado_em      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_obs_candidatura (candidatura_id),
  CONSTRAINT fk_obs_candidatura FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
  CONSTRAINT fk_obs_tecnico     FOREIGN KEY (tecnico_id)     REFERENCES utilizadores(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Histórico de alterações de estado (data e responsável)
-- ---------------------------------------------------------------
CREATE TABLE historico_estados (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  candidatura_id  INT UNSIGNED NOT NULL,
  estado_anterior VARCHAR(30)  NULL,
  estado_novo     VARCHAR(30)  NOT NULL,
  alterado_por    INT UNSIGNED NOT NULL,
  alterado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_hist_candidatura (candidatura_id),
  CONSTRAINT fk_hist_candidatura FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
  CONSTRAINT fk_hist_utilizador  FOREIGN KEY (alterado_por)   REFERENCES utilizadores(id)
) ENGINE=InnoDB;


-- Utilizador MySQL da aplicação com privilégios mínimos (princípio do menor privilégio)

CREATE USER 'bolsas_app'@'localhost' IDENTIFIED BY 'pass';
GRANT SELECT, INSERT, UPDATE, DELETE ON bolsas.* TO 'bolsas_app'@'localhost';
FLUSH PRIVILEGES;
