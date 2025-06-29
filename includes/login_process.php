<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (empty($cpf) || empty($senha)) {
        header('Location: index.php?erro=campos_vazios');
        exit;
    }

    // Lista de tentativas de autenticação em ordem
    $usuarios = [
        [
            'tabela' => 'Aluno',
            'campo_id' => 'matricula',
            'dashboard' => 'aluno/dashboard_aluno.php',
            'condicoes' => 'AND data_fim_validade >= CURDATE()' // aluno válido
        ],
        [
            'tabela' => 'Servidor',
            'campo_id' => 'siap',
            'dashboard' => function($user) {
                if ($user->is_admin) {
                    if ($user->setor_admin === 'CAD') {
                        return 'admin_cad/dashboard_cad.php';
                    } elseif ($user->setor_admin === 'COEN') {
                        return 'admin_coen/dashboard_coen.php';
                    }
                }
                return 'servidor/dashboard_servidor.php';
            }
        ],
        [
            'tabela' => 'Reprografo',
            'campo_id' => 'cpf',
            'dashboard' => 'reprografo/dashboard_reprografo.php'
        ]
    ];

    foreach ($usuarios as $usuario) {
        $sql = "SELECT * FROM {$usuario['tabela']} WHERE cpf = :cpf ";

        if (isset($usuario['condicoes'])) {
            $sql .= $usuario['condicoes'];
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user->senha)) {
            // Autenticação bem-sucedida
            $_SESSION['usuario'] = [
                'cpf' => $cpf,
                'nome' => $user->nome,
                'tipo' => strtolower($usuario['tabela']),
                'id'   => $user->{$usuario['campo_id']},
            ];

            // Dashboard apropriado
            $dashboard = is_callable($usuario['dashboard'])
                ? $usuario['dashboard']($user)
                : $usuario['dashboard'];

            header('Location: ../pages/' . $dashboard);
            exit;
        }
    }

    // Se chegou aqui, falhou
    header('Location: ../index.php?erro=login_invalido');
    exit;
} else {
    header('Location: ../index.php');
    exit;
}
