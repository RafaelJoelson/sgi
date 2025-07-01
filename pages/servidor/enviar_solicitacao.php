<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é servidor logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Acesso negado.']);
    exit;
}

// Validação básica
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['arquivo']) || empty($_POST['qtd_copias'])) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Dados incompletos.']);
    exit;
}

$arquivo = $_FILES['arquivo'];
$qtd_copias = intval($_POST['qtd_copias']);
$colorida = isset($_POST['colorida']) ? 1 : 0;
$cpf = $_SESSION['usuario']['cpf'];
$tipo_solicitante = 'Servidor';

// Valida arquivo
$permitidos = ['pdf','doc','docx','jpg','png'];
$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $permitidos) || $arquivo['error'] !== 0) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Arquivo inválido.']);
    exit;
}
if ($arquivo['size'] > 5*1024*1024) { // 5MB
    echo json_encode(['sucesso'=>false,'mensagem'=>'Arquivo muito grande.']);
    exit;
}

// Salva arquivo
$nome_arquivo = uniqid('imp_').'.'.$ext;
$destino = '../../uploads/'.$nome_arquivo;
if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Falha ao salvar arquivo.']);
    exit;
}

// Insere solicitação
$stmt = $conn->prepare("INSERT INTO SolicitacaoImpressao (cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, colorida, status) VALUES (:cpf, :tipo, :arquivo, :qtd, :colorida, 'Nova')");
$stmt->execute([
    ':cpf' => $cpf,
    ':tipo' => $tipo_solicitante,
    ':arquivo' => $nome_arquivo,
    ':qtd' => $qtd_copias,
    ':colorida' => $colorida
]);

if ($stmt->rowCount()) {
    echo json_encode(['sucesso'=>true,'mensagem'=>'Solicitação enviada com sucesso!']);
} else {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Erro ao registrar solicitação.']);
}
