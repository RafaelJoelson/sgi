<?php
// Página de debug temporariamente desativada
header('Content-Type: text/plain');
echo 'Página de debug desativada temporariamente.';
exit;
require_once '../../includes/config.php';
session_start();
header('Content-Type: text/plain');
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo 'Acesso negado';
    exit;
}
$siape = $_SESSION['usuario']['id'];
try {
    $stmt = $conn->prepare('SELECT * FROM CotaServidor WHERE siape = ?');
    $stmt->execute([$siape]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        print_r($row);
    } else {
        echo 'Nenhuma cota encontrada para siape=' . $siape;
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
