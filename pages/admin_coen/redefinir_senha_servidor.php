<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'COEN') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siap'], $_POST['nova_senha'])) {
    $siap = trim($_POST['siap']);
    $nova_senha = $_POST['nova_senha'];
    if (strlen($nova_senha) < 6) {
        $_SESSION['mensagem'] = 'A senha deve ter pelo menos 6 caracteres.';
        header('Location: dashboard_coen.php');
        exit;
    }
    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE Servidor SET senha = :senha WHERE siap = :siap');
    $stmt->bindValue(':senha', $hash);
    $stmt->bindValue(':siap', $siap);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Senha redefinida com sucesso!';
    } else {
        $_SESSION['mensagem'] = 'Erro ao redefinir senha.';
    }
    header('Location: dashboard_coen.php');
    exit;
} else {
    header('Location: dashboard_coen.php');
    exit;
}
