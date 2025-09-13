<?php
require_once '../../../includes/config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso' => false, 'novas' => 0]);
    exit;
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM SolicitacaoImpressao WHERE status = 'Nova'");
    $total_novas = $stmt->fetchColumn();

    echo json_encode(['sucesso' => true, 'novas' => (int)$total_novas]);

} catch (PDOException $e) {
    error_log("Erro ao verificar novas solicitações: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'novas' => 0]);
}
?>