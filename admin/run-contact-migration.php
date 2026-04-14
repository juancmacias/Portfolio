<?php
/**
 * Script Helper para ejecutar la migración de contact_submissions
 * Ejecutar SOLO UNA VEZ desde el navegador o CLI
 * 
 * Uso: 
 * - Navegador: http://localhost/admin/run-contact-migration.php
 * - CLI: php admin/run-contact-migration.php
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>Ejecutando migración 006_create_contact_submissions.sql</h2>\n";
    
    // Leer archivo de migración
    $sqlFile = __DIR__ . '/../database/migrations/006_create_contact_submissions.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo de migración no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en statements individuales (separados por ;)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "<p>Total de statements a ejecutar: " . count($statements) . "</p>\n";
    
    $successCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;
        
        try {
            echo "<p>Ejecutando statement " . ($index + 1) . "...</p>\n";
            $db->execute($statement);
            $successCount++;
            echo "<p style='color: green;'>✅ Ejecutado correctamente</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Advertencia: " . $e->getMessage() . "</p>\n";
            // No lanzar excepción, continuar con el siguiente statement
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Migración completada</h3>";
    echo "<p>Statements ejecutados: $successCount/" . count($statements) . "</p>";
    
    // Verificar que la tabla existe
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'contact_submissions'");
    if ($tableExists) {
        echo "<p style='color: green;'>✅ Tabla 'contact_submissions' creada correctamente</p>";
        
        // Obtener estructura de la tabla
        $columns = $db->fetchAll("DESCRIBE contact_submissions");
        echo "<h4>Estructura de la tabla:</h4>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ ERROR: La tabla 'contact_submissions' NO fue creada</p>";
    }
    
    // Verificar configuración del sistema
    $configs = $db->fetchAll("SELECT * FROM system_config WHERE config_key LIKE 'contact_%'");
    if (!empty($configs)) {
        echo "<h4>Configuración del sistema:</h4>";
        echo "<ul>";
        foreach ($configs as $config) {
            echo "<li><strong>" . htmlspecialchars($config['config_key']) . ":</strong> " 
                 . htmlspecialchars($config['config_value']) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><a href='pages/contact-submissions.php'>👉 Ir al panel de administración de contactos</a></p>";
    echo "<p><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (run-contact-migration.php) después de ejecutarlo</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error durante la migración</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
