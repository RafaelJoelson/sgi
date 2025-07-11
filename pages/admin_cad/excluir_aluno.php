<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um servidor CAD logado pode executar esta ação.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: dashboard_cad.php');
    exit;
}

// 2. VALIDAÇÃO DO INPUT
$matricula_para_excluir = $_GET['matricula'] ?? null;
if (empty($matricula_para_excluir)) {
    $_SESSION['mensagem_erro'] = 'Matrícula do aluno não fornecida.';
    header('Location: dashboard_cad.php');
    exit;
}

try {
    // 3. EXECUÇÃO DA EXCLUSÃO
    $stmt = $conn->prepare("DELETE FROM Aluno WHERE matricula = :matricula");
    $stmt->execute([':matricula' => $matricula_para_excluir]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['mensagem_sucesso'] = 'Aluno (Matrícula: ' . htmlspecialchars($matricula_para_excluir) . ') foi excluído com sucesso.';
    } else {
        $_SESSION['mensagem_erro'] = 'Aluno não encontrado ou já foi excluído.';
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registra o erro e informa o usuário.
    error_log("Erro ao excluir aluno: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = 'Ocorreu um erro de banco de dados ao tentar excluir o aluno.';
}

// 4. REDIRECIONAMENTO PADRÃO
header('Location: dashboard_cad.php');
exit;
