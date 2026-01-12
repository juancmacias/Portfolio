<?php
/**
 * Ejecutor de Migraciones de Base de Datos
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../config/config.local.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Ejecutor de Migraciones RAG</h1>";
echo "<pre>";

$db = Database::getInstance();
$migrationsDir = __DIR__ . '/../../../database/migrations/';

// Obtener parámetro de migración específica
$migration = $_GET['run'] ?? null;

if ($migration) {
    // Ejecutar migración específica
    $migrationFile = $migrationsDir . $migration . '.sql';
    
    if (!file_exists($migrationFile)) {
        echo "❌ Archivo de migración no encontrado: $migration.sql\n";
        exit;
    }
    
    echo "Ejecutando migración: $migration.sql\n";
    echo str_repeat('=', 60) . "\n\n";
    
    try {
        $sql = file_get_contents($migrationFile);
        
        // Dividir por ; para ejecutar múltiples queries
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       strpos($stmt, '--') !== 0 && 
                       strpos($stmt, 'USE ') !== 0;
            }
        );
        
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            
            try {
                $db->execute($statement);
                echo "✅ Ejecutado: " . substr($statement, 0, 60) . "...\n";
            } catch (Exception $e) {
                // Ignorar errores de "ya existe" o "no existe"
                if (strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "⚠️  Ya existe (omitiendo): " . substr($statement, 0, 60) . "...\n";
                } else {
                    echo "❌ Error: " . $e->getMessage() . "\n";
                    echo "   Query: " . substr($statement, 0, 100) . "...\n";
                }
            }
        }
        
        echo "\n✅ Migración completada\n";
        
    } catch (Exception $e) {
        echo "❌ Error ejecutando migración: " . $e->getMessage() . "\n";
    }
    
    echo "\n<a href='check-db.php'>🔍 Verificar Base de Datos</a> | ";
    echo "<a href='run-migration.php'>← Volver</a>";
    
} else {
    // Listar migraciones disponibles
    echo "Migraciones disponibles:\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $files = glob($migrationsDir . '*.sql');
    
    if (empty($files)) {
        echo "❌ No se encontraron archivos de migración\n";
    } else {
        foreach ($files as $file) {
            $filename = basename($file, '.sql');
            echo "📄 $filename\n";
            echo "   <a href='?run=$filename'>▶️ Ejecutar</a>\n\n";
        }
    }
    
    echo "\n<a href='dashboard.php'>← Volver al Dashboard</a>";
}

echo "</pre>";
