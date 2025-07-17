<?php
// Lista as solicitações recentes do servidor logado
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode([]);
    exit;
}

$cpf_servidor = $_SESSION['usuario']['cpf'];

try {
    // CORREÇÃO:
    // 1. Usa DATE_FORMAT para formatar a data e evitar "Invalid Date".
    // 2. Mantém o nome da coluna 'colorida' para consistência com o JS.
    $stmt = $conn->prepare(
        'SELECT 
            id, 
            arquivo_path as arquivo, 
            qtd_copias, 
            qtd_paginas, 
            colorida, 
            status, 
            DATE_FORMAT(data_criacao, "%d/%m/%Y %H:%i") as data_formatada 
         FROM SolicitacaoImpressao 
         WHERE cpf_solicitante = ? AND tipo_solicitante = "Servidor" 
         ORDER BY data_criacao DESC 
         LIMIT 10'
    );
    $stmt->execute([$cpf_servidor]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Erro ao listar solicitações do servidor: " . $e->getMessage());
    echo json_encode([]);
}
