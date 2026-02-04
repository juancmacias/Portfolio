<?php
/**
 * SSR Entry Point - PHP renderiza HTML inicial, React hidrata después
 * 
 * Este archivo actúa como "servidor SSR" que:
 * 1. Recibe todas las peticiones HTTP (via .htaccess)
 * 2. Determina la ruta solicitada
 * 3. Obtiene datos necesarios (DB, API, etc.)
 * 4. Renderiza HTML inicial usando templates PHP
 * 5. Inyecta state inicial para React hydration
 * 6. Sirve HTML completo + scripts React
 * 
 * React se encarga de hidratar y hacer la app interactiva
 */

// Evitar ejecución directa si es un asset estático
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|map|json)$/i', $uri)) {
    return false; // Dejar que Apache sirva el archivo directamente
}

// Configuración de errores (solo desarrollo)
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
if ($isLocal) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

// Cargar templates
require_once __DIR__ . '/templates/Layout.php';
require_once __DIR__ . '/templates/ArticleView.php';

// Definir constante para acceso a archivos admin
define('ADMIN_ACCESS', true);

// Cargar configuración de DB - Detectar ubicación automáticamente
// En local: admin/ está un nivel arriba (Portfolio/admin/)
// En producción: admin/ está al mismo nivel (www/admin/)
$configPaths = [
    __DIR__ . '/admin/config/database.php',      // Producción
    __DIR__ . '/../admin/config/database.php'    // Local/Desarrollo
];

$configLoaded = false;
foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    error_log("Error: No se encontró database.php en ninguna ubicación esperada");
}

/**
 * Obtener la ruta actual limpia
 */
function getRoute() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // Eliminar query string
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }
    
    // Asegurar que empieza con /
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }
    
    return $uri;
}

/**
 * Obtener la URL base del sitio sin www para consistencia SEO
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Eliminar www. para mantener consistencia con .htaccess y sitemap
    // Importante para canonical URLs, Open Graph y metadatos SEO
    $host = preg_replace('/^www\./i', '', $host);
    
    return $protocol . '://' . $host;
}

/**
 * Renderizar un artículo individual desde la DB
 */
function renderArticle($slug) {
    try {
        // Conectar a la base de datos
        if (!class_exists('Database')) {
            return render404('Error: No se puede conectar a la base de datos');
        }
        
        $db = Database::getInstance();
        
        // Obtener artículo por slug
        $article = $db->fetchOne(
            "SELECT * FROM articles WHERE slug = ? AND status = 'published'",
            [$slug]
        );
        
        if (!$article) {
            return render404('Artículo no encontrado');
        }
        
        // Incrementar vistas (opcional en SSR) - Deshabilitado por ahora
        // La clase Database no tiene método execute()
        // TODO: Implementar incremento de vistas si es necesario
        
        // Preparar state inicial para React
        $baseUrl = getBaseUrl();
        $articleUrl = $baseUrl . '/article/' . $slug;
        
        $initialState = [
            'route' => '/article/' . $slug,
            'title' => $article['title'] . ' | Juan Carlos Macías',
            'description' => $article['excerpt'] ?? substr(strip_tags($article['content']), 0, 160),
            'url' => $articleUrl,
            'ogImage' => $article['featured_image'] ?? $baseUrl . '/Assets/avatar.png',
            'article' => $article,
            'isSSR' => true,
            'timestamp' => date('c')
        ];
        
        // Renderizar contenido con template PHP
        $content = renderArticleView($article);
        
        // Envolver en layout y devolver HTML completo
        return renderLayout($content, $initialState);
        
    } catch (Exception $e) {
        error_log("Error renderizando artículo '$slug': " . $e->getMessage());
        return render404('Error al cargar el artículo: ' . $e->getMessage());
    }
}

/**
 * Renderizar home page
 */
