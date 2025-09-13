
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
(1, 1, 600, 0),
(2, 4, 600, 0),
(3, 7, 600, 0),
(4, 10, 600, 0),
(5, 13, 600, 0),
(6, 16, 600, 0),
(7, 19, 600, 0),
(8, 22, 600, 0),
(9, 25, 600, 0),
(10, 28, 600, 0),
(11, 31, 600, 0),
(12, 34, 600, 0),
(13, 37, 600, 0),
(14, 39, 600, 0),
(15, 2, 600, 0),
(16, 5, 600, 0),
(17, 8, 600, 0),
(18, 11, 600, 0),
(19, 14, 600, 0),
(20, 17, 600, 0),
(21, 20, 600, 0),
(22, 23, 600, 0),
(23, 26, 600, 0),
(24, 29, 600, 0),
(25, 32, 600, 0),
(26, 35, 600, 0);
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

CREATE TABLE `Usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpf` char(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sobrenome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('aluno','servidor') NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_fim_validade` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Usuario` (migrados de Aluno e Servidor)
--
INSERT INTO `Usuario` (`id`, `cpf`, `nome`, `sobrenome`, `email`, `senha`, `tipo_usuario`, `ativo`, `data_fim_validade`) VALUES
(1, '12345678901', 'Gretchen', 'Rainha', 'gretchen.reidaweb@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(2, '23456789012', 'Inês', 'Brasil', 'ines.brasil.sensual@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(3, '34567890123', 'Carminha', 'Sincera', 'carminha.sincera.meme@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(4, '34567890124', 'Chaves', 'SemTeto', 'chaves.sem.teto@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(5, '45678901234', 'Nazaré', 'Tedesco', 'nazare.confusa.tediosa@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(6, '56789012345', 'Joelma', 'Calypso', 'joelma.nao.sou.chimbinha@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(7, '67890123457', 'Cumpadi', 'Washington', 'cumpadi.washington.zoeira@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(8, '78901234568', 'Dona', 'Hermínia', 'dona.herminia.pai@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(9, '89012345679', 'Lula', 'Molusco', 'lula.molusco.9dedos@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(10, '10123456066', 'Tulla', 'Luana', 'tulla.luana.topzera@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 0, '2025-07-30'),
(11, '01222256789', 'Zé', 'Pequeno', 'ze.pequeno.cdd@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(12, '12000098765', 'Valentina', 'Mansur', 'valentina.mansur.bolada@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(13, '23456098765', 'Seu', 'Madruga', 'seu.madruga.14bis@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 0, '2025-07-30'),
(14, '34567033365', 'Valesca', 'Popozuda', 'valesca.popozuda.funk@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(15, '30567098005', 'Simas', '(Aquele Mesmo)', 'simasturbo@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(16, '24557098765', 'Luva', 'de Pedreiro', 'luva@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(17, '84567098744', 'Clóvis', 'Basílio', 'oatorkidbengala@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(18, '01215756789', 'Jair', 'Tornozeleira', 'deuspatriaerachadinha@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(19, '81206956789', 'Chico', 'Tadala', 'chicoduro@email.com', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'aluno', 1, '2025-07-30'),
(20, '10010011101', 'Coordenação', 'de Apoio ao Discente', 'cad.sjdr@ifsudestemg.edu.br', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(21, '20020022202', 'Coordenação', 'de Ensino', 'coen.sjdr@ifsudestemg.edu.br', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(22, '67890123456', 'Ailton', 'Magela de Assis Augusto', 'ailton.magela@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(23, '78901234567', 'Alessandra', 'Furtado Fernandes', 'alessandra.furtado@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(24, '89012345678', 'Alex', 'Mourão Terzi', 'alex.mourao@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(25, '90123456789', 'Alexandre', 'Furtado Fernandes', 'alexandre.furtado@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(26, '91234567890', 'Anderson', 'Geraldo Rodrigues', 'anderson.geraldo@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(27, '92345678901', 'André', 'Luís Fonseca Furtado', 'andre.luis@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(28, '93456789012', 'Angélica', 'Aparecida Amarante Terra', 'angelica.aparecida@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(29, '94567890123', 'Antônio', 'Cléber da Silva', 'antonio.cleber@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(30, '95678901234', 'Ataualpa', 'Luiz de Oliveira', 'ataualpa.luiz@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(31, '96789012345', 'Bruno', 'Márcio Agostini', 'bruno.marcio@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(32, '97890123456', 'Carla', 'Fabiana Gouvêa Lopes', 'carla.fabiana@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(33, '98901234567', 'Carlos', 'Augusto Braga Tavares', 'carlos.augusto@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(34, '99012345678', 'Celso', 'Luiz de Souza', 'celso.luiz@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(35, '90123456780', 'Denise', 'Espíndola Moraes', 'denise.espindola@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(36, '91234567881', 'Diego', 'Henrique dos Santos', 'diego.henrique@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(37, '92345678902', 'Elaine', 'Aparecida Carvalho', 'elaine.aparecida@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(38, '93456789013', 'Elke', 'Carvalho Teixeira', 'elke.carvalho@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(39, '94567890124', 'Ernani', 'Coimbra de Oliveira', 'ernani.coimbra@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(40, '95678901235', 'Esther', 'de Matos Ireno Marques', 'esther.matos@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(41, '96789012346', 'Eva', 'Vilma Muniz de Oliveira', 'eva.vilma@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(42, '97890123457', 'Fernanda', 'Maria do Nascimento Aihara', 'fernanda.maria@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(43, '98905894568', 'Gilma', 'Aparecida Santos Campos', 'gilma.aparecida@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(44, '20012345679', 'Gisele', 'Francisca da Silva Carvalho', 'gisele.francisca@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(45, '90123456781', 'Isabel', 'Cristina Adão Schiavon', 'isabel.cristina@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(46, '91234567882', 'Isabella', 'Cristina Moraes Campos', 'isabella.cristina@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(47, '92345678903', 'Ivete', 'Sara de Almeida', 'ivete.sara@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(48, '93456789014', 'Janaína', 'de Assis Rufino', 'janaina.assis@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(49, '94567890125', 'Janaína', 'Faria Cardoso Maia', 'janaina.faria@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(50, '95678901236', 'José', 'Bernardo de Broutelles', 'jose.bernardo@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(51, '96789012347', 'José', 'Félix Hernandez Martin', 'jose.felix@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(52, '97890123458', 'José', 'Saraiva Cruz', 'jose.saraiva@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(53, '98901234569', 'Juliana', 'Brito de Souza', 'juliana.brito@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(54, '99012345680', 'Kelen', 'Benfenatti Paiva', 'kelen.benfenatti@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(55, '90123456782', 'Larissa', 'de Oliveira Mendes', 'larissa.oliveira@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(56, '91234567883', 'Leandro', 'Eduardo Vieira Barros', 'leandro.eduardo@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(57, '92345678904', 'Leonardo', 'Henrique de Almeida e Silva', 'leonardo.henrique@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(58, '93456789015', 'Lílian', 'do Nascimento', 'lilian.nascimento@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(59, '94567890126', 'Liliane', 'Chaves de Resende', 'liliane.chaves@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(60, '95678901237', 'Lúcia', 'Helena de Magalhães', 'lucia.helena@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(61, '96789012348', 'Maria', 'das Graças Alves Costa', 'maria.gracas@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(62, '97890123459', 'Maurício', 'Carlos da Silva', 'mauricio.carlos@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(63, '98901234570', 'Monik', 'Evelin Leite Diniz', 'monik.evelin@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(64, '99012345681', 'Natália', 'Rabelo Soares', 'natalia.rabelo@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(65, '90123456783', 'Ozana', 'Aparecida do Sacramento', 'ozana.aparecida@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(66, '91234567884', 'Priscila', 'Fernandes Santanna', 'priscila.fernandes@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(67, '92345678905', 'Priscila', 'Souza Pereira', 'priscila.souza@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(68, '93456789016', 'Rosana', 'Machado de Souza', 'rosana.machado@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(69, '94567890127', 'Rúbia', 'Mara Ribeiro', 'rubia.mara@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(70, '95678901238', 'Rafael', 'Santiago Soares', 'rafael.santiago@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(71, '96789012349', 'Sâmara', 'Sathler Corrêa de Lima', 'samara.sathler@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(72, '97890123460', 'Suzana', 'Vale Rodrigues', 'suzana.vale@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(73, '98901234571', 'Tamíres', 'Partélli Correia', 'tamires.partelli@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(74, '99012345682', 'Teresinha', 'Moreira de Magalhães', 'teresinha.moreira@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(75, '90123456784', 'Tiago', 'André Carbonaro de Oliveira', 'tiago.andre@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(76, '91234567885', 'Waldilainy', 'de Campos', 'waldilainy.campos@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(77, '92345678906', 'Vaneska', 'Ribeiro Perfeito Santos', 'vaneska.ribeiro@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(78, '93456789017', 'Vitor', 'Cordeiro Costa', 'vitor.cordeiro@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(79, '94567890128', 'Viviane', 'Vasques da Silva Guilarduci', 'viviane.vasques@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 1, NULL),
(80, '95678901239', 'Bruno', 'de Lima Palhares', 'bruno.lima@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 0, NULL),
(81, '96789012350', 'Magno', 'Geraldo de Aquino', 'magno.geraldo@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 0, NULL),
(82, '97890123461', 'Rodrigo', 'de Carvalho Santos', 'rodrigo.carvalho@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 0, NULL),
(83, '98901234572', 'Rosalba', 'Lopes', 'rosalba.lopes@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 0, NULL),
(84, '99012345683', 'Valéria', 'Rezende Freitas Barros', 'valeria.rezende@if.edu', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC', 'servidor', 0, NULL);

-- --------------------------------------------------------
--
-- Estrutura da tabela `Aluno` (Refatorada)
--

CREATE TABLE `Aluno` (
  `usuario_id` int(11) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `cargo` enum('Líder','Vice-líder','Nenhum') DEFAULT 'Nenhum',
  `cota_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `cota_id` (`cota_id`),
  CONSTRAINT `Aluno_ibfk_1` FOREIGN KEY (`cota_id`) REFERENCES `CotaAluno` (`id`) ON DELETE SET NULL,
  CONSTRAINT `Aluno_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Aluno`
--

INSERT INTO `Aluno` (`usuario_id`, `matricula`, `cargo`, `cota_id`) VALUES
(1, '2025000001', 'Líder', 2),
(2, '2025000002', 'Vice-líder', 2),
(3, '2025000003', 'Vice-líder', 3),
(4, '2025000004', 'Vice-líder', 4),
(5, '2025000005', 'Líder', 5),
(6, '2025000006', 'Líder', 6),
(7, '2025000007', 'Vice-líder', 7),
(8, '2025000008', 'Vice-líder', 8),
(9, '2025000009', 'Líder', 9),
(10, '2025000010', 'Nenhum', 10),
(11, '2025000011', 'Líder', 11),
(12, '2025000012', 'Vice-líder', 12),
(13, '2025000013', 'Nenhum', 13),
(14, '2025000014', 'Vice-líder', 13),
(15, '2025000015', 'Líder', 13),
(16, '2025000016', 'Líder', 14),
(17, '2025000017', 'Vice-líder', 14),
(18, '2025000018', 'Líder', 11),
(19, '2025000019', 'Vice-líder', 11);

-- --------------------------------------------------------
--
-- Estrutura da tabela `Servidor`
-- (Refatorada)
--

CREATE TABLE `Servidor` (
  `usuario_id` int(11) NOT NULL,
  `siape` varchar(20) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `setor_admin` enum('CAD','COEN','NENHUM') NOT NULL DEFAULT 'NENHUM',
  PRIMARY KEY (`usuario_id`),
  UNIQUE KEY `siape` (`siape`),
  CONSTRAINT `Servidor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Servidor`
--

INSERT INTO `Servidor` (`usuario_id`, `siape`, `is_admin`, `is_super_admin`, `setor_admin`) VALUES
(20, '1000001', 1, 1, 'CAD'),
(21, '1000002', 1, 1, 'COEN'),
(22, '1000003', 0, 0, 'NENHUM'),
(23, '1000004', 0, 0, 'NENHUM'),
(24, '1000005', 0, 0, 'NENHUM'),
(25, '1000006', 0, 0, 'NENHUM'),
(26, '1000007', 0, 0, 'NENHUM'),
(27, '1000008', 0, 0, 'NENHUM'),
(28, '1000009', 0, 0, 'NENHUM'),
(29, '1000010', 0, 0, 'NENHUM'),
(30, '1000011', 0, 0, 'NENHUM'),
(31, '1000012', 0, 0, 'NENHUM'),
(32, '1000013', 0, 0, 'NENHUM'),
(33, '1000014', 0, 0, 'NENHUM'),
(34, '1000015', 0, 0, 'NENHUM'),
(35, '1000016', 1, 0, 'COEN'),
(36, '1000017', 0, 0, 'NENHUM'),
(37, '1000018', 0, 0, 'NENHUM'),
(38, '1000019', 0, 0, 'NENHUM'),
(39, '1000020', 0, 0, 'NENHUM'),
(40, '1000021', 0, 0, 'NENHUM'),
(41, '1000022', 0, 0, 'NENHUM'),
(42, '1000023', 0, 0, 'NENHUM'),
(43, '1000024', 0, 0, 'NENHUM'),
(44, '1000025', 0, 0, 'NENHUM'),
(45, '1000026', 0, 0, 'NENHUM'),
(46, '1000027', 0, 0, 'NENHUM'),
(47, '1000028', 0, 0, 'NENHUM'),
(48, '1000029', 0, 0, 'NENHUM'),
(49, '1000030', 0, 0, 'NENHUM'),
(50, '1000031', 0, 0, 'NENHUM'),
(51, '1000032', 0, 0, 'NENHUM'),
(52, '1000033', 0, 0, 'NENHUM'),
(53, '1000034', 0, 0, 'NENHUM'),
(54, '1000035', 0, 0, 'NENHUM'),
(55, '1000036', 0, 0, 'NENHUM'),
(56, '1000037', 0, 0, 'NENHUM'),
(57, '1000038', 0, 0, 'NENHUM'),
(58, '1000039', 0, 0, 'NENHUM'),
(59, '1000040', 0, 0, 'NENHUM'),
(60, '1000041', 0, 0, 'NENHUM'),
(61, '1000042', 0, 0, 'NENHUM'),
(62, '1000043', 0, 0, 'NENHUM'),
(63, '1000044', 0, 0, 'NENHUM'),
(64, '1000045', 1, 0, 'CAD'),
(65, '1000046', 0, 0, 'NENHUM'),
(66, '1000047', 0, 0, 'NENHUM'),
(67, '1000048', 0, 0, 'NENHUM'),
(68, '1000049', 0, 0, 'NENHUM'),
(69, '1000050', 0, 0, 'NENHUM'),
(70, '1000051', 0, 0, 'NENHUM'),
(71, '1000052', 0, 0, 'NENHUM'),
(72, '1000053', 0, 0, 'NENHUM'),
(73, '1000054', 0, 0, 'NENHUM'),
(74, '1000055', 0, 0, 'NENHUM'),
(75, '1000056', 1, 0, 'COEN'),
(76, '1000057', 0, 0, 'NENHUM'),
(77, '1000058', 0, 0, 'NENHUM'),
(78, '1000059', 0, 0, 'NENHUM'),
(79, '1000060', 0, 0, 'NENHUM'),
(80, '1000061', 0, 0, 'NENHUM'),
(81, '1000062', 0, 0, 'NENHUM'),
(82, '1000063', 0, 0, 'NENHUM'),
(83, '1000064', 0, 0, 'NENHUM'),
(84, '1000065', 0, 0, 'NENHUM');


-- --------------------------------------------------------
--
-- Estrutura da tabela `CotaServidor`
--

CREATE TABLE `CotaServidor` (
  `usuario_id` int(11) NOT NULL,
  `cota_pb_total` int(11) NOT NULL DEFAULT 1000,
  `cota_pb_usada` int(11) NOT NULL DEFAULT 0,
  `cota_color_total` int(11) NOT NULL DEFAULT 100,
  `cota_color_usada` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `CotaServidor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `Servidor` (`usuario_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `CotaServidor`
--

INSERT INTO `CotaServidor` (`usuario_id`, `cota_pb_total`, `cota_pb_usada`, `cota_color_total`, `cota_color_usada`) VALUES
(20, 0, 0, 0, 0),
(21, 0, 0, 0, 0),
(22, 1000, 0, 100, 0),
(23, 1000, 0, 100, 0),
(24, 1000, 0, 100, 0),
(25, 1000, 0, 100, 0),
(26, 1000, 0, 100, 0),
(27, 1000, 0, 100, 0),
(28, 1000, 0, 100, 0),
(29, 1000, 0, 100, 0),
(30, 1000, 0, 100, 0),
(31, 1000, 0, 100, 0),
(32, 1000, 0, 100, 0),
(33, 1000, 0, 100, 0),
(34, 1000, 0, 100, 0),
(35, 1000, 0, 100, 0),
(36, 1000, 0, 100, 0),
(37, 1000, 0, 100, 0),
(38, 1000, 0, 100, 0),
(39, 1000, 0, 100, 0),
(40, 1000, 0, 100, 0),
(41, 1000, 0, 100, 0),
(42, 1000, 0, 100, 0),
(43, 1000, 0, 100, 0),
(44, 1000, 0, 100, 0),
(45, 1000, 0, 100, 0),
(46, 1000, 0, 100, 0),
(47, 1000, 0, 100, 0),
(48, 1000, 0, 100, 0),
(49, 1000, 0, 100, 0),
(50, 1000, 0, 100, 0),
(51, 1000, 0, 100, 0),
(52, 1000, 0, 100, 0),
(53, 1000, 0, 100, 0),
(54, 1000, 0, 100, 0),
(55, 1000, 0, 100, 0),
(56, 1000, 0, 100, 0),
(57, 1000, 0, 100, 0),
(58, 1000, 0, 100, 0),
(59, 1000, 0, 100, 0),
(60, 1000, 0, 100, 0),
(61, 1000, 0, 100, 0),
(62, 1000, 0, 100, 0),
(63, 1000, 0, 100, 0),
(64, 1000, 0, 100, 0),
(65, 1000, 0, 100, 0),
(66, 1000, 0, 100, 0),
(67, 1000, 0, 100, 0),
(68, 1000, 0, 100, 0),
(69, 1000, 0, 100, 0),
(70, 1000, 0, 100, 0),
(71, 1000, 0, 100, 0),
(72, 1000, 0, 100, 0),
(73, 1000, 0, 100, 0),
(74, 1000, 0, 100, 0),
(75, 1000, 0, 100, 0),
(76, 1000, 0, 100, 0),
(77, 1000, 0, 100, 0),
(78, 1000, 0, 100, 0),
(79, 1000, 0, 100, 0),
(80, 1000, 0, 100, 0),
(81, 1000, 0, 100, 0),
(82, 1000, 0, 100, 0),
(83, 1000, 0, 100, 0),
(84, 1000, 0, 100, 0);


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
(1,'copyreiif', 'Everton', 'Elias de Souza Carvalho', 'copyrey@mgconecta.com.br', '$2y$10$2YGEaaMW1iBL1lEinmI50uaRxArlRYd7UtvnUNTvt7sBQTQfRstia'),
(2,'copyreiif2', 'Reprografia', '2', 'copyrey@mgconecta.com.br', '$2y$10$17dOADFRPti.MK62Y.shK.8ph9JJEFiQVI33hW9wCCKaDaQgU9bJC');

-- --------------------------------------------------------
--
-- Estrutura da tabela `SolicitacaoImpressao`
--

CREATE TABLE `SolicitacaoImpressao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `arquivo_path` text DEFAULT NULL,
  `qtd_copias` int(11) NOT NULL,
  `qtd_paginas` int(11) NOT NULL DEFAULT 1,
  `colorida` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Nova','Lida','Aceita','Rejeitada') NOT NULL DEFAULT 'Nova',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `reprografia_id` int(11) DEFAULT NULL,
  `arquivada` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `reprografia_id` (`reprografia_id`),
  CONSTRAINT `SolicitacaoImpressao_ibfk_1` FOREIGN KEY (`reprografia_id`) REFERENCES `Reprografia` (`id`) ON DELETE SET NULL,
  CONSTRAINT `SolicitacaoImpressao_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `SolicitacaoImpressao`
--

INSERT INTO `SolicitacaoImpressao` (`id`, `usuario_id`, `arquivo_path`, `qtd_copias`, `qtd_paginas`, `colorida`, `status`, `data_criacao`, `reprografia_id`) VALUES
(1, 1, 'trabalho_ana.pdf', 10, 2, 0, 'Rejeitada', '2025-06-27 10:00:00', 1),
(2, 5, 'relatorio_lucas.pdf', 5, 1, 0, 'Rejeitada', '2025-06-27 11:00:00', 1),
(3, 24, 'oficio_joao.pdf', 3, 1, 0, 'Rejeitada', '2025-07-10 12:00:00', 2),
(4, 8, 'relatorio_maria.pdf', 8, 2, 0, 'Rejeitada', '2025-07-01 09:00:00', 1),
(5, 8, 'oficio_maria.pdf', 4, 1, 1, 'Rejeitada', '2025-06-29 10:30:00', 2),
(6, 33, 'memorando_joao.pdf', 6, 3, 0, 'Rejeitada', '2025-05-30 14:00:00', 1),
(7, 37, NULL, 2, 5, 1, 'Rejeitada', '2025-04-01 16:00:00', 2),
(8, 22, 'relatorio_carlos.pdf', 10, 1, 0, 'Rejeitada', '2025-03-02 08:45:00', 1),
(9, 22, 'oficio_carlos.pdf', 3, 2, 1, 'Rejeitada', '2025-02-10 11:20:00', 2),
(10, 1, 'artigo_cientifico.pdf', 1, 15, 0, 'Rejeitada', '2024-11-05 14:30:00', 1),
(11, 2, 'lista_exercicios.pdf', 3, 5, 0, 'Rejeitada', '2024-11-10 09:00:00', 2),
(12, 24, 'documento_oficial.pdf', 2, 1, 1, 'Rejeitada', '2024-10-20 11:00:00', 1),
(13, 22, NULL, 50, 1, 0, 'Rejeitada', '2025-07-11 08:00:00', 2),
(14, 3, 'seminario_historia.pdf', 2, 8, 0, 'Rejeitada', '2025-07-09 15:00:00', 1),
(15, 5, NULL, 20, 1, 0, 'Rejeitada', '2025-07-08 10:00:00', 2),
(16, 21, 'planilha_financeira.pdf', 1, 3, 1, 'Rejeitada', '2025-07-05 13:45:00', 1),
(17, 6, 'capa_trabalho.jpg', 5, 1, 0, 'Rejeitada', '2025-07-02 18:00:00', 2),
(18, 1, 'resumo_livro.docx', 1, 4, 0, 'Nova', '2025-07-12 10:00:00', 1),
(19, 24, 'relatorio_atividades.pdf', 1, 22, 1, 'Nova', '2025-07-14 11:30:00', 1),
-- Solicitações de servidores ao longo de 2024
(20, 22, 'projeto_extensao.pdf', 2, 10, 0, 'Aceita', '2024-03-15 09:00:00', 1),
(21, 36, 'oficio_diretoria.docx', 1, 5, 1, 'Aceita', '2024-04-10 14:20:00', 2),
(22, 36, 'memorando_interno.pdf', 3, 2, 0, 'Lida', '2024-05-05 08:30:00', 1),
(23, 25, 'relatorio_financeiro.xlsx', 1, 12, 0, 'Aceita', '2024-06-01 11:45:00', 2),
(24, 26, 'plano_ensino.pdf', 2, 8, 1, 'Aceita', '2024-07-18 10:10:00', 1),
(25, 26, 'documento_administrativo.docx', 1, 6, 0, 'Aceita', '2024-08-22 15:00:00', 2),
(26, 26, 'relatorio_pesquisa.pdf', 2, 9, 1, 'Aceita', '2024-09-30 13:25:00', 1),
(27, 34, 'comunicado_oficial.pdf', 1, 4, 0, 'Aceita', '2024-10-12 16:40:00', 2),
(29, 31, 'relatorio_final.pdf', 2, 11, 0, 'Aceita', '2024-12-05 12:15:00', 2),
-- Solicitações de alunos ao longo de 2023
(30, 1, 'projeto_portugues.pdf', 5, 3, 0, 'Aceita', '2023-03-10 09:00:00', 1),
(31, 2, 'resenha_literaria.docx', 2, 4, 0, 'Aceita', '2023-04-15 10:30:00', 2),
(32, 3, 'trabalho_historia.pdf', 3, 2, 0, 'Aceita', '2023-05-20 14:00:00', 1),
(33, 15, 'relatorio_quimica.pdf', 4, 5, 1, 'Aceita', '2023-06-05 11:15:00', 2),
(34, 15, 'lista_matematica.pdf', 2, 6, 0, 'Aceita', '2023-07-12 08:45:00', 1),
(35, 7, 'projeto_biologia.pdf', 1, 8, 0, 'Aceita', '2023-08-18 13:20:00', 2),
(36, 8, 'capa_trabalho.pdf', 2, 1, 0, 'Aceita', '2023-09-25 15:00:00', 1),
(37, 17, 'artigo_cientifico.docx', 3, 7, 1, 'Aceita', '2023-10-30 16:40:00', 2),
(38, 15, 'resumo_geografia.pdf', 2, 2, 0, 'Aceita', '2023-11-15 09:30:00', 1),
(39, 17, 'trabalho_fisica.pdf', 1, 5, 0, 'Aceita', '2023-12-01 10:10:00', 2);

-- --------------------------------------------------------
--
-- Estrutura da tabela `Notificacao`
--

CREATE TABLE `Notificacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitacao_id` int(11) DEFAULT NULL,
  `destinatario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `visualizada` tinyint(1) NOT NULL DEFAULT 0,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_destinatario_id` (`destinatario_id`),
  KEY `solicitacao_id` (`solicitacao_id`),
  CONSTRAINT `Notificacao_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `SolicitacaoImpressao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `Notificacao`
--

INSERT INTO `Notificacao` (`solicitacao_id`, `destinatario_id`, `mensagem`, `visualizada`, `data_envio`) VALUES
(1, 1, 'Sua solicitação foi recebida.', 0, '2025-06-27 10:05:00'),
(2, 5, 'Sua solicitação foi aceita.', 1, '2025-06-27 11:10:00'),
(3, 20, 'Sua solicitação foi lida.', 0, '2025-06-27 12:10:00');

-- --------------------------------------------------------
--
-- Estrutura da tabela `LogDecrementoCota`
--

CREATE TABLE `LogDecrementoCota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `qtd_cotas` int(11) NOT NULL,
  `data` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `solicitacao_id` (`solicitacao_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `LogDecrementoCota_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `SolicitacaoImpressao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados para a tabela `LogDecrementoCota`
--

INSERT INTO `LogDecrementoCota` (`solicitacao_id`, `usuario_id`, `qtd_cotas`, `data`) VALUES
(1, 1, 20, '2025-06-27 10:01:00'),
(2, 5, 5, '2025-06-27 11:01:00'),
(4, 21, 16, '2025-07-01 09:01:00'),
(5, 21, 4, '2025-06-29 10:31:00'),
(6, 20, 18, '2025-05-30 14:01:00'),
(8, 22, 10, '2025-03-02 08:46:00'),
(9, 22, 6, '2025-02-10 11:21:00'),
(10, 1, 15, '2024-11-05 14:31:00'),
(11, 2, 15, '2024-11-10 09:01:00'),
(12, 20, 2, '2024-10-20 11:01:00'),
(13, 22, 50, '2025-07-11 08:01:00'),
(15, 5, 20, '2025-07-08 10:01:00'),
(16, 21, 3, '2025-07-05 13:46:00'),
(17, 6, 5, '2025-07-02 18:01:00');

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
  UPDATE Usuario u LEFT JOIN Aluno a ON u.id = a.usuario_id SET u.ativo = FALSE, a.cargo = 'Nenhum' WHERE u.tipo_usuario = 'aluno' AND u.data_fim_validade IS NOT NULL AND u.data_fim_validade < CURDATE();
  UPDATE Usuario u JOIN Servidor s ON u.id = s.usuario_id SET u.ativo = FALSE WHERE u.tipo_usuario = 'servidor' AND u.data_fim_validade IS NOT NULL AND u.data_fim_validade < CURDATE() AND s.is_admin = FALSE;
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
