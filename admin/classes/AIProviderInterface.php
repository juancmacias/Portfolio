<?php
/**
 * Interfaz para proveedores de IA
 * Define los métodos que deben implementar todos los proveedores de IA
 */
interface AIProviderInterface {
    /**
     * Generar contenido usando el proveedor de IA
     * 
     * @param string $prompt Prompt para generar contenido
     * @param array $options Opciones adicionales (model, max_tokens, temperature, etc.)
     * @return array Array con: content, model, tokens_used, cost_estimated
     * @throws Exception Si hay error en la generación
     */
    public function generate($prompt, $options = []);
    
    /**
     * Obtener el nombre para mostrar del proveedor
     * 
     * @return string Nombre del proveedor
     */
    public function getDisplayName();
    
    /**
     * Obtener los modelos disponibles
     * 
     * @return array Array de modelos con sus características
     */
    public function getAvailableModels();
    
    /**
     * Obtener el costo por token
     * 
     * @return float Costo por token
     */
    public function getCostPerToken();
    
    /**
     * Obtener el máximo de tokens soportado
     * 
     * @return int Máximo de tokens
     */
    public function getMaxTokens();
    
    /**
     * Verificar si el proveedor está disponible y configurado
     * 
     * @return bool True si está disponible
     */
    public function isAvailable();
}
?>