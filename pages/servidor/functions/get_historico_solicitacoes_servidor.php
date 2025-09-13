<?php
/**
 * Retorna todas as solicitações do servidor logado (histórico completo).
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
        "SELECT id, arquivo_path, qtd_copias, qtd_paginas, colorida, status, data_criacao 
         FROM SolicitacaoImpressao 
         WHERE usuario_id = :id 
         ORDER BY data_criacao DESC"
    );
    $stmt->execute([':id' => $usuario_id]);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['sucesso' => true, 'solicitacoes' => $solicitacoes]);
} catch (PDOException $e) {
    error_log("Erro ao buscar histórico do servidor: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar o banco de dados.']);
}
?>
