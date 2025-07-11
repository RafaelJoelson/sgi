<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Permissão: apenas servidor CAD pode acessar
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    echo json_encode(['mensagem' => 'Acesso negado.']);
    exit;
}

$stmt = $conn->prepare("SELECT siape, nome, sobrenome, email, setor_admin FROM Servidor WHERE setor_admin = 'CAD' ORDER BY nome ASC");
$stmt->execute();
$servidores = $stmt->fetchAll();
echo json_encode($servidores);
