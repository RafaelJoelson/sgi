<?php
require_once '../../includes/config.php';
session_start();

// 1. VERIFICAÇÃO DE PERMISSÃO
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor' || empty($_SESSION['usuario']['is_admin'])) {
    $_SESSION['mensagem_erro'] = 'Acesso negado.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php');
    exit;
}

// 2. COLETA E VALIDAÇÃO DOS DADOS
$siape = trim($_POST['siape'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$sobrenome = trim($_POST['sobrenome'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$setor_admin = in_array($_POST['setor_admin'], ['CAD', 'COEN', 'NENHUM']) ? $_POST['setor_admin'] : 'NENHUM';
// CORREÇÃO: Verifica o VALOR de 'is_admin', não apenas se ele existe.
$is_admin = (isset($_POST['is_admin']) && $_POST['is_admin'] == '1') ? 1 : 0;
$ativo = isset($_POST['ativo']) ? 1 : 0;
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;

if (empty($siape) || empty($nome) || empty($email)) {
    $_SESSION['mensagem_erro'] = 'Os campos SIAPE, Nome e E-mail são obrigatórios.';
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php?siape=' . urlencode($siape));
    exit;
}

try {
    // 3. ATUALIZAÇÃO NO BANCO DE DADOS
    $stmt = $conn->prepare(
        'UPDATE Servidor SET 
            nome = :nome, sobrenome = :sobrenome, email = :email, setor_admin = :setor_admin, 
            is_admin = :is_admin, data_fim_validade = :data_fim_validade, ativo = :ativo 
         WHERE siape = :siape'
    );
    
    $ok = $stmt->execute([
        ':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email,
        ':setor_admin' => $setor_admin, ':is_admin' => $is_admin,
        ':data_fim_validade' => $data_fim_validade, ':ativo' => $ativo, ':siape' => $siape
    ]);

    if ($ok) {
        $_SESSION['mensagem_sucesso'] = 'Servidor atualizado com sucesso!';

        // 4. VERIFICAÇÃO DE AUTO-ATUALIZAÇÃO
        if ($siape === $_SESSION['usuario']['id'] && ($ativo == 0 || $is_admin == 0)) {
            session_destroy();
            header('Location: ../../index.php?logout=autoedicao');
            exit;
        }
    } else {
        $_SESSION['mensagem_erro'] = 'Nenhuma alteração foi feita ou ocorreu um erro.';
    }

} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = 'Erro de banco de dados: ' . $e->getMessage();
}

// 5. REDIRECIONAMENTO DINÂMICO
$setor_logado = $_SESSION['usuario']['setor_admin'];
$path = ($setor_logado === 'CAD') ? '/pages/admin_cad/dashboard_cad.php' : '/pages/admin_coen/dashboard_coen.php';
$redirect_url = BASE_URL . $path;

header('Location: ' . $redirect_url);
exit;
