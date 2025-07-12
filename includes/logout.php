<?php
session_start();

// 1. Determina o destino do redirecionamento ANTES de destruir a sessão.
$redirect_url = '../index.php'; // URL padrão para Alunos e Servidores

// Verifica se o usuário logado é um reprografo
if (isset($_SESSION['usuario']['tipo']) && $_SESSION['usuario']['tipo'] === 'reprografo') {
    $redirect_url = '../reprografia.php'; // URL específica para o reprografo
}

// 2. Destroi todos os dados da sessão
$_SESSION = [];
session_unset();
session_destroy();

// 3. Redireciona para a página de login correta
header('Location: ' . $redirect_url);
exit;