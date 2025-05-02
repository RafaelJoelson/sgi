<?php
// Configurações básicas do site
define('SITE_NAME', 'Sistema de Gerenciamento de Impressão');
define('SITE_DESCRIPTION', 'Sistema de Gerencimanto de Impressão (SGI) Instituto Federal do Sudeste de Minas Gerais - Campus São João del-Rei');
define('CURRENT_YEAR', date('Y'));

// Configurações do banco de dados
//define('DB_HOST', 'localhost');
//define('DB_USER', 'usuario');
//define('DB_PASS', 'senha');
//define('DB_NAME', 'nome_do_banco');

// Funções úteis
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}

// Conexão com o banco de dados
/*try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    
    // Configura o PDO para lançar exceções em caso de erros
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}*/
?>