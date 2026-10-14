-- Dados de teste FICTÍCIOS (bolsas). Executar depois de schema.sql.
USE bolsas;

INSERT INTO bolsas (designacao, tipo_apoio, area_estudo, descricao, requisitos, montante, prazo_candidatura) VALUES
('Bolsa de Mérito Académico', 'Bolsa de estudo', 'Tecnologias de Informação',
 'Apoio destinado a estudantes com excelente aproveitamento escolar.',
 'Média mínima de 15 valores; matrícula válida no ano letivo corrente.',
 1500.00, '2026-12-31'),
('Apoio Social Alimentação', 'Apoio social', 'Todas as áreas',
 'Comparticipação em refeições para estudantes de agregados com menores rendimentos.',
 'Rendimento per capita do agregado inferior ao limite definido; declaração de rendimentos.',
 600.00, '2026-11-30'),
('Bolsa de Mobilidade Internacional', 'Bolsa de estudo', 'Engenharia',
 'Apoio a estudantes que realizem parte do curso no estrangeiro.',
 'Aceitação numa instituição parceira; plano de estudos aprovado.',
 2200.00, '2026-10-01'),
('Apoio ao Alojamento', 'Apoio social', 'Todas as áreas',
 'Comparticipação nas despesas de alojamento de estudantes deslocados.',
 'Residência habitual a mais de 50 km da instituição; contrato de arrendamento.',
 900.00, '2027-01-15'),
('Bolsa de Investigação Júnior', 'Bolsa de investigação', 'Ciências',
 'Integração de estudantes em projetos de investigação da instituição.',
 'Média mínima de 14 valores; carta de motivação.',
 1200.00, '2026-11-20');
