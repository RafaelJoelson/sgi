<?php
require_once '../../includes/config.php';
session_start();
header('Content-Type: text/plain');
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo 'Acesso negado';
    exit;
}
$siap = $_SESSION['usuario']['id'];
try {
    $stmt = $conn->prepare('SELECT * FROM CotaServidor WHERE siap = ?');
    $stmt->execute([$siap]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        print_r($row);
    } else {
        echo 'Nenhuma cota encontrada para siap=' . $siap;
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
