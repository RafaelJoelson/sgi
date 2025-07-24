<?php
require_once '../../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um servidor CAD pode executar esta ação.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    // Se não tiver permissão, pode redirecionar ou enviar um erro.
    // Para formulários AJAX, é melhor enviar um erro, mas como este é um formulário padrão, redirecionamos.
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// 2. VALIDAÇÃO DO INPUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['matricula']) || empty($_POST['nova_senha'])) {
    // Define uma mensagem de erro e redireciona de volta
    $_SESSION['mensagem_erro'] = 'Dados inválidos para redefinir a senha.';
    header('Location: ' . BASE_URL . '/pages/admin_cad/dashboard_cad.php');
    exit;
}

$matricula = trim($_POST['matricula']);
$nova_senha = $_POST['nova_senha'];

// Validação adicional (ex: comprimento mínimo da senha)
if (strlen($nova_senha) < 6) {
    $_SESSION['mensagem_erro'] = 'A nova senha deve ter no mínimo 6 caracteres.';
    header('Location: ' . BASE_URL . '/pages/admin_cad/dashboard_cad.php');
    exit;
}

try {
    // 3. ATUALIZAÇÃO NO BANCO DE DADOS
    // Gera o hash seguro para a nova senha
    $hash_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

    // Prepara e executa a atualização
    $stmt = $conn->prepare("UPDATE Aluno SET senha = :senha WHERE matricula = :matricula");
    $stmt->execute([
        ':senha' => $hash_senha,
        ':matricula' => $matricula
    ]);

    // 4. DEFINE A MENSAGEM DE SUCESSO NA SESSÃO
    // Esta é a mensagem que o "toast" irá exibir.
    $_SESSION['mensagem_sucesso'] = 'Senha do aluno (Matrícula: ' . htmlspecialchars($matricula) . ') foi redefinida com sucesso!';

} catch (PDOException $e) {
    // Em caso de erro, define uma mensagem de erro na sessão
    error_log("Erro ao redefinir senha do aluno: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = 'Ocorreu um erro de banco de dados ao tentar redefinir a senha.';
}

// 5. REDIRECIONA DE VOLTA PARA O PAINEL
// O painel irá ler a mensagem da sessão e exibir o "toast".
header('Location: ' . BASE_URL . '/pages/admin_cad/dashboard_cad.php');
exit;
