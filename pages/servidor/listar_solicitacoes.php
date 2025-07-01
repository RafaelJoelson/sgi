<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é servidor logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode([]);
    exit;
}

$cpf = $_SESSION['usuario']['cpf'];
$sql = "SELECT id, arquivo_path as arquivo, qtd_copias, colorida, status, data_criacao as data FROM SolicitacaoImpressao WHERE cpf_solicitante = :cpf AND tipo_solicitante = 'Servidor' ORDER BY data_criacao DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->execute([':cpf' => $cpf]);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($solicitacoes as &$s) {
    $s['data'] = date('d/m/Y H:i', strtotime($s['data']));
}
echo json_encode($solicitacoes);
