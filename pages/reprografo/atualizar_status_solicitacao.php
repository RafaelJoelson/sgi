<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é reprografo logado (ajuste conforme sua lógica de sessão)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['Aceita','Rejeitada'])) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Dados inválidos.']);
    exit;
}

$stmt = $conn->prepare("UPDATE SolicitacaoImpressao SET status = :status, cpf_reprografo = :cpf WHERE id = :id");
$stmt->execute([
    ':status' => $status,
    ':cpf' => $_SESSION['usuario']['cpf'],
    ':id' => $id
]);

if ($stmt->rowCount()) {
    echo json_encode(['sucesso'=>true,'mensagem'=>'Status atualizado com sucesso!']);
} else {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Falha ao atualizar status.']);
}
