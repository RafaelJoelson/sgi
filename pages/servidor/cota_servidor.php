<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Retorna as cotas PB e colorida do servidor logado
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}
$siap = $_SESSION['usuario']['id'];
// DEBUG: Exibir siap
file_put_contents(__DIR__.'/debug_cota_servidor.log', date('Y-m-d H:i:s')." siap: $siap\n", FILE_APPEND);
try {
    $stmt = $conn->prepare('SELECT cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada FROM CotaServidor WHERE siap = ?');
    $stmt->execute([$siap]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // DEBUG: Exibir resultado da query
    file_put_contents(__DIR__.'/debug_cota_servidor.log', date('Y-m-d H:i:s')." resultado: ".json_encode($row)."\n", FILE_APPEND);
    if ($row) {
        $pb_disp = (int)$row['cota_pb_total'] - (int)$row['cota_pb_usada'];
        $color_disp = (int)$row['cota_color_total'] - (int)$row['cota_color_usada'];
        echo json_encode([
            'sucesso' => true,
            'cota_pb_disponivel' => $pb_disp,
            'cota_color_disponivel' => $color_disp
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Cota não encontrada.']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar cotas.']);
}
