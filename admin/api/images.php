<?php
/**
 * API para gestión de imágenes de artículos - Admin Panel
 * Requiere autenticación de administrador
 */

define('ADMIN_ACCESS', true);

// Cargar configuración y autenticación
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación de administrador
if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Habilitar logging de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);

// Configurar headers para JSON - CORS simplificado y directo
header('Content-Type: application/json; charset=utf-8');

// CORS directo - sin complicaciones
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Debug: Log del request para admin
error_log("=== ADMIN IMAGES API REQUEST ===");
error_log("User: " . ($_SESSION['admin_user'] ?? 'Unknown'));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Query: " . $_SERVER['QUERY_STRING']);
error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para enviar respuesta JSON
function sendResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    // Log de la respuesta para admin
    error_log("ADMIN Images API Response: " . json_encode($response));
    
    echo json_encode($response);
    exit();
}

// Configuración de directorios - Adaptado para servidor
$PROJECT_ROOT = dirname(dirname(dirname(__DIR__))); // Subir tres niveles desde admin/api/

// Detectar si estamos en servidor o local y configurar paths apropiados
$isServer = (strpos(__DIR__, 'public_html') !== false) || (strpos($_SERVER['DOCUMENT_ROOT'], 'public_html') !== false);

if ($isServer) {
    // Configuración para servidor (public_html)
    $UPLOAD_DIR = $_SERVER['DOCUMENT_ROOT'] . '/Assets/Projects/';
    $THUMBNAILS_DIR = $UPLOAD_DIR . 'thumbnails/';
    $URL_PATH = '/Assets/Projects/';
} else {
    // Configuración para local (desarrollo)
    $UPLOAD_DIR = $PROJECT_ROOT . '/frontend/public/Assets/Projects/';
    $THUMBNAILS_DIR = $UPLOAD_DIR . 'thumbnails/';
    $URL_PATH = '/Assets/Projects/';
}

// Normalizar separadores de directorio para el sistema actual
$UPLOAD_DIR = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $UPLOAD_DIR);
$THUMBNAILS_DIR = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $THUMBNAILS_DIR);

// Obtener la URL base correcta
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$BASE_URL = $protocol . '://' . $host;

// Debug: Log de paths calculados
error_log("PROJECT_ROOT: " . $PROJECT_ROOT);
error_log("SERVER_MODE: " . ($isServer ? 'SERVER' : 'LOCAL'));
error_log("UPLOAD_DIR: " . $UPLOAD_DIR);
error_log("THUMBNAILS_DIR: " . $THUMBNAILS_DIR);
error_log("URL_PATH: " . $URL_PATH);

// Crear directorios si no existen
try {
    if (!is_dir($UPLOAD_DIR)) {
        if (!mkdir($UPLOAD_DIR, 0755, true)) {
            error_log("Failed to create UPLOAD_DIR: " . $UPLOAD_DIR);
            sendResponse(false, 'No se pudo crear el directorio de subida');
        }
        error_log("Created UPLOAD_DIR: " . $UPLOAD_DIR);
    }
    if (!is_dir($THUMBNAILS_DIR)) {
        if (!mkdir($THUMBNAILS_DIR, 0755, true)) {
            error_log("Failed to create THUMBNAILS_DIR: " . $THUMBNAILS_DIR);
            sendResponse(false, 'No se pudo crear el directorio de miniaturas');
        }
        error_log("Created THUMBNAILS_DIR: " . $THUMBNAILS_DIR);
    }
} catch (Exception $e) {
    error_log("Exception creating directories: " . $e->getMessage());
    sendResponse(false, 'Error al crear directorios: ' . $e->getMessage());
}

// Configuración de imágenes - Restricciones más estrictas para admin
$MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Verificar límite de archivos por sesión (anti-spam)
if (!isset($_SESSION['upload_count'])) {
    $_SESSION['upload_count'] = 0;
    $_SESSION['upload_reset_time'] = time();
}

// Reset contador cada hora
if (time() - $_SESSION['upload_reset_time'] > 3600) {
    $_SESSION['upload_count'] = 0;
    $_SESSION['upload_reset_time'] = time();
}

// Función para sanitizar nombres de archivo
function sanitizeFilename($filename) {
    $info = pathinfo($filename);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $info['filename']);
    $extension = strtolower($info['extension']);
    return $name . '.' . $extension;
}

// Función para formatear tamaño de archivo
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = array('B', 'KB', 'MB', 'GB');
    $i = floor(log($bytes) / log($k));
    return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
}

