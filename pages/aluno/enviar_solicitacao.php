<?php
session_start();
require_once '../../includes/config.php';
header('Content-Type: application/json');

// Verifica se é aluno logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
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
$colorida = 0; // Aluno não pode solicitar impressão colorida
$cpf = $_SESSION['usuario']['cpf'];
$tipo_solicitante = 'Aluno';

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

$qtd_paginas = isset($_POST['qtd_paginas']) ? intval($_POST['qtd_paginas']) : 0;
if ($qtd_paginas < 1) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Informe o número de páginas.']);
    exit;
}
$total_impressao = $qtd_paginas * $qtd_copias;
// Verifica cota do aluno
$stmt = $conn->prepare('SELECT a.cota_id, c.cota_total, c.cota_usada FROM Aluno a JOIN CotaAluno c ON a.cota_id = c.id WHERE a.cpf = ?');
$stmt->execute([$cpf]);
$cota = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cota) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Cota não encontrada.']);
    exit;
}
$disponivel = $cota['cota_total'] - $cota['cota_usada'];
if ($total_impressao > $disponivel) {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Cota insuficiente para esta solicitação.']);
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
$stmt = $conn->prepare("INSERT INTO SolicitacaoImpressao (cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, qtd_paginas, colorida, status) VALUES (:cpf, :tipo, :arquivo, :qtd, :qtd_paginas, 0, 'Nova')");
$stmt->execute([
    ':cpf' => $cpf,
    ':tipo' => $tipo_solicitante,
    ':arquivo' => $nome_arquivo,
    ':qtd' => $qtd_copias,
    ':qtd_paginas' => $qtd_paginas
]);

if ($stmt->rowCount()) {
    echo json_encode(['sucesso'=>true,'mensagem'=>'Solicitação enviada com sucesso!']);
} else {
    echo json_encode(['sucesso'=>false,'mensagem'=>'Erro ao registrar solicitação.']);
}
