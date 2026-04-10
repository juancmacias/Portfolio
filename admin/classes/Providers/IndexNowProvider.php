<?php
/**
 * IndexNowProvider
 * Notifica URLs a múltiples buscadores simultáneamente vía IndexNow API
 * 
 * Compatible con:
 * - Bing (Microsoft)
 * - Yandex (Rusia)
 * - Naver (Corea del Sur)
 * - Seznam.cz (República Checa)
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

class IndexNowProvider
{
    private $keyPath;
    private $webRoot;
    private $baseUrl;
    private $logger;
    
    /**
     * Constructor
     * @param string $baseUrl URL base del sitio (ej: https://www.juancarlosmacias.es)
     * @param string|null $webRoot Ruta raíz del servidor (opcional)
     */
    public function __construct($baseUrl, $webRoot = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->webRoot = $webRoot ?? $_SERVER['DOCUMENT_ROOT'];
        $this->keyPath = dirname(dirname(__DIR__)) . '/config/indexnow-key.txt';
        
        require_once dirname(__DIR__) . '/SearchEngineLogger.php';
        $this->logger = new SearchEngineLogger('indexnow');
    }
    
    /**
     * Notifica lista de URLs a IndexNow
     * @param array $urls Lista de URLs completas
     * @return array Resultado de la operación
     */
    public function notifyUrls($urls)
    {
        try {
            $this->logger->log('=== STARTING INDEXNOW NOTIFICATION ===');
            
            // 1. Verificar que existe la clave
            if (!file_exists($this->keyPath)) {
                throw new Exception('IndexNow key not found. Run setup-indexnow.php first.');
            }
            
            $key = trim(file_get_contents($this->keyPath));
            
            if (empty($key)) {
                throw new Exception('IndexNow key is empty');
            }
            
            $this->logger->log('Key loaded successfully');
            
            // 2. Verificar archivo de verificación público
            $keyFile = $this->webRoot . '/' . $key . '.txt';
            if (!file_exists($keyFile)) {
                throw new Exception('IndexNow verification file not found: ' . $keyFile);
            }
            
            $this->logger->log('Verification file found: ' . $key . '.txt');
            
            // 3. Preparar datos (máximo 10,000 URLs por llamada según spec)
            $chunks = array_chunk($urls, 10000);
            $results = [];
            
            $this->logger->log('Total URLs: ' . count($urls));
            $this->logger->log('Batches to send: ' . count($chunks));
            
            foreach ($chunks as $index => $chunk) {
                $batchNum = $index + 1;
                $this->logger->log("Sending batch {$batchNum}/" . count($chunks) . " with " . count($chunk) . " URLs");
                
                $result = $this->sendBatch($chunk, $key);
                $results[] = $result;
                
                // Pausa entre lotes para no saturar la API
                if ($batchNum < count($chunks)) {
                    usleep(500000); // 0.5 segundos
                }
            }
            
            // 4. Consolidar resultados
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            
            $this->logger->log("Batches succeeded: {$successCount}/" . count($results));
            $this->logger->success('IndexNow notification completed');
            
            return [
                'success' => $successCount > 0,
                'provider' => 'IndexNow',
                'batches_sent' => count($results),
                'batches_success' => $successCount,
                'total_urls' => count($urls),
                'message' => $successCount > 0 
                    ? "✓ IndexNow: {$successCount} lotes enviados correctamente (Bing, Yandex, Naver, Seznam)"
                    : "✗ IndexNow: Error en todos los lotes",
                'details' => $results
            ];
            
        } catch (Exception $e) {
            $this->logger->error('IndexNow Error: ' . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'IndexNow',
                'message' => '✗ IndexNow: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía un lote de URLs a IndexNow API
     * @param array $urls Lista de URLs del lote
     * @param string $key Clave IndexNow
     * @return array Resultado del envío
     */
    private function sendBatch($urls, $key)
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        
        $postData = json_encode([
            'host' => $host,
            'key' => $key,
            'urlList' => $urls
        ]);
        
        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $this->logger->log("IndexNow API Response: HTTP {$httpCode}");
        
        if ($error) {
            $this->logger->error("cURL Error: {$error}");
        }
        
        // IndexNow acepta 200 (OK) o 202 (Accepted) como éxito
        $success = in_array($httpCode, [200, 202]);
        
        if ($success) {
            $this->logger->log('Batch sent successfully');
        } else {
            $this->logger->error("Batch failed with HTTP {$httpCode}: {$response}");
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
