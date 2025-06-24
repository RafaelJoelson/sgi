-- COTA PARA ALUNOS POR TURMA
CREATE TABLE CotaAluno (
    turma VARCHAR(20),
    periodo VARCHAR(10),
    cota_total INT,
    cota_usada INT DEFAULT 0,
    PRIMARY KEY (turma, periodo)
);

-- COTA PARA TODOS OS SERVIDORES
CREATE TABLE CotaServidor (
    id INT PRIMARY KEY CHECK (id = 1),
    cota_total INT,
    cota_usada INT DEFAULT 0
);

-- ALUNO
CREATE TABLE Aluno (
    matricula VARCHAR(20) PRIMARY KEY,
    nome VARCHAR(100),
    cargo ENUM('Líder', 'Vice-líder', 'Nenhum') DEFAULT 'Nenhum',
    email VARCHAR(100),
    cpf CHAR(11) UNIQUE NOT NULL,
    senha VARCHAR(255),
    turma VARCHAR(20),
    periodo VARCHAR(10),
    data_fim_validade DATE,
    FOREIGN KEY (turma, periodo) REFERENCES CotaAluno(turma, periodo)
);

-- SERVIDOR
CREATE TABLE Servidor (
    siap VARCHAR(20) PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(100),
    cpf CHAR(11) UNIQUE NOT NULL,
    senha VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    setor_admin ENUM('CAD', 'COEN')
);

-- REPROGRAFO
CREATE TABLE Reprografo (
    cpf CHAR(11) PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(100),
    senha VARCHAR(255)
);

-- SOLICITAÇÃO DE IMPRESSÃO
CREATE TABLE SolicitacaoImpressao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf_solicitante CHAR(11) NOT NULL,
    arquivo_path TEXT,
    qtd_copias INT,
    colorida BOOLEAN,
    status ENUM('Nova', 'Lida', 'Aceita', 'Rejeitada') DEFAULT 'Nova',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    cpf_reprografo CHAR(11),
    FOREIGN KEY (cpf_reprografo) REFERENCES Reprografo(cpf)
);

-- NOTIFICAÇÕES
CREATE TABLE Notificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT,
    destinatario_cpf CHAR(11),
    mensagem TEXT,
    visualizada BOOLEAN DEFAULT FALSE,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id)
);

-- LOG DE DECREMENTO DE COTAS
CREATE TABLE LogDecrementoCota (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id INT,
    tipo_usuario ENUM('Aluno', 'Servidor'),
    referencia VARCHAR(20), -- matrícula ou siap
    qtd_copias INT,
    data DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitacao_id) REFERENCES SolicitacaoImpressao(id)
);
