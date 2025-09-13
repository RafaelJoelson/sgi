<?php
session_start();
require_once '../../../includes/config.php';
header('Content-Type: application/json');

// Garante que há um usuário logado com CPF
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    echo json_encode(['sucesso' => false, 'notificacoes' => []]);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];

try {
    // Busca notificações não visualizadas com status da solicitação
    $stmt = $conn->prepare("
        SELECT 
            n.id, 
            n.mensagem, 
            s.status
        FROM Notificacao n
        LEFT JOIN SolicitacaoImpressao s ON n.solicitacao_id = s.id
        WHERE n.destinatario_id = :id 
          AND n.visualizada = FALSE
    ");
    $stmt->execute([':id' => $usuario_id]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($notificacoes) {
        // Marca as notificações como visualizadas
        $ids_para_marcar = array_column($notificacoes, 'id');
        $placeholders = implode(',', array_fill(0, count($ids_para_marcar), '?'));

        $update_stmt = $conn->prepare("UPDATE Notificacao SET visualizada = TRUE WHERE id IN ($placeholders)");
        $update_stmt->execute($ids_para_marcar);

        // Retorna as notificações com status
        echo json_encode(['sucesso' => true, 'notificacoes' => $notificacoes]);
    } else {
        // Nenhuma notificação nova
        echo json_encode(['sucesso' => true, 'notificacoes' => []]);
    }

} catch (PDOException $e) {
    error_log("Erro ao verificar notificações: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de banco de dados.']);
}