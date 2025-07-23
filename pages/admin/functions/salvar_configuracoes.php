<?php
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');

// Permissão
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

// Coleta e valida os dados
$cota_aluno = filter_input(INPUT_POST, 'cota_padrao_aluno', FILTER_VALIDATE_INT);
$cota_servidor_pb = filter_input(INPUT_POST, 'cota_padrao_servidor_pb', FILTER_VALIDATE_INT);
$cota_servidor_color = filter_input(INPUT_POST, 'cota_padrao_servidor_color', FILTER_VALIDATE_INT);

if ($cota_aluno === false || $cota_servidor_pb === false || $cota_servidor_color === false) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Todos os valores devem ser números inteiros.']);
    exit;
}

try {
    $conn->beginTransaction();

    $sql = "INSERT INTO Configuracoes (chave, valor) VALUES (:chave, :valor)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
    $stmt = $conn->prepare($sql);

    $stmt->execute([':chave' => 'cota_padrao_aluno', ':valor' => $cota_aluno]);
    $stmt->execute([':chave' => 'cota_padrao_servidor_pb', ':valor' => $cota_servidor_pb]);
    $stmt->execute([':chave' => 'cota_padrao_servidor_color', ':valor' => $cota_servidor_color]);

    $conn->commit();

    // MUDANÇA: Define a mensagem de sucesso na sessão
    $_SESSION['mensagem'] = 'Padrões de cota salvos com sucesso!';

    // Responde ao JavaScript indicando sucesso
    echo json_encode(['sucesso' => true]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro ao salvar configurações: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar no banco de dados.']);
}
