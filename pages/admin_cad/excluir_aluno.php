<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

if (isset($_GET['matricula'])) {
    $matricula = $_GET['matricula'];

    // Verifica se existe antes de excluir
    $stmt = $conn->prepare("SELECT * FROM Aluno WHERE matricula = :matricula");
    $stmt->execute([':matricula' => $matricula]);
    $aluno = $stmt->fetch();

    if ($aluno) {
        $delete = $conn->prepare("DELETE FROM Aluno WHERE matricula = :matricula");
        $delete->execute([':matricula' => $matricula]);
        $_SESSION['mensagem'] = "Aluno removido com sucesso.";
    } else {
        $_SESSION['mensagem'] = "Aluno não encontrado.";
    }
}

header('Location: dashboard.php');
exit;
