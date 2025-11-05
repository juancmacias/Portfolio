<?php
/**
 * Endpoint AJAX para funciones de IA
 */

// Configurar para evitar output antes del JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Headers JSON primero
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

define('ADMIN_ACCESS', true);

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }
    
    // Includes
    require_once __DIR__ . '/../config/auth.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/AIContentGenerator.php';
    
    // Auth
    session_start();
    
    // Debug session
    error_log("AI API - Session data: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autenticado', 'session_debug' => $_SESSION]);
        exit;
    }
    
    // Crear instancia del generador de IA
    $aiGenerator = new AIContentGenerator();
    
    // Obtener datos
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Log de depuración (solo en desarrollo)
    error_log("AI API - Raw input: " . $rawInput);
    error_log("AI API - Parsed input: " . print_r($input, true));
    
    $action = $input['action'] ?? '';
    
    // Verificar si hay action
    if (empty($action)) {
        echo json_encode(['success' => false, 'error' => 'No action specified', 'debug' => $input]);
        exit;
    }
    
    // Test endpoint
    if ($action === 'test') {
        echo json_encode(['success' => true, 'message' => 'Endpoint funcionando']);
        exit;
    }
    
    // Verificar CSRF token si está presente
    if (isset($input['csrf_token'])) {
        $auth = new AdminAuth();
        if (!$auth->verifyCSRFToken($input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            exit;
        }
    }
    
    // Procesar acciones de IA
    switch ($action) {
        case 'generate_title':
            $topic = $input['topic'] ?? '';
            $keywords = $input['keywords'] ?? '';
            
            if (empty($topic)) {
                echo json_encode(['success' => false, 'error' => 'El tema es requerido']);
                exit;
            }
            
            $result = $aiGenerator->generateTitle($topic, $keywords);
            break;
            
        case 'generate_article':
            try {
                $title = $input['title'] ?? '';
                $keywords = $input['keywords'] ?? '';
                $wordCount = $input['word_count'] ?? 800;
                $tone = $input['tone'] ?? 'professional';
                $existingContent = $input['existing_content'] ?? '';
                
                error_log("AI API - Generate article params: title=$title, keywords=$keywords, wordCount=$wordCount, tone=$tone");
                
                if (empty($title)) {
                    echo json_encode(['success' => false, 'error' => 'El título es requerido']);
                    exit;
                }
                
                $result = $aiGenerator->generateArticle($title, $keywords, $wordCount, $tone, $existingContent);
                
                error_log("AI API - Generate article result: " . print_r($result, true));
                
            } catch (Exception $e) {
                error_log("AI API - Error in generate_article: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'generate_excerpt':
            $content = $input['content'] ?? '';
            $maxLength = $input['max_length'] ?? 150;
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => 'El contenido es requerido']);
                exit;
            }
            
            $result = $aiGenerator->generateExcerpt($content, $maxLength);
            break;
            
        case 'generate_meta_description':
            $content = $input['content'] ?? '';
            $keywords = $input['keywords'] ?? '';
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => 'El contenido es requerido']);
                exit;
            }
            
            $result = $aiGenerator->generateMetaDescription($content, $keywords);
            break;
            
        case 'generate_tags':
            $content = $input['content'] ?? '';
            $maxTags = $input['max_tags'] ?? 8;
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => 'El contenido es requerido']);
                exit;
            }
            
            $result = $aiGenerator->generateTags($content, $maxTags);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida: ' . $action]);
            exit;
    }
    
    // Responder con el resultado
    echo json_encode($result);
    
} catch (Exception $e) {
    ob_clean(); // Limpiar cualquier output previo
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

// Limpiar buffer
ob_end_flush();
?>