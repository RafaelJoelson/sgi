<?php
require_once '../../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um administrador (COEN) pode acessar esta página.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ' . BASE_URL . '/pages/admin_coen/dashboard_coen.php');
    exit;
}

// 2. VALIDAÇÃO DO INPUT
$siape_para_excluir = $_GET['siape'] ?? null;
if (empty($siape_para_excluir)) {
    $_SESSION['mensagem_erro'] = 'SIAPE do servidor não fornecido.';
    header('Location: ' . BASE_URL . '/pages/admin_coen/dashboard_coen.php');
    exit;
}

try {
    // 3. EXECUÇÃO DA EXCLUSÃO
    // A exclusão na tabela 'Usuario' irá remover em cascata o registro da tabela 'Servidor'
    $stmt = $conn->prepare(
        "DELETE u FROM Usuario u JOIN Servidor s ON u.id = s.usuario_id WHERE s.siape = :siape"
    );
    $stmt->execute([':siape' => $siape_para_excluir]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['mensagem_sucesso'] = 'Servidor (SIAPE: ' . htmlspecialchars($siape_para_excluir) . ') foi excluído com sucesso.';
    } else {
        $_SESSION['mensagem_erro'] = 'Servidor não encontrado ou já foi excluído.';
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registra o erro e informa o usuário.
    error_log("Erro ao excluir servidor: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = 'Ocorreu um erro de banco de dados ao tentar excluir o servidor.';
}

// 4. REDIRECIONAMENTO PADRÃO
header('Location: ' . BASE_URL . '/pages/admin_coen/dashboard_coen.php');
exit;
