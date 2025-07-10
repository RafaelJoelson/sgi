<?php
// Inicia a sessão no topo do arquivo, como deve ser.
session_start();
require_once 'config.php'; // Garanta que $conn (PDO) esteja aqui.

// Função para definir uma mensagem flash e redirecionar
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

// Validação inicial
if (empty($cpf) || empty($senha)) {
    redirecionar_com_erro('../index.php', 'CPF e senha são obrigatórios.');
}

// Lista de tipos de usuário para autenticação
$tipos_usuario = [
    [
        'tabela' => 'Aluno',
        'campo_id' => 'matricula',
        'dashboard' => 'aluno/dashboard_aluno.php',
        'check_ativo' => true // Indica que devemos verificar o status 'ativo'
    ],
    [
        'tabela' => 'Servidor',
        'campo_id' => 'siap',
        'dashboard' => function($user) { // Função para dashboards dinâmicos
            if ($user['is_admin']) {
                if ($user['setor_admin'] === 'CAD') return 'admin_cad/dashboard_cad.php';
                if ($user['setor_admin'] === 'COEN') return 'admin_coen/dashboard_coen.php';
            }
            return 'servidor/dashboard_servidor.php';
        },
        'check_ativo' => true // Também verifica o status 'ativo'
    ],
    [
        'tabela' => 'Reprografo',
        'campo_id' => 'cpf',
        'dashboard' => 'reprografo/dashboard_reprografo.php',
        'check_ativo' => false // Reprografo não possui coluna 'ativo'
    ]
];

$usuario_encontrado = null;
$senha_correta = false;
$usuario_ativo = false;

foreach ($tipos_usuario as $tipo) {
    $sql = "SELECT * FROM {$tipo['tabela']} WHERE cpf = :cpf";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
    $stmt->execute();
    
    // MUDANÇA 1: Usar fetch(PDO::FETCH_ASSOC) para garantir que o resultado seja um array associativo.
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $usuario_encontrado = true;
        // MUDANÇA 2: Acessar a senha e outros campos usando a sintaxe de array.
        if (password_verify($senha, $user['senha'])) {
            $senha_correta = true;

            // MUDANÇA 3: Lógica de verificação de status 'ativo' mais clara.
            // Admins podem logar mesmo se 'ativo' for 0, outros não.
            if ($tipo['tabela'] === 'Servidor' && !empty($user['is_admin'])) {
                $usuario_ativo = true;
            } elseif ($tipo['check_ativo'] && empty($user['ativo'])) {
                $usuario_ativo = false; // Usuário encontrado, senha correta, mas inativo.
            } else {
                $usuario_ativo = true;
            }

            // Se o usuário foi autenticado com sucesso e está ativo, pare o loop.
            if ($usuario_ativo) {
                // Autenticação bem-sucedida
                $_SESSION['usuario'] = [
                    'cpf' => $user['cpf'],
                    'nome' => $user['nome'],
                    'sobrenome' => $user['sobrenome'] ?? '',
                    'tipo' => strtolower($tipo['tabela']),
                    'id'   => $user[$tipo['campo_id']],
                ];

                // Adiciona campos extras para Servidor
                if ($tipo['tabela'] === 'Servidor') {
                    $_SESSION['usuario']['is_admin'] = $user['is_admin'];
                    $_SESSION['usuario']['setor_admin'] = $user['setor_admin'];
                }

                // Determina o dashboard apropriado
                $dashboard = is_callable($tipo['dashboard'])
                    ? $tipo['dashboard']($user)
                    : $tipo['dashboard'];

                header('Location: ../pages/' . $dashboard);
                exit;
            }
        }
        // Se encontrou o usuário em uma tabela, não precisa continuar procurando nas outras.
        break; 
    }
}

// MUDANÇA 4: Tratamento de erro específico após o loop
if ($usuario_encontrado && $senha_correta && !$usuario_ativo) {
    redirecionar_com_erro('../index.php', 'Seu usuário está desativado. Entre em contato com o administrador.');
} else {
    // Para todos os outros casos (CPF não encontrado ou senha incorreta), a mensagem é genérica por segurança.
    redirecionar_com_erro('../index.php', 'CPF ou senha inválidos.');
}