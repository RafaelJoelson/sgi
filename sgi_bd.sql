-- TABELA CURSO

CREATE TABLE Curso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigla VARCHAR(20) NOT NULL UNIQUE,
    nome_completo VARCHAR(100) NOT NULL
);

-- TABELA TURMA

CREATE TABLE Turma (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    periodo VARCHAR(20) NOT NULL,
    UNIQUE (curso_id, periodo),
    FOREIGN KEY (curso_id) REFERENCES Curso(id) ON DELETE CASCADE
);

-- TABELA PARA COTA DE ALUNOS

CREATE TABLE CotaAluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    cota_total INT DEFAULT 0 NOT NULL,
    cota_usada INT DEFAULT 0 NOT NULL,
    FOREIGN KEY (turma_id) REFERENCES Turma(id) ON DELETE CASCADE
);


-- ALUNO
CREATE TABLE Aluno (
    matricula VARCHAR(20) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    cargo ENUM('Líder', 'Vice-líder', 'Nenhum') DEFAULT 'Nenhum',
    email VARCHAR(100) NOT NULL,
    cpf CHAR(11) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    cota_id INT,
    data_fim_validade DATE,
    FOREIGN KEY (cota_id) REFERENCES CotaAluno(id) ON DELETE SET NULL
);

-- SERVIDOR
CREATE TABLE Servidor (
    siape VARCHAR(20) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    cpf CHAR(11) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE NOT NULL,
    setor_admin ENUM('CAD', 'COEN', 'NENHUM') DEFAULT 'NENHUM' NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_fim_validade DATE
);

-- COTA PARA TODOS OS SERVIDORES
CREATE TABLE CotaServidor (
    siape VARCHAR(20) PRIMARY KEY,
    cota_pb_total INT DEFAULT 1000 NOT NULL,
    cota_pb_usada INT DEFAULT 0 NOT NULL,
    cota_color_total INT DEFAULT 100 NOT NULL,
    cota_color_usada INT DEFAULT 0 NOT NULL,
    FOREIGN KEY (siape) REFERENCES Servidor(siape) ON DELETE CASCADE
);


-- REPROGRAFO
CREATE TABLE Reprografo (
    cpf CHAR(11) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    senha VARCHAR(255) NOT NULL
);

-- SOLICITAÇÃO DE IMPRESSÃO
CREATE TABLE SolicitacaoImpressao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf_solicitante CHAR(11) NOT NULL,
    tipo_solicitante ENUM('Aluno', 'Servidor') NOT NULL,
    arquivo_path TEXT,
    qtd_copias INT NOT NULL,
    qtd_paginas INT NOT NULL DEFAULT 1,
    colorida BOOLEAN DEFAULT FALSE NOT NULL,
    status ENUM('Nova', 'Lida', 'Aceita', 'Rejeitada') DEFAULT 'Nova' NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    cpf_reprografo CHAR(11),
    FOREIGN KEY (cpf_reprografo) REFERENCES Reprografo(cpf) ON DELETE SET NULL
);

-- NOTIFICAÇÕES
CREATE TABLE Notificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT NOT NULL,
    destinatario_cpf CHAR(11) NOT NULL,
    mensagem TEXT NOT NULL,
    visualizada BOOLEAN DEFAULT FALSE NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id) ON DELETE CASCADE
);

-- LOG DE DECREMENTO DE COTAS
CREATE TABLE LogDecrementoCota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT NOT NULL,
    tipo_usuario ENUM('Aluno', 'Servidor') NOT NULL,
    referencia VARCHAR(20) NOT NULL, -- matrícula ou siape
    qtd_cotas INT NOT NULL,
    data DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id) ON DELETE CASCADE
);

-- TABELA DE SEMESTRES LETIVOS
CREATE TABLE SemestreLetivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ano INT NOT NULL,
    semestre ENUM('1','2') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    UNIQUE (ano, semestre)
);

-- LOG DE ALTERAÇÕES DE SEMESTRE LETIVO
CREATE TABLE IF NOT EXISTS LogSemestreLetivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    setor VARCHAR(10) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    data DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- HABILITAR O EVENTO DO SCHEDULER
-- O evento scheduler deve estar habilitado para que os eventos sejam executados automaticamente

SET GLOBAL event_scheduler = ON;

-- EVENTO PARA LIMPAR COTAS DE ALUNOS INATIVOS
-- Este evento irá zerar as cotas de alunos que não estão mais ativos

