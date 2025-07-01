<?php
// Lista as solicitações recentes do servidor logado
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode([]);
    exit;
}
$id_servidor = $_SESSION['usuario']['id'];
try {
    $stmt = $pdo->prepare('SELECT id, arquivo, qtd_copias, qtd_paginas, tipo_impressao, status, DATE_FORMAT(data, "%d/%m/%Y %H:%i") as data FROM SolicitacaoImpressao WHERE id_servidor = ? ORDER BY data DESC LIMIT 10');
    $stmt->execute([$id_servidor]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([]);
}
