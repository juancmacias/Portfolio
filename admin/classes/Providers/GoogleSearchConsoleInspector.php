<?php
/**
 * GoogleSearchConsoleInspector
 * Inspecciona el estado de indexación de URLs usando Google Search Console API
 * 
 * Funcionalidades:
 * - Verificar estado de indexación de URLs individuales
 * - Obtener problemas de cobertura (coverage issues)
 * - Identificar errores de rastreo (crawl errors)
 * - Analizar sitemap y URLs enviadas
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

class GoogleSearchConsoleInspector
{
    private $credentialsPath;
    private $baseUrl;
    private $logger;
    private $client;
    private $service;
    
    /**
     * Constructor
     * @param string $baseUrl URL base del sitio
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->credentialsPath = dirname(dirname(__DIR__)) . '/config/service-account-credentials.json';
        
        require_once dirname(__DIR__) . '/SearchEngineLogger.php';
        $this->logger = new SearchEngineLogger('gsc-inspector');
        
        $this->initializeClient();
    }
    
    /**
     * Inicializa el cliente de Google
     */
    private function initializeClient()
    {
        try {
            // Buscar vendor/autoload.php en múltiples ubicaciones
            $autoloadPaths = [
                dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php',
                dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
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
                throw new Exception('Google Client Library not installed');
            }
            
            require_once $autoloadPath;
            
            if (!file_exists($this->credentialsPath)) {
                throw new Exception('Google Service Account credentials not found');
            }
            
            $this->client = new Google\Client();
            $this->client->setAuthConfig($this->credentialsPath);
            $this->client->setScopes([
                'https://www.googleapis.com/auth/webmasters.readonly',
                'https://www.googleapis.com/auth/webmasters'
            ]);
            
            $this->client->fetchAccessTokenWithAssertion();
            $this->service = new Google\Service\Webmasters($this->client);
            
            $this->logger->log('Google Search Console client initialized');
            
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize GSC client: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene lista de sitemaps enviados
     * @return array Lista de sitemaps con estado
     */
    public function getSitemaps()
    {
        try {
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            
            $this->logger->log("Getting sitemaps for: {$siteUrl}");
            
            $sitemaps = $this->service->sitemaps->listSitemaps($siteUrl);
            
            $result = [];
            if ($sitemaps->getSitemap()) {
                foreach ($sitemaps->getSitemap() as $sitemap) {
                    $result[] = [
                        'path' => $sitemap->getPath(),
                        'type' => $sitemap->getType(),
                        'lastSubmitted' => $sitemap->getLastSubmitted(),
                        'lastDownloaded' => $sitemap->getLastDownloaded(),
                        'isPending' => $sitemap->getIsPending(),
                        'isSitemapsIndex' => $sitemap->getIsSitemapsIndex(),
                        'errors' => $sitemap->getErrors(),
                        'warnings' => $sitemap->getWarnings()
                    ];
                }
            }
            
            $this->logger->log('Found ' . count($result) . ' sitemaps');
            return $result;
            
        } catch (Google\Service\Exception $e) {
            $this->logger->error('Error getting sitemaps: ' . $e->getMessage());
            throw new Exception('Error obteniendo sitemaps: ' . $e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Inspecciona una URL específica
     * @param string $url URL completa a inspeccionar
     * @return array Estado de la URL
     */
    public function inspectUrl($url)
    {
        try {
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            
            $this->logger->log("Inspecting URL: {$url}");
            
            // Usar URL Inspection API (Search Console API v1)
            // Nota: Este endpoint requiere permisos de Search Console
            $urlInspectionRequest = new Google\Service\Webmasters\InspectUrlRequest();
            $urlInspectionRequest->setInspectionUrl($url);
            $urlInspectionRequest->setSiteUrl($siteUrl);
            
            $response = $this->service->urlInspection->inspect($urlInspectionRequest);
            
            $indexStatus = $response->getInspectionResult();
            
            return [
                'url' => $url,
                'indexStatusResult' => $indexStatus ? [
                    'verdict' => $indexStatus->getVerdict(),
                    'coverageState' => $indexStatus->getCoverageState(),
                    'robotsTxtState' => $indexStatus->getRobotsTxtState(),
                    'indexingState' => $indexStatus->getIndexingState(),
                    'lastCrawlTime' => $indexStatus->getLastCrawlTime(),
                    'pageFetchState' => $indexStatus->getPageFetchState(),
                    'googleCanonical' => $indexStatus->getGoogleCanonical(),
                    'userCanonical' => $indexStatus->getUserCanonical()
                ] : null
            ];
            
        } catch (Google\Service\Exception $e) {
            // Si el API de inspección no está disponible, usar método alternativo
            $this->logger->log('WARN: URL Inspection API not available, using alternative method');
            
            return [
                'url' => $url,
                'error' => 'URL Inspection API requires additional permissions',
                'suggestion' => 'Use Search Console web interface for detailed URL inspection'
            ];
        } catch (Exception $e) {
            $this->logger->error('Error inspecting URL: ' . $e->getMessage());
            throw new Exception('Error inspeccionando URL: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtiene errores de rastreo del sitio
     * @param string $category Categoría de errores (serverError, soft404, notFound, etc.)
     * @param string $platform Plataforma (web, mobile, smartphoneOnly)
     * @return array Lista de errores de rastreo
     */
    public function getCrawlErrors($category = 'notFound', $platform = 'web')
    {
        try {
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            
            $this->logger->log("Getting crawl errors: category={$category}, platform={$platform}");
            
            // Nota: El API de Crawl Errors fue deprecado en 2019
            // Ahora se usa el Coverage Report vía Search Analytics
            
            return [
                'deprecated' => true,
                'message' => 'Crawl Errors API was deprecated. Use Coverage Report in Search Console.',
                'alternative' => 'Access https://search.google.com/search-console coverage report'
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Error getting crawl errors: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene estadísticas de búsqueda (Search Analytics)
     * @param array $options Opciones de consulta (startDate, endDate, dimensions, etc.)
     * @return array Estadísticas de búsqueda
     */
    public function getSearchAnalytics($options = [])
    {
        try {
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            
            // Configuración por defecto: últimos 30 días
            $endDate = $options['endDate'] ?? date('Y-m-d', strtotime('-3 days')); // GSC tiene delay de 2-3 días
            $startDate = $options['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
            
            $dimensions = $options['dimensions'] ?? ['page', 'query'];
            $rowLimit = $options['rowLimit'] ?? 1000;
            
            $this->logger->log("Getting search analytics: {$startDate} to {$endDate}");
            
            $request = new Google\Service\Webmasters\SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions($dimensions);
            $request->setRowLimit($rowLimit);
            
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            
            $rows = $response->getRows();
            $result = [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalRows' => count($rows),
                'rows' => []
            ];
            
            if ($rows) {
                foreach ($rows as $row) {
                    $result['rows'][] = [
                        'keys' => $row->getKeys(),
                        'clicks' => $row->getClicks(),
                        'impressions' => $row->getImpressions(),
                        'ctr' => $row->getCtr(),
                        'position' => $row->getPosition()
                    ];
                }
            }
            
            $this->logger->log('Retrieved ' . count($rows) . ' analytics rows');
            return $result;
            
        } catch (Google\Service\Exception $e) {
            $this->logger->error('Error getting search analytics: ' . $e->getMessage());
            throw new Exception('Error obteniendo analíticas: ' . $e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Obtiene páginas con problemas de indexación
     * Analiza las páginas del sitemap vs páginas indexadas
     * @return array Análisis de cobertura
     */
    public function getCoverageAnalysis()
    {
        try {
            $this->logger->log('Starting coverage analysis');
            
            // 1. Obtener sitemaps
            $sitemaps = $this->getSitemaps();
            
            // 2. Obtener analytics para ver qué páginas tienen impressions
            $analytics = $this->getSearchAnalytics([
                'dimensions' => ['page'],
                'rowLimit' => 5000
            ]);
            
            // 3. Compilar resultados
            $indexedPages = [];
            foreach ($analytics['rows'] as $row) {
                if (isset($row['keys'][0])) {
                    $indexedPages[] = $row['keys'][0];
                }
            }
            
            $result = [
                'sitemaps' => $sitemaps,
                'indexedPagesCount' => count($indexedPages),
                'indexedPages' => array_slice($indexedPages, 0, 100), // Primeras 100
                'analysisDate' => date('Y-m-d H:i:s'),
                'note' => 'Para análisis completo de cobertura, usar Search Console web interface'
            ];
            
            $this->logger->success('Coverage analysis completed');
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error('Error in coverage analysis: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene URLs con más clics (top performing)
     * @param int $limit Número máximo de URLs
     * @return array Lista de URLs ordenadas por clics
     */
    public function getTopPerformingUrls($limit = 20)
    {
        try {
            $analytics = $this->getSearchAnalytics([
                'dimensions' => ['page'],
                'rowLimit' => $limit
            ]);
            
            // Ordenar por clics
            usort($analytics['rows'], function($a, $b) {
                return $b['clicks'] - $a['clicks'];
            });
            
            return array_slice($analytics['rows'], 0, $limit);
            
        } catch (Exception $e) {
            $this->logger->error('Error getting top performing URLs: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verifica si un sitio está verificado en Search Console
     * @return bool True si está verificado
     */
    public function isSiteVerified()
    {
        try {
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            
            $sites = $this->service->sites->listSites();
            
            foreach ($sites->getSiteEntry() as $site) {
                if ($site->getSiteUrl() === $siteUrl) {
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error('Error checking site verification: ' . $e->getMessage());
            return false;
        }
    }
}