// Obtener método y acción
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Procesar request
try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $images = array();
                
                // Buscar archivos de imagen
                $pattern = $UPLOAD_DIR . '*.{jpg,jpeg,png,gif,webp}';
                $files = glob($pattern, GLOB_BRACE);
                
                if ($files === false) {
                    $files = array();
                }
                
                foreach ($files as $file) {
                    if (!is_file($file)) continue;
                    
                    $filename = basename($file);
                    $info = pathinfo($filename);
                    $name = $info['filename'];
                    
                    // Solo mostrar archivos originales (sin sufijos de redes sociales)
                    if (preg_match('/_(facebook|instagram|linkedin|twitter|thumb)$/', $name)) {
                        continue;
                    }
                    
                    $filesize = filesize($file);
                    $created = date('Y-m-d H:i:s', filemtime($file));
                    
                    $imageData = array(
                        'filename' => $filename,
                        'name' => $name,
                        'size' => $filesize,
                        'url' => $BASE_URL . $URL_PATH . $filename,
                        'created' => $created
                    );
                    
                    // Verificar si existe thumbnail
                    $thumbnailPath = $THUMBNAILS_DIR . $name . '_thumb.' . $info['extension'];
                    if (file_exists($thumbnailPath)) {
                        $imageData['thumbnail'] = $BASE_URL . $URL_PATH . 'thumbnails/' . $name . '_thumb.' . $info['extension'];
                    }
                    
                    // Buscar versiones para redes sociales
                    $versions = array();
                    $socials = array('facebook', 'instagram', 'linkedin', 'twitter');
                    foreach ($socials as $social) {
                        $socialFile = $UPLOAD_DIR . $name . '_' . $social . '.' . $info['extension'];
                        if (file_exists($socialFile)) {
                            $versions[$social] = $BASE_URL . $URL_PATH . $name . '_' . $social . '.' . $info['extension'];
                        }
                    }
                    
                    if (!empty($versions)) {
                        $imageData['versions'] = $versions;
                    }
                    
                    $images[] = $imageData;
                }
                
                sendResponse(true, 'Imágenes cargadas', array(
                    'images' => $images,
                    'total' => count($images)
                ));
                
            } else {
                sendResponse(false, 'Acción no válida para GET');
            }
            break;
            
        case 'POST':
            if ($action === 'upload') {
                // Verificar límite de uploads por hora
                if ($_SESSION['upload_count'] >= 50) {
                    sendResponse(false, 'Límite de subidas por hora alcanzado', null, 429);
                }
                
                // Verificar que se envió un archivo
                if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    sendResponse(false, 'No se recibió ningún archivo válido');
                }
                
                $file = $_FILES['image'];
                
                // Validar tamaño
                if ($file['size'] > $MAX_FILE_SIZE) {
                    sendResponse(false, 'El archivo es demasiado grande. Máximo 10MB');
                }
                
                // Validar tipo MIME
                if (!in_array($file['type'], $ALLOWED_TYPES)) {
                    sendResponse(false, 'Tipo de archivo no permitido. Solo: JPG, PNG, GIF, WEBP');
                }
                
                // Validar extensión
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, $ALLOWED_EXTENSIONS)) {
                    sendResponse(false, 'Extensión de archivo no permitida');
                }
                
                // Generar nombre único
                $filename = sanitizeFilename($file['name']);
                $targetPath = $UPLOAD_DIR . $filename;
                
                // Si ya existe, agregar timestamp
                if (file_exists($targetPath)) {
                    $info = pathinfo($filename);
                    $filename = $info['filename'] . '_' . time() . '.' . $info['extension'];
                    $targetPath = $UPLOAD_DIR . $filename;
                }
                
                // Mover archivo
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    sendResponse(false, 'Error al guardar el archivo');
                }
                
                // Incrementar contador de uploads
                $_SESSION['upload_count']++;
                
                // Log de la subida
                error_log("ADMIN: Image uploaded by " . ($_SESSION['admin_user'] ?? 'Unknown') . " - " . $filename);
                
                sendResponse(true, 'Imagen subida exitosamente', array(
                    'image' => array(
                        'filename' => $filename,
                        'url' => $BASE_URL . $URL_PATH . $filename,
                        'size' => filesize($targetPath)
                    )
                ));
                
            } else {
                sendResponse(false, 'Acción no válida para POST');
            }
            break;
            
        case 'DELETE':
            if ($action === 'delete') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['filename'])) {
                    sendResponse(false, 'Nombre de archivo requerido');
                }
                
                $filename = basename($input['filename']); // Seguridad
                $filePath = $UPLOAD_DIR . $filename;
                
                if (!file_exists($filePath)) {
                    sendResponse(false, 'El archivo no existe');
                }
                
                // Eliminar archivo original
                if (!unlink($filePath)) {
                    sendResponse(false, 'Error al eliminar el archivo');
                }
                
                // Eliminar versiones asociadas
                $info = pathinfo($filename);
                $name = $info['filename'];
                $extension = $info['extension'];
                
                // Eliminar thumbnail
                $thumbnailFile = $THUMBNAILS_DIR . $name . '_thumb.' . $extension;
                if (file_exists($thumbnailFile)) {
                    unlink($thumbnailFile);
                }
                
                // Eliminar versiones para redes sociales
                $socials = array('facebook', 'instagram', 'linkedin', 'twitter');
                foreach ($socials as $social) {
                    $socialFile = $UPLOAD_DIR . $name . '_' . $social . '.' . $extension;
                    if (file_exists($socialFile)) {
                        unlink($socialFile);
                    }
                }
                
                // Log de la eliminación
                error_log("ADMIN: Image deleted by " . ($_SESSION['admin_user'] ?? 'Unknown') . " - " . $filename);
                
                sendResponse(true, 'Imagen eliminada exitosamente');
                
            } else {
                sendResponse(false, 'Acción no válida para DELETE');
            }
            break;
            
        default:
            sendResponse(false, 'Método HTTP no permitido');
            break;
    }
    
} catch (Exception $e) {
    sendResponse(false, 'Error del servidor: ' . $e->getMessage());
} catch (Error $e) {
    sendResponse(false, 'Error fatal: ' . $e->getMessage());
}
?>