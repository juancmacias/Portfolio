<?php
/**
 * Track Visit API Endpoint
 * Registra visitas al portfolio y envía notificaciones a Telegram
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Leer datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $page = $data['page'] ?? 'unknown';
    $referrer = $data['referrer'] ?? 'direct';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Sanitizar página
    $page = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
    $page = substr($page, 0, 200); // Limitar longitud
    
    // Cargar TelegramNotifier
    $telegramPath = __DIR__ . '/../../admin/classes/TelegramNotifier.php';
    
    if (file_exists($telegramPath)) {
        require_once $telegramPath;
        
        $telegram = new TelegramNotifier();
        
        // Metadata de la visita
        $visitMetadata = [
            'page' => $page,
            'ip' => $ip,
            'referrer' => $referrer,
            'user_agent' => substr($userAgent, 0, 100),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Enviar notificación (con rate limiting interno)
        $result = $telegram->notifyPageVisit($visitMetadata);
        
        // Respuesta
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'notification_sent' => $result
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'notification_sent' => false,
            'message' => 'Telegram disabled'
        ]);
    }
    
} catch (Exception $e) {
    error_log("[TRACK-VISIT ERROR] " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal error'
    ]);
}
?>
