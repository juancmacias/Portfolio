<?php
/**
 * Script de verificación y corrección de base de datos RAG
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../config/config.local.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Verificación Base de Datos RAG</h1>";
echo "<pre>";

$db = Database::getInstance();

// Verificar tabla enhanced_conversations
echo "\n=== Verificando tabla enhanced_conversations ===\n";
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'enhanced_conversations'");
    if ($result) {
        echo "✅ Tabla enhanced_conversations existe\n\n";
        
        // Ver estructura
        echo "Estructura de la tabla:\n";
        $columns = $db->fetchAll("DESCRIBE enhanced_conversations");
        foreach ($columns as $col) {
            echo sprintf("  - %-20s %-15s %s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'] == 'NO' ? 'NOT NULL' : 'NULLABLE'
            );
        }
        
        // Contar registros
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM enhanced_conversations");
        echo "\n📊 Total de conversaciones: " . $count['total'] . "\n";
        
        if ($count['total'] > 0) {
            // Mostrar últimas 5
            echo "\nÚltimas 5 conversaciones:\n";
            $recent = $db->fetchAll("
                SELECT session_id, 
                       LEFT(user_message, 50) as message_preview,
                       created_at
                FROM enhanced_conversations 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            foreach ($recent as $conv) {
                echo sprintf("  [%s] Session: %s - %s...\n", 
                    $conv['created_at'], 
                    substr($conv['session_id'], 0, 8),
                    $conv['message_preview']
                );
            }
        }
        
    } else {
        echo "❌ Tabla enhanced_conversations NO EXISTE\n";
        echo "\n🔧 ¿Ejecutar migración? (corre el script de migración)\n";
        echo "Archivo: database/migrations/002_create_rag_tables.sql\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Verificar otras tablas RAG
echo "\n\n=== Verificando otras tablas RAG ===\n";
$ragTables = [
    'chat_prompts',
    'reference_documents',
    'document_chunks',
    'simple_embeddings',
    'chat_configuration'
];

foreach ($ragTables as $table) {
    try {
        $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM $table");
        echo sprintf("%-25s %s (%d registros)\n", 
            $table, 
            $result ? '✅' : '❌',
            $count['total'] ?? 0
        );
    } catch (Exception $e) {
        echo sprintf("%-25s ❌ Error\n", $table);
    }
}

echo "\n</pre>";
echo "<p><a href='dashboard.php'>← Volver al Dashboard</a></p>";
