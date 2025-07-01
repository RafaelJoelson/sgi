<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}
$id = intval($_POST['id'] ?? 0);
$qtd_paginas = intval($_POST['qtd_paginas'] ?? 0);
if ($id < 1 || $qtd_paginas < 1 || $qtd_paginas > 500) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Dados inválidos.']);
    exit;
}
$stmt = $conn->prepare('UPDATE SolicitacaoImpressao SET qtd_paginas = ? WHERE id = ?');
$stmt->execute([$qtd_paginas, $id]);
if ($stmt->rowCount()) {
    echo json_encode(['sucesso'=>true,'mensagem'=>'Número de páginas atualizado.']);
} else {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Falha ao atualizar número de páginas.']);
}
