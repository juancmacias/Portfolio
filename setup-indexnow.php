<?php
/**
 * Script de configuración inicial para IndexNow
 * 
 * Este script genera la clave necesaria para IndexNow y crea el archivo de verificación.
 * Ejecutar UNA VEZ al inicializar el sistema y luego eliminar por seguridad.
 * 
 * Uso:
 * 1. Ejecutar desde navegador: http://tusitio.com/setup-indexnow.php
 * 2. Verificar que se haya creado el archivo de clave en la raíz
 * 3. ELIMINAR este script después de la primera ejecución
 */

// Prevenir ejecución si ya existe la clave
$keyFilePath = __DIR__ . '/admin/config/indexnow-key.txt';
if (file_exists($keyFilePath)) {
    die('
    <html>
    <head>
        <title>IndexNow - Ya configurado</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .alert { padding: 15px; border-radius: 5px; margin: 20px 0; }
            .alert-warning { background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; }
            .alert-success { background-color: #d4edda; border: 1px solid #28a745; color: #155724; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
            ol { line-height: 1.8; }
        </style>
    </head>
    <body>
        <h1>⚠️ IndexNow ya está configurado</h1>
        <div class="alert alert-warning">
            <strong>Atención:</strong> La clave de IndexNow ya existe en el sistema.
        </div>
        <p>Si necesitas regenerar la clave, elimina primero los archivos existentes:</p>
        <ul>
            <li><code>admin/config/indexnow-key.txt</code></li>
            <li><code>{key}.txt</code> (archivo público en la raíz)</li>
        </ul>
        <div class="alert alert-success">
            <strong>Importante:</strong> Por razones de seguridad, elimina este archivo (<code>setup-indexnow.php</code>) de tu servidor.
        </div>
    </body>
    </html>
    ');
}

// Generar clave de 32 caracteres hexadecimales (128 bits)
$key = bin2hex(random_bytes(16));

// Crear directorio config si no existe
$configDir = __DIR__ . '/admin/config';
if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
}

// Guardar clave en archivo de configuración
$saved = file_put_contents($keyFilePath, $key);
if ($saved === false) {
    die('
    <html>
    <head>
        <title>Error - Setup IndexNow</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .alert-danger { background-color: #f8d7da; border: 1px solid #dc3545; color: #721c24; padding: 15px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>❌ Error al crear archivo de configuración</h1>
        <div class="alert-danger">
            No se pudo guardar <code>' . $keyFilePath . '</code>. Verifica los permisos de escritura.
        </div>
    </body>
    </html>
    ');
}

// Cambiar permisos del archivo de clave (solo lectura para el propietario)
chmod($keyFilePath, 0600);

// Crear archivo de verificación público en la raíz
$publicKeyFile = __DIR__ . '/' . $key . '.txt';
$savedPublic = file_put_contents($publicKeyFile, $key);

if ($savedPublic === false) {
    unlink($keyFilePath); // Eliminar archivo de configuración si falla la creación del público
    die('
    <html>
    <head>
        <title>Error - Setup IndexNow</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .alert-danger { background-color: #f8d7da; border: 1px solid #dc3545; color: #721c24; padding: 15px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>❌ Error al crear archivo de verificación público</h1>
        <div class="alert-danger">
            No se pudo crear <code>' . $publicKeyFile . '</code>. Verifica los permisos de escritura en la raíz.
        </div>
    </body>
    </html>
    ');
}

// Verificar accesibilidad del archivo de verificación
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
           '://' . $_SERVER['HTTP_HOST'];
$verificationUrl = $baseUrl . '/' . $key . '.txt';

// Intentar verificar el archivo via HTTP
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);
$response = @file_get_contents($verificationUrl, false, $context);
$httpCode = 200; // Por defecto asumimos éxito

if (isset($http_response_header)) {
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
    $httpCode = isset($matches[1]) ? (int)$matches[1] : 200;
}

$verificationStatus = ($httpCode === 200 && $response === $key) 
    ? '<span style="color: green;">✔ Verificación exitosa</span>' 
    : '<span style="color: orange;">⚠ No se pudo verificar automáticamente (verifica manualmente)</span>';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IndexNow - Configuración Completada</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #28a745;
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #17a2b8;
            color: #0c5460;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
        }
        code {
            background: #f4f4f4;
            padding: 2px 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
        .key-display {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-wrap: break-word;
            margin: 15px 0;
        }
        ol, ul {
            line-height: 2;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .verification-test {
            margin: 20px 0;
            padding: 15px;
            background: #e9ecef;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ IndexNow Configurado Correctamente</h1>
        
        <div class="alert alert-success">
            <strong>¡Éxito!</strong> Se ha generado la clave de IndexNow y los archivos necesarios.
        </div>

        <h2>🔑 Tu clave de IndexNow:</h2>
        <div class="key-display"><?php echo htmlspecialchars($key); ?></div>

        <h2>📁 Archivos creados:</h2>
        <ol>
            <li><strong>Clave privada:</strong> <code>admin/config/indexnow-key.txt</code> (permisos 600)</li>
            <li><strong>Archivo de verificación público:</strong> <code><?php echo htmlspecialchars($key); ?>.txt</code> (raíz del sitio)</li>
        </ol>

        <div class="verification-test">
            <h3>🔍 Verificación de accesibilidad:</h3>
            <p><strong>URL de verificación:</strong> <a href="<?php echo htmlspecialchars($verificationUrl); ?>" target="_blank"><?php echo htmlspecialchars($verificationUrl); ?></a></p>
            <p><strong>Estado:</strong> <?php echo $verificationStatus; ?></p>
        </div>

        <div class="alert alert-info">
            <h3>📋 Próximos pasos:</h3>
            <ol>
                <li>Verifica que puedes acceder a <a href="<?php echo htmlspecialchars($verificationUrl); ?>" target="_blank"><?php echo htmlspecialchars($verificationUrl); ?></a></li>
                <li>El archivo debe mostrar exactamente: <code><?php echo htmlspecialchars($key); ?></code></li>
                <li>Configura tu sitio en <a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster Tools</a></li>
                <li>Usa el sistema de notificaciones desde <code>admin/pages/sitemap-manager.php</code></li>
            </ol>
        </div>

        <div class="alert alert-warning">
            <h3>⚠️ Seguridad - IMPORTANTE:</h3>
            <ol>
                <li><strong>ELIMINA este archivo (<code>setup-indexnow.php</code>) de tu servidor AHORA</strong></li>
                <li>Verifica que <code>admin/config/indexnow-key.txt</code> tenga permisos 600</li>
                <li>Asegúrate de que el archivo de clave esté en <code>.gitignore</code></li>
                <li>NO compartas tu clave de IndexNow públicamente</li>
            </ol>
        </div>

        <div class="alert alert-danger">
            <h3>🔐 Protección de credenciales:</h3>
            <p>Asegúrate de que tu archivo <code>.gitignore</code> incluye:</p>
            <pre><code>admin/config/indexnow-key.txt
/*.txt
!robots.txt</code></pre>
        </div>

        <h2>🌐 Buscadores soportados con esta clave:</h2>
        <ul>
            <li><strong>Bing</strong> (Microsoft)</li>
            <li><strong>Yandex</strong> (Rusia)</li>
            <li><strong>Naver</strong> (Corea del Sur)</li>
            <li><strong>Seznam.cz</strong> (República Checa)</li>
        </ul>

        <p style="margin-top: 30px; text-align: center;">
            <a href="admin/pages/sitemap-manager.php" class="btn">Ir al Gestor de Sitemap</a>
        </p>
    </div>
</body>
</html>
