<?php
/**
 * API REST para Proyectos - Portfolio
 * Manejo de proyectos desde base de datos MySQL
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
error_log("=== PROJECTS API START ===");
error_log("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Cargar configuración de la API
require_once __DIR__ . '/config.php';

// Cargar utilidades de imágenes
require_once __DIR__ . '/../../admin/includes/image_utils.php';

try {
    // Crear instancia de base de datos
    $db = Database::getInstance();
    
    // Verificar si se solicita un proyecto específico por slug o ID
    $project_slug = $_GET['slug'] ?? null;
    $project_id = $_GET['id'] ?? null;
    
    if ($project_slug || $project_id) {
        // Buscar proyecto específico
        if ($project_slug) {
            $proyecto = $db->fetchOne("SELECT * FROM projects WHERE slug = ? AND status = 'active'", [$project_slug]);
        } else {
            $proyecto = $db->fetchOne("SELECT * FROM projects WHERE id = ? AND status = 'active'", [intval($project_id)]);
        }
        
        if (!$proyecto) {
            api_error('Proyecto no encontrado', 404);
        }
        
        // Formatear proyecto para compatibilidad EXACTA con JSON original
        $formatted_project = [
            'id' => intval($proyecto['id']),
            'imgPath' => $proyecto['image_path'] ?? '',
            'isBlog' => isset($proyecto['is_blog']) ? (bool)$proyecto['is_blog'] : false,
            'title' => $proyecto['title'] ?? '',
            'description' => $proyecto['description'] ?? '',
            'ghLink' => $proyecto['github_link'] ?? '',
            'demoLink' => $proyecto['demo_link'] ?? '',
            'tipo' => $proyecto['project_type'] ?? 'web'
        ];
        
        api_response(true, $formatted_project, 'Proyecto obtenido correctamente');
        
    } else {
        // Obtener parámetros de filtrado y paginación
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(50, intval($_GET['limit'] ?? 10))); // Máximo 50 por página
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = 'active'; // Solo proyectos activos para frontend público
        
        // Construir condiciones WHERE
        $whereConditions = ["status = ?"];
        $params = [$status];
        
        // Filtro de búsqueda
        if (!empty($search)) {
            $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Filtro de tipo
        if (!empty($type)) {
            $whereConditions[] = "project_type = ?";
            $params[] = $type;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        // Calcular offset para paginación
        $offset = ($page - 1) * $limit;
        
        // Contar total de proyectos
        $countSQL = "SELECT COUNT(*) as total FROM projects $whereClause";
        $totalResult = $db->fetchOne($countSQL, $params);
        $total = $totalResult ? $totalResult['total'] : 0;
        
        // Obtener proyectos con paginación
        $projectsSQL = "SELECT 
                            id, title, slug, description, image_path, 
                            github_link, demo_link, project_type, status,
                            is_blog, is_featured,
                            sort_order, created_at, updated_at
                        FROM projects 
                        $whereClause 
                        ORDER BY sort_order ASC, created_at DESC 
                        LIMIT $limit OFFSET $offset";
        
        $proyectos = $db->fetchAll($projectsSQL, $params);
        
        // Formatear datos para compatibilidad EXACTA con JSON original
        $formatted_projects = array_map(function($proyecto) {
            return [
                'id' => intval($proyecto['id']),
                'imgPath' => $proyecto['image_path'] ?? '',
                'isBlog' => isset($proyecto['is_blog']) ? (bool)$proyecto['is_blog'] : false,
                'title' => $proyecto['title'] ?? '',
                'description' => $proyecto['description'] ?? '',
                'ghLink' => $proyecto['github_link'] ?? '',
                'demoLink' => $proyecto['demo_link'] ?? '',
                'tipo' => $proyecto['project_type'] ?? 'web'
            ];
        }, $proyectos);
        
        // Devolver respuesta en formato esperado por el frontend
        $response = [
            'success' => true,
            'data' => [
                'projects' => $formatted_projects,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ],
            'message' => 'Proyectos obtenidos correctamente'
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    // En caso de error, devolver estructura con success: false
    $error_response = [
        'success' => false,
        'data' => [
            'projects' => []
        ],
        'message' => 'Error al obtener proyectos: ' . $e->getMessage()
    ];
    
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>