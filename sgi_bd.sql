-- COTA PARA ALUNOS POR TURMA
CREATE TABLE CotaAluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma VARCHAR(20) NOT NULL,
    periodo VARCHAR(10) NOT NULL,
    cota_total INT DEFAULT 0 NOT NULL,
    cota_usada INT DEFAULT 0 NOT NULL,
    UNIQUE (turma, periodo)
);

-- COTA PARA TODOS OS SERVIDORES
CREATE TABLE CotaServidor (
    id INT PRIMARY KEY CHECK (id = 1),
    cota_total INT NOT NULL,
    cota_usada INT DEFAULT 0 NOT NULL
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
    cota_id INT,
    data_fim_validade DATE,
    FOREIGN KEY (cota_id) REFERENCES CotaAluno(id)
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
    setor_admin ENUM('CAD', 'COEN', 'NENHUM') DEFAULT 'NENHUM' NOT NULL
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
    arquivo_path TEXT,
    qtd_copias INT NOT NULL,
    colorida BOOLEAN NOT NULL,
    status ENUM('Nova', 'Lida', 'Aceita', 'Rejeitada') DEFAULT 'Nova' NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    cpf_reprografo CHAR(11),
    FOREIGN KEY (cpf_reprografo) REFERENCES Reprografo(cpf)
);

-- NOTIFICAÇÕES
CREATE TABLE Notificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT NOT NULL,
    destinatario_cpf CHAR(11) NOT NULL,
    mensagem TEXT NOT NULL,
    visualizada BOOLEAN DEFAULT FALSE NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id)
);

-- LOG DE DECREMENTO DE COTAS
CREATE TABLE LogDecrementoCota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT NOT NULL,
    tipo_usuario ENUM('Aluno', 'Servidor') NOT NULL,
    referencia VARCHAR(20) NOT NULL, -- matrícula ou siap
    qtd_copias INT NOT NULL,
    data DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id)
);

-- CotaAluno
INSERT INTO CotaAluno (id, turma, periodo, cota_total, cota_usada) VALUES
(1, 'LET', '2025.1', 250, 200),
(2, 'GRH', '2025.1', 250, 100),
(3, 'LOG', '2025.1', 250, 50);

-- CotaServidor
INSERT INTO CotaServidor (id, cota_total, cota_usada) VALUES
(1, 1000, 650);

-- Aluno
INSERT INTO Aluno (matricula, nome, sobrenome, cargo, email, cpf, senha, cota_id, data_fim_validade) VALUES
('20250001', 'Ana', 'Silva', 'Líder', 'ana.silva@email.com', '12345678901', 'senha123', 1, '2025-12-31'),
('20250002', 'Bruno', 'Souza', 'Nenhum', 'bruno.souza@email.com', '23456789012', 'senha123', 2, '2025-12-31'),
('20250003', 'Carla', 'Oliveira', 'Vice-líder', 'carla.oliveira@email.com', '34567890123', 'senha123', 3, '2025-12-31'),
('20250004', 'Pedro', 'Oliveira', 'Vice-líder', 'pedro.oliveira@email.com', '34567890124', 'senha123', 3, '2025-12-31'),
('20250005', 'Lucas', 'Pereira', 'Nenhum', 'lucas.pereira@email.com', '45678901234', 'senha123', 1, '2025-12-31'),
('20250006', 'Mariana', 'Lima', 'Nenhum', 'mariana@email.com', '56789012345', 'senha123', 2, '2025-12-31'),
('20250007', 'Roberto', 'Santos', 'Líder', 'roberto@email.com', '67890123456', 'senha123', 1, '2025-12-31'),
('20250008', 'Fernanda', 'Costa', 'Nenhum', 'fernanda@email.com', '78901234567', 'senha123', 2, '2025-12-31'),
('20250009', 'Juliana', 'Mendes', 'Nenhum', 'juliana@email.com', '89012345678', 'senha123', 3, '2025-12-31'),
('20250010', 'Ricardo', 'Almeida', 'Vice-líder', 'ricardo@email.com', '90123456789', 'senha123', 1, '2025-12-31'),
('20250011', 'Patrícia', 'Barros', 'Nenhum', 'patricia@email.com', '11223344556', 'senha123', 2, '2025-12-31'),
('20250012', 'Eduardo', 'Martins', 'Líder', 'eduardo@email.com', '22334455667', 'senha123', 3, '2025-12-31'),
('20250013', 'Camila', 'Ramos', 'Nenhum', 'camila@email.com', '33445566778', 'senha123', 1, '2025-12-31'),
('20250014', 'Felipe', 'Gomes', 'Nenhum', 'felipe@email.com', '44556677889', 'senha123', 2, '2025-12-31'),
('20250015', 'Aline', 'Ferreira', 'Vice-líder', 'aline@email.com', '55667788990', 'senha123', 3, '2025-12-31'),
('20250016', 'Thiago', 'Moraes', 'Nenhum', 'thiago@email.com', '66778899001', 'senha123', 1, '2025-12-31'),
('20250017', 'Bianca', 'Teixeira', 'Nenhum', 'bianca@email.com', '77889900112', 'senha123', 2, '2025-12-31'),
('20250018', 'Gustavo', 'Rocha', 'Líder', 'gustavo@email.com', '88990011223', 'senha123', 3, '2025-12-31');

-- Servidor
INSERT INTO Servidor (siap, nome, sobrenome, email, cpf, senha, is_admin, setor_admin) VALUES
('1001', 'João', 'Silva', 'joao.silva@if.edu', '45678901234', 'senha123', TRUE, 'CAD'),
('1002', 'Maria', 'Fernandes', 'maria.fernandes@if.edu', '56789012345', 'senha123', FALSE, 'NENHUM');

-- Reprografo
INSERT INTO Reprografo (cpf, nome, sobrenome, email, senha) VALUES
('67890123456', 'Paulo', 'Lima', 'paulo.lima@if.edu', 'senha123'),
('78901234567', 'Fernanda', 'Costa', 'fernanda.costa@if.edu', 'senha123');

-- SolicitacaoImpressao
INSERT INTO SolicitacaoImpressao (id, cpf_solicitante, arquivo_path, qtd_copias, colorida, status, data_criacao, cpf_reprografo) VALUES
(1, '12345678901', 'trabalho_ana.pdf', 10, FALSE, 'Nova', '2025-06-25 10:00:00', NULL),
(2, '23456789012', 'relatorio_bruno.pdf', 5, TRUE, 'Aceita', '2025-06-25 11:00:00', '67890123456');

-- Notificacao
INSERT INTO Notificacao (id, solicitacao_id, destinatario_cpf, mensagem, visualizada, data_envio) VALUES
(1, 1, '12345678901', 'Sua solicitação foi recebida.', FALSE, '2025-06-25 10:05:00'),
(2, 2, '23456789012', 'Sua solicitação foi aceita.', TRUE, '2025-06-25 11:10:00');

-- LogDecrementoCota
INSERT INTO LogDecrementoCota (id, solicitacao_id, tipo_usuario, referencia, qtd_copias, data) VALUES
(1, 2, 'Aluno', '20250002', 5, '2025-06-25 11:15:00');