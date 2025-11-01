<?php
/**
 * Configuración Local - Portfolio Admin
 * EJEMPLO - Copia este archivo como config.local.php y configura tus valores
 * 
 * @package Portfolio Admin
 * @author Juan Carlos Macías
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('ADMIN_ACCESS')) {
    die('Acceso directo no permitido');
}

/**
 * Configuración de Base de Datos
 * Actualiza estos valores con tu configuración de base de datos
 */
function get_db_config() {
    return [
        'host' => 'localhost',                   // ⚠️  CAMBIAR: Host de tu base de datos
        'database' => 'tu_database',            // ⚠️  CAMBIAR: Nombre de tu base de datos
        'username' => 'tu_usuario',             // ⚠️  CAMBIAR: Usuario de base de datos
        'password' => 'tu_password',            // ⚠️  CAMBIAR: Contraseña de base de datos
        'charset' => 'utf8mb4',
        'port' => 3306
    ];
}

/**
 * Configuración del Sistema
 */
function get_system_config() {
    return [
        'app_name' => 'Portfolio Admin',
        'app_version' => '1.0.0',
        'debug_mode' => true,                    // ⚠️  CAMBIAR: false en producción
        'session_timeout' => 3600,              // 1 hora
        'upload_max_size' => 10485760,          // 10MB en bytes
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'timezone' => 'Europe/Madrid'
    ];
}

/**
 * Configuración de Seguridad
 */
function get_security_config() {
    return [
        'password_min_length' => 8,
        'session_name' => 'portfolio_admin_session',
        'csrf_token_name' => '_csrf_token',
        'max_login_attempts' => 5,
        'lockout_duration' => 900               // 15 minutos
    ];
}

/**
 * Configuración de IA/API (Opcional)
 * Solo configurar si planeas usar las funciones de IA
 */
function get_ai_config() {
    return [
        // Configuración para generación de contenido automático
        'enabled' => false,                     // ⚠️  CAMBIAR: true si quieres usar IA
        'default_provider' => 'groq',           // 'groq', 'huggingface', 'openai'
        'max_content_length' => 10000,          // Longitud máxima del contenido
        'rate_limit_requests' => 10,            // Requests por minuto
        'rate_limit_window' => 60,              // Ventana de tiempo en segundos
        
        // API Keys (configura solo los proveedores que uses)
        'api_keys' => [
            'groq' => '',                        // ⚠️  CAMBIAR: Tu API key de Groq
            'huggingface' => '',                 // ⚠️  CAMBIAR: Tu API key de Hugging Face
            'openai' => ''                       // ⚠️  CAMBIAR: Tu API key de OpenAI
        ]
    ];
}

/**
 * Verificar si estamos en modo desarrollo
 */
function is_development() {
    $config = get_system_config();
    return $config['debug_mode'] === true;
}

/**
 * Obtener configuración de email (para notificaciones)
 */
function get_email_config() {
    return [
        'enabled' => false,                     // ⚠️  CAMBIAR: true si quieres emails
        'smtp_host' => 'smtp.gmail.com',        // ⚠️  CAMBIAR: Tu servidor SMTP
        'smtp_port' => 587,
        'smtp_secure' => 'tls',                 // 'tls' o 'ssl'
        'smtp_username' => '',                  // ⚠️  CAMBIAR: Tu email
        'smtp_password' => '',                  // ⚠️  CAMBIAR: Tu contraseña de email
        'from_email' => 'admin@tudominio.com', // ⚠️  CAMBIAR: Email remitente
        'from_name' => 'Portfolio Admin'
    ];
}

/**
 * Rutas del sistema
 */
function get_paths_config() {
    $base_path = dirname(dirname(dirname(__FILE__)));
    
    return [
        'base' => $base_path,
        'admin' => $base_path . '/admin',
        'uploads' => $base_path . '/frontend/public/Assets',
        'temp' => $base_path . '/temp',
        'logs' => $base_path . '/logs'
    ];
}

/**
 * URLs del sistema
 */
function get_urls_config() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return [
        'base_url' => $protocol . '://' . $host,
        'admin_url' => $protocol . '://' . $host . '/admin',
        'api_url' => $protocol . '://' . $host . '/api',
        'assets_url' => $protocol . '://' . $host . '/Assets'
    ];
}

// Configurar zona horaria
$system_config = get_system_config();
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($system_config['timezone']);
}

// Configurar manejo de errores según el entorno
if (is_development()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

/**
 * INSTRUCCIONES DE CONFIGURACIÓN:
 * 
 * 1. Copia este archivo como 'config.local.php'
 * 2. Actualiza la configuración de base de datos en get_db_config()
 * 3. Cambia debug_mode a false en producción
 * 4. Si usas IA, configura las API keys en get_ai_config()
 * 5. Si necesitas emails, configura SMTP en get_email_config()
 * 6. Asegúrate de que el archivo config.local.php esté en .gitignore
 */
?>