DELIMITER //
CREATE EVENT IF NOT EXISTS desativar_usuarios_expirados
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  UPDATE Aluno SET ativo = FALSE WHERE data_fim_validade < CURDATE();
  UPDATE Servidor SET ativo = FALSE WHERE data_fim_validade < CURDATE() AND is_admin = FALSE;
END;//
DELIMITER ;

-- EVENTO PARA LIMPAR SOLICITAÇÕES ANTIGAS
-- Este evento irá remover solicitações de impressão que tenham mais de 15 dias

DELIMITER //
CREATE EVENT IF NOT EXISTS limpar_solicitacoes_antigas
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  DELETE FROM SolicitacaoImpressao 
  WHERE data_criacao < NOW() - INTERVAL 15 DAY;
END;//
DELIMITER ;

-- EVENTO PARA RESETAR COTAS NO INÍCIO DE CADA SEMESTRE LETIVO
DELIMITER //
CREATE EVENT IF NOT EXISTS resetar_cotas_semestre
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  DECLARE prox_semestre_inicio DATE;
  -- Busca o próximo semestre letivo que começa hoje
  SELECT data_inicio INTO prox_semestre_inicio FROM SemestreLetivo WHERE data_inicio = CURDATE() LIMIT 1;
  IF prox_semestre_inicio IS NOT NULL THEN
    -- Reseta cotas de alunos: cada turma recebe 600 cópias, cota usada volta a 0
    UPDATE CotaAluno SET cota_total = 600, cota_usada = 0;
    -- Reseta cotas de servidores: 1000 PB e 100 coloridas, usadas voltam a 0
    UPDATE CotaServidor SET cota_pb_total = 1000, cota_pb_usada = 0, cota_color_total = 100, cota_color_usada = 0;
  END IF;
END;//
DELIMITER ;

-- CURSOS
INSERT INTO Curso (id, sigla, nome_completo) VALUES
(1, 'LET', 'Letras (Habilitação Português/Espanhol)'),
(2, 'GRH', 'Tecnologia em Gestão de Recursos Humanos'),
(3, 'LOG', 'Tecnologia em Logística'),
(4, 'GTI', 'Tecnologia em Gestão da Tecnologia da Informação'),
(5, 'GA', 'Tecnologia em Gestão Ambiental'),
(6, 'TECINF', 'Técnico em Informática'),
(7, 'TECADM', 'Técnico em Administração'),
(8, 'TECANC', 'Técnico em Análise Clínicas'),
(9, 'TECENF', 'Técnico em Enfermagem'),
(10, 'TECSEG', 'Técnico em Segurança do Trabalho');

-- TURMAS (cada turma é uma oferta de curso em um período)
INSERT INTO Turma (id, curso_id, periodo) VALUES
(1, 1, '1° Período'),
(2, 1, '2° Período'),
(3, 1, '3° Período'),
(4, 2, '1° Período'),
(5, 2, '2° Período'),
(6, 2, '3° Período'),
(7, 3, '1° Período'),
(8, 3, '2° Período'),
(9, 3, '3° Período'),
(10, 4, '1° Período'),
(11, 4, '2° Período'),
(12, 4, '3° Período'),
(13, 5, '1° Período'),
(14, 5, '2° Período'),
(15, 5, '3° Período'),
(16, 6, '1° Período'),
(17, 6, '2° Período'),
(18, 6, '3° Período');

-- COTA ALUNO
INSERT INTO CotaAluno (id, turma_id, cota_total, cota_usada) VALUES
(1, 1, 250, 100),
(2, 4, 200, 50),
(3, 7, 180, 30),
(4, 10, 220, 80),
(5, 13, 150, 20),
(6, 16, 170, 10);

-- ALUNOS
INSERT INTO Aluno (matricula, nome, sobrenome, cargo, email, cpf, senha, ativo, cota_id, data_fim_validade) VALUES
('20250001', 'Ana', 'Silva', 'Líder', 'ana.silva@email.com', '12345678901', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 1, NULL),
('20250002', 'Bruno', 'Souza', 'Nenhum', 'bruno.souza@email.com', '23456789012', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 2, NULL),
('20250003', 'Carla', 'Oliveira', 'Vice-líder', 'carla.oliveira@email.com', '34567890123', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 3, NULL),
('20250004', 'Pedro', 'Oliveira', 'Vice-líder', 'pedro.oliveira@email.com', '34567890124', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 4, NULL),
('20250005', 'Lucas', 'Pereira', 'Nenhum', 'lucas.pereira@email.com', '35678901236', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 5, NULL),
('20250006', 'Mariana', 'Lima', 'Nenhum', 'mariana@email.com', '56789012345', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 6, NULL);

