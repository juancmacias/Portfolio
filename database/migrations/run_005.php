<?php
/**
 * Ejecutar migración 005_update_ai_logs_table.sql
 */

// Cargar configuración
require_once __DIR__ . '/../../admin/config/config.local.php';
$dbConfig = get_db_config();

// Conectar a la base de datos
$mysqli = new mysqli(
    $dbConfig['host'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database']
);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

echo "Conectado a la base de datos: {$dbConfig['database']}\n\n";

// Leer archivo de migración
$migrationFile = __DIR__ . '/005_update_ai_logs_table.sql';
$sql = file_get_contents($migrationFile);

// Eliminar comentarios y dividir en statements individuales
$statements = array_filter(
    array_map('trim', 
        preg_split('/;(?=(?:[^"\'`]*["\'][^"\'`]*["\'])*[^"\'`]*$)/m', $sql)
    ),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               $stmt !== '';
    }
);

echo "Ejecutando " . count($statements) . " statements de migración...\n\n";

$success = 0;
$errors = 0;

foreach ($statements as $index => $statement) {
    // Limpiar el statement
    $statement = trim($statement);
    
    // Saltar comentarios
    if (empty($statement) || preg_match('/^--/', $statement)) {
        continue;
    }
    
    echo "Statement " . ($index + 1) . ": ";
    echo substr($statement, 0, 60) . "...\n";
    
    if ($mysqli->query($statement)) {
        echo "✅ Éxito\n\n";
        $success++;
    } else {
        // Verificar si el error es por "IF EXISTS/IF NOT EXISTS" ya aplicado
        if (strpos($mysqli->error, 'Duplicate') !== false || 
            strpos($mysqli->error, 'check that column') !== false ||
            strpos($mysqli->error, 'already exists') !== false ||
            strpos($mysqli->error, "doesn't exist") !== false) {
            echo "⚠️  Ya aplicado o no necesario: " . $mysqli->error . "\n\n";
            $success++;
        } else {
            echo "❌ Error: " . $mysqli->error . "\n\n";
            $errors++;
        }
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "RESUMEN:\n";
echo "✅ Exitosos: $success\n";
echo "❌ Errores: $errors\n";
echo str_repeat('=', 50) . "\n\n";

// Verificar estructura final
echo "Verificando estructura de la tabla ai_logs:\n\n";
$result = $mysqli->query("DESCRIBE ai_logs");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-15s %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
} else {
    echo "Error al verificar tabla: " . $mysqli->error . "\n";
}

$mysqli->close();
echo "\n✅ Migración completada.\n";
?>
