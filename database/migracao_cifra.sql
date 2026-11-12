-- Só para bases JÁ criadas antes da cifra (alternativa a voltar a correr o schema.sql).
USE bolsas;

ALTER TABLE utilizadores  MODIFY nif  VARCHAR(255) NULL;
ALTER TABLE candidaturas  MODIFY iban VARCHAR(255) NOT NULL;

-- Dados de teste antigos (em claro) deixam de ser legíveis: limpar
UPDATE utilizadores SET nif = NULL WHERE nif IS NOT NULL AND nif NOT LIKE 'v1:%';
DELETE FROM candidaturas WHERE iban NOT LIKE 'v1:%';   -- apaga também documentos, esclarecimentos e histórico (CASCADE)
-- Depois, apagar os ficheiros antigos de storage/uploads/ (exceto .gitkeep)
