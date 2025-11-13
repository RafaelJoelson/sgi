<?php
require_once '../../../includes/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$qtd_copias = filter_input(INPUT_POST, 'qtd_copias', FILTER_VALIDATE_INT);
$qtd_paginas = filter_input(INPUT_POST, 'qtd_paginas', FILTER_VALIDATE_INT);
$solicitar_balcao = isset($_POST['solicitar_balcao']);
$colorida = isset($_POST['tipo_impressao']) && $_POST['tipo_impressao'] === 'colorida' ? 1 : 0;
$arquivo = $_FILES['arquivo'] ?? null;

if (!$qtd_copias || !$qtd_paginas || $qtd_copias <= 0 || $qtd_paginas <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Quantidade de cópias e páginas deve ser maior que zero.']);
    exit;
}

if (!$solicitar_balcao && (empty($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O envio do arquivo é obrigatório.']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Verifica a cota do servidor
    $stmt_cota = $conn->prepare("SELECT * FROM CotaServidor WHERE usuario_id = :id FOR UPDATE");
    $stmt_cota->execute([':id' => $usuario_id]);
    $cota = $stmt_cota->fetch();

    if (!$cota) throw new Exception('Cota de impressão não encontrada para este servidor.');

    $cotas_solicitadas = $qtd_copias * $qtd_paginas;
    
    if ($colorida) {
        $cota_restante = $cota->cota_color_total - $cota->cota_color_usada;
        $campo_update = 'cota_color_usada';
    } else {
        $cota_restante = $cota->cota_pb_total - $cota->cota_pb_usada;
        $campo_update = 'cota_pb_usada';
    }

    if ($cotas_solicitadas > $cota_restante) {
        throw new Exception('Saldo de cotas insuficiente. Restam: ' . $cota_restante);
    }

    // 2. Processa o arquivo (se houver)
    $nome_arquivo_final = null;
    if (!$solicitar_balcao) {
        $uploads_dir = realpath(__DIR__ . '/../../uploads');
        if (!$uploads_dir || !is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);
        
        // Nova lógica para nomear o arquivo para evitar conflitos, preservando o nome original.
        // Formato: [id_do_usuario]_[timestamp]_[nome_original_do_arquivo]
        $nome_original = basename($arquivo['name']);
        $timestamp = time();
        $nome_arquivo_final = $usuario_id . '_' . $timestamp . '_' . $nome_original;
        
        $caminho_destino = $uploads_dir . '/' . $nome_arquivo_final;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
            throw new Exception('Falha ao mover o arquivo enviado.');
        }
    }

    // 3. Insere a solicitação
    $stmt_insert = $conn->prepare(
        "INSERT INTO SolicitacaoImpressao (usuario_id, arquivo_path, qtd_copias, qtd_paginas, colorida, status)
         VALUES (:uid, :path, :copias, :paginas, :colorida, 'Nova')"
    );
    $stmt_insert->execute([
        ':uid' => $usuario_id,
        ':path' => $nome_arquivo_final,
        ':copias' => $qtd_copias,
        ':paginas' => $qtd_paginas,
        ':colorida' => $colorida
    ]);
    $solicitacao_id = $conn->lastInsertId();

    // 4. Atualiza a cota usada
    $stmt_update_cota = $conn->prepare("UPDATE CotaServidor SET $campo_update = $campo_update + :usado WHERE usuario_id = :id");
    $stmt_update_cota->execute([':usado' => $cotas_solicitadas, ':id' => $usuario_id]);

    // 5. Cria notificação para o servidor
    $mensagem_notificacao = "Sua solicitação (#{$solicitacao_id}) foi enviada com sucesso e está aguardando análise.";
    $stmt_notificacao = $conn->prepare(
        "INSERT INTO Notificacao (solicitacao_id, destinatario_id, mensagem) VALUES (:sid, :did, :msg)"
    );
    $stmt_notificacao->execute([':sid' => $solicitacao_id, ':did' => $usuario_id, ':msg' => $mensagem_notificacao]);

    $conn->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada com sucesso!']);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    
    if (isset($caminho_destino) && file_exists($caminho_destino)) {
        unlink($caminho_destino);
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}