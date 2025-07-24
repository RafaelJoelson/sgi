<?php
require_once '../../../includes/config.php';
session_start();
header('Content-Type: application/json');

// Permissão
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

try {
    // Busca todas as configurações de uma vez
    $stmt = $conn->query("SELECT chave, valor FROM Configuracoes WHERE chave LIKE 'cota_padrao_%'");
    // PDO::FETCH_KEY_PAIR cria um array associativo ['chave' => 'valor']
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode(['sucesso' => true, 'dados' => $configs]);

} catch (PDOException $e) {
    error_log("Erro ao obter configurações: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar o banco de dados.']);
}
