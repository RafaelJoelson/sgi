<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um administrador (CAD ou COEN) pode executar esta ação.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ../../index.php');
    exit;
}

// 2. VALIDAÇÃO DO INPUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['siape']) || empty($_POST['nova_senha'])) {
    $_SESSION['mensagem_erro'] = 'Dados inválidos para redefinir a senha.';
    // Redirecionamento dinâmico em caso de erro
    $setor_logado = $_SESSION['usuario']['setor_admin'] ?? 'coen'; // Padrão para coen se não definido
    $redirect_url = ($setor_logado === 'CAD') ? '../admin_cad/dashboard_cad.php' : 'dashboard_coen.php';
    header('Location: ' . $redirect_url);
    exit;
}

$siape = trim($_POST['siape']);
$nova_senha = $_POST['nova_senha'];

// Validação adicional (ex: comprimento mínimo da senha)
if (strlen($nova_senha) < 6) {
    $_SESSION['mensagem_erro'] = 'A nova senha deve ter no mínimo 6 caracteres.';
    $setor_logado = $_SESSION['usuario']['setor_admin'] ?? 'coen';
    $redirect_url = ($setor_logado === 'CAD') ? '../admin_cad/dashboard_cad.php' : 'dashboard_coen.php';
    header('Location: ' . $redirect_url);
    exit;
}

try {
    // 3. ATUALIZAÇÃO NO BANCO DE DADOS
    $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE Servidor SET senha = :senha WHERE siape = :siape");
    $stmt->execute([
        ':senha' => $hash_senha,
        ':siape' => $siape
    ]);

    // 4. DEFINE A MENSAGEM DE SUCESSO NA SESSÃO
    $_SESSION['mensagem_sucesso'] = 'Senha do servidor (SIAPE: ' . htmlspecialchars($siape) . ') foi redefinida com sucesso!';

} catch (PDOException $e) {
    error_log("Erro ao redefinir senha do servidor: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = 'Ocorreu um erro de banco de dados ao tentar redefinir a senha.';
}

// 5. REDIRECIONAMENTO DINÂMICO DE VOLTA PARA O PAINEL DE ORIGEM
$setor_logado = $_SESSION['usuario']['setor_admin'] ?? 'coen';
$redirect_url = ($setor_logado === 'CAD') ? '../admin_cad/dashboard_cad.php' : 'dashboard_coen.php';
header('Location: ' . $redirect_url);
exit;
