<?php
/**
 * Diagnóstico del Menú - Inspector GSC
 */

define('ADMIN_ACCESS', true);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración y autenticación
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

echo "<h1>Diagnóstico del Menú - Inspector GSC</h1>";
echo "<hr>";

echo "<h2>1. Verificación de Rutas</h2>";
echo "<p>Rutas registradas en \$routes:</p>";
echo "<pre>";
print_r($routes);
echo "</pre>";

echo "<h3>¿Existe 'gsc-inspector'?</h3>";
if (isset($routes['gsc-inspector'])) {
    echo "✅ <strong>OK:</strong> Ruta 'gsc-inspector' existe<br>";
    echo "Valor: <code>" . $routes['gsc-inspector'] . "</code><br>";
    echo "URL completa: <code>" . getRoute('gsc-inspector') . "</code><br>";
} else {
    echo "❌ <strong>ERROR:</strong> Ruta 'gsc-inspector' NO existe<br>";
    echo "<strong>SOLUCIÓN:</strong> Agregar en admin/includes/config.php:<br>";
    echo "<pre>'gsc-inspector' => '/pages/gsc-inspector.php',</pre>";
}

echo "<h2>2. Verificación del Menú de Navegación</h2>";
echo "<p>Contenido de \$navigationMenu:</p>";
echo "<pre>";
print_r($navigationMenu);
echo "</pre>";

echo "<h3>¿Existe 'Herramientas SEO' → 'Inspector GSC'?</h3>";
$foundMenu = false;
foreach ($navigationMenu as $item) {
    if ($item['title'] === 'Herramientas SEO' && isset($item['children'])) {
        echo "✅ Encontrado menú 'Herramientas SEO'<br>";
        echo "<ul>";
        foreach ($item['children'] as $child) {
            echo "<li>" . htmlspecialchars($child['title']) . " → " . htmlspecialchars($child['url']) . "</li>";
            if ($child['title'] === 'Inspector GSC') {
                $foundMenu = true;
                echo "✅ <strong>Encontrado 'Inspector GSC'</strong>";
            }
        }
        echo "</ul>";
    }
}

if (!$foundMenu) {
    echo "<br>❌ <strong>ERROR:</strong> No se encontró el item 'Inspector GSC' en el menú<br>";
    echo "<strong>SOLUCIÓN:</strong> Agregar en admin/includes/config.php en la sección 'Herramientas SEO':<br>";
    echo "<pre>";
    echo htmlspecialchars("[
    'title' => 'Inspector GSC',
    'url' => getRoute('gsc-inspector'),
    'active' => ['gsc-inspector']
],");
    echo "</pre>";
}

echo "<h2>3. Verificación del Archivo de Página</h2>";
$pageFile = __DIR__ . '/gsc-inspector.php';
echo "Buscando: <code>$pageFile</code><br>";
if (file_exists($pageFile)) {
    echo "✅ <strong>OK:</strong> Archivo de página existe<br>";
} else {
    echo "❌ <strong>ERROR:</strong> Archivo de página NO existe<br>";
}

echo "<hr>";
echo "<h2>Resumen</h2>";
if (isset($routes['gsc-inspector']) && $foundMenu && file_exists($pageFile)) {
    echo "✅ <strong>TODO OK:</strong> El menú debería mostrarse correctamente<br>";
    echo "<p><a href='" . getRoute('gsc-inspector') . "' class='btn btn-primary'>Ir al Inspector GSC</a></p>";
} else {
    echo "❌ <strong>REQUIERE ACCIÓN:</strong> Revisa los errores marcados arriba<br>";
    echo "<p><strong>Acción recomendada:</strong> Subir el archivo <code>admin/includes/config.php</code> actualizado al servidor.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
code { background: #e8e8e8; padding: 2px 6px; border-radius: 3px; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
.btn:hover { background: #0056b3; }
</style>
