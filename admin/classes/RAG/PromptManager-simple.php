<?php
/**
 * Gestor de Prompts RAG - Versión Simplificada
 * Compatible con esquema de base de datos simple
 */

class PromptManager {
    private $db;
    private $cache = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener prompt por nombre
     */
    public function getPrompt($name) {
        $cacheKey = "prompt_$name";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        try {
            $sql = "SELECT * FROM chat_prompts WHERE name = ? AND is_active = 1 LIMIT 1";
            $prompt = $this->db->fetchOne($sql, [$name]);
            
            if ($prompt) {
                $this->cache[$cacheKey] = $prompt;
                return $prompt;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error obteniendo prompt $name - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener todos los prompts
     */
    public function getAllPrompts() {
        try {
            $sql = "SELECT * FROM chat_prompts ORDER BY created_at DESC";
            $prompts = $this->db->fetchAll($sql);
            return $prompts ?: [];
            
        } catch (Exception $e) {
            error_log("Error obteniendo prompts - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener prompts activos
     */
    public function getActivePrompts() {
        try {
            $sql = "SELECT * FROM chat_prompts WHERE is_active = 1 ORDER BY name";
            $prompts = $this->db->fetchAll($sql);
            return $prompts ?: [];
            
        } catch (Exception $e) {
            error_log("Error obteniendo prompts activos - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crear nuevo prompt
     */
    public function createPrompt($data) {
        try {
            $sql = "INSERT INTO chat_prompts (name, prompt_text, context_prompt, is_active) 
                    VALUES (?, ?, ?, 1)";
            
            $this->db->query($sql, [
                $data['name'],
                $data['prompt_text'],
                $data['description'] ?? '' // Usar description como context_prompt
            ]);
            
            $this->clearCache();
            
            return [
                'success' => true,
                'id' => $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar prompt existente
     */
    public function updatePrompt($id, $data) {
        try {
            $sql = "UPDATE chat_prompts 
                    SET name = ?, prompt_text = ?, context_prompt = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $this->db->query($sql, [
                $data['name'],
                $data['prompt_text'],
                $data['description'] ?? $data['context_prompt'] ?? '',
                $data['is_active'] ?? 1,
                $id
            ]);
            
            $this->clearCache();
            
            return [
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar prompt
     */
    public function deletePrompt($id) {
        try {
            $sql = "DELETE FROM chat_prompts WHERE id = ?";
            $this->db->query($sql, [$id]);
            
            $this->clearCache();
            
            return [
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cambiar estado de un prompt
     */
    public function togglePromptStatus($id) {
        try {
            $sql = "UPDATE chat_prompts SET is_active = 1 - is_active WHERE id = ?";
            $this->db->query($sql, [$id]);
            
            $this->clearCache();
            
            return [
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Construir prompt final reemplazando variables
     */
    public function buildFinalPrompt($promptData, $variables = []) {
        $finalPrompt = $promptData['prompt_text'];
        
        // Agregar contexto si existe
        if (!empty($promptData['context_prompt'])) {
            $finalPrompt = $promptData['context_prompt'] . "\n\n" . $finalPrompt;
        }
        
        // Reemplazar variables básicas
        $defaultVariables = [
            'context' => $variables['context'] ?? '',
            'user_name' => $variables['user_name'] ?? 'Usuario',
            'user_message' => $variables['user_message'] ?? '',
            'portfolio_data' => $variables['portfolio_data'] ?? '',
            'conversation_history' => $variables['conversation_history'] ?? '',
            'timestamp' => date('Y-m-d H:i:s'),
            'model_used' => $variables['model_used'] ?? 'groq',
            'language' => $variables['language'] ?? 'es',
            'session_id' => $variables['session_id'] ?? uniqid()
        ];
        
        // Reemplazar todas las variables
        foreach ($defaultVariables as $key => $value) {
            $finalPrompt = str_replace('{' . $key . '}', $value, $finalPrompt);
        }
        
        // Reemplazar variables adicionales proporcionadas
        foreach ($variables as $key => $value) {
            if (!isset($defaultVariables[$key])) {
                $finalPrompt = str_replace('{' . $key . '}', $value, $finalPrompt);
            }
        }
        
        return $finalPrompt;
    }
    
    /**
     * Obtener prompt por ID
     */
    public function getPromptById($id) {
        try {
            $sql = "SELECT * FROM chat_prompts WHERE id = ?";
            return $this->db->fetchOne($sql, [$id]);
            
        } catch (Exception $e) {
            error_log("Error obteniendo prompt por ID $id - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Limpiar caché
     */
    private function clearCache() {
        $this->cache = [];
    }
    
    /**
     * Obtener estadísticas de prompts
     */
    public function getStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    COUNT(DISTINCT name) as unique_names
                FROM chat_prompts
            ";
            
            return $this->db->fetchOne($sql) ?: [
                'total' => 0,
                'active' => 0,
                'unique_names' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas de prompts - " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'unique_names' => 0
            ];
        }
    }
}
?>