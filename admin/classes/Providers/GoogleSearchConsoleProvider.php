<?php
/**
 * GoogleSearchConsoleProvider
 * Notifica sitemap a Google Search Console usando Service Account
 * 
 * Requiere:
 * - Google Cloud Project con Search Console API habilitada
 * - Service Account creado y con permisos en Search Console
 * - Archivo admin/config/service-account-credentials.json
 * - Librería google/apiclient instalada via Composer
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

class GoogleSearchConsoleProvider
{
    private $credentialsPath;
    private $baseUrl;
    private $logger;
    
    /**
     * Constructor
     * @param string $baseUrl URL base del sitio (ej: https://www.juancarlosmacias.es)
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->credentialsPath = dirname(dirname(__DIR__)) . '/config/service-account-credentials.json';
        
        require_once dirname(__DIR__) . '/SearchEngineLogger.php';
        $this->logger = new SearchEngineLogger('google');
    }
    
    /**
     * Notifica sitemap a Google Search Console
     * @param array $urls Opcional - no usado por Google (envía sitemap completo)
     * @return array Resultado de la operación
     */
    public function notifyUrls($urls = [])
    {
        try {
            $this->logger->log('=== STARTING GOOGLE SEARCH CONSOLE NOTIFICATION ===');
            
            // 1. Verificar credenciales
            if (!file_exists($this->credentialsPath)) {
                throw new Exception('Google Service Account credentials not found: ' . $this->credentialsPath);
            }
            
            $this->logger->log('Credentials file found');
            
            // 2. Verificar librería Google Client
            // Intentar múltiples ubicaciones (producción y desarrollo)
            $autoloadPaths = [
                // Producción: vendor fuera de public_html (ej: /home/user/vendor/autoload.php)
                dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php',
                // Desarrollo: vendor en raíz del proyecto Portfolio
                dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
                // Alternativa: vendor en directorio padre del proyecto
                dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/vendor/autoload.php'
            ];
            
            $autoloadPath = null;
            foreach ($autoloadPaths as $path) {
                if (file_exists($path)) {
                    $autoloadPath = $path;
                    break;
                }
            }
            
            if (!$autoloadPath) {
                throw new Exception('Google Client Library not installed. Run: composer require google/apiclient');
            }
            
            require_once $autoloadPath;
            
            $this->logger->log('Google Client Library loaded');
            
            // 3. Verificar que la clase existe
            if (!class_exists('Google\Client')) {
                throw new Exception('Google\Client class not found. Reinstall: composer require google/apiclient');
            }
            
            // 4. Crear cliente Google
            $client = new Google\Client();
            $client->setAuthConfig($this->credentialsPath);
            $client->setScopes(['https://www.googleapis.com/auth/webmasters']);
            
            $this->logger->log('Google Client configured');
            
            // 5. Autenticar (genera token automáticamente con Service Account)
            $client->fetchAccessTokenWithAssertion();
            $token = $client->getAccessToken();
            
            if (!$token) {
                throw new Exception('Failed to generate access token');
            }
            
            $this->logger->log('Access token generated successfully');
            
            // 6. Crear servicio Webmasters (Search Console)
            $service = new Google\Service\Webmasters($client);
            
            $this->logger->log('Webmasters service created');
            
            // 7. Preparar URL del sitio (Domain Property format)
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            $sitemapUrl = $this->baseUrl . '/sitemap.xml';
            
            $this->logger->log("Submitting sitemap: {$sitemapUrl}");
            $this->logger->log("To site property: {$siteUrl}");
            
            // 8. Enviar sitemap
            // Nota: Google API de Search Console usa PUT para submit, la librería lo maneja automáticamente
            $service->sitemaps->submit($siteUrl, $sitemapUrl);
            
            $this->logger->success('Sitemap submitted successfully to Google Search Console');
            
            return [
                'success' => true,
                'provider' => 'Google Search Console',
                'message' => '✓ Google: Sitemap enviado correctamente',
                'sitemap_url' => $sitemapUrl,
                'site_url' => $siteUrl
            ];
            
        } catch (Google\Service\Exception $e) {
            $errorMsg = 'Google API Error: ' . $e->getMessage();
            $this->logger->error($errorMsg);
            $this->logger->error('HTTP Code: ' . $e->getCode());
            
            // Errores comunes
            $message = $e->getMessage();
            if ($e->getCode() === 403) {
                $message = 'Acceso denegado. Verifica que el Service Account sea Owner en Search Console';
            } elseif ($e->getCode() === 404) {
                $message = 'Propiedad no encontrada. Verifica que el sitio esté agregado como Domain Property';
            }
            
            return [
                'success' => false,
                'provider' => 'Google Search Console',
                'message' => '✗ Google: ' . $message,
                'error' => $e->getMessage(),
                'http_code' => $e->getCode()
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Google Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'provider' => 'Google Search Console',
                'message' => '✗ Google: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}
