<?php
require_once '../../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Validação do Token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensagem_erro'] = 'Erro de validação de segurança (CSRF). Tente novamente.';
    header('Location: ' . BASE_URL . '/pages/admin_cad/dashboard_cad.php');
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
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;
// CORREÇÃO: A validação agora verifica 'turma_id' em vez de 'cota_id'
if (empty($matricula) || empty($nome) || empty($email) || strlen($cpf) !== 11 || empty($senha) || empty($turma_id)) {
    $_SESSION['mensagem_erro'] = 'Todos os campos obrigatórios devem ser preenchidos corretamente.';
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php');
    exit;
}

try {
    $conn->beginTransaction();

    // 3. VERIFICAÇÃO DE DUPLICIDADE
    // Verifica se CPF ou E-mail já existem na tabela Usuario
    $stmt_check = $conn->prepare("SELECT id FROM Usuario WHERE cpf = :cpf OR email = :email");
    $stmt_check->execute([':cpf' => $cpf, ':email' => $email]);
    if ($stmt_check->fetch()) {
        throw new Exception('O CPF ou E-mail informado já está cadastrado no sistema.');
    }

    // Verifica se a matrícula já existe na tabela Aluno
    $stmt_check_matricula = $conn->prepare("SELECT usuario_id FROM Aluno WHERE matricula = :matricula");
    $stmt_check_matricula->execute([':matricula' => $matricula]);
    if ($stmt_check_matricula->fetch()) {
        throw new Exception('A matrícula informada já está em uso.');
    }

    // 4. INSERÇÃO NA TABELA USUARIO
    $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
    $stmt_insert_user = $conn->prepare(
        "INSERT INTO Usuario (cpf, nome, sobrenome, email, senha, tipo_usuario, data_fim_validade, ativo)
         VALUES (:cpf, :nome, :sobrenome, :email, :senha, 'aluno', :validade, 1)"
    );
    $stmt_insert_user->execute([
        ':cpf' => $cpf,
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':senha' => $hash_senha,
        ':validade' => $data_fim_validade
    ]);
    $usuario_id = $conn->lastInsertId();

    // 5. LÓGICA DE COTA E CARGO
    $stmt_cota = $conn->prepare("SELECT id FROM CotaAluno WHERE turma_id = :turma_id");
    $stmt_cota->execute([':turma_id' => $turma_id]);
    $cota_id = $stmt_cota->fetchColumn();

    if (!$cota_id) {
        $stmt_create_cota = $conn->prepare("INSERT INTO CotaAluno (turma_id, cota_total, cota_usada) VALUES (:turma_id, 0, 0)");
        $stmt_create_cota->execute([':turma_id' => $turma_id]);
        $cota_id = $conn->lastInsertId();
    }

    if ($cargo === 'Líder' || $cargo === 'Vice-líder') {
        $stmt_check_cargo = $conn->prepare("SELECT COUNT(*) FROM Aluno WHERE cota_id = :cota_id AND cargo = :cargo");
        $stmt_check_cargo->execute([':cota_id' => $cota_id, ':cargo' => $cargo]);
        if ($stmt_check_cargo->fetchColumn() > 0) {
            throw new Exception("A turma selecionada já possui um {$cargo}.");
        }
    }

    // 6. INSERÇÃO NA TABELA ALUNO
    $stmt_insert_aluno = $conn->prepare("INSERT INTO Aluno (usuario_id, matricula, cargo, cota_id) VALUES (:uid, :mat, :cargo, :cid)");
    $stmt_insert_aluno->execute([':uid' => $usuario_id, ':mat' => $matricula, ':cargo' => $cargo, ':cid' => $cota_id]);
    
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
