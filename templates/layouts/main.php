<?php
/**
 * ============================================
 * Main Layout Template
 * ============================================
 * 
 * Base HTML template for all pages.
 * 
 * Variables expected:
 * - $title: Page title
 * - $styles: Additional CSS files (array)
 * - $scripts: Additional JS files (array)
 * - $content: Main page content
 * 
 * ============================================
 */

$title = $title ?? 'Prompt Manager';
$styles = $styles ?? [];
$scripts = $scripts ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $basePath ?? '' ?>/logoPM.png">
    
    <!-- Common Styles -->
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>/public/assets/css/common.css">
    
    <!-- Page-specific Styles -->
    <?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
    
    <!-- Inline Styles -->
    <?php if (!empty($inlineStyles)): ?>
    <style><?= $inlineStyles ?></style>
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
    
    <?php 
    // Include header if exists
    if (!empty($showHeader) && file_exists(__DIR__ . '/../partials/header.php')) {
        include __DIR__ . '/../partials/header.php';
    }
    ?>
    
    <!-- Main Content -->
    <main id="main-content">
        <?= $content ?? '' ?>
    </main>
    
    <?php 
    // Include footer if exists
    if (!empty($showFooter) && file_exists(__DIR__ . '/../partials/footer.php')) {
        include __DIR__ . '/../partials/footer.php';
    }
    ?>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Common Scripts -->
    <script src="<?= $basePath ?? '' ?>/public/assets/js/common.js"></script>
    
    <!-- Page-specific Scripts -->
    <?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
    
    <!-- Inline Scripts -->
    <?php if (!empty($inlineScripts)): ?>
    <script><?= $inlineScripts ?></script>
    <?php endif; ?>
</body>
</html>

