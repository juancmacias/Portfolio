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
    private $logFile;
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
        // Eliminar www. del baseUrl si existe
        $baseUrl = preg_replace('/^(https?:\/\/)www\./i', '$1', $baseUrl);
        
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->domain = parse_url($baseUrl, PHP_URL_HOST);
        $this->maxDepth = $maxDepth;
        
        // Configurar archivo de log
        $logDir = __DIR__;
        $this->logFile = $logDir . '/sitemap_generator.log';
        
        // Limpiar log anterior si existe
        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
        
        $this->log("=== INICIO GENERACIÓN SITEMAP ===");
        $this->log("Base URL: " . $this->baseUrl);
        $this->log("Domain: " . $this->domain);
        $this->log("Max Depth: " . $this->maxDepth);
    }
    
    /**
     * Escribe en el archivo de log
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        @file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Genera el sitemap completo
     * @return array Resultado con información del proceso
     */
    public function generateSitemap() 
    {
        $startTime = microtime(true);
        
        try {
            $this->log("Iniciando generación del sitemap...");
            
            // Limpiar arrays
            $this->visitedUrls = [];
            $this->validUrls = [];
            $this->currentDepth = 0;
            
            $this->log("Arrays limpiados");
            
            // NO hacer crawling automático del sitio (puede causar errores con APIs protegidas)
            // Solo agregar la home
            $this->log("Agregando URL home...");
            $this->addValidUrl($this->baseUrl);
            
            // Buscar URLs adicionales en robots.txt y sitemap.xml existente
            $this->log("Buscando en archivos del sitio...");
            $this->discoverFromSiteFiles();
            
            // Buscar URLs dinámicas desde la base de datos
            $this->log("Buscando URLs desde base de datos...");
            $this->discoverFromAPIs();
            
            // Agregar rutas conocidas de React/SPA
            $this->log("Agregando rutas conocidas de React/SPA...");
            $this->addKnownReactRoutes();
            
            // Generar XML
            $this->log("Generando XML...");
            $xmlContent = $this->generateXML();
            $this->log("XML generado. Tamaño: " . strlen($xmlContent) . " bytes");
            
            // Guardar archivo
            $sitemapPath = $this->getSitemapPath();
            $this->log("Guardando sitemap en: $sitemapPath");
            $saved = file_put_contents($sitemapPath, $xmlContent);
            
            if ($saved === false) {
                $this->log("ERROR: No se pudo guardar el archivo");
            } else {
                $this->log("Sitemap guardado exitosamente. Tamaño: " . strlen($xmlContent) . " bytes");
            }
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            $this->log("URLs totales encontradas: " . count($this->validUrls));
            $this->log("Tiempo de ejecución: {$executionTime}s");
            $this->log("=== FIN GENERACIÓN SITEMAP ===");
            
            return [
                'success' => $saved !== false,
                'message' => $saved ? 'Sitemap generado exitosamente' : 'Error al guardar sitemap',
                'urls_found' => count($this->validUrls),
                'file_path' => $sitemapPath,
                'log_file' => $this->logFile,
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
    private function getSitemapPath($filename = 'sitemap.xml') 
    {
        // Directorio raíz del proyecto (3 niveles arriba desde admin/classes/)
        $rootDir = realpath(__DIR__ . '/../../');
        return $rootDir . DIRECTORY_SEPARATOR . $filename;
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
        
        // Verificar si es reciente (menos de 7 días)
        $isRecent = (time() - $lastModified) < (7 * 24 * 60 * 60);

        return [
            'exists' => true,
            'path' => $sitemapPath,
            'size' => $this->formatBytes($fileSize),
            'last_modified' => date('d M Y, H:i', $lastModified),
            'url_count' => $urlCount,
            'is_recent' => $isRecent
        ];
    }

    /**
     * Agrega las rutas conocidas de React/SPA
     */
    private function addKnownReactRoutes() 
    {
        $this->log("  - Rutas conocidas a agregar: " . count($this->knownReactRoutes));
        
        foreach ($this->knownReactRoutes as $route) {
            $fullUrl = $this->baseUrl . $route;
            
            // Agregar directamente sin verificar (son rutas conocidas que existen)
            // No hacer peticiones HTTP para evitar errores con APIs protegidas
            $this->addValidUrl($fullUrl);
            $this->log("    - Agregada: $route");
        }
        
        $this->log("  - Rutas conocidas agregadas exitosamente");
    }

    /**
     * Descubre URLs desde archivos locales del sitio
     */
    private function discoverFromSiteFiles() 
    {
        // Usar archivos locales en lugar de HTTP para evitar errores de "Acceso directo no permitido"
        
        // Buscar robots.txt en el sistema de archivos local
        $robotsLocalPath = $this->getSitemapPath('robots.txt');
        $this->log("  - Ruta robots.txt: $robotsLocalPath");
        
        if (file_exists($robotsLocalPath)) {
            $this->log("  - robots.txt encontrado");
            $robotsContent = file_get_contents($robotsLocalPath);
            if ($robotsContent) {
                // Buscar Sitemap: directives
                preg_match_all('/Sitemap:\s*(.+)/i', $robotsContent, $matches);
                $this->log("  - Sitemaps encontrados en robots.txt: " . count($matches[1] ?? []));
                // Comentado para evitar llamadas HTTP
                // foreach ($matches[1] ?? [] as $sitemapUrl) {
                //     $this->parseExistingSitemap(trim($sitemapUrl));
                // }
                if (count($matches[1] ?? []) > 0) {
                    $this->log("  - Parseado de sitemaps desde robots.txt deshabilitado (evitar errores HTTP)");
                }
            }
        } else {
            $this->log("  - robots.txt NO encontrado");
        }

        // Buscar sitemap.xml local
        $sitemapLocalPath = $this->getSitemapPath('sitemap.xml');
        $this->log("  - Ruta sitemap.xml: $sitemapLocalPath");
        
        if (file_exists($sitemapLocalPath)) {
            $this->log("  - sitemap.xml existente encontrado");
            // Comentado para evitar errores HTTP en parseExistingSitemap
            // $this->parseExistingSitemap($sitemapLocalPath);
            $this->log("  - Parseado de sitemap.xml existente deshabilitado (evitar errores HTTP)");
        } else {
            $this->log("  - sitemap.xml NO encontrado (será creado)");
        }
    }

    /**
     * Descubre URLs desde la base de datos (artículos, proyectos, etc.)
     */
    private function discoverFromAPIs() 
    {
        // Acceso directo a la base de datos en lugar de APIs HTTP para evitar errores
        
        $this->log("  - Conectando a base de datos...");
        
        try {
            // Definir constante requerida para acceso a database.php
            if (!defined('ADMIN_ACCESS')) {
                define('ADMIN_ACCESS', true);
            }
            
            // Obtener conexión a la base de datos
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            
            $this->log("  - Conexión exitosa");
            $this->log("  - Consultando artículos publicados...");
            
            // Consultar artículos publicados directamente desde la DB
            $articles = $db->fetchAll(
                "SELECT slug, updated_at FROM articles 
                 WHERE status = 'published' 
                 ORDER BY updated_at DESC"
            );
            
            $this->log("  - Artículos encontrados: " . count($articles ?? []));
            
            if ($articles) {
                foreach ($articles as $article) {
                    $articleUrl = $this->baseUrl . '/article/' . $article['slug'];
                    
                    // Agregar con metadata de la base de datos
                    $this->validUrls[$articleUrl] = [
                        'url' => $articleUrl,
                        'lastmod' => date('Y-m-d', strtotime($article['updated_at'])),
                        'changefreq' => 'weekly',
                        'priority' => '0.7'
                    ];
                    
                    $this->visitedUrls[$articleUrl] = true;
                    $this->log("    - Agregado: " . $article['slug']);
                }
            }
            
            $this->log("  - Total artículos agregados: " . count($articles ?? []));
            
        } catch (Exception $e) {
            $this->log("  - ERROR DB: " . $e->getMessage());
        }
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
                'follow_location' => true,
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("SitemapGenerator: No se pudo acceder a $url");
            return null;
        }

        // Verificar si es JSON válido
        $data = @json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("SitemapGenerator: Respuesta no es JSON válido de $url - " . substr($response, 0, 100));
            return null;
        }
        
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
     * @param string $sitemapPath Ruta local o URL del sitemap a parsear
     */
    private function parseExistingSitemap($sitemapPath) 
    {
        // Intentar leer como archivo local primero
        if (file_exists($sitemapPath)) {
            $content = @file_get_contents($sitemapPath);
        } else {
            // Si no existe localmente, intentar como URL (con cautela)
            $content = @file_get_contents($sitemapPath);
        }
        
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