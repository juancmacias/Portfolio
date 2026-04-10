<?php
/**
 * SearchEngineNotifier
 * Orquestador principal para notificar sitemaps a todos los motores de búsqueda
 * 
 * Coordina el envío de notificaciones a:
 * - Google Search Console (vía Service Account API)
 * - Bing Webmaster Tools (vía API Key)
 * - IndexNow (múltiples buscadores: Bing, Yandex, Naver, Seznam)
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

require_once __DIR__ . '/SearchEngineLogger.php';
require_once __DIR__ . '/Providers/IndexNowProvider.php';
require_once __DIR__ . '/Providers/BingWebmasterProvider.php';
require_once __DIR__ . '/Providers/GoogleSearchConsoleProvider.php';

class SearchEngineNotifier
{
    private $baseUrl;
    private $webRoot;
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
        $this->logger = new SearchEngineLogger('notifier');
    }
    
    /**
     * Notifica a todos los motores de búsqueda disponibles
     * @param array $urls Lista de URLs a notificar
     * @return array Resultados de cada proveedor
     */
    public function notifyAll($urls)
    {
        $this->logger->log('=== STARTING NOTIFICATION PROCESS ===');
        $this->logger->log('Base URL: ' . $this->baseUrl);
        $this->logger->log('Total URLs to notify: ' . count($urls));
        
        $results = [];
        $startTime = microtime(true);
        
        // 1. Google Search Console (envía sitemap completo, no URLs individuales)
        $this->logger->log('--- NOTIFYING GOOGLE SEARCH CONSOLE ---');
        try {
            $google = new GoogleSearchConsoleProvider($this->baseUrl);
            $results['google'] = $google->notifyUrls();
            
            if ($results['google']['success']) {
                $this->logger->success('Google notification succeeded');
            } else {
                $this->logger->error('Google notification failed: ' . $results['google']['message']);
            }
        } catch (Exception $e) {
            $this->logger->error('Google exception: ' . $e->getMessage());
            $results['google'] = [
                'success' => false,
                'provider' => 'Google Search Console',
                'message' => '✗ Google: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
        
        // 2. Bing Webmaster API
        $this->logger->log('--- NOTIFYING BING WEBMASTER ---');
        try {
            $bing = new BingWebmasterProvider($this->baseUrl);
            $results['bing'] = $bing->notifyUrls($urls);
            
            if ($results['bing']['success']) {
                $this->logger->success('Bing notification succeeded');
            } else {
                $this->logger->error('Bing notification failed: ' . $results['bing']['message']);
            }
        } catch (Exception $e) {
            $this->logger->error('Bing exception: ' . $e->getMessage());
            $results['bing'] = [
                'success' => false,
                'provider' => 'Bing Webmaster',
                'message' => '✗ Bing: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
        
        // 3. IndexNow (múltiples buscadores simultáneamente)
        $this->logger->log('--- NOTIFYING INDEXNOW ---');
        try {
            $indexNow = new IndexNowProvider($this->baseUrl, $this->webRoot);
            $results['indexnow'] = $indexNow->notifyUrls($urls);
            
            if ($results['indexnow']['success']) {
                $this->logger->success('IndexNow notification succeeded');
            } else {
                $this->logger->error('IndexNow notification failed: ' . $results['indexnow']['message']);
            }
        } catch (Exception $e) {
            $this->logger->error('IndexNow exception: ' . $e->getMessage());
            $results['indexnow'] = [
                'success' => false,
                'provider' => 'IndexNow',
                'message' => '✗ IndexNow: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
        
        // Resumen final
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $totalProviders = count($results);
        
        $this->logger->log("=== NOTIFICATION PROCESS COMPLETED ===");
        $this->logger->log("Execution time: {$executionTime} seconds");
        $this->logger->log("Providers notified: {$successCount}/{$totalProviders}");
        
        if ($successCount > 0) {
            $this->logger->success("At least one provider succeeded");
        } else {
            $this->logger->error("All providers failed");
        }
        
        return [
            'success' => $successCount > 0,
            'providers_total' => $totalProviders,
            'providers_success' => $successCount,
            'providers_failed' => $totalProviders - $successCount,
            'execution_time' => $executionTime,
            'urls_count' => count($urls),
            'results' => $results
        ];
    }
    
    /**
     * Notifica solo a proveedores específicos
     * @param array $urls Lista de URLs
     * @param array $providers Lista de proveedores a usar ['google', 'bing', 'indexnow']
     * @return array Resultados de proveedores seleccionados
     */
    public function notifySelected($urls, $providers = [])
    {
        $this->logger->log('=== STARTING SELECTIVE NOTIFICATION ===');
        $this->logger->log('Selected providers: ' . implode(', ', $providers));
        
        $results = [];
        
        if (in_array('google', $providers)) {
            try {
                $google = new GoogleSearchConsoleProvider($this->baseUrl);
                $results['google'] = $google->notifyUrls();
            } catch (Exception $e) {
                $results['google'] = [
                    'success' => false,
                    'provider' => 'Google Search Console',
                    'message' => '✗ Google: ' . $e->getMessage()
                ];
            }
        }
        
        if (in_array('bing', $providers)) {
            try {
                $bing = new BingWebmasterProvider($this->baseUrl);
                $results['bing'] = $bing->notifyUrls($urls);
            } catch (Exception $e) {
                $results['bing'] = [
                    'success' => false,
                    'provider' => 'Bing Webmaster',
                    'message' => '✗ Bing: ' . $e->getMessage()
                ];
            }
        }
        
        if (in_array('indexnow', $providers)) {
            try {
                $indexNow = new IndexNowProvider($this->baseUrl, $this->webRoot);
                $results['indexnow'] = $indexNow->notifyUrls($urls);
            } catch (Exception $e) {
                $results['indexnow'] = [
                    'success' => false,
                    'provider' => 'IndexNow',
                    'message' => '✗ IndexNow: ' . $e->getMessage()
                ];
            }
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        
        return [
            'success' => $successCount > 0,
            'providers_total' => count($results),
            'providers_success' => $successCount,
            'results' => $results
        ];
    }
}
