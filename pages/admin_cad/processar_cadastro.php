<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas um servidor CAD pode acessar esta página.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || $_SESSION['usuario']['setor_admin'] !== 'CAD') {
    header('Location: ../../index.php');
    exit;
}

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form_aluno.php');
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS
$matricula = trim($_POST['matricula'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$cpf = preg_replace('/\D/', '', trim($_POST['cpf'] ?? '')); // Remove caracteres não numéricos
$senha = $_POST['senha'] ?? '';
$cargo = $_POST['cargo'] ?? 'Nenhum';
$cota_id = filter_input(INPUT_POST, 'cota_id', FILTER_VALIDATE_INT);

// Validações básicas
if (empty($matricula) || empty($nome) || empty($email) || strlen($cpf) !== 11 || empty($senha) || empty($cota_id)) {
    $_SESSION['mensagem_erro'] = 'Todos os campos obrigatórios devem ser preenchidos corretamente.';
    header('Location: form_aluno.php');
    exit;
}
if (!$email) {
    $_SESSION['mensagem_erro'] = 'O formato do e-mail é inválido.';
    header('Location: form_aluno.php');
    exit;
}

try {
    // 3. INICIA A TRANSAÇÃO NO BANCO DE DADOS
    $conn->beginTransaction();

    // 4. VERIFICAÇÃO COMPLETA DE DUPLICIDADE (CPF, Matrícula)
    // Verifica se o CPF já existe em QUALQUER tabela de usuário
    $stmt_check_cpf = $conn->prepare("
        SELECT cpf FROM Aluno WHERE cpf = :cpf
        UNION ALL
        SELECT cpf FROM Servidor WHERE cpf = :cpf
        UNION ALL
        SELECT cpf FROM Reprografo WHERE login = :cpf -- Supondo que o login do reprografo possa ser um CPF
    ");
    $stmt_check_cpf->execute([':cpf' => $cpf]);
    if ($stmt_check_cpf->fetch()) {
        throw new Exception('O CPF informado já está cadastrado no sistema para outro usuário.');
    }

    // Verifica se a matrícula já existe
    $stmt_check_matricula = $conn->prepare("SELECT matricula FROM Aluno WHERE matricula = :matricula");
    $stmt_check_matricula->execute([':matricula' => $matricula]);
    if ($stmt_check_matricula->fetch()) {
        throw new Exception('A matrícula informada já está em uso.');
    }

    // Hash da senha
    $hash_senha = password_hash($senha, PASSWORD_DEFAULT);

    // Define a data de validade automaticamente para o fim do semestre letivo vigente
    $stmt_semestre = $conn->prepare("SELECT data_fim FROM SemestreLetivo WHERE CURDATE() BETWEEN data_inicio AND data_fim ORDER BY data_fim DESC LIMIT 1");
    $stmt_semestre->execute();
    $semestre = $stmt_semestre->fetch(PDO::FETCH_ASSOC);
    $data_fim_validade = $semestre ? $semestre['data_fim'] : null;

    // 5. INSERÇÃO NA TABELA ALUNO
    $stmt_insert = $conn->prepare(
        "INSERT INTO Aluno (matricula, nome, sobrenome, email, cpf, senha, cargo, cota_id, data_fim_validade)
         VALUES (:matricula, :nome, :sobrenome, :email, :cpf, :senha, :cargo, :cota_id, :validade)"
    );
    $stmt_insert->execute([
        ':matricula' => $matricula,
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':cpf' => $cpf,
        ':senha' => $hash_senha,
        ':cargo' => $cargo,
        ':cota_id' => $cota_id,
        ':validade' => $data_fim_validade
    ]);
    
    // 6. ATUALIZAÇÃO DA COTA DA TURMA
    // Nota: Esta lógica parece contar o número de alunos na turma, não a cota usada para impressão.
    $atualizaCota = $conn->prepare("UPDATE CotaAluno SET cota_usada = cota_usada + 1 WHERE id = :cota_id");
    $atualizaCota->execute([':cota_id' => $cota_id]);

    // 7. CONFIRMA A TRANSAÇÃO
    $conn->commit();

    $_SESSION['mensagem_sucesso'] = 'Aluno cadastrado com sucesso!';
    header('Location: dashboard_cad.php');
    exit;

} catch (Exception $e) {
    // 8. REVERTE A TRANSAÇÃO EM CASO DE ERRO
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar aluno: ' . $e->getMessage();
    header('Location: form_aluno.php');
    exit;
}
