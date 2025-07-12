<?php
// Configurações básicas do site
define('SITE_NAME', 'Sistema de Gerenciamento de Impressão');
define('SITE_DESCRIPTION', 'Sistema de Gerencimanto de Impressão (SGI) Instituto Federal do Sudeste de Minas Gerais - Campus São João del-Rei');
define('CURRENT_YEAR', date('Y'));

// Caminho base do site (ajuste se estiver em subpasta)
define('BASE_URL', '/sgi'); // ou '/' se estiver na raiz

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sgi');

// --- CORREÇÃO CRÍTICA ---
// O caminho base DEVE ser o URL completo do seu site para que os links
// para CSS, imagens e scripts funcionem corretamente com o .htaccess.
// Use 'https' se você tiver um certificado SSL.
//define('BASE_URL', 'https://ifsudestesgisjdr.free.nf');

// Configurações do banco de dados
//define('DB_HOST', 'sql102.infinityfree.com');
//define('DB_USER', 'if0_39311333');
//define('DB_PASS', 'fOs7jQlfLZG');
//define('DB_NAME', 'if0_39311333_sgi');

// Funções úteis
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}

// Conexão com o banco de dados
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
