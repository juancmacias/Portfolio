<?php
/**
 * ========================================
 * GENERADOR AUTOMÁTICO DE SITEMAP.XML
 * ========================================
 * 
 * Analiza la estructura del sitio web y genera
 * automáticamente un sitemap.xml válido para SEO.
 * 
 * Funcionalidades:
 * - Web scraping del sitio principal
 * - Extracción de enlaces internos
 * - Generación de XML estándar
 * - Filtrado de URLs no válidas
 * 
 * Autor: Sistema Portfolio JCMS
 * Versión: 1.0.0
 * ========================================
 */

class SitemapGenerator 
{
    private $baseUrl;
    private $domain;
    private $visitedUrls = [];
    private $validUrls = [];
    private $maxDepth;
    private $currentDepth = 0;
    private $excludePatterns = [
        '/logout',
        '/admin',
        '/#',
        'javascript:',
        'mailto:',
        'tel:',
        '.pdf',
        '.jpg',
        '.jpeg',
        '.png',
        '.gif',
        '.svg',
        '.css',
        '.js'
    ];

    // URLs conocidas para sitios React/SPA
    private $knownReactRoutes = [
        '/about',
        '/project',  // Lista de proyectos
        '/resume',
        '/articles', // Lista de artículos
        '/politics'  // Política de privacidad
    ];

