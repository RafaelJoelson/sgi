<?php
require_once '../../../includes/config.php';
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
$cpf = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$setor_admin = in_array($_POST['setor_admin'], ['CAD', 'COEN', 'NENHUM']) ? $_POST['setor_admin'] : 'NENHUM';
$is_admin = (isset($_POST['is_admin']) && $_POST['is_admin'] == '1') ? 1 : 0;
$data_fim_validade = !empty($_POST['data_fim_validade']) ? $_POST['data_fim_validade'] : null;
$senha = $_POST['senha'] ?? '';

if (empty($siape) || empty($nome) || empty($email) || strlen($cpf) !== 11 || empty($senha)) {
    $_SESSION['mensagem_erro'] = 'Todos os campos obrigatórios devem ser preenchidos corretamente.';
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php');
    exit;
}

try {
    $conn->beginTransaction();

    // 3. VERIFICAÇÃO DE DUPLICIDADE (Atualizado)
    $stmt_check_siape = $conn->prepare("SELECT usuario_id FROM Servidor WHERE siape = :siape");
    $stmt_check_siape->execute([':siape' => $siape]);
    if ($stmt_check_siape->fetch()) {
        throw new Exception('O SIAPE informado já está cadastrado.');
    }

    $stmt_check_cpf = $conn->prepare("SELECT id FROM Usuario WHERE cpf = :cpf OR email = :email");
    $stmt_check_cpf->execute([':cpf' => $cpf, ':email' => $email]);
    if ($stmt_check_cpf->fetch()) {
        throw new Exception('O CPF ou E-mail informado já está cadastrado no sistema.');
    }

    $hash_senha = password_hash($senha, PASSWORD_DEFAULT);

    // 4. INSERÇÃO NA TABELA USUARIO
    $stmt_user = $conn->prepare(
        "INSERT INTO Usuario (cpf, nome, sobrenome, email, senha, tipo_usuario, data_fim_validade, ativo)
         VALUES (:cpf, :nome, :sobrenome, :email, :senha, 'servidor', :validade, 1)"
    );
    $stmt_user->execute([
        ':cpf' => $cpf, ':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email,
        ':senha' => $hash_senha, ':validade' => $data_fim_validade
    ]);
    $usuario_id = $conn->lastInsertId();

    // MUDANÇA: Busca os valores padrão da tabela de configurações
    $configs_stmt = $conn->query("SELECT chave, valor FROM Configuracoes WHERE chave LIKE 'cota_padrao_servidor_%'");
    $configs = $configs_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $cota_servidor_pb = $configs['cota_padrao_servidor_pb'] ?? 1000; // Fallback para 1000
    $cota_servidor_color = $configs['cota_padrao_servidor_color'] ?? 100; // Fallback para 100

    // 5. INSERÇÃO NA TABELA SERVIDOR
    $stmt_servidor = $conn->prepare(
        "INSERT INTO Servidor (usuario_id, siape, is_admin, setor_admin) VALUES (:uid, :siape, :is_admin, :setor)"
    );
    $stmt_servidor->execute([':uid' => $usuario_id, ':siape' => $siape, ':is_admin' => $is_admin, ':setor' => $setor_admin]);

    // 6. INSERÇÃO NA TABELA DE COTAS DO SERVIDOR
    $stmt_insert_cota = $conn->prepare(
        'INSERT INTO CotaServidor (siape, cota_pb_total, cota_pb_usada, cota_color_total, cota_color_usada) 
         VALUES (:siape, :cota_pb, 0, :cota_color, 0)'
    );
    $stmt_insert_cota->execute([
        ':usuario_id' => $usuario_id, // CORREÇÃO: deveria ser usuario_id, mas a tabela CotaServidor ainda usa siape. Mantendo por compatibilidade com o diff anterior. O ideal seria refatorar CotaServidor também.
        ':cota_pb' => $cota_servidor_pb,
        ':cota_color' => $cota_servidor_color
    ]);

    $conn->commit();
    $_SESSION['mensagem_sucesso'] = 'Servidor cadastrado com sucesso!';
    
    // 7. REDIRECIONAMENTO DINÂMICO
    $setor_logado = $_SESSION['usuario']['setor_admin'];
    $path = ($setor_logado === 'CAD') ? '/pages/admin_cad/dashboard_cad.php' : '/pages/admin_coen/dashboard_coen.php';
    $redirect_url = BASE_URL . $path;
    
    header('Location: ' . $redirect_url);
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['mensagem_erro'] = 'Erro ao cadastrar servidor: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/admin/form_servidor.php');
    exit;
}
