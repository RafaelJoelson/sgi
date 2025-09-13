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

    // Busca o usuario_id correspondente à matrícula para usar nas atualizações
    $stmt_get_id = $conn->prepare("SELECT usuario_id FROM Aluno WHERE matricula = :matricula");
    $stmt_get_id->execute([':matricula' => $matricula]);
    $usuario_id = $stmt_get_id->fetchColumn();
    if (!$usuario_id) {
        throw new Exception("Aluno com a matrícula informada não encontrado.");
    }

    $stmt_cota = $conn->prepare("SELECT id FROM CotaAluno WHERE turma_id = :turma_id");
    $stmt_cota->execute([':turma_id' => $turma_id]);
    $cota_id = $stmt_cota->fetchColumn();

    if (!$cota_id) {
        $stmt_create_cota = $conn->prepare("INSERT INTO CotaAluno (turma_id) VALUES (:turma_id)");
        $stmt_create_cota->execute([':turma_id' => $turma_id]);
        $cota_id = $conn->lastInsertId();
    }

    if ($cargo === 'Líder' || $cargo === 'Vice-líder') {
        $stmt_check_cargo = $conn->prepare("SELECT COUNT(*) FROM Aluno WHERE cota_id = :cota_id AND cargo = :cargo AND usuario_id != :usuario_id");
        $stmt_check_cargo->execute([':cota_id' => $cota_id, ':cargo' => $cargo, ':usuario_id' => $usuario_id]);
        if ($stmt_check_cargo->fetchColumn() > 0) {
            throw new Exception("A turma selecionada já possui um {$cargo}.");
        }
    }

    // Atualiza a tabela Usuario
    $stmt_update_user = $conn->prepare(
        "UPDATE Usuario SET nome = :nome, sobrenome = :sobrenome, email = :email, ativo = :ativo, data_fim_validade = :validade WHERE id = :id"
    );
    $stmt_update_user->execute([
        ':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email, ':ativo' => $ativo, ':validade' => $data_fim_validade, ':id' => $usuario_id
    ]);

    // Atualiza a tabela Aluno
    $stmt_update_aluno = $conn->prepare("UPDATE Aluno SET cargo = :cargo, cota_id = :cota_id WHERE usuario_id = :id");
    $stmt_update_aluno->execute([
        ':cargo' => $cargo, ':cota_id' => $cota_id, ':id' => $usuario_id
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
