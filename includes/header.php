<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - <?= SITE_NAME ?> - <?= $pageTitle ?? 'Página' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css?v=<?= ASSET_VERSION ?>">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
<header>
    <div>
        <img src="<?= BASE_URL ?>/img/logo_horizontal_ifsudestemg.png" alt="logomarca do IF">
        <img class="logo-if-mobile" src="<?= BASE_URL ?>/img/logo-if.png" alt="logomarca do IF">
    </div>
    <div class="header-title">
        <h1><?= SITE_NAME ?></h1>
        <hr>
        <?php if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])): ?>
            <div class="user-info">
                <h3>Bem-vindo(a): <?= htmlspecialchars($_SESSION['usuario']['nome'] . (isset($_SESSION['usuario']['sobrenome']) ? ' ' . $_SESSION['usuario']['sobrenome'] : '')) ?></h3>
                <a class="logout" href="<?= BASE_URL ?>/includes/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        <?php endif; ?>
    </div>
</header>
