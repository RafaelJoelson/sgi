<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'];
    $nova_senha = $_POST['nova_senha'];

    if (!empty($matricula) && !empty($nova_senha)) {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Aluno SET senha = :senha WHERE matricula = :matricula");
        $stmt->execute([
            ':senha' => $hash,
            ':matricula' => $matricula
        ]);

        $_SESSION['mensagem'] = "Senha redefinida com sucesso.";
    }
}

header('Location: dashboard.php');
exit;
