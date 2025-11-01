<?php
/**
 * Endpoint de Artículos - API Portfolio
 * Gestiona los artículos del blog/portfolio
 * URL: /api/portfolio/articles.php
 */

// Headers CORS inmediatos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Debug: Log de inicio
error_log("=== ARTICLES API START ===");
error_log("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Cargar configuración de la API
require_once __DIR__ . '/config.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handle_get_articles();
            break;
        case 'POST':
            handle_post_article();
            break;
        default:
            api_error('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    api_error('Error interno del servidor', 500, $e->getMessage());
}

/**
 * Obtener lista de artículos
 */
function handle_get_articles() {
    try {
        // Conectar a la base de datos
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar si la tabla de artículos existe - Compatible con MySQLi
        try {
            if ($db->getDriverInfo()['driver'] === 'mysqli') {
                // MySQLi
                $result = $conn->query("SHOW TABLES LIKE 'articles'");
                $table_exists = $result && $result->num_rows > 0;
            } else {
                // PDO
                $stmt = $conn->prepare("SHOW TABLES LIKE 'articles'");
                $stmt->execute();
                $table_exists = $stmt->rowCount() > 0;
            }
        } catch (Exception $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            // Si hay error verificando la tabla, asumir que existe y continuar
            $table_exists = true;
        }
        
        if (!$table_exists) {
            // La tabla no existe, devolver respuesta vacía pero válida
            $pagination = [
                'current_page' => 1,
                'per_page' => intval($_GET['limit'] ?? 10),
                'total_items' => 0,
                'total_pages' => 0,
                'has_next' => false,
                'has_prev' => false
            ];
            
            api_response(true, [
                'articles' => [],
                'pagination' => $pagination
            ], 'Sistema de artículos no inicializado - tabla no encontrada');
        }
        
        // Obtener parámetros
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? 'published';
        
        // Obtener total de artículos usando el método compatible
        if ($status === 'published') {
            if (isset($_GET['id'])) {
                $count_sql = "SELECT COUNT(*) as total FROM articles WHERE status = 'published' AND id = ?";
                $articles_sql = "SELECT id, title, slug, excerpt, content, status, featured_image, 
                               tags, meta_description, created_at, updated_at, published_at
                        FROM articles 
                        WHERE status = 'published' AND id = ?
                        ORDER BY created_at DESC";
                $query_params = [$_GET['id']];
            } else {
                $count_sql = "SELECT COUNT(*) as total FROM articles WHERE status = 'published'";
                $articles_sql = "SELECT id, title, slug, excerpt, content, status, featured_image, 
                               tags, meta_description, created_at, updated_at, published_at
                        FROM articles 
                        WHERE status = 'published'
                        ORDER BY created_at DESC 
                        LIMIT $limit OFFSET $offset";
                $query_params = [];
            }
        } else {
            // Para otros estados
            if (isset($_GET['id'])) {
                $count_sql = "SELECT COUNT(*) as total FROM articles WHERE status = ? AND id = ?";
                $articles_sql = "SELECT id, title, slug, excerpt, content, status, featured_image, 
                               tags, meta_description, created_at, updated_at, published_at
                        FROM articles 
                        WHERE status = ? AND id = ?
                        ORDER BY created_at DESC";
                $query_params = [$status, $_GET['id']];
            } else {
                $count_sql = "SELECT COUNT(*) as total FROM articles WHERE status = ?";
                $articles_sql = "SELECT id, title, slug, excerpt, content, status, featured_image, 
                               tags, meta_description, created_at, updated_at, published_at
                        FROM articles 
                        WHERE status = ?
                        ORDER BY created_at DESC 
                        LIMIT $limit OFFSET $offset";
                $query_params = [$status];
            }
        }
        
        $count_result = $db->fetchOne($count_sql, $query_params);
        $total = $count_result['total'] ?? 0;
        
        $articles = $db->fetchAll($articles_sql, $query_params);
        
        // Procesar tags (JSON a array)
        foreach ($articles as &$article) {
            $article['tags'] = json_decode($article['tags'] ?? '[]', true);
        }
        
        // Información de paginación
        $pagination = [
            'current_page' => $page,
            'per_page' => $limit,
            'total_items' => $total,
            'total_pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ];
        
        if (isset($_GET['id'])) {
            // Artículo específico
            $article = $articles[0] ?? null;
            if (!$article) {
                api_error('Artículo no encontrado', 404);
            }
            api_response(true, $article, 'Artículo obtenido correctamente');
        } else {
            // Lista de artículos
            api_response(true, [
                'articles' => $articles,
                'pagination' => $pagination
            ], 'Artículos obtenidos correctamente');
        }
        
    } catch (PDOException $e) {
        api_error('Error de base de datos', 500, $e->getMessage());
    }
}

/**
 * Crear nuevo artículo (requiere autenticación)
 */
function handle_post_article() {
    api_error('Funcionalidad no implementada. Use el panel de administración.', 501);
}
?>