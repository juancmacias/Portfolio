<?php
/**
 * ========================================
 * TEST DE RUTAS - DEBUG
 * ========================================
 * 
 * Archivo temporal para verificar que las rutas
 * y el menú se están generando correctamente.
 * 
 * ⚠️ ELIMINAR después del debug
 * ========================================
 */

session_start();

// Verificar autenticación (igual que otros archivos del admin)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../includes/config.php';

echo "<h2>🔧 Debug de Rutas y Menú</h2>";

echo "<h3>📍 Rutas definidas:</h3>";
echo "<pre>";
print_r($routes);
echo "</pre>";

echo "<h3>🗂️ Menú de navegación:</h3>";
echo "<pre>";
print_r($navigationMenu);
echo "</pre>";

echo "<h3>🔗 URLs generadas:</h3>";
foreach (['dashboard', 'projects', 'articles', 'sitemap-manager', 'settings'] as $route) {
    echo "<p><strong>$route:</strong> " . getRoute($route) . "</p>";
}

echo "<h3>📂 Archivos en /pages/:</h3>";
$pagesDir = __DIR__;
$files = scandir($pagesDir);
echo "<ul>";
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";
?>