    public function __construct($baseUrl, $maxDepth = 3) 
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->domain = parse_url($baseUrl, PHP_URL_HOST);
        $this->maxDepth = $maxDepth;
    }

    /**
     * Genera el sitemap completo
     * @return array Resultado con información del proceso
     */
    public function generateSitemap() 
    {
        $startTime = microtime(true);
        
        try {
            // Limpiar arrays
            $this->visitedUrls = [];
            $this->validUrls = [];
            $this->currentDepth = 0;
            
            // Analizar sitio web principal
            $this->crawlUrl($this->baseUrl);
            
            // Buscar URLs adicionales en robots.txt y sitemap.xml
            $this->discoverFromSiteFiles();
            
            // Buscar URLs dinámicas en APIs
            $this->discoverFromAPIs();
            
            // Agregar rutas conocidas de React/SPA
            $this->addKnownReactRoutes();
            
            // Generar XML
            $xmlContent = $this->generateXML();
            
            // Guardar archivo
            $sitemapPath = $this->getSitemapPath();
            $saved = file_put_contents($sitemapPath, $xmlContent);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            return [
                'success' => $saved !== false,
                'message' => $saved ? 'Sitemap generado exitosamente' : 'Error al guardar sitemap',
                'urls_found' => count($this->validUrls),
                'file_path' => $sitemapPath,
                'file_size' => $saved ? $this->formatBytes($saved) : '0 bytes',
                'execution_time' => $executionTime . ' segundos',
                'urls' => array_values($this->validUrls)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'urls_found' => 0,
                'execution_time' => 0,
                'urls' => []
            ];
        }
    }

    /**
     * Analiza una URL específica y extrae enlaces
     * @param string $url URL a analizar
     * @param int $depth Profundidad actual
     */
    private function crawlUrl($url, $depth = 0) 
    {
        // Límites de profundidad y URLs ya visitadas
        if ($depth > $this->maxDepth || in_array($url, $this->visitedUrls)) {
            return;
        }

        $this->visitedUrls[] = $url;

        try {
            // Configurar contexto HTTP
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'SitemapGenerator/1.0 (Portfolio JCMS)',
                    'follow_location' => true,
                    'max_redirects' => 3
                ]
            ]);

            // Obtener contenido HTML
            $html = @file_get_contents($url, false, $context);
            
            if ($html === false) {
                return;
            }

            // Agregar URL válida
            $this->addValidUrl($url);

            // Parsear HTML
            $dom = new DOMDocument();
            libxml_use_internal_errors(true); // Suprimir warnings HTML
            $dom->loadHTML($html);
            libxml_clear_errors();

            // Extraer enlaces
            $xpath = new DOMXPath($dom);
            $links = $xpath->query('//a[@href]');

            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                $absoluteUrl = $this->resolveUrl($href, $url);
                
                if ($this->isValidUrl($absoluteUrl) && $depth < $this->maxDepth) {
                    $this->crawlUrl($absoluteUrl, $depth + 1);
                }
            }

        } catch (Exception $e) {
            // Log error silencioso, continuar con otras URLs
            error_log("SitemapGenerator: Error crawling $url - " . $e->getMessage());
        }
    }

    /**
     * Resuelve URL relativa a absoluta
     * @param string $href Enlace href
     * @param string $baseUrl URL base actual
     * @return string URL absoluta
     */
    private function resolveUrl($href, $baseUrl) 
    {
        // URL ya absoluta
        if (parse_url($href, PHP_URL_SCHEME)) {
            return $href;
        }

        // URL relativa que empieza con /
        if (strpos($href, '/') === 0) {
            $parsedBase = parse_url($this->baseUrl);
            return $parsedBase['scheme'] . '://' . $parsedBase['host'] . $href;
        }

        // URL relativa
        $basePath = dirname($baseUrl);
        return rtrim($basePath, '/') . '/' . ltrim($href, '/');
    }

    /**
     * Valida si una URL debe incluirse en el sitemap
     * @param string $url URL a validar
     * @return bool True si es válida
     */
    private function isValidUrl($url) 
    {
        // Verificar dominio
        $urlDomain = parse_url($url, PHP_URL_HOST);
        if ($urlDomain !== $this->domain) {
            return false;
        }

        // Verificar patrones excluidos
        foreach ($this->excludePatterns as $pattern) {
            if (strpos($url, $pattern) !== false) {
                return false;
            }
        }

        // Verificar si ya existe
        if (in_array($url, array_keys($this->validUrls))) {
            return false;
        }

        return true;
    }

    /**
     * Agrega URL válida con metadatos
     * @param string $url URL válida
     */
    private function addValidUrl($url) 
    {
        // Normalizar URL (quitar fragment y parámetros innecesarios)
        $cleanUrl = strtok($url, '#');
        $cleanUrl = strtok($cleanUrl, '?');
        
        $this->validUrls[$cleanUrl] = [
            'url' => $cleanUrl,
            'lastmod' => date('Y-m-d'),
            'changefreq' => $this->getChangeFreq($cleanUrl),
            'priority' => $this->getPriority($cleanUrl)
        ];
    }

    /**
     * Determina frecuencia de cambio según la URL
     * @param string $url URL
     * @return string Frecuencia de cambio
     */
    private function getChangeFreq($url) 
    {
        if ($url === $this->baseUrl || $url === $this->baseUrl . '/') {
            return 'weekly'; // Homepage cambia frecuentemente
        }
        
        if (strpos($url, '/articles') !== false || strpos($url, '/blog') !== false) {
            return 'weekly'; // Artículos cambian regularmente
        }
        
        if (strpos($url, '/projects') !== false) {
            return 'monthly'; // Proyectos cambian mensualmente
        }
        
        return 'yearly'; // Páginas estáticas
    }

    /**
     * Determina prioridad según la URL
     * @param string $url URL
     * @return string Prioridad (0.0-1.0)
     */
    private function getPriority($url) 
    {
        if ($url === $this->baseUrl || $url === $this->baseUrl . '/') {
            return '1.0'; // Homepage máxima prioridad
        }
        
        if (strpos($url, '/projects') !== false) {
            return '0.8'; // Proyectos alta prioridad
        }
        
        if (strpos($url, '/articles') !== false || strpos($url, '/blog') !== false) {
            return '0.7'; // Artículos buena prioridad
        }
        
        if (strpos($url, '/about') !== false || strpos($url, '/resume') !== false) {
            return '0.6'; // Páginas importantes
        }
        
        return '0.5'; // Prioridad normal
    }

    /**
     * Genera el contenido XML del sitemap
     * @return string Contenido XML
     */
    private function generateXML() 
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($this->validUrls as $urlData) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($urlData['url']) . "</loc>\n";
            $xml .= "    <lastmod>" . $urlData['lastmod'] . "</lastmod>\n";
            $xml .= "    <changefreq>" . $urlData['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $urlData['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }

    /**
     * Obtiene la ruta donde guardar el sitemap
     * @return string Ruta completa del archivo
     */
    private function getSitemapPath() 
    {
        // Directorio raíz del proyecto (3 niveles arriba desde admin/classes/)
        $rootDir = realpath(__DIR__ . '/../../');
        return $rootDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
    }

    /**
     * Formatea bytes en formato legible
     * @param int $bytes Número de bytes
     * @return string Tamaño formateado
     */
    private function formatBytes($bytes) 
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Obtiene información del sitemap actual
     * @return array Información del archivo actual
     */
    public function getSitemapInfo() 
    {
        $sitemapPath = $this->getSitemapPath();
        
        if (!file_exists($sitemapPath)) {
            return [
                'exists' => false,
                'message' => 'No existe sitemap.xml'
            ];
        }

        $fileSize = filesize($sitemapPath);
        $lastModified = filemtime($sitemapPath);
        
        // Contar URLs en el sitemap actual
        $urlCount = 0;
        $content = file_get_contents($sitemapPath);
        if ($content) {
            $urlCount = substr_count($content, '<url>');
        }

        return [
            'exists' => true,
            'path' => $sitemapPath,
            'size' => $this->formatBytes($fileSize),
            'last_modified' => date('d M Y, H:i', $lastModified),
            'url_count' => $urlCount,
            'is_recent' => (time() - $lastModified) < 86400 // Menos de 24 horas
        ];
    }

    /**
     * Agrega rutas conocidas de React/SPA al sitemap
     * Estas rutas no se pueden descubrir mediante scraping tradicional
     */
    private function addKnownReactRoutes() 
    {
        foreach ($this->knownReactRoutes as $route) {
            $fullUrl = $this->baseUrl . $route;
            
            // Verificar que la ruta responde correctamente
            if ($this->isUrlAccessible($fullUrl)) {
                $this->addValidUrl($fullUrl);
            }
        }
    }

    /**
     * Busca URLs adicionales en archivos del sitio (robots.txt, sitemap.xml)
     */
    private function discoverFromSiteFiles() 
    {
        // Buscar en robots.txt
        $robotsUrl = $this->baseUrl . '/robots.txt';
        $robotsContent = @file_get_contents($robotsUrl);
        
        if ($robotsContent) {
            // Buscar Sitemap: directives
            preg_match_all('/Sitemap:\s*(.+)/i', $robotsContent, $matches);
            foreach ($matches[1] ?? [] as $sitemapUrl) {
                $this->parseExistingSitemap(trim($sitemapUrl));
            }
        }

        // Buscar sitemap.xml en la raíz
        $sitemapUrl = $this->baseUrl . '/sitemap.xml';
        $this->parseExistingSitemap($sitemapUrl);
    }

    /**
     * Busca URLs dinámicas consultando APIs del portfolio
     */
    private function discoverFromAPIs() 
    {
        // Consultar API de artículos
        $articlesUrl = $this->baseUrl . '/api/portfolio/articles.php';
        $articlesData = $this->fetchAPIData($articlesUrl);
        
        if ($articlesData && isset($articlesData['data']['articles'])) {
            foreach ($articlesData['data']['articles'] as $article) {
                if (isset($article['slug'])) {
                    $articleUrl = $this->baseUrl . '/article/' . $article['slug'];
                    $this->addValidUrl($articleUrl);
                }
            }
        }

        // Consultar API de proyectos
        $projectsUrl = $this->baseUrl . '/api/portfolio/projects.php';
        $projectsData = $this->fetchAPIData($projectsUrl);
        
        if ($projectsData && isset($projectsData['data'])) {
            foreach ($projectsData['data'] as $project) {
                // Los proyectos en React no tienen páginas individuales aparentemente
                // Solo agregamos si tienen un patrón específico que usemos
                if (isset($project['id']) && isset($project['title'])) {
                    // Verificar si hay rutas dinámicas para proyectos individuales
                    // Por ahora comentado hasta confirmar el patrón de URLs
                    // $projectUrl = $this->baseUrl . '/project/' . $project['id'];
                    // $this->addValidUrl($projectUrl);
                }
            }
        }

        // También intentar leer datos_proyectos.json directamente
        $this->discoverFromProjectsJSON();
    }

    /**
     * Obtiene datos de un endpoint API
     * @param string $url URL del API
     * @return array|null Datos del API o null si falla
     */
    private function fetchAPIData($url) 
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'SitemapGenerator/1.0 (Portfolio JCMS)',
                'follow_location' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data ?: null;
    }

    /**
     * Lee proyectos directamente desde datos_proyectos.json
     */
    private function discoverFromProjectsJSON() 
    {
        $jsonUrl = $this->baseUrl . '/api/portfolio/datos_proyectos.json';
        $jsonData = $this->fetchAPIData($jsonUrl);
        
        if ($jsonData && is_array($jsonData)) {
            foreach ($jsonData as $project) {
                // Verificar si existen páginas individuales para proyectos
                // Por ahora solo registramos que hay X proyectos disponibles
                // Las URLs individuales dependen de cómo esté configurado React
                
                // Si hay slugs o IDs que se usen en rutas, agregar aquí
                if (isset($project['id'])) {
                    // Ejemplo: si hubiera rutas como /project/view/1, /project/view/2, etc.
                    // $projectUrl = $this->baseUrl . '/project/view/' . $project['id'];
                    // $this->addValidUrl($projectUrl);
                }
            }
        }
    }

    /**
     * Parsea un sitemap existente para encontrar URLs
     * @param string $sitemapUrl URL del sitemap a parsear
     */
    private function parseExistingSitemap($sitemapUrl) 
    {
        $content = @file_get_contents($sitemapUrl);
        if (!$content) return;

        // Parsear XML del sitemap
        $xml = @simplexml_load_string($content);
        if (!$xml) return;

        foreach ($xml->url ?? [] as $urlElement) {
            $url = (string)$urlElement->loc;
            if ($this->isValidUrl($url) && !in_array($url, $this->visitedUrls)) {
                $this->addValidUrl($url);
            }
        }
    }

    /**
     * Verifica si una URL es accesible (responde 200)
     * @param string $url URL a verificar
     * @return bool True si es accesible
     */
    private function isUrlAccessible($url) 
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 10,
                'user_agent' => 'SitemapGenerator/1.0 (Portfolio JCMS)',
                'follow_location' => true,
                'max_redirects' => 3
            ]
        ]);

        $headers = @get_headers($url, 0, $context);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        }
        
        // Si HEAD falla, intentar GET
        $response = @file_get_contents($url, false, $context);
        return $response !== false;
    }

    /**
     * Notifica a los buscadores sobre la actualización del sitemap
     * @param string $sitemapUrl URL completa del sitemap
     * @return array Resultado de las notificaciones
     */
    public function notifySearchEngines($sitemapUrl = null) 
    {
        if (!$sitemapUrl) {
            $sitemapUrl = $this->baseUrl . '/sitemap.xml';
        }

        $searchEngines = [
            'Google' => 'http://www.google.com/ping?sitemap=' . urlencode($sitemapUrl),
            'Bing' => 'http://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl),
            'Yandex' => 'http://webmaster.yandex.com/ping?sitemap=' . urlencode($sitemapUrl),
        ];

        $results = [];
        $totalSuccess = 0;

        foreach ($searchEngines as $engine => $pingUrl) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'user_agent' => 'SitemapGenerator/1.0 (Portfolio JCMS)',
                        'follow_location' => true,
                        'max_redirects' => 3
                    ]
                ]);

                $response = @file_get_contents($pingUrl, false, $context);
                
                // Verificar headers de respuesta
                $headers = $http_response_header ?? [];
                $statusCode = $this->extractStatusCode($headers);
                
                if ($response !== false && ($statusCode >= 200 && $statusCode < 300)) {
                    $results[$engine] = [
                        'success' => true,
                        'message' => 'Notificación enviada correctamente',
                        'status_code' => $statusCode,
                        'response_size' => strlen($response)
                    ];
                    $totalSuccess++;
                } else {
                    $results[$engine] = [
                        'success' => false,
                        'message' => 'Error en la respuesta del servidor',
                        'status_code' => $statusCode ?: 'Error de conexión'
                    ];
                }

                // Pausa pequeña entre requests para ser respetuosos
                usleep(500000); // 0.5 segundos

            } catch (Exception $e) {
                $results[$engine] = [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'status_code' => 'Exception'
                ];
            }
        }

        return [
            'total_engines' => count($searchEngines),
            'successful_notifications' => $totalSuccess,
            'results' => $results,
            'sitemap_url' => $sitemapUrl
        ];
    }

    /**
     * Extrae el código de estado HTTP de los headers
     * @param array $headers Headers HTTP
     * @return int|null Código de estado
     */
    private function extractStatusCode($headers) 
    {
        if (empty($headers)) return null;
        
        $statusLine = $headers[0] ?? '';
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
            return (int)$matches[1];
        }
        
        return null;
    }
}
?>