<?php
/**
 * Retorna os dados de cota da turma do aluno logado.
 */
require_once '../../../includes/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$cota_id = $_SESSION['usuario']['cota_id'] ?? null;

if (!$cota_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Aluno não associado a nenhuma turma/cota.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT cota_total, cota_usada FROM CotaAluno WHERE id = :cota_id");
    $stmt->execute([':cota_id' => $cota_id]);
    $cota = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['sucesso' => true, 'cota' => $cota]);

} catch (PDOException $e) {
    error_log("Erro ao buscar cota do aluno: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar o banco de dados.']);
}