<?php
/**
 * Test diagnóstico completo del endpoint contact-submit.php
 * Acceder desde: /api/portfolio/diagnostic-contact.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Contact Form API</title>
    <style>
        body{font-family:monospace;padding:20px;max-width:900px;margin:0 auto;background:#f9f9f9;}
        h1{color:#333;border-bottom:3px solid #667eea;padding-bottom:10px;}
        h2{color:#667eea;margin-top:30px;}
        pre{background:#fff;padding:15px;border-radius:5px;overflow-x:auto;border:1px solid #ddd;}
        .success{color:green;background:#e8f5e9;padding:10px;margin:5px 0;border-radius:5px;}
        .error{color:red;background:#ffebee;padding:10px;margin:5px 0;border-radius:5px;}
        .info{color:#1976d2;background:#e3f2fd;padding:10px;margin:5px 0;border-radius:5px;}
        .warning{color:#f57c00;background:#fff3e0;padding:10px;margin:5px 0;border-radius:5px;}
        button{background:#667eea;color:white;border:none;padding:12px 24px;border-radius:5px;cursor:pointer;font-size:16px;margin:10px 5px;}
        button:hover{background:#5568d3;}
        .test-section{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
    </style>
</head>
<body>
    <h1>🧪 Diagnóstico Completo - Contact Form API</h1>
    
    <?php
    // Test 1: Verificar archivos existen
    echo "<div class='test-section'>";
    echo "<h2>1️⃣ Verificación de Archivos del Sistema</h2>";
    $files = [
        'Endpoint contact-submit.php' => __DIR__ . '/contact-submit.php',
        'Database Config' => __DIR__ . '/../../admin/config/database.php',
        'Config Local' => __DIR__ . '/../../admin/config/config.local.php',
        'Telegram Notifier' => __DIR__ . '/../../admin/classes/TelegramNotifier.php'
    ];

    $allFilesOk = true;
    foreach ($files as $name => $path) {
        $exists = file_exists($path);
        if (!$exists) $allFilesOk = false;
        $class = $exists ? 'success' : 'error';
        $icon = $exists ? '✅' : '❌';
        echo "<div class='$class'>$icon <strong>$name:</strong> " . ($exists ? 'Existe' : 'NO ENCONTRADO - ' . $path) . "</div>";
    }
    echo "</div>";

    // Test 2: Verificar conexión a base de datos
    echo "<div class='test-section'>";
    echo "<h2>2️⃣ Conexión a Base de Datos</h2>";
    $dbOk = false;
    try {
        if (!defined('ADMIN_ACCESS')) {
            define('ADMIN_ACCESS', true);
        }
        require_once __DIR__ . '/../../admin/config/database.php';
        $db = Database::getInstance();
        echo "<div class='success'>✅ <strong>Conexión DB:</strong> Exitosa</div>";
        $dbOk = true;
        
        // Verificar tabla
        $tableExists = $db->fetchOne("SHOW TABLES LIKE 'contact_submissions'");
        if ($tableExists) {
            echo "<div class='success'>✅ <strong>Tabla contact_submissions:</strong> Existe</div>";
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM contact_submissions")['count'];
            echo "<div class='info'>📊 <strong>Registros totales:</strong> $count</div>";
            
            // Mostrar estructura de tabla
            $columns = $db->fetchAll("DESCRIBE contact_submissions");
            echo "<div class='info'><strong>📋 Estructura de la tabla:</strong></div>";
            echo "<pre>";
            foreach ($columns as $col) {
                echo $col['Field'] . " - " . $col['Type'] . " " . ($col['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
            }
            echo "</pre>";
        } else {
            echo "<div class='error'>❌ <strong>Tabla contact_submissions:</strong> NO EXISTE - Necesitas ejecutar la migración</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ <strong>Error DB:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='info'><strong>Stack trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
    }
    echo "</div>";

    // Test 3: Verificar permisos de escritura
    echo "<div class='test-section'>";
    echo "<h2>3️⃣ Permisos de Escritura</h2>";
    $testFile = __DIR__ . '/../../logs/test_write.txt';
    $logsDir = dirname($testFile);
    
    if (!is_dir($logsDir)) {
        echo "<div class='warning'>⚠️ <strong>Directorio logs/:</strong> No existe - Intentando crear...</div>";
        @mkdir($logsDir, 0755, true);
    }
    
    if (@file_put_contents($testFile, 'test')) {
        echo "<div class='success'>✅ <strong>Escritura en logs/:</strong> OK</div>";
        @unlink($testFile);
    } else {
        echo "<div class='error'>❌ <strong>Escritura en logs/:</strong> SIN PERMISOS</div>";
    }
    echo "</div>";

    // Test 4: Intentar incluir el endpoint y ver errores
    echo "<div class='test-section'>";
    echo "<h2>4️⃣ Análisis del Endpoint</h2>";
    $endpointPath = __DIR__ . '/contact-submit.php';
    if (file_exists($endpointPath)) {
        $content = file_get_contents($endpointPath);
        $lines = explode("\n", $content);
        echo "<div class='info'>📄 <strong>Líneas de código:</strong> " . count($lines) . "</div>";
        
        // Verificar sintaxis PHP
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($endpointPath) . " 2>&1", $output, $return_var);
        if ($return_var === 0) {
            echo "<div class='success'>✅ <strong>Sintaxis PHP:</strong> Válida</div>";
        } else {
            echo "<div class='error'>❌ <strong>Sintaxis PHP:</strong> Errores encontrados<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre></div>";
        }
        
        // Mostrar primeras líneas
        echo "<div class='info'><strong>🔍 Primeras 15 líneas del archivo:</strong></div>";
        echo "<pre>" . htmlspecialchars(implode("\n", array_slice($lines, 0, 15))) . "</pre>";
    }
    echo "</div>";

    // Test 5: Enviar solicitud de prueba SOLO SI TODO ESTÁ OK
    if ($dbOk && $allFilesOk) {
        echo "<div class='test-section'>";
        echo "<h2>5️⃣ Test de Envío Real</h2>";
        
        echo "<button onclick='testSubmit()'>🚀 Enviar Petición de Prueba</button>";
        echo "<button onclick='location.reload()'>🔄 Recargar Página</button>";
        
        echo "<div id='test-result' style='margin-top:20px;'></div>";
        
        echo "<script>
        async function testSubmit() {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<div class=\"info\">⏳ Enviando petición...</div>';
            
            const testData = {
                name: 'Test Usuario Diagnóstico',
                email: 'diagnostico@test.com',
                phone: '612345678',
                message: 'Mensaje de prueba desde diagnóstico - ' + new Date().toISOString(),
                website: ''
            };
            
            try {
                const url = window.location.origin + '/api/portfolio/contact-submit.php';
                resultDiv.innerHTML += '<div class=\"info\">📡 <strong>URL:</strong> ' + url + '</div>';
                resultDiv.innerHTML += '<div class=\"info\">📤 <strong>Datos enviados:</strong><pre>' + JSON.stringify(testData, null, 2) + '</pre></div>';
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });
                
                resultDiv.innerHTML += '<div class=\"info\">📊 <strong>Status Code:</strong> ' + response.status + ' ' + response.statusText + '</div>';
                
                const contentType = response.headers.get('content-type');
                resultDiv.innerHTML += '<div class=\"info\">📋 <strong>Content-Type:</strong> ' + contentType + '</div>';
                
                const text = await response.text();
                resultDiv.innerHTML += '<div class=\"info\">📥 <strong>Response Raw:</strong><pre>' + text.substring(0, 500) + (text.length > 500 ? '...' : '') + '</pre></div>';
                
                try {
                    const json = JSON.parse(text);
                    if (response.ok) {
                        resultDiv.innerHTML += '<div class=\"success\">✅ <strong>Respuesta exitosa:</strong><pre>' + JSON.stringify(json, null, 2) + '</pre></div>';
                    } else {
                        resultDiv.innerHTML += '<div class=\"error\">❌ <strong>Error del servidor:</strong><pre>' + JSON.stringify(json, null, 2) + '</pre></div>';
                    }
                } catch (e) {
                    resultDiv.innerHTML += '<div class=\"error\">❌ <strong>Error parseando JSON:</strong> ' + e.message + '</div>';
                }
                
            } catch (error) {
                resultDiv.innerHTML += '<div class=\"error\">❌ <strong>Error de red:</strong> ' + error.message + '</div>';
            }
        }
        </script>";
        
        echo "</div>";
    } else {
        echo "<div class='test-section'>";
        echo "<div class='error'>⚠️ <strong>Test de envío deshabilitado:</strong> Corrige primero los errores de archivos o base de datos</div>";
        echo "</div>";
    }

    // Test 6: Verificar logs de errores
    echo "<div class='test-section'>";
    echo "<h2>6️⃣ Logs de Errores del Servidor</h2>";
    $logPaths = [
        __DIR__ . '/../../logs/php_errors.log',
        __DIR__ . '/../../error_log',
        __DIR__ . '/../error_log',
        ini_get('error_log')
    ];

    $logFound = false;
    foreach ($logPaths as $fullPath) {
        if ($fullPath && file_exists($fullPath)) {
            $logFound = true;
            echo "<div class='success'>✅ <strong>Log encontrado:</strong> $fullPath</div>";
            $lines = @file($fullPath);
            if ($lines) {
                $lastLines = array_slice($lines, -15);
                echo "<div class='info'><strong>📝 Últimas 15 líneas:</strong></div>";
                echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
            }
            break;
        }
    }

    if (!$logFound) {
        echo "<div class='warning'>⚠️ No se encontró archivo de log PHP en las rutas comunes</div>";
        echo "<div class='info'><strong>Rutas buscadas:</strong><pre>" . implode("\n", $logPaths) . "</pre></div>";
    }
    echo "</div>";

    // Información del sistema
    echo "<div class='test-section'>";
    echo "<h2>7️⃣ Información del Sistema</h2>";
    echo "<div class='info'>🐘 <strong>PHP Version:</strong> " . PHP_VERSION . "</div>";
    echo "<div class='info'>🌐 <strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
    echo "<div class='info'>📂 <strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>";
    echo "<div class='info'>🔧 <strong>display_errors:</strong> " . ini_get('display_errors') . "</div>";
    echo "<div class='info'>📝 <strong>log_errors:</strong> " . ini_get('log_errors') . "</div>";
    echo "<div class='info'>⏰ <strong>Timezone:</strong> " . date_default_timezone_get() . "</div>";
    
    $extensions = ['curl', 'pdo', 'pdo_mysql', 'json', 'mbstring'];
    echo "<div class='info'><strong>🔌 Extensiones PHP:</strong><br>";
    foreach ($extensions as $ext) {
        $loaded = extension_loaded($ext);
        echo ($loaded ? '✅' : '❌') . " $ext<br>";
    }
    echo "</div>";
    echo "</div>";
    ?>
    
    <hr>
    <p style="text-align:center;color:#666;">
        <em>Diagnóstico completado: <?php echo date('Y-m-d H:i:s'); ?></em><br>
        <small>Archivo: <?php echo __FILE__; ?></small>
    </p>
</body>
</html>
