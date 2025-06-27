<?php
require_once '../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cargo = $_POST['cargo'];
    $cota_id = $_POST['cota_id'];
    $data_fim_validade = $_POST['data_fim_validade'];

    $stmt = $conn->prepare("UPDATE Aluno 
                            SET nome = :nome, email = :email, cargo = :cargo, cota_id = :cota_id, data_fim_validade = :validade 
                            WHERE matricula = :matricula");
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':cargo' => $cargo,
        ':cota_id' => $cota_id,
        ':validade' => $data_fim_validade,
        ':matricula' => $matricula
    ]);

    $_SESSION['mensagem'] = 'Aluno atualizado com sucesso!';
    header('Location: dashboard.php');
    exit;

}

header('Location: form_aluno.php?erro=1');
exit;
