<?php
require_once '../../includes/config.php';
session_start();

// Segurança: apenas servidores podem cadastrar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}

// Verifica se a cota existe
$verifica = $conn->prepare("SELECT COUNT(*) FROM CotaAluno WHERE id = :cota_id");
$verifica->execute([':cota_id' => $cota_id]);

if ($verifica->fetchColumn() == 0) {
    $_SESSION['mensagem'] = 'Cota selecionada inválida.';
    header('Location: form_aluno.php?erro=1');
    exit;
}


// Validação básica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($_POST['matricula']);
    $nome = trim($_POST['nome']);
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email = trim($_POST['email']);
    $cpf = trim($_POST['cpf']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $cargo = $_POST['cargo'];
    $cota_id = $_POST['cota_id'];
    $data_fim_validade = $_POST['data_fim_validade'];

    $stmt = $conn->prepare("INSERT INTO Aluno (matricula, nome, sobrenome, email, cpf, senha, cargo, cota_id, data_fim_validade)
                            VALUES (:matricula, :nome, :sobrenome, :email, :cpf, :senha, :cargo, :cota_id, :validade)");
    $stmt->execute([
        ':matricula' => $matricula,
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':cpf' => $cpf,
        ':senha' => $senha,
        ':cargo' => $cargo,
        ':cota_id' => $cota_id,
        ':validade' => $data_fim_validade
    ]);
    
    // Atualiza cota usada
    $atualizaCota = $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + 1 WHERE id = :cota_id");
    $atualizaCota->execute([':cota_id' => $cota_id]);

    $_SESSION['mensagem'] = 'Aluno cadastrado com sucesso!';
    header('Location: dashboard.php');
    exit;

}

header('Location: form_aluno.php?erro=1');
exit;
