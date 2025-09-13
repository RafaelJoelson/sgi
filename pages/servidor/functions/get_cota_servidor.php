<?php
/**
 * Retorna os dados de cota do servidor logado.
 */
require_once '../../../includes/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];

try {
    $stmt = $conn->prepare(
        "SELECT cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada 
         FROM CotaServidor WHERE usuario_id = :id"
    );
    $stmt->execute([':id' => $usuario_id]);
    $cota = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['sucesso' => true, 'cota' => $cota]);
} catch (PDOException $e) {
    error_log("Erro ao buscar cota do servidor: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar o banco de dados.']);
}