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
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email = trim($_POST['email']);
    $cargo = $_POST['cargo'];
    $cota_id = $_POST['cota_id'];
    $data_fim_validade = $_POST['data_fim_validade'];

    // Verifica cota anterior
    $stmt_antiga = $conn->prepare("SELECT cota_id FROM Aluno WHERE matricula = :matricula");
    $stmt_antiga->execute([':matricula' => $matricula]);
    $cota_anterior = $stmt_antiga->fetchColumn();

    if ($cota_anterior != $cota_id) {
        // Decrementa cota usada da cota antiga
        $stmt_dec = $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada - 1 WHERE id = :id");
        $stmt_dec->execute([':id' => $cota_anterior]);

        // Incrementa cota usada da nova cota
        $stmt_inc = $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + 1 WHERE id = :id");
        $stmt_inc->execute([':id' => $cota_id]);
    }

    $stmt = $conn->prepare("UPDATE Aluno 
                            SET nome = :nome, sobrenome = :sobrenome, email = :email, cargo = :cargo, cota_id = :cota_id, data_fim_validade = :validade 
                            WHERE matricula = :matricula");
    $stmt->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':cargo' => $cargo,
        ':cota_id' => $cota_id,
        ':validade' => $data_fim_validade,
        ':matricula' => $matricula
    ]);

    $verifica = $conn->prepare("SELECT COUNT(*) FROM CotaAluno WHERE id = :cota_id");
    $verifica->execute([':cota_id' => $cota_id]);

    if ($verifica->fetchColumn() == 0) {
        $_SESSION['mensagem'] = 'Cota selecionada inválida.';
        header("Location: form_aluno.php?matricula=$matricula&erro=1");
        exit;
    }

    $_SESSION['mensagem'] = 'Aluno atualizado com sucesso!';
    header('Location: dashboard.php');
    exit;
}

header('Location: form_aluno.php?erro=1');
exit;
