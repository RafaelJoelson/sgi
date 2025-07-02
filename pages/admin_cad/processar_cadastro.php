<?php
require_once '../../includes/config.php';
session_start();

// Permissão: apenas servidor CAD pode acessar
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
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

    // Verifica se a cota existe (deve ser feito aqui, após receber o POST)
    $verifica = $conn->prepare("SELECT COUNT(*) FROM CotaAluno WHERE id = :cota_id");
    $verifica->execute([':cota_id' => $cota_id]);
    if ($verifica->fetchColumn() == 0) {
        $_SESSION['mensagem'] = 'Cota selecionada inválida.';
        header('Location: form_aluno.php?erro=1');
        exit;
    }

    // Define a data de validade automaticamente para o fim do semestre letivo vigente
    $stmt_semestre = $conn->prepare("SELECT data_fim FROM SemestreLetivo WHERE data_inicio <= :hoje AND data_fim >= :hoje ORDER BY data_fim DESC LIMIT 1");
    $stmt_semestre->execute([':hoje' => date('Y-m-d')]);
    $semestre = $stmt_semestre->fetch();
    $data_fim_validade = $semestre ? $semestre->data_fim : null;

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
    header('Location: dashboard_cad.php');
    exit;

}

header('Location: form_aluno.php?erro=1');
exit;
