<?php
session_start();
require_once 'config.php';

// Função para autenticar usuário
function autenticarUsuario($conn, $cpf, $senha) {
    // Busca usuário por CPF
    $stmt = $conn->prepare("SELECT u.*, 
                           a.id AS aluno_id, a.ativo AS aluno_ativo,
                           s.id AS servidor_id, s.is_admin
                           FROM Usuario u
                           LEFT JOIN Aluno a ON u.id = a.id
                           LEFT JOIN Servidor s ON u.id = s.id
                           WHERE u.cpf = ?");
    $stmt->execute([$cpf]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se encontrou o usuário e se a senha está correta
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        return $usuario;
    }
    
    return false;
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']); // Remove formatação
    $senha = $_POST['senha'];

    // Validações básicas
    if (empty($cpf) || strlen($cpf) !== 11) {
        $_SESSION['login_erro'] = "CPF inválido";
        header("Location: .../index.php");
        exit();
    }

    if (empty($senha)) {
        $_SESSION['login_erro'] = "Senha não informada";
        header("Location: ../index.php");
        exit();
    }

    // Tenta autenticar
    $usuario = autenticarUsuario($conn, $cpf, $senha);

    if ($usuario) {
        // Verifica se é aluno ativo
        if ($usuario['tipo'] === 'A' && !$usuario['aluno_ativo']) {
            $_SESSION['login_erro'] = "Sua conta de aluno está desativada para este semestre";
            header("Location: ../index.php");
            exit();
        }

        // Configura a sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_cpf'] = $usuario['cpf'];
        $_SESSION['usuario_tipo'] = $usuario['tipo']; // 'A' ou 'S'

        // Redireciona conforme o tipo de usuário
        switch ($usuario['tipo']) {
            case 'A': // Aluno
                $_SESSION['aluno_matricula'] = $usuario['matricula'] ?? '';
                $_SESSION['aluno_turma'] = $usuario['turma'] ?? '';
                header("Location: aluno/dashboard.php");
                break;
                
            case 'S': // Servidor
                $_SESSION['servidor_siap'] = $usuario['siap'] ?? '';
                
                // Verifica se é admin
                if ($usuario['is_admin']) {
                    $_SESSION['is_admin'] = true;
                    header("Location: ../pages/admin.php");
                } else {
                    $_SESSION['is_admin'] = false;
                    header("Location: servidor/dashboard.php");
                }
                break;
                
            default:
                // Caso inválido (não deveria acontecer)
                session_destroy();
                $_SESSION['login_erro'] = "Tipo de usuário desconhecido";
                header("Location: ../index.php");
        }
        exit();
    } else {
        $_SESSION['login_erro'] = "CPF ou senha incorretos";
        header("Location: ../index.php");
        exit();
    }
} else {
    // Acesso direto ao script
    header("Location: ../index.php");
    exit();
}