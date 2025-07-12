<?php
session_start();
require_once 'config.php';

function redirecionar_com_erro($url, $mensagem) {
    $_SESSION['erro_login'] = $mensagem;
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login_repro.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';

if (empty($login) || empty($senha)) {
    redirecionar_com_erro('../login_repro.php', 'Login e senha são obrigatórios.');
}

try {
    // Busca o reprografo pelo campo 'login'
    $stmt = $conn->prepare("SELECT * FROM Reprografo WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $reprografo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reprografo && password_verify($senha, $reprografo['senha'])) {
        // Autenticação bem-sucedida
        // MUDANÇA: A sessão agora é criada com o ID numérico como identificador principal.
        $_SESSION['usuario'] = [
            'id'        => $reprografo['id'], // Usa o ID auto-incremento como identificador único
            'login'     => $reprografo['login'],
            'nome'      => $reprografo['nome'],
            'sobrenome' => $reprografo['sobrenome'] ?? '',
            'tipo'      => 'reprografo'
        ];
        
        header('Location: ../pages/reprografo/dashboard_reprografo.php');
        exit;
    } else {
        // Falha na autenticação
        redirecionar_com_erro('../login_repro.php', 'Login ou senha inválidos.');
    }

} catch (PDOException $e) {
    error_log("Erro no login do reprografo: " . $e->getMessage());
    redirecionar_com_erro('../login_repro.php', 'Ocorreu um erro no servidor. Tente novamente.');
}
