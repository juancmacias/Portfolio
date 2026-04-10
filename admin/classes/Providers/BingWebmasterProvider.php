<?php
/**
 * BingWebmasterProvider
 * Envía URLs a Bing Webmaster Tools API oficial
 * 
 * Requiere:
 * - Cuenta en Bing Webmaster Tools
 * - API Key generada desde el panel
 * - Archivo admin/config/bing-api-key.txt
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

class BingWebmasterProvider
{
    private $apiKeyPath;
    private $baseUrl;
    private $logger;
    
    /**
     * Constructor
     * @param string $baseUrl URL base del sitio (ej: https://www.juancarlosmacias.es)
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKeyPath = dirname(dirname(__DIR__)) . '/config/bing-api-key.txt';
        
        require_once dirname(__DIR__) . '/SearchEngineLogger.php';
        $this->logger = new SearchEngineLogger('bing');
    }
    
    /**
     * Notifica lista de URLs a Bing
     * @param array $urls Lista de URLs completas
     * @return array Resultado de la operación
     */
    public function notifyUrls($urls)
    {
        try {
            $this->logger->log('=== STARTING BING NOTIFICATION ===');
            
            // 1. Verificar API Key
            if (!file_exists($this->apiKeyPath)) {
                throw new Exception('Bing API Key not found. Create: admin/config/bing-api-key.txt');
            }
            
            $apiKey = trim(file_get_contents($this->apiKeyPath));
            
            if (empty($apiKey)) {
                throw new Exception('Bing API Key is empty');
            }
            
            $this->logger->log('API Key loaded successfully');
            
            // 2. Dividir en lotes (máximo 10,000 URLs por batch según Bing API)
            $chunks = array_chunk($urls, 10000);
            $results = [];
            
            $this->logger->log('Total URLs: ' . count($urls));
            $this->logger->log('Batches to send: ' . count($chunks));
            
            foreach ($chunks as $index => $chunk) {
                $batchNum = $index + 1;
                $this->logger->log("Sending batch {$batchNum}/" . count($chunks) . " with " . count($chunk) . " URLs");
                
                $result = $this->sendBatch($chunk, $apiKey);
                $results[] = $result;
                
                // Pausa entre lotes
                if ($batchNum < count($chunks)) {
                    usleep(500000); // 0.5 segundos
                }
            }
            
            // 3. Consolidar resultados
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            
            $this->logger->log("Batches succeeded: {$successCount}/" . count($results));
            $this->logger->success('Bing notification completed');
            
            return [
                'success' => $successCount > 0,
                'provider' => 'Bing Webmaster',
                'batches_sent' => count($results),
                'batches_success' => $successCount,
                'total_urls' => count($urls),
                'message' => $successCount > 0
                    ? "✓ Bing: {$successCount} lotes enviados correctamente"
                    : "✗ Bing: Error en todos los lotes",
                'details' => $results
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Bing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'Bing Webmaster',
                'message' => '✗ Bing: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía un lote a Bing API
     * @param array $urls Lista de URLs del lote
     * @param string $apiKey API Key de Bing
     * @return array Resultado del envío
     */
    private function sendBatch($urls, $apiKey)
    {
        // Endpoint: SubmitUrlbatch con API key en query string
        $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' 
                    . urlencode($apiKey);
        
        $requestBody = json_encode([
            'siteUrl' => $this->baseUrl,
            'urlList' => $urls
        ]);
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $this->logger->log("Bing API Response: HTTP {$httpCode} - {$response}");
        
        if ($error) {
            $this->logger->error("cURL Error: {$error}");
        }
        
        // Bing responde con 200 y {"d": null} en éxito
        $success = ($httpCode === 200);
        
        if ($success) {
            $this->logger->log('Batch sent successfully');
        } else {
            $this->logger->error("Batch failed with HTTP {$httpCode}");
        }
        
        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error,
            'urls_count' => count($urls)
        ];
    }
}
