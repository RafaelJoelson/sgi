<?php
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');

// Verifica se o usuário é a reprografia logada
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

// Pega o ID da sessão, que é a fonte mais segura.
$reprografia_id = $_SESSION['usuario']['id'] ?? 0;

if (empty($reprografia_id)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do usuário não encontrado na sessão.']);
    exit;
}

try {
    // CORREÇÃO: Garante que a coluna 'id' seja selecionada na consulta.
    $stmt = $conn->prepare("SELECT id, login, nome, sobrenome, email FROM reprografia WHERE id = :id");
    $stmt->execute([':id' => $reprografia_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dados) {
        echo json_encode(['sucesso' => true, 'dados' => $dados]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário reprografia não encontrado no banco de dados.']);
    }
} catch (PDOException $e) {
    error_log("Erro ao obter dados do reprografia: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar o banco de dados.']);
}
