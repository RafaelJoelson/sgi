<?php
require_once '../../../includes/config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$reprografia_id = $_SESSION['usuario']['id'];
$solicitacao_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$novo_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

if (!$solicitacao_id || !in_array($novo_status, ['Aceita', 'Rejeitada'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Busca a solicitação para obter detalhes
    $stmt_sol = $conn->prepare(
        "SELECT si.*, u.tipo_usuario 
         FROM SolicitacaoImpressao si 
         JOIN Usuario u ON si.usuario_id = u.id 
         WHERE si.id = :id"
    );
    $stmt_sol->execute([':id' => $solicitacao_id]);
    $solicitacao = $stmt_sol->fetch();

    if (!$solicitacao) throw new Exception('Solicitação não encontrada.');

    // 2. Lógica de devolução de cota se a solicitação for rejeitada
    if ($novo_status === 'Rejeitada') {
        $cotas_a_devolver = $solicitacao->qtd_copias * $solicitacao->qtd_paginas;

        if ($solicitacao->tipo_usuario === 'aluno') {
            $stmt_aluno = $conn->prepare("SELECT cota_id FROM Aluno WHERE usuario_id = :uid");
            $stmt_aluno->execute([':uid' => $solicitacao->usuario_id]);
            $cota_id = $stmt_aluno->fetchColumn();
            
            if ($cota_id) {
                $stmt_devolve = $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada - :valor WHERE id = :id");
                $stmt_devolve->execute([':valor' => $cotas_a_devolver, ':id' => $cota_id]);
            }
        } elseif ($solicitacao->tipo_usuario === 'servidor') {
            $campo_update = $solicitacao->colorida ? 'cota_color_usada' : 'cota_pb_usada';
            $stmt_devolve = $conn->prepare("UPDATE CotaServidor SET $campo_update = $campo_update - :valor WHERE usuario_id = :uid");
            $stmt_devolve->execute([':valor' => $cotas_a_devolver, ':uid' => $solicitacao->usuario_id]);
        }
    }

    // 3. Atualiza o status da solicitação
    $stmt_update = $conn->prepare(
        "UPDATE SolicitacaoImpressao SET status = :status, reprografia_id = :rid WHERE id = :id"
    );
    $stmt_update->execute([
        ':status' => $novo_status,
        ':rid' => $reprografia_id,
        ':id' => $solicitacao_id
    ]);

    // 4. Cria notificação para o solicitante
    $mensagem_notificacao = "Sua solicitação (#{$solicitacao_id}) foi {$novo_status}.";
    $stmt_notificacao = $conn->prepare(
        "INSERT INTO Notificacao (solicitacao_id, destinatario_id, mensagem) VALUES (:sid, :did, :msg)"
    );
    $stmt_notificacao->execute([
        ':sid' => $solicitacao_id,
        ':did' => $solicitacao->usuario_id,
        ':msg' => $mensagem_notificacao
    ]);

    $conn->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => "Solicitação #{$solicitacao_id} foi marcada como {$novo_status}."]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Erro ao atualizar status: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
?>