-- SERVIDORES
INSERT INTO Servidor (siape, nome, sobrenome, email, cpf, senha, is_admin, setor_admin, ativo, data_fim_validade) VALUES
('1000001', 'João', 'Silva', 'joao.silva@if.edu', '45678901234', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 'CAD', TRUE, NULL),
('1000002', 'Maria', 'Fernandes', 'maria.fernandes@if.edu', '97164635102', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', FALSE, 'NENHUM', TRUE, NULL),
('1000003', 'Carlos', 'Oliveira', 'carlos.oliveira@if.edu', '67890123456', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', TRUE, 'COEN', TRUE, NULL);

-- COTA SERVIDOR
INSERT INTO CotaServidor (siape, cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada) VALUES
('1000001', 1000, 0, 100, 0),
('1000002', 1000, 0, 100, 0),
('1000003', 1000, 0, 100, 0);

-- REPROGRAFO
INSERT INTO Reprografo (cpf, nome, sobrenome, email, senha) VALUES
('11111111111', 'Paulo', 'Lima', 'paulo.lima@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC'),
('22222222222', 'Fernanda', 'Costa', 'fernanda.costa@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC');

-- SOLICITAÇÃO DE IMPRESSÃO
INSERT INTO SolicitacaoImpressao (id, cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, qtd_paginas, colorida, status, data_criacao, cpf_reprografo) VALUES
(1, '12345678901', 'Aluno', 'trabalho_ana.pdf', 10, 2, FALSE, 'Nova', '2025-06-27 10:00:00', NULL),
(2, '45678901234', 'Aluno', 'relatorio_lucas.pdf', 5, 1, TRUE, 'Aceita', '2025-06-27 11:00:00', '11111111111'),
(3, '1000001', 'Servidor', 'oficio_joao.pdf', 3, 1, FALSE, 'Lida', '2025-06-27 12:00:00', '22222222222');

-- SOLICITAÇÕES DE IMPRESSÃO DE SERVIDORES (exemplo para testes)
INSERT INTO SolicitacaoImpressao (cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, qtd_paginas, colorida, status, data_criacao, cpf_reprografo) VALUES
('45678901234', 'Servidor', 'relatorio_maria.pdf', 8, 2, FALSE, 'Aceita', '2025-06-28 09:00:00', '11111111111'),
('45678901234', 'Servidor', 'oficio_maria.pdf', 4, 1, TRUE, 'Aceita', '2025-06-29 10:30:00', '22222222222'),
('1000001', 'Servidor', 'memorando_joao.pdf', 6, 3, FALSE, 'Aceita', '2025-06-30 14:00:00', '11111111111'),
('1000001', 'Servidor', 'ata_joao.pdf', 2, 5, TRUE, 'Aceita', '2025-07-01 16:00:00', '22222222222'),
('67890123456', 'Servidor', 'relatorio_carlos.pdf', 10, 1, FALSE, 'Aceita', '2025-07-02 08:45:00', '11111111111'),
('67890123456', 'Servidor', 'oficio_carlos.pdf', 3, 2, TRUE, 'Aceita', '2025-07-02 11:20:00', '22222222222');

-- NOTIFICAÇÃO
INSERT INTO Notificacao (solicitacao_id, destinatario_cpf, mensagem, visualizada, data_envio) VALUES
(1, '12345678901', 'Sua solicitação foi recebida.', FALSE, '2025-06-27 10:05:00'),
(2, '45678901234', 'Sua solicitação foi aceita.', TRUE, '2025-06-27 11:10:00'),
(3, '1000001', 'Sua solicitação foi lida.', FALSE, '2025-06-27 12:10:00');

-- LOG DE DECREMENTO DE COTAS
INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_cotas, data) VALUES
(2, 'Aluno', '20250005', 5, '2025-06-27 11:15:00'),
(3, 'Servidor', '1000001', 3, '2025-06-27 12:15:00'),
(1, 'Aluno', '20250001', 10, '2025-06-27 10:20:00'),
(4, 'Servidor', '1002', 4, '2025-06-28 09:15:00'),
(5, 'Servidor', '1003', 6, '2025-06-29 10:35:00');

-- Exemplo de inserção de semestres letivos
INSERT INTO SemestreLetivo (ano, semestre, data_inicio, data_fim) VALUES
(2025, '1', '2025-02-19', '2025-07-30'),
(2025, '2', '2025-08-01', '2025-12-20');

