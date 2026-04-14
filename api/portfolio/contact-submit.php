<?php
/**
 * API Endpoint - Formulario de Contacto
 * Procesa solicitudes del formulario de contacto público
 * 
 * @package Portfolio
 * @author Juan Carlos Macías
 * @version 1.0
 */

// Configuración de errores (desactivado en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar cualquier output previo
if (ob_get_level()) ob_end_clean();
ob_start();

/**
 * Función helper para responder JSON (definir PRIMERO)
 */
function respondJson($success, $data = null, $error = null, $httpCode = 200) {
    // Limpiar cualquier output buffer
    if (ob_get_level()) ob_end_clean();
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Manejador de errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_end_clean();
        error_log("Fatal error: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
        respondJson(false, null, 'Error interno del servidor', 500);
    }
});

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(false, null, 'Método no permitido', 405);
}

// Cargar dependencias con manejo de errores
try {
    if (!defined('ADMIN_ACCESS')) {
        define('ADMIN_ACCESS', true);
    }
    
    $dbPath = __DIR__ . '/../../admin/config/database.php';
    if (!file_exists($dbPath)) {
        throw new Exception('Archivo de configuración no encontrado');
    }
    require_once $dbPath;
    
    // TelegramNotifier es opcional
    $telegramPath = __DIR__ . '/../../admin/classes/TelegramNotifier.php';
    if (file_exists($telegramPath)) {
        require_once $telegramPath;
    }
} catch (Exception $e) {
    error_log('Error loading dependencies: ' . $e->getMessage());
    respondJson(false, null, 'Error de configuración del servidor', 500);
} catch (Error $e) {
    error_log('Fatal error loading dependencies: ' . $e->getMessage());
    respondJson(false, null, 'Error interno del servidor', 500);
}

try {
    // Obtener datos del formulario
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campos requeridos
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $message = trim($input['message'] ?? '');
    $website = trim($input['website'] ?? ''); // Honeypot
    
    // Anti-bot: verificar honeypot (debe estar vacío)
    if (!empty($website)) {
        // Bot detectado, registrar en log pero no devolver error explícito
        error_log('Bot detectado en formulario contacto - Honeypot field rellenado: ' . $website);
        // Simular respuesta exitosa para confundir al bot
        respondJson(true, ['id' => 0], 'Mensaje recibido');
    }
    
    // Validaciones
    if (empty($name) || strlen($name) < 3) {
        throw new Exception('El nombre debe tener al menos 3 caracteres');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    if (empty($phone) || strlen($phone) < 9) {
        throw new Exception('El teléfono debe tener al menos 9 caracteres');
    }
    
    if (empty($message) || strlen($message) < 10) {
        throw new Exception('El mensaje debe tener al menos 10 caracteres');
    }
    
    // Sanitizar inputs
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Rate limiting - verificar envíos recientes del mismo IP
    $db = Database::getInstance();
    $userIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $recentSubmission = $db->fetchOne(
        "SELECT id FROM contact_submissions 
         WHERE user_ip = ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
         ORDER BY created_at DESC LIMIT 1",
        [$userIp]
    );
    
    if ($recentSubmission) {
        throw new Exception('Ya has enviado un mensaje recientemente. Por favor, espera unos minutos antes de volver a intentarlo.');
    }
    
    // Insertar en base de datos (subject como cadena vacía para compatibilidad)
    $submissionId = $db->insert(
        "INSERT INTO contact_submissions 
         (name, email, phone, subject, message, user_ip, user_agent, referrer, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')",
        [
            $name,
            $email,
            $phone,
            '', // Subject como cadena vacía (campo eliminado del formulario)
            $message,
            $userIp,
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $_SERVER['HTTP_REFERER'] ?? 'direct'
        ]
    );
    
    // Enviar notificación por Telegram (si está habilitado)
    try {
        if (function_exists('get_telegram_config')) {
            $telegramConfig = get_telegram_config();
            
            if ($telegramConfig['enabled']) {
                $notifier = new TelegramNotifier();
                
                $telegramMessage = "📬 *Nuevo Mensaje de Contacto* #" . $submissionId . "\n\n";
                $telegramMessage .= "👤 *Nombre:* " . $name . "\n";
                $telegramMessage .= "📧 *Email:* " . $email . "\n";
                $telegramMessage .= "📱 *Teléfono:* " . $phone . "\n\n";
                $telegramMessage .= "💬 *Mensaje:*\n" . substr($message, 0, 200);
                
                if (strlen($message) > 200) {
                    $telegramMessage .= "...";
                }
                
                $telegramMessage .= "\n\n🔗 Ver en admin: /admin/pages/contact-submissions.php?id=" . $submissionId;
                
                $notifier->sendMessage($telegramMessage);
            }
        }
    } catch (Exception $e) {
        // Error en notificación no debe romper el flujo
        error_log("Error enviando notificación Telegram: " . $e->getMessage());
    }
    
    // Respuesta exitosa
    respondJson(true, [
        'submission_id' => $submissionId,
        'message' => '¡Gracias por tu mensaje! Te responderé lo antes posible.'
    ]);
    
} catch (Exception $e) {
    error_log("Error in contact submit: " . $e->getMessage());
    respondJson(false, null, $e->getMessage(), 400);
} catch (Error $e) {
    error_log("Fatal error in contact submit: " . $e->getMessage());
    respondJson(false, null, 'Error interno del servidor', 500);
}
