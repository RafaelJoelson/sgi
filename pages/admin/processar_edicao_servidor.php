<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um administrador (is_admin = true) pode acessar esta página.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ../../index.php');
    exit;
}

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/form_servidor.php');
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS
$siape = trim($_POST['siape'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$setor_admin = in_array($_POST['setor_admin'], ['CAD', 'COEN', 'NENHUM']) ? $_POST['setor_admin'] : 'NENHUM';
$is_admin = isset($_POST['is_admin']) ? 1 : 0;
$ativo = isset($_POST['ativo']) ? 1 : 0;
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;

// Validações básicas
if (empty($siape) || empty($nome) || empty($email)) {
    $_SESSION['mensagem_erro'] = 'Os campos SIAPE, Nome e E-mail são obrigatórios.';
    header('Location: ../admin/form_servidor.php?siape=' . urlencode($siape));
    exit;
}

if (!$email) {
    $_SESSION['mensagem_erro'] = 'O formato do e-mail é inválido.';
    header('Location: ../admin/form_servidor.php?siape=' . urlencode($siape));
    exit;
}

try {
    // 3. ATUALIZAÇÃO NO BANCO DE DADOS
    $stmt = $conn->prepare(
        'UPDATE Servidor SET 
            nome = :nome, 
            sobrenome = :sobrenome, 
            email = :email, 
            setor_admin = :setor_admin, 
            is_admin = :is_admin, 
            data_fim_validade = :data_fim_validade, 
            ativo = :ativo 
         WHERE siape = :siape'
    );
    
    $ok = $stmt->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':setor_admin' => $setor_admin,
        ':is_admin' => $is_admin,
        ':data_fim_validade' => $data_fim_validade,
        ':ativo' => $ativo,
        ':siape' => $siape
    ]);

    if ($ok) {
        $_SESSION['mensagem_sucesso'] = 'Servidor atualizado com sucesso!';

        // 4. VERIFICAÇÃO DE AUTO-ATUALIZAÇÃO
        // Se o admin atualizou a si mesmo e se desativou ou removeu o próprio privilégio de admin
        if ($siape === $_SESSION['usuario']['id'] && ($ativo == 0 || $is_admin == 0)) {
            // Destrói a sessão e força o logout
            session_destroy();
            header('Location: ../../index.php?logout=autoedicao');
            exit;
        }

    } else {
        $_SESSION['mensagem_erro'] = 'Nenhuma alteração foi feita ou ocorreu um erro.';
    }

} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = 'Erro de banco de dados: ' . $e->getMessage();
}

// 5. REDIRECIONAMENTO DINÂMICO
$setor_logado = $_SESSION['usuario']['setor_admin'];
$redirect_url = '../../index.php'; // URL padrão de fallback

if ($setor_logado === 'CAD') {
    $redirect_url = '../admin_cad/dashboard_cad.php';
} elseif ($setor_logado === 'COEN') {
    $redirect_url = '../admin_coen/dashboard_coen.php';
}

header('Location: ' . $redirect_url);
exit;
