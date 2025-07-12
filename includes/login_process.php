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

$tipos_usuario = [
    ['tabela' => 'Aluno', 'campo_id' => 'matricula', 'dashboard' => 'aluno/dashboard_aluno.php', 'check_ativo' => true],
    ['tabela' => 'Servidor', 'campo_id' => 'siape', 'dashboard' => function($user) { if ($user['is_admin']) { if ($user['setor_admin'] === 'CAD') return 'admin_cad/dashboard_cad.php'; if ($user['setor_admin'] === 'COEN') return 'admin_coen/dashboard_coen.php'; } return 'servidor/dashboard_servidor.php'; }, 'check_ativo' => true]
];

$usuario_encontrado = false;
$senha_correta = false;
$usuario_ativo = false;

foreach ($tipos_usuario as $tipo) {
    $sql = "SELECT * FROM {$tipo['tabela']} WHERE cpf = :cpf";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $usuario_encontrado = true;
        
        if (password_verify($senha, $user['senha'])) {
            $senha_correta = true;

            // ==================================================================
            // MUDANÇA CRÍTICA: Lógica de verificação de usuário ativo corrigida.
            // ==================================================================
            $usuario_ativo = false; // Começamos presumindo que não está ativo.

            // Caso 1: Usuário é um admin? Admins estão sempre ativos para login.
            if (!empty($user['is_admin'])) {
                $usuario_ativo = true;
            } 
            // Caso 2: É um usuário comum que precisa de verificação E está com status 'ativo'?
            elseif ($tipo['check_ativo'] && !empty($user['ativo'])) {
                $usuario_ativo = true;
            }
            // Caso 3: É um tipo de usuário que não precisa de verificação (ex: Reprografo)?
            elseif (!$tipo['check_ativo']) {
                $usuario_ativo = true;
            }
            // ==================================================================

            if ($usuario_ativo) {
                $_SESSION['usuario'] = [
                    'cpf' => $user['cpf'],
                    'nome' => $user['nome'],
                    'sobrenome' => $user['sobrenome'] ?? '',
                    'tipo' => strtolower($tipo['tabela']),
                    'id'   => $user[$tipo['campo_id']],
                ];

                if ($tipo['tabela'] === 'Servidor') {
                    $_SESSION['usuario']['is_admin'] = $user['is_admin'];
                    $_SESSION['usuario']['setor_admin'] = $user['setor_admin'];
                }

                $dashboard = is_callable($tipo['dashboard'])
                    ? $tipo['dashboard']($user)
                    : $tipo['dashboard'];

                header('Location: ../pages/' . $dashboard);
                exit;
            }
        }
        
        // Se encontramos o usuário nesta tabela, não precisamos procurar em outras.
        break; 
    }
}

// Tratamento de erro após o loop
if ($usuario_encontrado && $senha_correta && !$usuario_ativo) {
    redirecionar_com_erro('../index.php', 'Seu usuário está desativado. Entre em contato com o administrador.');
} else {
    redirecionar_com_erro('../index.php', 'CPF ou senha inválidos.');
}