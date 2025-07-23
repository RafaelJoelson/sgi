<?php
session_start();
require_once '../../../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']['cpf'])) {
    echo json_encode([]);
    exit;
}

$cpf_usuario = $_SESSION['usuario']['cpf'];
$tipo_usuario = $_SESSION['usuario']['tipo'];

try {
    // MUDANÇA: Usando DATE_FORMAT para formatar a data diretamente no SQL
    $sql = "SELECT 
                id, 
                arquivo_path as arquivo, 
                qtd_copias, 
                qtd_paginas,
                colorida,
                status, 
                DATE_FORMAT(data_criacao, '%d/%m/%Y %H:%i') as data_formatada
            FROM SolicitacaoImpressao 
            WHERE cpf_solicitante = :cpf 
              AND tipo_solicitante = :tipo
            ORDER BY data_criacao DESC 
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':cpf' => $cpf_usuario, ':tipo' => ucfirst($tipo_usuario)]);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($solicitacoes);

} catch (PDOException $e) {
    error_log("Erro ao listar solicitações: " . $e->getMessage());
    echo json_encode([]); // Retorna um array vazio em caso de erro
}
