<?php
/**
 * API de Diagnóstico RAG - Para llamadas AJAX
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/config/config.local.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

class RAGHealthAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getDetailedHealth() {
        $health = [
            'database' => ['status' => false, 'details' => ''],
            'tables' => ['status' => false, 'details' => [], 'missing' => []],
            'files' => ['status' => false, 'details' => ''],
            'config' => ['status' => false, 'details' => ''],
            'overall' => 'critical'
        ];
        
        // Test de base de datos
        try {
            $health['database']['status'] = $this->db->testConnection();
            $health['database']['details'] = $health['database']['status'] ? 'Conexión exitosa' : 'Sin conexión';
        } catch (Exception $e) {
            $health['database']['details'] = 'Error: ' . $e->getMessage();
        }
        
        // Test de tablas
        $requiredTables = [
            'chat_prompts',
            'reference_documents', 
            'document_chunks',
            'enhanced_conversations',
            'simple_embeddings',
            'chat_configuration'
        ];
        
        $existingTables = 0;
        foreach ($requiredTables as $table) {
            try {
                $exists = $this->db->fetchOne("SHOW TABLES LIKE ?", [$table]);
                if ($exists) {
                    $health['tables']['details'][] = "$table: ✅ Existe";
                    $existingTables++;
                } else {
                    $health['tables']['details'][] = "$table: ❌ Faltante";
                    $health['tables']['missing'][] = $table;
                }
            } catch (Exception $e) {
                $health['tables']['details'][] = "$table: ❌ Error verificando";
                $health['tables']['missing'][] = $table;
            }
        }
        
        $health['tables']['status'] = $existingTables === count($requiredTables);
        
        // Test de archivos
        $uploadsDir = __DIR__ . '/../uploads/documents/';
        if (!is_dir($uploadsDir)) {
            $health['files']['details'] = 'Directorio no existe';
        } elseif (!is_writable($uploadsDir)) {
            $health['files']['details'] = 'Sin permisos de escritura';
        } else {
            $health['files']['status'] = true;
            $health['files']['details'] = 'Directorio OK';
        }
        
        // Test de configuración
        try {
            $configCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM chat_configuration WHERE is_active = 1");
            $count = $configCount['count'] ?? 0;
            $health['config']['status'] = $count > 0;
            $health['config']['details'] = $count > 0 ? "$count configuraciones activas" : "Sin configuraciones activas";
        } catch (Exception $e) {
            $health['config']['details'] = 'Error: ' . $e->getMessage();
        }
        
        // Estado general
        $healthyComponents = array_filter([
            $health['database']['status'],
            $health['tables']['status'],
            $health['files']['status'],
            $health['config']['status']
        ]);
        
        $healthPercentage = count($healthyComponents) / 4;
        
        if ($healthPercentage >= 1) {
            $health['overall'] = 'excellent';
        } elseif ($healthPercentage >= 0.75) {
            $health['overall'] = 'good';
        } elseif ($healthPercentage >= 0.5) {
            $health['overall'] = 'warning';
        } else {
            $health['overall'] = 'critical';
        }
        
        return $health;
    }
}

try {
    $healthAPI = new RAGHealthAPI();
    $health = $healthAPI->getDetailedHealth();
    
    echo json_encode([
        'success' => true,
        'health' => $health
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}