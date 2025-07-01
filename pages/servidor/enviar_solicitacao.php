<?php
// Processa envio de solicitação de impressão do servidor
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}
$siap = $_SESSION['usuario']['id']; // id do servidor = siap
$cpf_servidor = $_SESSION['usuario']['cpf']; // cpf do servidor

// Validação básica
if (empty($_FILES['arquivo']['name']) || empty($_POST['qtd_copias']) || empty($_POST['qtd_paginas']) || empty($_POST['tipo_impressao'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos.']);
    exit;
}
$qtd_copias = (int)$_POST['qtd_copias'];
$qtd_paginas = (int)$_POST['qtd_paginas'];
$tipo_impressao = $_POST['tipo_impressao'] === 'colorida' ? 'colorida' : 'pb';

if ($qtd_copias < 1 || $qtd_paginas < 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Quantidade inválida.']);
    exit;
}

// Verifica cotas
try {
    $stmt = $conn->prepare('SELECT cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada FROM CotaServidor WHERE siap = ?');
    $stmt->execute([$siap]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('Cota do servidor não encontrada.');
    $total_paginas = $qtd_copias * $qtd_paginas;
    $pb_disp = $row['cota_pb_total'] - $row['cota_pb_usada'];
    $color_disp = $row['cota_color_total'] - $row['cota_color_usada'];
    if ($tipo_impressao === 'colorida' && $color_disp < $total_paginas) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Cota colorida insuficiente.']);
        exit;
    }
    if ($tipo_impressao === 'pb' && $pb_disp < $total_paginas) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Cota PB insuficiente.']);
        exit;
    }
    // Upload do arquivo
    $dir = '../../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $nome_arquivo = uniqid('imp_') . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/','_', $_FILES['arquivo']['name']);
    $destino = $dir . $nome_arquivo;
    if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar arquivo.']);
        exit;
    }
    // Insere solicitação
    $stmt = $conn->prepare('INSERT INTO SolicitacaoImpressao (cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, qtd_paginas, colorida, status, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $cpf_servidor, 'Servidor', $nome_arquivo, $qtd_copias, $qtd_paginas, ($tipo_impressao === 'colorida' ? 1 : 0), 'Nova'
    ]);
    echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
