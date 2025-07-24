<?php
require_once '../../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php');
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS
$matricula = trim($_POST['matricula'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$cpf = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$senha = $_POST['senha'] ?? '';
$cargo = $_POST['cargo'] ?? 'Nenhum';
$turma_id = filter_input(INPUT_POST, 'turma_id', FILTER_VALIDATE_INT); // Recebe o ID da Turma

// CORREÇÃO: A validação agora verifica 'turma_id' em vez de 'cota_id'
if (empty($matricula) || empty($nome) || empty($email) || strlen($cpf) !== 11 || empty($senha) || empty($turma_id)) {
    $_SESSION['mensagem_erro'] = 'Todos os campos obrigatórios devem ser preenchidos corretamente.';
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php');
    exit;
}

try {
    $conn->beginTransaction();

    // CORREÇÃO: Lógica para encontrar ou criar a cota baseada na turma selecionada
    $stmt_cota = $conn->prepare("SELECT id FROM CotaAluno WHERE turma_id = :turma_id");
    $stmt_cota->execute([':turma_id' => $turma_id]);
    $cota_id = $stmt_cota->fetchColumn();

    if (!$cota_id) {
        // Se a turma ainda não tem uma cota, cria uma nova
        $stmt_create_cota = $conn->prepare("INSERT INTO CotaAluno (turma_id, cota_total, cota_usada) VALUES (:turma_id, 0, 0)");
        $stmt_create_cota->execute([':turma_id' => $turma_id]);
        $cota_id = $conn->lastInsertId();
    }

    // Validação de cargo (Líder/Vice)
    if ($cargo === 'Líder' || $cargo === 'Vice-líder') {
        $stmt_check_cargo = $conn->prepare("SELECT COUNT(*) FROM Aluno WHERE cota_id = :cota_id AND cargo = :cargo");
        $stmt_check_cargo->execute([':cota_id' => $cota_id, ':cargo' => $cargo]);
        if ($stmt_check_cargo->fetchColumn() > 0) {
            throw new Exception("A turma selecionada já possui um {$cargo}.");
        }
    }

    // Verificação de duplicidade de CPF e Matrícula
    $stmt_check_cpf = $conn->prepare("SELECT cpf FROM Aluno WHERE cpf = :cpf UNION ALL SELECT cpf FROM Servidor WHERE cpf = :cpf");
    $stmt_check_cpf->execute([':cpf' => $cpf]);
    if ($stmt_check_cpf->fetch()) {
        throw new Exception('O CPF informado já está cadastrado no sistema.');
    }

    $stmt_check_matricula = $conn->prepare("SELECT matricula FROM Aluno WHERE matricula = :matricula");
    $stmt_check_matricula->execute([':matricula' => $matricula]);
    if ($stmt_check_matricula->fetch()) {
        throw new Exception('A matrícula informada já está em uso.');
    }

    // Hash da senha e data de validade
    $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
    $stmt_semestre = $conn->prepare("SELECT data_fim FROM SemestreLetivo WHERE CURDATE() BETWEEN data_inicio AND data_fim LIMIT 1");
    $stmt_semestre->execute();
    $data_fim_validade = $stmt_semestre->fetchColumn() ?: null;

    // Inserção do aluno
    $stmt_insert = $conn->prepare(
        "INSERT INTO Aluno (matricula, nome, sobrenome, email, cpf, senha, cargo, cota_id, data_fim_validade, ativo)
         VALUES (:matricula, :nome, :sobrenome, :email, :cpf, :senha, :cargo, :cota_id, :validade, 1)"
    );
    $stmt_insert->execute([
        ':matricula' => $matricula, ':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email,
        ':cpf' => $cpf, ':senha' => $hash_senha, ':cargo' => $cargo, ':cota_id' => $cota_id, ':validade' => $data_fim_validade
    ]);
    
    $conn->commit();
    $_SESSION['mensagem_sucesso'] = 'Aluno cadastrado com sucesso!';
    header('Location: ' . BASE_URL . '/pages/admin_cad/dashboard_cad.php');
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar aluno: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php');
    exit;
}
