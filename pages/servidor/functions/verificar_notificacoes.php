<?php
session_start();
require_once '../../../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    echo json_encode(['sucesso' => false, 'notificacoes' => []]);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];

try {
    $stmt = $conn->prepare("
        SELECT n.id, n.mensagem, s.status
        FROM Notificacao n
        LEFT JOIN SolicitacaoImpressao s ON n.solicitacao_id = s.id
        WHERE n.destinatario_id = :id AND n.visualizada = FALSE
    ");
    $stmt->execute([':id' => $usuario_id]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($notificacoes) {
        $ids_para_marcar = array_column($notificacoes, 'id');
        $placeholders = implode(',', array_fill(0, count($ids_para_marcar), '?'));

        $update_stmt = $conn->prepare("UPDATE Notificacao SET visualizada = TRUE WHERE id IN ($placeholders)");
        $update_stmt->execute($ids_para_marcar);

        echo json_encode(['sucesso' => true, 'notificacoes' => $notificacoes]);
    } else {
        echo json_encode(['sucesso' => true, 'notificacoes' => []]);
    }

} catch (PDOException $e) {
    error_log("Erro ao verificar notificações do servidor: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de banco de dados.']);
}