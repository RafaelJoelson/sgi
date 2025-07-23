<?php
session_start();
require_once '../../../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}
$cpf = $_SESSION['usuario']['cpf'];
// Busca cota_id do aluno
$stmt = $conn->prepare('SELECT cota_id FROM Aluno WHERE cpf = ?');
$stmt->execute([$cpf]);
$cota_id = $stmt->fetchColumn();
if (!$cota_id) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Cota não encontrada.']);
    exit;
}
// Busca cota disponível
$stmt = $conn->prepare('SELECT cota_total, cota_usada FROM CotaAluno WHERE id = ?');
$stmt->execute([$cota_id]);
$cota = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cota) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Cota não encontrada.']);
    exit;
}
$cota_disponivel = max(0, $cota['cota_total'] - $cota['cota_usada']);
echo json_encode(['sucesso'=>true,'cota_disponivel'=>$cota_disponivel]);
