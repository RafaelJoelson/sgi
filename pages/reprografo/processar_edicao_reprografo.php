<?php
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');

// Verifica se o usuário é um reprografo logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

// Coleta e validação dos dados
$reprografo_id = $_SESSION['usuario']['id'];
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$nova_senha = $_POST['nova_senha'] ?? '';
$confirma_senha = $_POST['confirma_senha'] ?? '';

if (empty($nome) || empty($sobrenome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome e sobrenome são obrigatórios.']);
    exit;
}

if ($nova_senha !== $confirma_senha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não coincidem.']);
    exit;
}

try {
    $params = [
        ':id' => $reprografo_id,
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email
    ];

    // Constrói a query dinamicamente para atualizar a senha apenas se ela for fornecida
    $sql = "UPDATE Reprografo SET nome = :nome, sobrenome = :sobrenome, email = :email";
    if (!empty($nova_senha)) {
        $sql .= ", senha = :senha";
        $params[':senha'] = password_hash($nova_senha, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Atualiza os dados na sessão para refletir a mudança imediatamente
    $_SESSION['usuario']['nome'] = $nome;
    $_SESSION['usuario']['sobrenome'] = $sobrenome;

    echo json_encode([
        'sucesso' => true, 
        'mensagem' => 'Dados atualizados com sucesso!',
        'novo_nome' => $nome . ' ' . $sobrenome // Envia o novo nome completo para o JS
    ]);

} catch (PDOException $e) {
    error_log("Erro ao atualizar dados do reprografo: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar os dados no banco.']);
}
