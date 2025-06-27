-- COTA PARA ALUNOS POR TURMA
CREATE TABLE CotaAluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma VARCHAR(20) NOT NULL,
    turma_nome VARCHAR(100) NOT NULL,
    periodo VARCHAR(10) NOT NULL,
    cota_total INT DEFAULT 0 NOT NULL,
    cota_usada INT DEFAULT 0 NOT NULL,
    UNIQUE (turma, periodo)
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
    siap VARCHAR(20) PRIMARY KEY,
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
    siap VARCHAR(20) PRIMARY KEY,
    cota_pb_total INT DEFAULT 1000 NOT NULL,
    cota_pb_usada INT DEFAULT 0 NOT NULL,
    cota_color_total INT DEFAULT 100 NOT NULL,
    cota_color_usada INT DEFAULT 0 NOT NULL,
    FOREIGN KEY (siap) REFERENCES Servidor(siap) ON DELETE CASCADE
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
    referencia VARCHAR(20) NOT NULL, -- matrícula ou siap
    qtd_copias INT NOT NULL,
    data DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id) ON DELETE CASCADE
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
  UPDATE Servidor SET ativo = FALSE WHERE data_fim_validade < CURDATE();
END;
//
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
END;
//
DELIMITER ;

-- CotaAluno
INSERT INTO CotaAluno (id, turma, periodo, cota_total, cota_usada) VALUES
(1, 'LET', '2025.1', 250, 200),
(2, 'GRH', '2025.1', 250, 100),
(3, 'LOG', '2025.1', 250, 50);

-- Servidor
INSERT INTO Servidor (siap, nome, sobrenome, email, cpf, senha, is_admin, setor_admin, ativo, data_fim_validade) VALUES
('1001', 'João', 'Silva', 'joao.silva@if.edu', '45678901234', 'senha123', TRUE, 'CAD', TRUE, '2025-12-31'),
('1002', 'Maria', 'Fernandes', 'maria.fernandes@if.edu', '56789012345', 'senha123', FALSE, 'NENHUM', TRUE, '2025-12-31'),
('1003', 'Carlos', 'Oliveira', 'carlos.oliveira@if.edu', '67890123456', 'senha123', FALSE, 'COEN', TRUE, '2025-12-31');

-- CotaServidor
INSERT INTO CotaServidor (siap, cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada) VALUES
('1001', 1000, 200, 100, 10),
('1002', 1000, 150, 100, 5),
('1003', 1000, 100, 100, 0);

-- Aluno
INSERT INTO Aluno (matricula, nome, sobrenome, cargo, email, cpf, senha, ativo, cota_id, data_fim_validade) VALUES
('20250001', 'Ana', 'Silva', 'Líder', 'ana.silva@email.com', '12345678901', 'senha123', TRUE, 1, '2025-12-31'),
('20250002', 'Bruno', 'Souza', 'Nenhum', 'bruno.souza@email.com', '23456789012', 'senha123', TRUE, 2, '2025-12-31'),
('20250003', 'Carla', 'Oliveira', 'Vice-líder', 'carla.oliveira@email.com', '34567890123', 'senha123', TRUE, 3, '2025-12-31'),
('20250004', 'Pedro', 'Oliveira', 'Vice-líder', 'pedro.oliveira@email.com', '34567890124', 'senha123', TRUE, 3, '2025-12-31'),
('20250005', 'Lucas', 'Pereira', 'Nenhum', 'lucas.pereira@email.com', '45678901234', 'senha123', TRUE, 1, '2025-12-31'),
('20250006', 'Mariana', 'Lima', 'Nenhum', 'mariana@email.com', '56789012345', 'senha123', TRUE, 2, '2025-12-31'),
('20250007', 'Roberto', 'Santos', 'Líder', 'roberto@email.com', '67890123456', 'senha123', TRUE, 1, '2025-12-31'),
('20250008', 'Fernanda', 'Costa', 'Nenhum', 'fernanda@email.com', '78901234567', 'senha123', TRUE, 2, '2025-12-31'),
('20250009', 'Juliana', 'Mendes', 'Nenhum', 'juliana@email.com', '89012345678', 'senha123', TRUE, 3, '2025-12-31'),
('20250010', 'Ricardo', 'Almeida', 'Vice-líder', 'ricardo@email.com', '90123456789', 'senha123', TRUE, 1, '2025-12-31');

-- Reprografo
INSERT INTO Reprografo (cpf, nome, sobrenome, email, senha) VALUES
('11111111111', 'Paulo', 'Lima', 'paulo.lima@if.edu', 'senha123'),
('22222222222', 'Fernanda', 'Costa', 'fernanda.costa@if.edu', 'senha123');

-- SolicitacaoImpressao
INSERT INTO SolicitacaoImpressao (id, cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, colorida, status, data_criacao, cpf_reprografo) VALUES
(1, '12345678901', 'Aluno', 'trabalho_ana.pdf', 10, FALSE, 'Nova', '2025-06-25 10:00:00', NULL),
(2, '45678901234', 'Aluno', 'relatorio_lucas.pdf', 5, TRUE, 'Aceita', '2025-06-25 11:00:00', '11111111111'),
(3, '1001', 'Servidor', 'oficio_joao.pdf', 3, FALSE, 'Lida', '2025-06-25 12:00:00', '22222222222');

-- Notificacao
INSERT INTO Notificacao (solicitacao_id, destinatario_cpf, mensagem, visualizada, data_envio) VALUES
(1, '12345678901', 'Sua solicitação foi recebida.', FALSE, '2025-06-25 10:05:00'),
(2, '45678901234', 'Sua solicitação foi aceita.', TRUE, '2025-06-25 11:10:00'),
(3, '1001', 'Sua solicitação foi lida.', FALSE, '2025-06-25 12:10:00');

-- LogDecrementoCota
INSERT INTO LogDecrementoCota (solicitacao_id, tipo_usuario, referencia, qtd_copias, data) VALUES
(2, 'Aluno', '20250005', 5, '2025-06-25 11:15:00'),
(3, 'Servidor', '1001', 3, '2025-06-25 12:15:00');