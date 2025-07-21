<?php
require_once '../../includes/config.php';
session_start();
header('Content-Type: application/json');

// Verifica se o usuário é um reprografia logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografia') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

// Coleta e validação dos dados
$reprografia_id = $_SESSION['usuario']['id'];
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$nova_senha = $_POST['nova_senha'] ?? '';
$confirma_senha = $_POST['confirma_senha'] ?? '';
$logo_file = $_FILES['logo'] ?? null;

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
        ':id' => $reprografia_id,
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email
    ];

    // Constrói a query dinamicamente
    $sql = "UPDATE reprografia SET nome = :nome, sobrenome = :sobrenome, email = :email";
    
    // Atualiza a senha apenas se for fornecida
    if (!empty($nova_senha)) {
        $sql .= ", senha = :senha";
        $params[':senha'] = password_hash($nova_senha, PASSWORD_DEFAULT);
    }

    // Lida com o upload do logo
    if ($logo_file && $logo_file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../img/';
        // Garante que o nome do arquivo seja sempre 'logo_reprografia.png'
        $file_extension = strtolower(pathinfo($logo_file['name'], PATHINFO_EXTENSION));
        $new_filename = 'logo_reprografia.' . $file_extension;
        $destination = $upload_dir . $new_filename;

        // Valida o tipo de arquivo
        if (!in_array($file_extension, ['png', 'webp'])) {
            throw new Exception('Formato de logo inválido. Use apenas PNG ou WEBP.');
        }

        // Move o arquivo para o destino
        if (!move_uploaded_file($logo_file['tmp_name'], $destination)) {
            throw new Exception('Falha ao salvar o novo logo.');
        }
    }

    $sql .= " WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Atualiza os dados na sessão
    $_SESSION['usuario']['nome'] = $nome;
    $_SESSION['usuario']['sobrenome'] = $sobrenome;

    echo json_encode([
        'sucesso' => true, 
        'mensagem' => 'Dados atualizados com sucesso!',
        'novo_nome' => $nome . ' ' . $sobrenome
    ]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
