<?php
require_once '../../../includes/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];
$cota_id = $_SESSION['usuario']['cota_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$qtd_copias = filter_input(INPUT_POST, 'qtd_copias', FILTER_VALIDATE_INT);
$qtd_paginas = filter_input(INPUT_POST, 'qtd_paginas', FILTER_VALIDATE_INT);
$solicitar_balcao = isset($_POST['solicitar_balcao']);
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

    // 1. Verifica a cota da turma (apenas para verificação, SEM decrementar)
    if (!$cota_id) throw new Exception('Você não está associado a nenhuma turma com cota.');

    $stmt_cota = $conn->prepare("SELECT cota_total, cota_usada FROM CotaAluno WHERE id = :cota_id FOR UPDATE");
    $stmt_cota->execute([':cota_id' => $cota_id]);
    $cota = $stmt_cota->fetch();

    if (!$cota) throw new Exception('Cota da turma não encontrada.');

    $cotas_solicitadas = $qtd_copias * $qtd_paginas;
    $cota_restante = $cota->cota_total - $cota->cota_usada;

    if ($cotas_solicitadas > $cota_restante) {
        throw new Exception('Saldo de cotas da turma insuficiente. Restam: ' . $cota_restante);
    }

    // 2. Processa o arquivo (se houver)
    $nome_arquivo_final = null;
    if (!$solicitar_balcao) {
        $uploads_dir = realpath(__DIR__ . '/../../uploads');
        if (!$uploads_dir || !is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        
        $nome_original = basename($arquivo['name']);
        $timestamp = time();
        $nome_arquivo_final = $usuario_id . '_' . $timestamp . '_' . $nome_original;
        
        $caminho_destino = $uploads_dir . '/' . $nome_arquivo_final;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
            throw new Exception('Falha ao mover o arquivo enviado.');
        }
    }

    // 3. Insere a solicitação (com status 'Nova', SEM decrementar cotas ainda)
    $stmt_insert = $conn->prepare(
        "INSERT INTO SolicitacaoImpressao (usuario_id, arquivo_path, qtd_copias, qtd_paginas, colorida, status)
         VALUES (:uid, :path, :copias, :paginas, 0, 'Nova')"
    );
    $stmt_insert->execute([
        ':uid' => $usuario_id,
        ':path' => $nome_arquivo_final,
        ':copias' => $qtd_copias,
        ':paginas' => $qtd_paginas
    ]);
    $solicitacao_id = $conn->lastInsertId();

    // 4. REMOVIDO: Não decrementar cotas aqui. As cotas serão decrementadas apenas quando a reprografia ACEITAR a solicitação.

    // 5. Cria notificação para o aluno
    $mensagem_notificacao = "Sua solicitação (#{$solicitacao_id}) foi enviada com sucesso e está aguardando análise.";
    $stmt_notificacao = $conn->prepare(
        "INSERT INTO Notificacao (solicitacao_id, destinatario_id, mensagem) VALUES (:sid, :did, :msg)"
    );
    $stmt_notificacao->execute([':sid' => $solicitacao_id, ':did' => $usuario_id, ':msg' => $mensagem_notificacao]);

    $conn->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada com sucesso!']);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    
    // Apaga o arquivo se ele foi movido mas a transação falhou
    if (isset($caminho_destino) && file_exists($caminho_destino)) {
        unlink($caminho_destino);
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
?>