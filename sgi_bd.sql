-- SQL Dump Completo (com Super Admin e Eventos Atualizados)
-- Versão do servidor: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
--
-- Estrutura da tabela `Curso`
--

CREATE TABLE `Curso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sigla` varchar(20) NOT NULL,
  `nome_completo` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sigla` (`sigla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Curso`
--

INSERT INTO `Curso` (`id`, `sigla`, `nome_completo`) VALUES
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

-- --------------------------------------------------------
--
-- Estrutura da tabela `Turma`
--

CREATE TABLE `Turma` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curso_id` (`curso_id`,`periodo`),
  CONSTRAINT `Turma_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `Curso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Turma`
--

INSERT INTO `Turma` (`id`, `curso_id`, `periodo`) VALUES
(1, 1, '1º Período'), (2, 1, '2º Período'), (3, 1, '3º Período'), (19, 1, '4º Período'), (20, 1, '5º Período'), (21, 1, '6º Período'), (22, 1, '7º Período'),
(4, 2, '1º Período'), (5, 2, '2º Período'), (6, 2, '3º Período'), (23, 2, '4º Período'), (24, 2, '5º Período'),
(7, 3, '1º Período'), (8, 3, '2º Período'), (9, 3, '3º Período'), (25, 3, '4º Período'), (26, 3, '5º Período'),
(10, 4, '1º Período'), (11, 4, '2º Período'), (12, 4, '3º Período'), (27, 4, '4º Período'), (28, 4, '5º Período'),
(13, 5, '1º Período'), (14, 5, '2º Período'), (15, 5, '3º Período'), (29, 5, '4º Período'), (30, 5, '5º Período'),
(16, 6, '1º Período'), (17, 6, '2º Período'), (18, 6, '3º Período'), (31, 6, '4º Período'),
(32, 7, '1º Período'), (33, 7, '2º Período'), (34, 7, '3º Período'),
(35, 8, '1º Período'), (36, 8, '2º Período'),
(37, 9, '1º Período'), (38, 9, '2º Período'),
(39, 10, '1º Período');

-- --------------------------------------------------------
--
-- Estrutura da tabela `CotaAluno`
--

CREATE TABLE `CotaAluno` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turma_id` int(11) NOT NULL,
  `cota_total` int(11) NOT NULL DEFAULT 0,
  `cota_usada` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `turma_id` (`turma_id`),
  CONSTRAINT `CotaAluno_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `Turma` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `CotaAluno`
--

INSERT INTO `CotaAluno` (`id`, `turma_id`, `cota_total`, `cota_usada`) VALUES
(1, 1, 250, 100), (2, 4, 200, 50), (3, 7, 180, 30), (4, 10, 220, 80), (5, 13, 150, 20), (6, 16, 170, 10),
(7, 19, 0, 0), (8, 20, 0, 0), (9, 23, 0, 0), (10, 25, 0, 0), (11, 27, 0, 0), (12, 29, 0, 0), (13, 31, 0, 0), (14, 32, 0, 0);

-- --------------------------------------------------------
--
-- Estrutura da tabela `Configuracoes`
--
CREATE TABLE `Configuracoes` (
  `chave` VARCHAR(50) NOT NULL,
  `valor` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Configuracoes`
--
INSERT INTO `Configuracoes` (`chave`, `valor`) VALUES
('cota_padrao_aluno', '600'),
('cota_padrao_servidor_pb', '1000'),
('cota_padrao_servidor_color', '100');

-- --------------------------------------------------------
--
-- Estrutura da tabela `Aluno`
--

