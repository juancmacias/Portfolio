<?php
/**
 * Debug GSC Inspector - Ver errores exactos
 */

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

define('ADMIN_ACCESS', true);

echo "<h1>Debug: Inspector GSC</h1>";
echo "<hr>";

// Paso 1: Verificar autenticación
echo "<h2>1. Autenticación</h2>";
try {
    require_once __DIR__ . '/../config/auth.php';
    $auth = new AdminAuth();
    if (!$auth->isLoggedIn()) {
        echo "❌ No autenticado - redirigiendo...<br>";
        header('Location: login.php');
        exit();
    }
    echo "✅ Autenticado correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error en auth: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit();
}

// Paso 2: Cargar config
echo "<h2>2. Configuración</h2>";
try {
    require_once __DIR__ . '/../includes/config.php';
    echo "✅ Config cargado<br>";
    
    if (isset($navigationMenu)) {
        echo "✅ navigationMenu existe<br>";
    } else {
        echo "⚠️ navigationMenu NO definido<br>";
    }
} catch (Exception $e) {
    echo "❌ Error en config: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Paso 3: Verificar clase
echo "<h2>3. Clase GoogleSearchConsoleInspector</h2>";
$classFile = __DIR__ . '/../classes/Providers/GoogleSearchConsoleInspector.php';
echo "Ruta: <code>$classFile</code><br>";

if (file_exists($classFile)) {
    echo "✅ Archivo existe<br>";
    try {
        require_once $classFile;
        echo "✅ Archivo cargado sin errores de sintaxis<br>";
        
        if (class_exists('GoogleSearchConsoleInspector')) {
            echo "✅ Clase GoogleSearchConsoleInspector existe<br>";
        } else {
            echo "❌ Clase GoogleSearchConsoleInspector NO existe después de cargar archivo<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error al cargar archivo: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "❌ Archivo NO existe<br>";
}

// Paso 4: Intentar instanciar
echo "<h2>4. Instanciación</h2>";
if (class_exists('GoogleSearchConsoleInspector')) {
    try {
        $baseUrl = 'https://www.juancarlosmacias.es';
        echo "BaseURL: <code>$baseUrl</code><br>";
        
        $inspector = new GoogleSearchConsoleInspector($baseUrl);
        echo "✅ Inspector instanciado correctamente<br>";
        
        // Intentar llamar un método
        try {
            $verified = $inspector->isSiteVerified();
            echo "✅ Método isSiteVerified() funciona<br>";
            echo "Sitio verificado: " . ($verified ? 'Sí' : 'No') . "<br>";
        } catch (Exception $e) {
            echo "⚠️ Error en método: " . $e->getMessage() . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error al instanciar: " . $e->getMessage() . "<br>";
        echo "<strong>Archivo:</strong> " . $e->getFile() . " <strong>Línea:</strong> " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Paso 5: Verificar headers
echo "<h2>5. Headers HTML</h2>";
try {
    require_once __DIR__ . '/../includes/components/header.php';
    echo "✅ Header cargado correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error en header: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>Resumen</h2>";
echo "<p>Si todos los pasos anteriores tienen ✅, entonces el problema es en el HTML del gsc-inspector.php</p>";
echo "<p><a href='gsc-inspector.php'>Intentar ir al Inspector GSC</a></p>";
?>
