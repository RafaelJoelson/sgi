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

// O Reprografo foi removido desta lista
$tipos_usuario = [
    ['tabela' => 'Servidor', 'campo_id' => 'siape', 'check_ativo' => true],
    ['tabela' => 'Aluno', 'campo_id' => 'matricula', 'check_ativo' => true]
];

foreach ($tipos_usuario as $tipo) {
    $sql = "SELECT * FROM {$tipo['tabela']} WHERE cpf = :cpf";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        if ($tipo['check_ativo'] && empty($user['ativo'])) {
            redirecionar_com_erro('../index.php', 'Seu usuário está desativado.');
        }

        $_SESSION['usuario'] = [
            'cpf' => $user['cpf'],
            'nome' => $user['nome'],
            'sobrenome' => $user['sobrenome'] ?? '',
            'tipo' => strtolower($tipo['tabela']),
            'id'   => $user[$tipo['campo_id']],
        ];

        $dashboard_url = '';

        if ($tipo['tabela'] === 'Servidor') {
            $_SESSION['usuario']['is_admin'] = $user['is_admin'];
            $_SESSION['usuario']['setor_admin'] = $user['setor_admin'];
            // MUDANÇA: Adiciona a flag de super admin na sessão
            $_SESSION['usuario']['is_super_admin'] = $user['is_super_admin'] ?? 0;

            if (!empty($user['is_super_admin'])) {
                // Se for super admin, pode ter um painel central no futuro
                // Por agora, redireciona para o painel do seu setor
                $dashboard_url = ($user['setor_admin'] === 'CAD') 
                    ? '../pages/admin_cad/dashboard_cad.php' 
                    : '../pages/admin_coen/dashboard_coen.php';
            } elseif (!empty($user['is_admin'])) {
                if ($user['setor_admin'] === 'CAD') {
                    $dashboard_url = '../pages/admin_cad/dashboard_cad.php';
                } elseif ($user['setor_admin'] === 'COEN') {
                    $dashboard_url = '../pages/admin_coen/dashboard_coen.php';
                }
            } else {
                $dashboard_url = '../pages/servidor/dashboard_servidor.php';
            }

        } elseif ($tipo['tabela'] === 'Aluno') {
            $dashboard_url = '../pages/aluno/dashboard_aluno.php';
        }

        header('Location: ' . $dashboard_url);
        exit;
    }
}

redirecionar_com_erro('../index.php', 'CPF ou senha inválidos.');
