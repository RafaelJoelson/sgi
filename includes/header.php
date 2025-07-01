<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= $pageTitle ?? 'Página' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles.css">
</head>
<body>
<header>
    <div>
        <img src="<?= BASE_URL ?>/img/logo_horizontal_ifsudestemg.png" alt="logomarca do IF">
    </div>
    <div class="header-title">
        <h1><?= SITE_NAME ?></h1>
        <hr>
        <?php if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])): ?>
            <div class="user-info">
                <h4>Bem-vindo: <?= htmlspecialchars($_SESSION['usuario']['nome']) ?></h4>
                <a href="<?= BASE_URL ?>/includes/logout.php"><h4>SAIR</h4></a>
            </div>
        <?php endif; ?>
    </div>
</header>
