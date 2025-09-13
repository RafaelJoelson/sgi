<?php
/**
 * Retorna as 5 solicitações mais recentes do servidor logado.
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
         ORDER BY data_criacao DESC 
         LIMIT 5"
    );
    $stmt->execute([':id' => $usuario_id]);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['sucesso' => true, 'solicitacoes' => $solicitacoes]);
} catch (PDOException $e) {
    error_log("Erro ao buscar solicitações do servidor: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar o banco de dados.']);
}