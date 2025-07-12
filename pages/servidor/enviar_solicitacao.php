<?php
// Processa envio de solicitação de impressão do servidor
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');

// --- 1. Verificação de Permissão ---
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$siape = $_SESSION['usuario']['id'];
$cpf_servidor = $_SESSION['usuario']['cpf'];

// --- 2. Validação de Inputs ---
$is_balcao = isset($_POST['solicitar_balcao']) && $_POST['solicitar_balcao'] === 'on';
$qtd_copias = filter_input(INPUT_POST, 'qtd_copias', FILTER_VALIDATE_INT);
$qtd_paginas = filter_input(INPUT_POST, 'qtd_paginas', FILTER_VALIDATE_INT);
$tipo_impressao = $_POST['tipo_impressao'] ?? 'pb';

// Validação mais robusta
if (!$qtd_copias || !$qtd_paginas || $qtd_copias < 1 || $qtd_paginas < 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Quantidades de cópias e páginas devem ser números válidos e maiores que zero.']);
    exit;
}

// Se NÃO for solicitação de balcão, o arquivo é obrigatório.
if (!$is_balcao && (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O envio do arquivo é obrigatório e falhou. Verifique o arquivo e tente novamente.']);
    exit;
}

try {
    // --- 3. Iniciar Transação ---
    $conn->beginTransaction();

    // Verifica cotas do servidor
    $stmt_cota = $conn->prepare('SELECT cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada FROM CotaServidor WHERE siape = ?');
    $stmt_cota->execute([$siape]);
    $cota = $stmt_cota->fetch(PDO::FETCH_ASSOC);

    if (!$cota) {
        throw new Exception('Cota do servidor não encontrada.');
    }

    $total_paginas_solicitadas = $qtd_copias * $qtd_paginas;
    
    if ($tipo_impressao === 'colorida') {
        if (($cota['cota_color_total'] - $cota['cota_color_usada']) < $total_paginas_solicitadas) {
            throw new Exception('Cota colorida insuficiente para esta solicitação.');
        }
    } else { // Preto e Branco
        if (($cota['cota_pb_total'] - $cota['cota_pb_usada']) < $total_paginas_solicitadas) {
            throw new Exception('Cota de impressão P&B insuficiente para esta solicitação.');
        }
    }

    // --- 4. Lógica Condicional de Upload ---
    $nome_arquivo_final = null; // Valor padrão para solicitação de balcão

    if (!$is_balcao) {
        $dir_uploads = '../../uploads/';
        if (!is_dir($dir_uploads)) {
            mkdir($dir_uploads, 0775, true);
        }
        
        // Gera um nome de arquivo único e seguro
        $extensao = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
        $nome_base = pathinfo($_FILES['arquivo']['name'], PATHINFO_FILENAME);
        $nome_seguro = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nome_base);
        $nome_arquivo_final = 'serv_' . $siape . '_' . uniqid() . '.' . $extensao;
        $caminho_destino = $dir_uploads . $nome_arquivo_final;

        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_destino)) {
            throw new Exception('Falha crítica ao salvar o arquivo no servidor.');
        }
    }

    // --- 5. Inserir Solicitação no Banco ---
    $stmt_insert = $conn->prepare(
        'INSERT INTO SolicitacaoImpressao (cpf_solicitante, tipo_solicitante, arquivo_path, qtd_copias, qtd_paginas, colorida, status, data_criacao) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt_insert->execute([
        $cpf_servidor, 
        'Servidor', 
        $nome_arquivo_final, // Será NULL se for de balcão
        $qtd_copias, 
        $qtd_paginas, 
        ($tipo_impressao === 'colorida' ? 1 : 0), 
        'Nova'
    ]);

    // --- 6. Confirmar Transação ---
    $conn->commit();

    echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada com sucesso!']);

} catch (Exception $e) {
    // --- 7. Reverter Transação em Caso de Erro ---
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}