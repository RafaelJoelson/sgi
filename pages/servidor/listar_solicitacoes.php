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
    $stmt = $conn->prepare('SELECT id, arquivo_path as arquivo, qtd_copias, qtd_paginas, colorida as tipo_impressao, status, DATE_FORMAT(data_criacao, "%d/%m/%Y %H:%i") as data FROM SolicitacaoImpressao WHERE cpf_solicitante = ? AND tipo_solicitante = "Servidor" ORDER BY data_criacao DESC LIMIT 10');
    $stmt->execute([$cpf_servidor]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([]);
}
