<?php
/**
 * Endpoint para registrar vista de artículo
 * URL: /api/portfolio/view-article.php
 */

// Headers CORS inmediatos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar configuración
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../admin/classes/ArticleManager.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_error('Método no permitido', 405);
    }
    
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    $articleId = $input['article_id'] ?? null;
    
    if (!$articleId || !is_numeric($articleId)) {
        api_error('ID de artículo requerido', 400);
    }
    
    // Usar ArticleManager para incrementar vista
    $articleManager = new ArticleManager();
    $result = $articleManager->incrementView($articleId);
    
    if ($result['success']) {
        api_response(true, [
            'message' => 'Vista registrada correctamente',
            'views' => $result['views'],
            'article_id' => $articleId
        ]);
    } else {
        api_error($result['message'], 500);
    }
    
} catch (Exception $e) {
    error_log("Error en view-article.php: " . $e->getMessage());
    api_error('Error interno del servidor', 500, $e->getMessage());
}
?>