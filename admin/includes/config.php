<?php
/**
 * Configuración base del sistema administrativo
 */

// Configuración de la aplicación
define('ADMIN_VERSION', '2.0.0');
define('ADMIN_TITLE', 'Portfolio Admin');
define('ADMIN_DESCRIPTION', 'Sistema de administración de contenido');

// Rutas base
define('ADMIN_ROOT', __DIR__);
define('ADMIN_URL', '/admin');
define('ASSETS_URL', ADMIN_URL . '/assets');
define('API_URL', '/api/portfolio');

// Configuración de base de datos (heredada del config principal)
require_once __DIR__ . '/../config/database.php';

// Configuración de sesiones
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de errores (solo en desarrollo)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de cabeceras de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Función de autoload para clases
spl_autoload_register(function($class) {
    $paths = [
        ADMIN_ROOT . '/classes/' . $class . '.php',
        ADMIN_ROOT . '/includes/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Funciones de utilidad globales
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit();
    }
}

function redirectTo($url) {
    header('Location: ' . $url);
    exit();
}

function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Configuración de rutas
$routes = [
    'dashboard' => '/pages/dashboard.php',
    'projects' => '/pages/projects.php',
    'project-create' => '/pages/project-create.php',
    'project-edit' => '/pages/project-edit.php',
    'articles' => '/pages/articles.php',
    'article-create' => '/pages/article-create.php',
    'article-edit' => '/pages/article-create.php',
    'article-view' => '/pages/article-view.php',
    'sitemap-manager' => '/pages/sitemap-manager.php',
    'settings' => '/pages/settings.php',
    'login' => '/pages/login.php',
    'logout' => '/pages/logout.php'
];

function getRoute($name) {
    global $routes;
    return ADMIN_URL . ($routes[$name] ?? '/');
}

// Mensajes flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// Configuración de menú de navegación
$navigationMenu = [
    [
        'title' => 'Dashboard',
        'url' => getRoute('dashboard'),
        'icon' => '📊',
        'active' => ['dashboard']
    ],
    [
        'title' => 'Proyectos',
        'url' => getRoute('projects'),
        'icon' => '💼',
        'active' => ['projects', 'project-create', 'project-edit']
    ],
    [
        'title' => 'Artículos',
        'icon' => '📝',
        'children' => [
            [
                'title' => 'Todos los artículos',
                'url' => getRoute('articles'),
                'active' => ['articles']
            ],
            [
                'title' => 'Crear artículo',
                'url' => getRoute('article-create'),
                'active' => ['article-create', 'article-edit']
            ]
        ]
    ],
    [
        'title' => 'Herramientas SEO',
        'icon' => '🔍',
        'children' => [
            [
                'title' => 'Generador de Sitemap',
                'url' => getRoute('sitemap-manager'),
                'active' => ['sitemap-manager']
            ]
        ]
    ],
    [
        'title' => 'Configuración',
        'url' => getRoute('settings'),
        'icon' => '⚙️',
        'active' => ['settings']
    ]
];

// Breadcrumbs
function setBreadcrumb($items) {
    $_SESSION['breadcrumb'] = $items;
}

function getBreadcrumb() {
    return $_SESSION['breadcrumb'] ?? [];
}

// Configuración de paginación
define('ITEMS_PER_PAGE', 20);

function getPaginationData($page, $totalItems, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $itemsPerPage;
    
    return [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'prev_page' => $page - 1,
        'next_page' => $page + 1
    ];
}
?>