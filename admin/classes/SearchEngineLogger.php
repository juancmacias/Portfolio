<?php
/**
 * SearchEngineLogger
 * Logger específico para notificaciones a buscadores
 * 
 * Genera logs diarios por proveedor en logs/search-engines/
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

class SearchEngineLogger
{
    private $logFile;
    private $provider;
    
    /**
     * Constructor
     * @param string $provider Nombre del proveedor (google, bing, indexnow, notifier)
     */
    public function __construct($provider)
    {
        $this->provider = $provider;
        
        // Directorio de logs - crear si no existe
        $logDir = dirname(dirname(__DIR__)) . '/logs/search-engines';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Archivo de log diario
        $this->logFile = $logDir . '/' . $provider . '_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Registra un mensaje informativo
     * @param string $message Mensaje a registrar
     */
    public function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$this->provider}] {$message}" . PHP_EOL;
        @file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Registra un mensaje de error
     * @param string $message Mensaje de error
     */
    public function error($message)
    {
        $this->log("ERROR: {$message}");
    }
    
    /**
     * Registra un mensaje de éxito
     * @param string $message Mensaje de éxito
     */
    public function success($message)
    {
        $this->log("SUCCESS: {$message}");
    }
    
    /**
     * Obtiene la ruta del archivo de log actual
     * @return string Ruta completa al archivo de log
     */
    public function getLogFile()
    {
        return $this->logFile;
    }
}
