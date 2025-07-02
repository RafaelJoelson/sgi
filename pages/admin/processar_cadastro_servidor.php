<?php
require_once '../../includes/config.php';
session_start();
// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    $_SESSION['mensagem'] = 'Acesso negado.';
    header('Location: ../admin/form_servidor.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siap = trim($_POST['siap']);
    $nome = trim($_POST['nome']);
    $sobrenome = trim($_POST['sobrenome']);
    $email = trim($_POST['email']);
    $cpf = trim($_POST['cpf']);
    $setor_admin = $_POST['setor_admin'];
    $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
    $data_fim_validade = $_POST['data_fim_validade'] ?? null;
    $senha = $_POST['senha'] ?? null;
    if (strlen($siap) < 2 || strlen($cpf) != 11) {
        $_SESSION['mensagem'] = 'SIAP ou CPF inválido.';
        header('Location: ../admin/form_servidor.php');
        exit;
    }
    // Verifica duplicidade
    $stmt = $conn->prepare('SELECT siap FROM Servidor WHERE siap = :siap OR cpf = :cpf');
    $stmt->execute([':siap' => $siap, ':cpf' => $cpf]);
    if ($stmt->fetch()) {
        $_SESSION['mensagem'] = 'SIAP ou CPF já cadastrado.';
        header('Location: ../admin/form_servidor.php');
        exit;
    }
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO Servidor (siap, nome, sobrenome, email, cpf, senha, is_admin, setor_admin, ativo, data_fim_validade) VALUES (:siap, :nome, :sobrenome, :email, :cpf, :senha, :is_admin, :setor_admin, 1, :data_fim_validade)');
    $ok = $stmt->execute([
        ':siap' => $siap,
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':cpf' => $cpf,
        ':senha' => $hash,
        ':is_admin' => $is_admin,
        ':setor_admin' => $setor_admin,
        ':data_fim_validade' => $data_fim_validade
    ]);
    if ($ok) {
        $_SESSION['mensagem'] = 'Servidor cadastrado com sucesso!';
        echo "<script>window.history.go(-1);</script>";
        exit;
    } else {
        $_SESSION['mensagem'] = 'Erro ao cadastrar servidor.';
        echo "<script>window.history.go(-1);</script>";
        exit;
    }
}
header('Location: ../admin/form_servidor.php');
exit;
