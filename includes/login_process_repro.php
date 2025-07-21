<?php
session_start();
require_once 'config.php';
//require_once 'tarefas_diarias.php';
function redirecionar_com_erro($url, $mensagem) {
    $_SESSION['erro_login'] = $mensagem;
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../reprografia.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';
// Verifica se os campos de login e senha estão preenchidos
// MUDANÇA: Adicionada verificação para garantir que os campos não estejam vazios
if (empty($login) || empty($senha)) {
    redirecionar_com_erro('../reprografia.php', 'Login e senha são obrigatórios.');
}

try {
    // Busca o reprografia pelo campo 'login'
    $stmt = $conn->prepare("SELECT * FROM Reprografia WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $reprografia = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reprografia && password_verify($senha, $reprografia['senha'])) {
        // Autenticação bem-sucedida
        // MUDANÇA: A sessão agora é criada com o ID numérico como identificador principal.
        $_SESSION['usuario'] = [
            'id'        => $reprografia['id'], // Usa o ID auto-incremento como identificador único
            'login'     => $reprografia['login'],
            'nome'      => $reprografia['nome'],
            'sobrenome' => $reprografia['sobrenome'] ?? '',
            'tipo'      => 'reprografia'
        ];
        
        header('Location: ../pages/reprografia/dashboard_reprografia.php');
        exit;
    } else {
        // Falha na autenticação
        redirecionar_com_erro('../reprografia.php', 'Login ou senha inválidos.');
    }

} catch (PDOException $e) {
    error_log("Erro no login do reprografia: " . $e->getMessage());
    redirecionar_com_erro('../reprografia.php', 'Ocorreu um erro no servidor. Tente novamente.');
}
