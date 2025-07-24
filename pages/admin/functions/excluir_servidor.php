<?php
require_once '../../../includes/config.php';
header('Content-Type: application/json');
session_start();
// Apenas um administrador (CAD ou COEN) pode acessar esta página.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ' . BASE_URL . '/index.php'); // Redirecionamento seguro
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siape'])) {
    $siape = trim($_POST['siape']);
    $stmt = $conn->prepare('DELETE FROM Servidor WHERE siape = :siape');
    if ($stmt->execute([':siape' => $siape])) {
        echo json_encode(['mensagem' => 'Servidor excluído com sucesso.']);
    } else {
        echo json_encode(['mensagem' => 'Erro ao excluir servidor.']);
    }
    exit;
}
echo json_encode(['mensagem' => 'Requisição inválida.']);
