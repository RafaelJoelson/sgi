<?php
session_start();
require_once 'config.php';

function redirecionar_com_erro($url, $mensagem) {
    $_SESSION['erro_login'] = $mensagem;
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';

if (empty($cpf) || empty($senha)) {
    redirecionar_com_erro('../index.php', 'CPF e senha são obrigatórios.');
}

try {
    // 1. Busca o usuário na tabela central 'Usuario'
    $stmt = $conn->prepare("SELECT * FROM Usuario WHERE cpf = :cpf");
    $stmt->execute([':cpf' => $cpf]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verifica se o usuário existe, se a senha está correta e se está ativo
    if (!$user || !password_verify($senha, $user['senha'])) {
        redirecionar_com_erro('../index.php', 'CPF ou senha inválidos.');
    }
    if (empty($user['ativo'])) {
        redirecionar_com_erro('../index.php', 'Seu usuário está desativado. Entre em contato com o setor responsável.');
    }

    session_regenerate_id(true); // Previne Session Fixation

    // 3. Inicia a construção da sessão com os dados comuns
    $_SESSION['usuario'] = [
        'id'        => $user['id'], // ID principal da tabela Usuario
        'cpf'       => $user['cpf'],
        'nome'      => $user['nome'],
        'sobrenome' => $user['sobrenome'] ?? '',
        'email'     => $user['email'],
        'tipo'      => $user['tipo_usuario']
    ];
    $dashboard_url = '';
    // 4. Busca dados específicos do perfil e define o URL do painel
    if ($user['tipo_usuario'] === 'servidor') {
        $stmt_servidor = $conn->prepare("SELECT * FROM Servidor WHERE usuario_id = :id");
        $stmt_servidor->execute([':id' => $user['id']]);
        $servidor_data = $stmt_servidor->fetch(PDO::FETCH_ASSOC);
        // Adiciona os dados específicos do servidor à sessão
        $_SESSION['usuario'] = array_merge($_SESSION['usuario'], $servidor_data);
        // Lógica de redirecionamento para servidores
        if (!empty($servidor_data['is_admin'])) {
            $dashboard_url = ($servidor_data['setor_admin'] === 'CAD')
                ? '../pages/admin_cad/dashboard_cad.php'
                : '../pages/admin_coen/dashboard_coen.php';
        } else {
            $dashboard_url = '../pages/servidor/dashboard_servidor.php';
        }
    } elseif ($user['tipo_usuario'] === 'aluno') {
        $stmt_aluno = $conn->prepare("SELECT * FROM Aluno WHERE usuario_id = :id");
        $stmt_aluno->execute([':id' => $user['id']]);
        $aluno_data = $stmt_aluno->fetch(PDO::FETCH_ASSOC);
        // Adiciona os dados específicos do aluno à sessão
        $_SESSION['usuario'] = array_merge($_SESSION['usuario'], $aluno_data);
        $dashboard_url = '../pages/aluno/dashboard_aluno.php';
    }
    // 5. Redireciona para o painel correto
    if (!empty($dashboard_url)) {
        header('Location: ' . $dashboard_url);
        exit;
    } else {
        // Fallback caso algo dê errado (ex: tipo de usuário inválido)
        redirecionar_com_erro('../index.php', 'Tipo de usuário desconhecido. Contate o suporte.');
    }
} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    redirecionar_com_erro('../index.php', 'Ocorreu um erro no servidor. Tente novamente mais tarde.');
}
// Se o script chegar até aqui, significa que nenhum usuário foi encontrado
redirecionar_com_erro('../index.php', 'CPF ou senha inválidos.');
