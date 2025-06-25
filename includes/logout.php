<?php
session_start();

// Destroi todos os dados da sessão
$_SESSION = [];
session_unset();
session_destroy();

// Redireciona para a página de login
header('Location: ../index.php');
exit;
