<?php
// Configurações básicas do site
define('SITE_NAME', 'Sistema de Gerenciamento de Impressão');
define('SITE_DESCRIPTION', 'SGI - Instituto Federal do Sudeste de Minas Gerais - Campus São João del-Rei');
define('CURRENT_YEAR', date('Y'));
// Versão dos arquivos CSS e JS para controle de cache
define('ASSET_VERSION', '2.6.6'); // Atualize essa versão quando fizer alterações significativas
define('HORARIO_FUNC_INICIO', 0); // Horário de funcionamento da reprografia
define('HORARIO_FUNC_FIM', 23); // Horário de funcionamento da reprografia
// Caminho base do site (ajuste se estiver em subpasta)
define('BASE_URL', '/sgi'); // ou '/' se estiver na raiz
define('PROJECT_ROOT', dirname(__DIR__)); // Define o caminho absoluto da raiz do projeto

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sgi');

/*
// O caminho base DEVE ser o URL completo do seu site para que os links
// para CSS, imagens e scripts funcionem corretamente com o .htaccess.
// Use 'https' se você tiver um certificado SSL.
define('BASE_URL', 'https://ifsudestesgisjdr.free.nf');

// Configurações do banco de dados
define('DB_HOST', 'sql102.infinityfree.com');
define('DB_USER', 'if0_39311333');
define('DB_PASS', 'fOs7jQlfLZG');
define('DB_NAME', 'if0_39311333_sgi');
*/
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

// Função para gerar a trilha de migalhas (Breadcrumbs)
// MUDANÇA: Agora ignora diretórios específicos na trilha de migalhas
function gerar_migalhas() {
    // Define o link da página inicial
    $migalhas = '<a href="' . BASE_URL . '/index.php">Início</a>';

    // Pega o caminho do URL, remove a base e divide em partes
    $caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $caminho_base = parse_url(BASE_URL, PHP_URL_PATH);
    if (strpos($caminho, $caminho_base) === 0) {
        $caminho = substr($caminho, strlen($caminho_base));
    }
    $partes = explode('/', trim($caminho, '/'));

    // MUDANÇA: Lista de diretórios a serem ignorados na trilha de migalhas (Breadcrumbs)
    $partes_ignoradas = ['pages', 'admin', 'admin_cad', 'admin_coen', 'aluno', 'servidor', 'reprografo'];

    $url_parcial = BASE_URL;
    
    foreach ($partes as $parte) {
        // Se a parte estiver vazia ou na lista de ignorados, pula para a próxima
        if (empty($parte) || in_array($parte, $partes_ignoradas)) {
            $url_parcial .= '/' . $parte; // Adiciona ao caminho, mas não exibe
            continue;
        }

        // Monta o URL para esta parte do caminho
        $url_parcial .= '/' . $parte;
        
        // Limpa o nome do arquivo (remove .php) e formata para exibição
        $nome_exibicao = str_replace(['_', '.php'], [' ', ''], $parte);
        $nome_exibicao = ucwords($nome_exibicao);

        // Mapeamento de nomes para algo mais amigável
        $mapa_nomes = [
            'Dashboard Cad' => 'Painel CAD',
            'Dashboard Coen' => 'Painel COEN',
            'Configurar Semestre' => 'Configurações de Semestre',
            'Gerenciar Turmas' => 'Gerenciar Turmas',
            'Form Aluno' => 'Formulário de Aluno',
            'Form Servidor' => 'Formulário de Servidor',
            'Historico Solicitacoes' => 'Histórico de Solicitações',
            'Relatorio Reprografia' => 'Relatório Reprografia',
            'Dashboard Reprografia' => 'Painel da Reprografia',
            // Adicione outros mapeamentos conforme necessário
        ];
        $nome_exibicao = $mapa_nomes[$nome_exibicao] ?? $nome_exibicao;

        // Adiciona a parte à trilha de migalhas
        // A última parte não será um link
        if ($parte === end($partes)) {
            $migalhas .= ' &raquo; <span>' . htmlspecialchars($nome_exibicao) . '</span>';
        } else {
            $migalhas .= ' &raquo; <a href="' . $url_parcial . '">' . htmlspecialchars($nome_exibicao) . '</a>';
        }
    }

    echo '<nav class="trilha-migalhas">' . $migalhas . '</nav>';
}