function renderHome() {
    $baseUrl = getBaseUrl();
    
    $initialState = [
        'route' => '/',
        'title' => 'Juan Carlos Macías | Ingeniero Full Stack de IA Generativa',
        'description' => 'Portfolio de Juan Carlos Macías - Desarrollador Full Stack especializado en Inteligencia Artificial',
        'url' => $baseUrl . '/',
        'ogImage' => $baseUrl . '/Assets/avatar.png',
        'isSSR' => true
    ];
    
    // Contenido básico - React enriquecerá después
    $content = <<<HTML
<div class="home-section">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center py-5">
                <!-- Imagen LCP pre-renderizada para optimización -->
                <img 
                    src="/Assets/b1.png" 
                    alt="Juan Carlos Macías" 
                    aria-label="Desarrollador Full Stack IA" 
                    class="img-fluid mb-4" 
                    fetchpriority="high" 
                    decoding="async" 
                    width="450" 
                    height="450" 
                    style="max-height:450px; border-radius:120px;"
                >
                
                <h1 class="heading">Juan Carlos Macías Salvador</h1>
                <h2 class="heading-subtitle">Ingeniero Full Stack de IA Generativa</h2>
                <p class="lead mt-4">
                    Desarrollador Full Stack especializado en Inteligencia Artificial, 
                    con experiencia en React, PHP, Python y tecnologías de IA generativa.
                </p>
                <div class="loading-spinner mt-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando contenido interactivo...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
    
    return renderLayout($content, $initialState);
}

/**
 * Renderizar páginas estáticas (About, Projects, etc.)
 */
function renderStaticPage($route) {
    $baseUrl = getBaseUrl();
    
    $titles = [
        '/about' => 'Sobre mí',
        '/project' => 'Proyectos',
        '/resume' => 'Currículum',
        '/articles' => 'Artículos',
        '/politics' => 'Política de privacidad'
    ];
    
    $title = ($titles[$route] ?? 'Portfolio') . ' | Juan Carlos Macías';
    
    $initialState = [
        'route' => $route,
        'title' => $title,
        'description' => 'Portfolio de Juan Carlos Macías',
        'url' => $baseUrl . $route,
        'isSSR' => true
    ];
    
    // Contenido mínimo - React renderizará el componente completo
    $content = <<<HTML
<div class="page-loading">
    <div class="container py-5 text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-3 text-muted">Cargando contenido...</p>
    </div>
</div>
HTML;
    
    return renderLayout($content, $initialState);
}

/**
 * Renderizar página 404
 */
function render404($message = 'Página no encontrada') {
    http_response_code(404);
    
    $baseUrl = getBaseUrl();
    
    $initialState = [
        'route' => '/404',
        'title' => '404 - Página no encontrada | Juan Carlos Macías',
        'description' => 'La página solicitada no existe',
        'url' => $baseUrl . '/404',
        'isSSR' => true
    ];
    
    $messageSafe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    $content = <<<HTML
<div class="container py-5 text-center">
    <div class="row">
        <div class="col-md-12">
            <h1 class="display-1">404</h1>
            <h2 class="mb-4">{$messageSafe}</h2>
            <p class="lead mb-4">La página que buscas no existe o ha sido movida.</p>
            <a href="/" class="btn btn-primary btn-lg">
                <i class="fas fa-home me-2"></i>
                Volver al inicio
            </a>
        </div>
    </div>
</div>
HTML;
    
    return renderLayout($content, $initialState);
}

/**
 * Router principal - determina qué renderizar según la ruta
 */
function renderRoute($route) {
    // Home
    if ($route === '/') {
        return renderHome();
    }
    
    // Artículo individual: /article/slug-del-articulo
    if (preg_match('#^/article/([a-z0-9\-]+)$#i', $route, $matches)) {
        $slug = $matches[1];
        return renderArticle($slug);
    }
    
    // Páginas estáticas
    $staticPages = ['/about', '/project', '/resume', '/articles', '/politics'];
    if (in_array($route, $staticPages, true)) {
        return renderStaticPage($route);
    }
    
    // 404 - Ruta no encontrada
    return render404();
}

// ==========================================
// EJECUCIÓN PRINCIPAL
// ==========================================

try {
    // Obtener ruta
    $route = getRoute();
    
    // Log para debugging (solo local)
    if ($isLocal) {
        error_log("SSR Request: " . $route);
    }
    
    // Renderizar
    $html = renderRoute($route);
    
    // Headers HTTP
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Powered-By: PHP-SSR-React');
    header('X-SSR-Route: ' . $route);
    header('Content-Length: ' . strlen($html));
    
    // Deshabilitar output buffering de Apache
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
    }
    
    // Enviar respuesta completa
    echo $html;
    
    // Forzar flush de todos los buffers
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
    
} catch (Exception $e) {
    error_log("SSR Fatal Error: " . $e->getMessage());
    
    // Error 500
    http_response_code(500);
    echo render404('Error interno del servidor');
}
