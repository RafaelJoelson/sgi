<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}
$siap = $_SESSION['usuario']['siap'];
// Busca cotas do servidor
$stmt = $conn->prepare('SELECT cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada FROM CotaServidor WHERE siap = ?');
$stmt->execute([$siap]);
$cota = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cota) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Cota não encontrada.']);
    exit;
}
$cota_pb_disponivel = max(0, $cota['cota_pb_total'] - $cota['cota_pb_usada']);
$cota_color_disponivel = max(0, $cota['cota_color_total'] - $cota['cota_color_usada']);
echo json_encode([
    'sucesso'=>true,
    'cota_pb_disponivel'=>$cota_pb_disponivel,
    'cota_color_disponivel'=>$cota_color_disponivel
]);
