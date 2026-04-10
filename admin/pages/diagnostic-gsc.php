<?php
/**
 * Diagnóstico del Inspector GSC
 * Script para identificar problemas de configuración
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico Inspector Google Search Console</h1>";
echo "<hr>";

// 1. Verificar archivo de clase
echo "<h2>1. Verificación de Archivos</h2>";
$classFile = __DIR__ . '/../classes/Providers/GoogleSearchConsoleInspector.php';
echo "Buscando: <code>$classFile</code><br>";
if (file_exists($classFile)) {
    echo "✅ <strong>OK:</strong> Archivo de clase existe<br>";
} else {
    echo "❌ <strong>ERROR:</strong> Archivo de clase NO existe<br>";
}

// 2. Verificar credenciales
echo "<h2>2. Verificación de Credenciales</h2>";
$credentialsFile = __DIR__ . '/../config/service-account-credentials.json';
echo "Buscando: <code>$credentialsFile</code><br>";
if (file_exists($credentialsFile)) {
    echo "✅ <strong>OK:</strong> Archivo de credenciales existe<br>";
    $credentials = json_decode(file_get_contents($credentialsFile), true);
    if ($credentials) {
        echo "✅ <strong>OK:</strong> JSON válido<br>";
        echo "Service Account Email: <code>" . ($credentials['client_email'] ?? 'N/A') . "</code><br>";
    } else {
        echo "❌ <strong>ERROR:</strong> JSON inválido<br>";
    }
} else {
    echo "❌ <strong>ERROR:</strong> Archivo de credenciales NO existe<br>";
}

// 3. Verificar vendor/autoload.php
echo "<h2>3. Verificación de Google Client Library</h2>";
$vendorPaths = [
    dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php',
    dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
    dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/vendor/autoload.php'
];

$vendorFound = false;
foreach ($vendorPaths as $i => $path) {
    echo "Path " . ($i + 1) . ": <code>$path</code><br>";
    if (file_exists($path)) {
        echo "✅ <strong>OK:</strong> vendor/autoload.php encontrado en esta ruta<br>";
        $vendorFound = true;
        
        // Intentar requerir
        try {
            require_once $path;
            echo "✅ <strong>OK:</strong> vendor/autoload.php cargado correctamente<br>";
            
            // Verificar si existe la clase de Google
            if (class_exists('Google\\Client')) {
                echo "✅ <strong>OK:</strong> Clase Google\\Client disponible<br>";
            } else {
                echo "❌ <strong>ERROR:</strong> Clase Google\\Client NO disponible<br>";
                echo "Ejecutar: <code>composer require google/apiclient:\"^2.0\"</code><br>";
            }
        } catch (Exception $e) {
            echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
        }
        break;
    }
}

if (!$vendorFound) {
    echo "❌ <strong>ERROR:</strong> vendor/autoload.php no encontrado en ninguna ruta<br>";
    echo "Ejecutar en el directorio del proyecto: <code>composer install</code><br>";
}

// 4. Intentar instanciar la clase
echo "<h2>4. Prueba de Instanciación</h2>";
if (file_exists($classFile)) {
    try {
        require_once $classFile;
        echo "✅ <strong>OK:</strong> Clase GoogleSearchConsoleInspector cargada<br>";
        
        $baseUrl = 'http://www.perfil.in';
        try {
            $inspector = new GoogleSearchConsoleInspector($baseUrl);
            echo "✅ <strong>OK:</strong> Inspector instanciado correctamente<br>";
            echo "✅ <strong>ÉXITO:</strong> Todo funciona correctamente!<br>";
        } catch (Exception $e) {
            echo "❌ <strong>ERROR al instanciar:</strong> " . $e->getMessage() . "<br>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>ERROR al cargar clase:</strong> " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// 5. Verificar logs
echo "<h2>5. Directorio de Logs</h2>";
$logDir = dirname(dirname(dirname(__DIR__))) . '/logs/search-engines';
echo "Directorio: <code>$logDir</code><br>";
if (is_dir($logDir)) {
    echo "✅ <strong>OK:</strong> Directorio de logs existe<br>";
    if (is_writable($logDir)) {
        echo "✅ <strong>OK:</strong> Directorio de logs es escribible<br>";
    } else {
        echo "⚠️ <strong>ADVERTENCIA:</strong> Directorio de logs NO es escribible<br>";
    }
} else {
    echo "⚠️ <strong>ADVERTENCIA:</strong> Directorio de logs NO existe (se creará automáticamente)<br>";
}

echo "<hr>";
echo "<p><strong>Diagnóstico completado.</strong> Revisa los errores marcados con ❌</p>";
?>
