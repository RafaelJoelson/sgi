<?php
require_once '../../../includes/config.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php');
    exit;
}

$matricula = trim($_POST['matricula'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$cargo = $_POST['cargo'] ?? 'Nenhum';
$turma_id = filter_input(INPUT_POST, 'turma_id', FILTER_VALIDATE_INT);
$ativo = isset($_POST['ativo']) ? 1 : 0;
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;

if (empty($matricula) || empty($nome) || empty($email) || empty($turma_id)) {
    $_SESSION['mensagem_erro'] = 'Os campos Matrícula, Nome, E-mail e Turma são obrigatórios.';
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php?matricula=' . urlencode($matricula));
    exit;
}

// --- MUDANÇA: REGRAS DE NEGÓCIO PARA O CARGO ---
if ($ativo == 0) {
    // Se o aluno está sendo inativado, seu cargo DEVE ser 'Nenhum'.
    $cargo = 'Nenhum';
} elseif ($ativo == 1 && $cargo === 'Nenhum') {
    // Se o aluno está ativo, ele NÃO PODE ter o cargo 'Nenhum'.
    $_SESSION['mensagem_erro'] = 'Um aluno ativo deve ter o cargo de "Líder" ou "Vice-líder".';
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php?matricula=' . urlencode($matricula));
    exit;
}
// --- FIM DA MUDANÇA ---

try {
    $conn->beginTransaction();

    $stmt_cota = $conn->prepare("SELECT id FROM CotaAluno WHERE turma_id = :turma_id");
    $stmt_cota->execute([':turma_id' => $turma_id]);
    $cota_id = $stmt_cota->fetchColumn();

    if (!$cota_id) {
        $stmt_create_cota = $conn->prepare("INSERT INTO CotaAluno (turma_id) VALUES (:turma_id)");
        $stmt_create_cota->execute([':turma_id' => $turma_id]);
        $cota_id = $conn->lastInsertId();
    }

    if ($cargo === 'Líder' || $cargo === 'Vice-líder') {
        $stmt_check_cargo = $conn->prepare("SELECT COUNT(*) FROM Aluno WHERE cota_id = :cota_id AND cargo = :cargo AND matricula != :matricula");
        $stmt_check_cargo->execute([':cota_id' => $cota_id, ':cargo' => $cargo, ':matricula' => $matricula]);
        if ($stmt_check_cargo->fetchColumn() > 0) {
            throw new Exception("A turma selecionada já possui um {$cargo}.");
        }
    }

    $stmt_update = $conn->prepare(
        "UPDATE Aluno SET 
            nome = :nome, sobrenome = :sobrenome, email = :email, cargo = :cargo, 
            cota_id = :cota_id, data_fim_validade = :validade, ativo = :ativo
         WHERE matricula = :matricula"
    );
    $stmt_update->execute([
        ':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email, ':cargo' => $cargo,
        ':cota_id' => $cota_id, ':validade' => $data_fim_validade, ':ativo' => $ativo, ':matricula' => $matricula
    ]);
    
    $conn->commit();
    $_SESSION['mensagem_sucesso'] = 'Aluno atualizado com sucesso!';
    header('Location: ' . BASE_URL . '/pages/admin_cad/dashboard_cad.php');
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['mensagem_erro'] = 'Erro ao atualizar aluno: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/admin_cad/form_aluno.php?matricula=' . urlencode($matricula));
    exit;
}
