<?php
require_once '../../includes/config.php';
session_start();
// Permissão: apenas servidor CAD ou COEN pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || !in_array($_SESSION['usuario']['setor_admin'], ['CAD','COEN'])) {
    $_SESSION['mensagem'] = 'Acesso negado.';
    header('Location: ../admin/form_servidor.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['siap'])) {
    $siap = trim($_POST['siap']);
    $nome = trim($_POST['nome']);
    $sobrenome = trim($_POST['sobrenome']);
    $email = trim($_POST['email']);
    $setor_admin = $_POST['setor_admin'];
    $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
    $data_fim_validade = $_POST['data_fim_validade'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $stmt = $conn->prepare('UPDATE Servidor SET nome = :nome, sobrenome = :sobrenome, email = :email, setor_admin = :setor_admin, is_admin = :is_admin, data_fim_validade = :data_fim_validade, ativo = :ativo WHERE siap = :siap');
    $ok = $stmt->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':setor_admin' => $setor_admin,
        ':is_admin' => $is_admin,
        ':data_fim_validade' => $data_fim_validade,
        ':ativo' => $ativo,
        ':siap' => $siap
    ]);
    if ($ok) {
        $_SESSION['mensagem'] = 'Servidor atualizado com sucesso!';
        echo "<script>window.history.go(-1);</script>";
        exit;
    } else {
        $_SESSION['mensagem'] = 'Erro ao atualizar servidor.';
        echo "<script>window.history.go(-1);</script>";
        exit;
    }
}
header('Location: ../admin/form_servidor.php');
exit;