CREATE TABLE `Aluno` (
  `matricula` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sobrenome` varchar(100) NOT NULL,
  `cargo` enum('Líder','Vice-líder','Nenhum') DEFAULT 'Nenhum',
  `email` varchar(100) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `cota_id` int(11) DEFAULT NULL,
  `data_fim_validade` date DEFAULT NULL,
  PRIMARY KEY (`matricula`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `cota_id` (`cota_id`),
  CONSTRAINT `Aluno_ibfk_1` FOREIGN KEY (`cota_id`) REFERENCES `CotaAluno` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Aluno`
--

INSERT INTO `Aluno` (`matricula`, `nome`, `sobrenome`, `cargo`, `email`, `cpf`, `senha`, `ativo`, `cota_id`, `data_fim_validade`) VALUES
('20250001', 'Ana', 'Silva', 'Líder', 'ana.silva@email.com', '12345678901', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 1, '2025-07-30'),
('20250002', 'Bruno', 'Souza', 'Nenhum', 'bruno.souza@email.com', '23456789012', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 2, '2025-07-30'),
('20250003', 'Carla', 'Oliveira', 'Vice-líder', 'carla.oliveira@email.com', '34567890123', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 3, '2025-07-30'),
('20250004', 'Pedro', 'Oliveira', 'Vice-líder', 'pedro.oliveira@email.com', '34567890124', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 4, '2025-07-30'),
('20250005', 'Lucas', 'Pereira', 'Nenhum', 'lucas.pereira@email.com', '45678901234', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 5, '2025-07-30'),
('20250006', 'Mariana', 'Lima', 'Nenhum', 'mariana@email.com', '56789012345', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 6, '2025-07-30'),
('20250007', 'Rafael', 'Costa', 'Nenhum', 'rafael.costa@email.com', '67890123457', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 7, '2025-07-30'),
('20250008', 'Juliana', 'Fernandes', 'Nenhum', 'juliana.fernandes@email.com', '78901234568', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 8, '2025-07-30'),
('20250009', 'Felipe', 'Martins', 'Nenhum', 'felipe.martins@email.com', '89012345679', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 9, '2025-07-30'),
('20250010', 'Amanda', 'Barros', 'Nenhum', 'amanda.barros@email.com', '90123456780', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 10, '2025-07-30'),
('20250011', 'Thiago', 'Mendes', 'Nenhum', 'thiago.mendes@email.com', '012222567891', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 11, '2025-07-30'),
('20250012', 'Patricia', 'Alves', 'Nenhum', 'patricia.alves@email.com', '12000098765', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 12, '2025-07-30'),
('20250013', 'Rodrigo', 'Santos', 'Nenhum', 'rodrigo.santos@email.com', '23456098765', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 13, '2025-07-30'),
('20250014', 'Beatriz', 'Gomes', 'Nenhum', 'beatriz.gomes@email.com', '34567098765', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 14, '2025-07-30');

-- --------------------------------------------------------
--
-- Estrutura da tabela `Servidor`
--

CREATE TABLE `Servidor` (
  `siape` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sobrenome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `setor_admin` enum('CAD','COEN','NENHUM') NOT NULL DEFAULT 'NENHUM',
  `ativo` tinyint(1) DEFAULT 1,
  `data_fim_validade` date DEFAULT NULL,
  PRIMARY KEY (`siape`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Servidor`
--

INSERT INTO `Servidor` (`siape`, `nome`, `sobrenome`, `email`, `cpf`, `senha`, `is_admin`, `is_super_admin`, `setor_admin`, `ativo`, `data_fim_validade`) VALUES 
('1001', 'Coordenação', 'de Apoio ao Discente', 'cad.sjdr@ifsudestemg.edu.br', '10010011101', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 1, 'CAD', 1, NULL),
('1002', 'Coordenação', 'de Ensino', 'coen.sjdr@ifsudestemg.edu.br', '20020022202', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 1, 1, 'COEN', 1, NULL),
('1003', 'Carlos', 'Oliveira', 'carlos.oliveira@if.edu', '67890123456', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1004', 'João', 'Pereira', 'joao.pereira@if.edu', '78901234567', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1005', 'Maria', 'Fernandes', 'maria.fernandes@if.edu', '89012345678', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1006', 'Eduardo', 'Santos', 'eduardo.santos@if.edu', '90123456789', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1007', 'Ana', 'Lima', 'ana.lima@if.edu', '91234567890', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1008', 'Fernando', 'Alves', 'fernando.alves@if.edu', '92345678901', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1009', 'Juliana', 'Mendes', 'juliana.mendes@if.edu', '93456789012', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1010', 'Roberto', 'Silva', 'roberto.silva@if.edu', '94567890123', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1011', 'Patrícia', 'Costa', 'patricia.costa@if.edu', '95678901234', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1012', 'André', 'Souza', 'andre.souza@if.edu', '96789012345', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1013', 'Luciana', 'Ribeiro', 'luciana.ribeiro@if.edu', '97890123456', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1014', 'Marcos', 'Dias', 'marcos.dias@if.edu', '98901234567', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1015', 'Bruna', 'Souza', 'bruna.souza@if.edu', '99012345678', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1016', 'Tiago', 'Rocha', 'tiago.rocha@if.edu', '90123456780', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1017', 'Rafaela', 'Silveira', 'rafaela.silveira@if.edu', '91234567881', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1018', 'Henrique', 'Cunha', 'henrique.cunha@if.edu', '92345678902', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1019', 'Vanessa', 'Oliveira', 'vanessa.oliveira@if.edu', '93456789013', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1020', 'Bruno', 'Martins', 'bruno.martins@if.edu', '94567890124', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1021', 'Camila', 'Nascimento', 'camila.nascimento@if.edu', '95678901235', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1022', 'Rogério', 'Farias', 'rogerio.farias@if.edu', '96789012346', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1023', 'Letícia', 'Azevedo', 'leticia.azevedo@if.edu', '97890123457', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1024', 'Diego', 'Lopes', 'diego.lopes@if.edu', '98901234568', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL),
('1025', 'Natália', 'Garcia', 'natalia.garcia@if.edu', '99012345679', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 0, 0, 'NENHUM', 1, NULL);


-- --------------------------------------------------------
--
-- Estrutura da tabela `CotaServidor`
--

CREATE TABLE `CotaServidor` (
  `siape` varchar(20) NOT NULL,
  `cota_pb_total` int(11) NOT NULL DEFAULT 1000,
  `cota_pb_usada` int(11) NOT NULL DEFAULT 0,
  `cota_color_total` int(11) NOT NULL DEFAULT 100,
  `cota_color_usada` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`siape`),
  CONSTRAINT `CotaServidor_ibfk_1` FOREIGN KEY (`siape`) REFERENCES `Servidor` (`siape`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `CotaServidor`
--

INSERT INTO `CotaServidor` (`siape`, `cota_pb_total`, `cota_pb_usada`, `cota_color_total`, `cota_color_usada`) VALUES
('1001', 1000, 0, 100, 0),
('1002', 1000, 0, 100, 0),
('1003', 1000, 0, 100, 0);

-- --------------------------------------------------------
--
-- Estrutura da tabela `Reprografia`
--

CREATE TABLE `Reprografia` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(100) NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `sobrenome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `senha` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_unique` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Reprografia`
--

INSERT INTO `Reprografia` (`id`,`login`, `nome`, `sobrenome`, `email`, `senha`) VALUES
(1,'copyreiif', 'Paulo', 'Lima', 'paulo.lima@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC'),
(2,'copyreiif2', 'Fernanda', 'Costa', 'fernanda.costa@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC');

-- --------------------------------------------------------
--
-- Estrutura da tabela `SolicitacaoImpressao`
--

CREATE TABLE `SolicitacaoImpressao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpf_solicitante` char(11) NOT NULL,
  `tipo_solicitante` enum('Aluno','Servidor') NOT NULL,
  `arquivo_path` text DEFAULT NULL,
  `qtd_copias` int(11) NOT NULL,
  `qtd_paginas` int(11) NOT NULL DEFAULT 1,
  `colorida` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Nova','Lida','Aceita','Rejeitada') NOT NULL DEFAULT 'Nova',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `reprografia_id` int(11) DEFAULT NULL,
  `arquivada` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reprografia_id` (`reprografia_id`),
  CONSTRAINT `SolicitacaoImpressao_ibfk_1` FOREIGN KEY (`reprografia_id`) REFERENCES `Reprografia` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `SolicitacaoImpressao`
--

INSERT INTO `SolicitacaoImpressao` (`id`, `cpf_solicitante`, `tipo_solicitante`, `arquivo_path`, `qtd_copias`, `qtd_paginas`, `colorida`, `status`, `data_criacao`, `Reprografia_id`) VALUES
(1, '12345678901', 'Aluno', 'trabalho_ana.pdf', 10, 2, 0, 'Aceita', '2025-06-27 10:00:00', 1),
(2, '45678901234', 'Aluno', 'relatorio_lucas.pdf', 5, 1, 0, 'Aceita', '2025-06-27 11:00:00', 1),
(3, '10010011101', 'Servidor', 'oficio_joao.pdf', 3, 1, 0, 'Lida', '2025-07-10 12:00:00', 2),
(4, '20020022202', 'Servidor', 'relatorio_maria.pdf', 8, 2, 0, 'Aceita', '2025-07-01 09:00:00', 1),
(5, '20020022202', 'Servidor', 'oficio_maria.pdf', 4, 1, 1, 'Aceita', '2025-06-29 10:30:00', 2),
(6, '10010011101', 'Servidor', 'memorando_joao.pdf', 6, 3, 0, 'Aceita', '2025-05-30 14:00:00', 1),
(7, '10010011101', 'Servidor', 'ata_joao.pdf', 2, 5, 1, 'Rejeitada', '2025-04-01 16:00:00', 2),
(8, '67890123456', 'Servidor', 'relatorio_carlos.pdf', 10, 1, 0, 'Aceita', '2025-03-02 08:45:00', 1),
(9, '67890123456', 'Servidor', 'oficio_carlos.pdf', 3, 2, 1, 'Aceita', '2025-02-10 11:20:00', 2),
(10, '12345678901', 'Aluno', 'artigo_cientifico.pdf', 1, 15, 0, 'Aceita', '2024-11-05 14:30:00', 1),
(11, '23456789012', 'Aluno', 'lista_exercicios.pdf', 3, 5, 0, 'Aceita', '2024-11-10 09:00:00', 2),
(12, '10010011101', 'Servidor', 'documento_oficial.pdf', 2, 1, 1, 'Aceita', '2024-10-20 11:00:00', 1),
(13, '67890123456', 'Servidor', NULL, 50, 1, 0, 'Aceita', '2025-07-11 08:00:00', 2),
(14, '34567890123', 'Aluno', 'seminario_historia.pdf', 2, 8, 0, 'Rejeitada', '2025-07-09 15:00:00', 1),
(15, '45678901234', 'Aluno', NULL, 20, 1, 0, 'Aceita', '2025-07-08 10:00:00', 2),
(16, '20020022202', 'Servidor', 'planilha_financeira.pdf', 1, 3, 1, 'Aceita', '2025-07-05 13:45:00', 1),
(17, '56789012345', 'Aluno', 'capa_trabalho.jpg', 5, 1, 0, 'Aceita', '2025-07-02 18:00:00', 2),
(18, '12345678901', 'Aluno', 'resumo_livro.docx', 1, 4, 0, 'Nova', '2025-07-12 10:00:00', NULL),
(19, '10010011101', 'Servidor', 'relatorio_atividades.pdf', 1, 22, 1, 'Nova', '2025-07-14 11:30:00', NULL);

-- --------------------------------------------------------
--
-- Estrutura da tabela `Notificacao`
--

CREATE TABLE `Notificacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitacao_id` int(11) NOT NULL,
  `destinatario_cpf` char(11) NOT NULL,
  `mensagem` text NOT NULL,
  `visualizada` tinyint(1) NOT NULL DEFAULT 0,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `solicitacao_id` (`solicitacao_id`),
  CONSTRAINT `Notificacao_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `SolicitacaoImpressao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Notificacao`
--

INSERT INTO `Notificacao` (`solicitacao_id`, `destinatario_cpf`, `mensagem`, `visualizada`, `data_envio`) VALUES
(1, '12345678901', 'Sua solicitação foi recebida.', 0, '2025-06-27 10:05:00'),
(2, '45678901234', 'Sua solicitação foi aceita.', 1, '2025-06-27 11:10:00'),
(3, '10010011101', 'Sua solicitação foi lida.', 0, '2025-06-27 12:10:00');

-- --------------------------------------------------------
--
-- Estrutura da tabela `LogDecrementoCota`
--

CREATE TABLE `LogDecrementoCota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitacao_id` int(11) NOT NULL,
  `tipo_usuario` enum('Aluno','Servidor') NOT NULL,
  `referencia` varchar(20) NOT NULL,
  `qtd_cotas` int(11) NOT NULL,
  `data` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `solicitacao_id` (`solicitacao_id`),
  CONSTRAINT `LogDecrementoCota_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `SolicitacaoImpressao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `LogDecrementoCota`
--

INSERT INTO `LogDecrementoCota` (`solicitacao_id`, `tipo_usuario`, `referencia`, `qtd_cotas`, `data`) VALUES
(1, 'Aluno', '20250001', 20, '2025-06-27 10:01:00'),
(2, 'Aluno', '20250005', 5, '2025-06-27 11:01:00'),
(4, 'Servidor', '1002', 16, '2025-07-01 09:01:00'),
(5, 'Servidor', '1002', 4, '2025-06-29 10:31:00'),
(6, 'Servidor', '1001', 18, '2025-05-30 14:01:00'),
(8, 'Servidor', '1003', 10, '2025-03-02 08:46:00'),
(9, 'Servidor', '1003', 6, '2025-02-10 11:21:00'),
(10, 'Aluno', '20250001', 15, '2024-11-05 14:31:00'),
(11, 'Aluno', '20250002', 15, '2024-11-10 09:01:00'),
(12, 'Servidor', '1001', 2, '2024-10-20 11:01:00'),
(13, 'Servidor', '1003', 50, '2025-07-11 08:01:00'),
(15, 'Aluno', '20250005', 20, '2025-07-08 10:01:00'),
(16, 'Servidor', '1002', 3, '2025-07-05 13:46:00'),
(17, 'Aluno', '20250006', 5, '2025-07-02 18:01:00');

-- --------------------------------------------------------
--
-- Estrutura da tabela `SemestreLetivo`
--

CREATE TABLE `SemestreLetivo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `semestre` enum('1','2') NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ano` (`ano`,`semestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `SemestreLetivo`
--

INSERT INTO `SemestreLetivo` (`id`, `ano`, `semestre`, `data_inicio`, `data_fim`) VALUES
(1, 2025, '1', '2025-02-19', '2025-07-30'),
(2, 2025, '2', '2025-08-01', '2025-12-20'),
(3, 2024, '2', '2024-08-01', '2024-12-20');

-- --------------------------------------------------------
--
-- Estrutura da tabela `LogSemestreLetivo`
--

CREATE TABLE `LogSemestreLetivo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(100) NOT NULL,
  `setor` varchar(10) NOT NULL,
  `acao` varchar(255) NOT NULL,
  `data` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- #####################################################################
-- # NOTA IMPORTANTE PARA HOSPEDAGEM
-- #####################################################################
--
-- Os blocos "CREATE EVENT" abaixo servem como documentação da lógica
-- que deve ser implementada no CRON JOB (`tarefas_diarias.php`).
-- Eles estão comentados para não causar erros durante a importação.
--
-- #####################################################################

/*
-- EVENTO PARA DESATIVAR USUÁRIOS EXPIRADOS (LÓGICA PARA O CRON JOB)
DELIMITER //
CREATE EVENT IF NOT EXISTS desativar_usuarios_expirados
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  UPDATE Aluno SET ativo = FALSE, cargo = 'Nenhum' WHERE data_fim_validade IS NOT NULL AND data_fim_validade < CURDATE();
  UPDATE Servidor SET ativo = FALSE WHERE data_fim_validade IS NOT NULL AND data_fim_validade < CURDATE() AND is_admin = FALSE;
END;//
DELIMITER ;
*/

/*
-- EVENTO PARA ARQUIVAR SOLICITAÇÕES ANTIGAS (LÓGICA PARA O CRON JOB)
DELIMITER //
CREATE EVENT IF NOT EXISTS arquivar_solicitacoes_antigas
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  UPDATE SolicitacaoImpressao 
  SET arquivada = TRUE, arquivo_path = NULL
  WHERE data_criacao < NOW() - INTERVAL 15 DAY 
    AND status IN ('Aceita', 'Rejeitada') 
    AND arquivada = FALSE;
END;//
DELIMITER ;
*/

/*
-- EVENTO PARA RESETAR COTAS NO INÍCIO DE CADA SEMESTRE LETIVO (LÓGICA PARA O CRON JOB)
DELIMITER //
CREATE EVENT IF NOT EXISTS resetar_cotas_semestre
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
  DECLARE prox_semestre_inicio DATE;
  DECLARE v_cota_aluno INT;
  DECLARE v_cota_servidor_pb INT;
  DECLARE v_cota_servidor_color INT;

  SELECT data_inicio INTO prox_semestre_inicio FROM SemestreLetivo WHERE data_inicio = CURDATE() LIMIT 1;

  IF prox_semestre_inicio IS NOT NULL THEN
    SELECT valor INTO v_cota_aluno FROM Configuracoes WHERE chave = 'cota_padrao_aluno' LIMIT 1;
    SELECT valor INTO v_cota_servidor_pb FROM Configuracoes WHERE chave = 'cota_padrao_servidor_pb' LIMIT 1;
    SELECT valor INTO v_cota_servidor_color FROM Configuracoes WHERE chave = 'cota_padrao_servidor_color' LIMIT 1;

    UPDATE CotaAluno SET cota_total = IFNULL(v_cota_aluno, 600), cota_usada = 0;
    UPDATE CotaServidor SET cota_pb_total = IFNULL(v_cota_servidor_pb, 1000), cota_pb_usada = 0, cota_color_total = IFNULL(v_cota_servidor_color, 100), cota_color_usada = 0;
  END IF;
END;//
DELIMITER ;
*/

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
