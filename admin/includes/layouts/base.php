<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= ADMIN_TITLE ?></title>
    <meta name="description" content="<?= ADMIN_DESCRIPTION ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/favicon.png">
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/reset.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/base.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/components.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/layout.css">
    
    <!-- CSS específico de página -->
    <?php if (isset($pageCSS)): ?>
        <?php foreach ($pageCSS as $css): ?>
            <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/<?= $css ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- CSS inline si es necesario -->
    <?php if (isset($inlineCSS)): ?>
        <style><?= $inlineCSS ?></style>
    <?php endif; ?>
</head>
<body class="admin-layout <?= $bodyClass ?? '' ?>">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner" style="display: none;">
        <div class="spinner"></div>
        <div class="loading-text">Cargando...</div>
    </div>
    
    <!-- Header -->
    <?php include ADMIN_ROOT . '/includes/components/header.php'; ?>
    
    <!-- Main Layout -->
    <div class="admin-container">
        <!-- Sidebar -->
        <?php if (!isset($hideSidebar) || !$hideSidebar): ?>
            <?php include ADMIN_ROOT . '/includes/components/sidebar.php'; ?>
        <?php endif; ?>
        
        <!-- Main Content -->
        <main class="main-content <?= isset($hideSidebar) && $hideSidebar ? 'full-width' : '' ?>">
            <!-- Breadcrumb -->
            <?php if (!empty(getBreadcrumb())): ?>
                <?php include ADMIN_ROOT . '/includes/components/breadcrumb.php'; ?>
            <?php endif; ?>
            
            <!-- Flash Messages -->
            <?php include ADMIN_ROOT . '/includes/components/flash-messages.php'; ?>
            
            <!-- Page Header -->
            <?php if (isset($pageHeader) && $pageHeader): ?>
                <div class="page-header">
                    <div class="page-header-content">
                        <?php if (isset($pageTitle)): ?>
                            <h1 class="page-title">
                                <?php if (isset($pageIcon)): ?>
                                    <span class="page-icon"><?= $pageIcon ?></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($pageTitle) ?>
                            </h1>
                        <?php endif; ?>
                        
                        <?php if (isset($pageDescription)): ?>
                            <p class="page-description"><?= htmlspecialchars($pageDescription) ?></p>
                        <?php endif; ?>
                        
                        <?php if (isset($pageActions)): ?>
                            <div class="page-actions">
                                <?= $pageActions ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="page-content">
                <?php include $contentFile; ?>
            </div>
        </main>
    </div>
    
    <!-- Footer -->
    <?php if (!isset($hideFooter) || !$hideFooter): ?>
        <?php include ADMIN_ROOT . '/includes/components/footer.php'; ?>
    <?php endif; ?>
    
    <!-- Modales globales -->
    <?php include ADMIN_ROOT . '/includes/components/modals.php'; ?>
    
    <!-- JavaScript Base -->
    <script src="<?= ASSETS_URL ?>/js/utils.js"></script>
    <script src="<?= ASSETS_URL ?>/js/components.js"></script>
    <script src="<?= ASSETS_URL ?>/js/app.js"></script>
    
    <!-- JavaScript específico de página -->
    <?php if (isset($pageJS)): ?>
        <?php foreach ($pageJS as $js): ?>
            <script src="<?= ASSETS_URL ?>/js/<?= $js ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript inline si es necesario -->
    <?php if (isset($inlineJS)): ?>
        <script><?= $inlineJS ?></script>
    <?php endif; ?>
    
    <!-- Variables globales para JavaScript -->
    <script>
        window.AdminConfig = {
            baseUrl: '<?= ADMIN_URL ?>',
            apiUrl: '<?= API_URL ?>',
            assetsUrl: '<?= ASSETS_URL ?>',
            version: '<?= ADMIN_VERSION ?>',
            isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
            currentUser: <?= isLoggedIn() && isset($_SESSION['admin_user']) ? json_encode($_SESSION['admin_user']) : 'null' ?>
        };
    </script>
</body>
</html>