<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form_aluno.php');
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS
$matricula = trim($_POST['matricula'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$cargo = $_POST['cargo'] ?? 'Nenhum';
$cota_id = filter_input(INPUT_POST, 'cota_id', FILTER_VALIDATE_INT);
$ativo = isset($_POST['ativo']) ? 1 : 0;
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;

// Validações básicas
if (empty($matricula) || empty($nome) || empty($email) || empty($cota_id)) {
    $_SESSION['mensagem_erro'] = 'Os campos Matrícula, Nome, E-mail e Turma são obrigatórios.';
    header('Location: form_aluno.php?matricula=' . urlencode($matricula));
    exit;
}

try {
    $conn->beginTransaction();

    // 3. VERIFICAÇÃO DE CARGO (Líder/Vice)
    if ($cargo === 'Líder' || $cargo === 'Vice-líder') {
        // Verifica se OUTRO aluno na mesma turma já tem o cargo
        $stmt_check_cargo = $conn->prepare("SELECT COUNT(*) FROM Aluno WHERE cota_id = :cota_id AND cargo = :cargo AND matricula != :matricula");
        $stmt_check_cargo->execute([
            ':cota_id' => $cota_id,
            ':cargo' => $cargo,
            ':matricula' => $matricula
        ]);
        if ($stmt_check_cargo->fetchColumn() > 0) {
            throw new Exception("A turma selecionada já possui um {$cargo}.");
        }
    }

    // 4. ATUALIZAÇÃO NA TABELA ALUNO
    $stmt_update = $conn->prepare(
        "UPDATE Aluno SET 
            nome = :nome, 
            sobrenome = :sobrenome, 
            email = :email, 
            cargo = :cargo, 
            cota_id = :cota_id, 
            data_fim_validade = :validade, 
            ativo = :ativo
         WHERE matricula = :matricula"
    );
    $stmt_update->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':cargo' => $cargo,
        ':cota_id' => $cota_id,
        ':validade' => $data_fim_validade,
        ':ativo' => $ativo,
        ':matricula' => $matricula
    ]);
    
    $conn->commit();
    $_SESSION['mensagem_sucesso'] = 'Aluno atualizado com sucesso!';
    header('Location: dashboard_cad.php');
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['mensagem_erro'] = 'Erro ao atualizar aluno: ' . $e->getMessage();
    header('Location: form_aluno.php?matricula=' . urlencode($matricula));
    exit;
}
