<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um administrador (CAD ou COEN) pode acessar esta página.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ' . BASE_URL . '/index.php'); // Redirecionamento seguro
    exit;
}

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php'); // Redirecionamento seguro
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS
$siape = trim($_POST['siape'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$cpf = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$setor_admin = in_array($_POST['setor_admin'], ['CAD', 'COEN', 'NENHUM']) ? $_POST['setor_admin'] : 'NENHUM';
$is_admin = isset($_POST['is_admin']) ? 1 : 0;
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;
$senha = $_POST['senha'] ?? '';

// Validações básicas
if (empty($siape) || empty($nome) || empty($email) || strlen($cpf) !== 11 || empty($senha)) {
    $_SESSION['mensagem_erro'] = 'Todos os campos obrigatórios devem ser preenchidos corretamente.';
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php'); // Redirecionamento seguro
    exit;
}

try {
    // 3. INICIA A TRANSAÇÃO NO BANCO DE DADOS
    $conn->beginTransaction();

    // 4. VERIFICAÇÃO COMPLETA DE DUPLICIDADE (CPF e SIAPE)
    $stmt_check_siape = $conn->prepare("SELECT siape FROM Servidor WHERE siape = :siape");
    $stmt_check_siape->execute([':siape' => $siape]);
    if ($stmt_check_siape->fetch()) {
        throw new Exception('O SIAPE informado já está cadastrado.');
    }

    $stmt_check_cpf = $conn->prepare("SELECT cpf FROM Aluno WHERE cpf = :cpf UNION ALL SELECT cpf FROM Servidor WHERE cpf = :cpf");
    $stmt_check_cpf->execute([':cpf' => $cpf]);
    if ($stmt_check_cpf->fetch()) {
        throw new Exception('O CPF informado já está cadastrado no sistema para outro usuário.');
    }

    $hash_senha = password_hash($senha, PASSWORD_DEFAULT);

    // 5. INSERÇÃO NAS TABELAS
    $stmt_insert_servidor = $conn->prepare(
        'INSERT INTO Servidor (siape, nome, sobrenome, email, cpf, senha, is_admin, setor_admin, ativo, data_fim_validade) 
         VALUES (:siape, :nome, :sobrenome, :email, :cpf, :senha, :is_admin, :setor_admin, 1, :data_fim_validade)'
    );
    $stmt_insert_servidor->execute([
        ':siape' => $siape, ':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email,
        ':cpf' => $cpf, ':senha' => $hash_senha, ':is_admin' => $is_admin,
        ':setor_admin' => $setor_admin, ':data_fim_validade' => $data_fim_validade
    ]);

    $stmt_insert_cota = $conn->prepare(
        'INSERT INTO CotaServidor (siape, cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada) 
         VALUES (:siape, 1000, 0, 100, 0)'
    );
    $stmt_insert_cota->execute([':siape' => $siape]);

    // 6. CONFIRMA A TRANSAÇÃO
    $conn->commit();
    $_SESSION['mensagem_sucesso'] = 'Servidor cadastrado com sucesso!';
    
    // 7. REDIRECIONAMENTO DINÂMICO
    $setor_logado = $_SESSION['usuario']['setor_admin'];
    
    $path = ($setor_logado === 'CAD') ? '/pages/admin_cad/dashboard_cad.php' : '/pages/admin_coen/dashboard_coen.php';
    $redirect_url = BASE_URL . $path;
    
    header('Location: ' . $redirect_url);
    exit;

} catch (Exception $e) {
    // 8. REVERTE A TRANSAÇÃO EM CASO DE ERRO
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar servidor: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php'); // Redirecionamento seguro
    exit;
}
