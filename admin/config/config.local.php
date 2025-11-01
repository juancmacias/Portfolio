<?php
/**
 * ========================================
 * ARCHIVO DE CONFIGURACIÓN - DESARROLLO
 * ========================================
 * 
 * ⚠️  IMPORTANTE: 
 * Este archivo contiene configuración sensible.
 * Ajusta los valores según tu entorno local.
 * 
 * ========================================
 */

// ==========================================
// CONFIGURACIÓN DE BASE DE DATOS
// ==========================================
function get_db_config() {
    return [
        'host' => '160.153.128.38',
        'username' => 'dev',
        'password' => 'BOpDzDF9x=P}',
        'database' => 'i-portfolio'
    ];
}

// ==========================================
// CONFIGURACIÓN DE SEGURIDAD
// ==========================================
function get_security_config() {
    return [
        'session_timeout' => 3600,               // Tiempo de sesión en segundos (1 hora)
        'max_login_attempts' => 5,               // Máximo intentos de login
        'lockout_time' => 900,                   // Tiempo de bloqueo en segundos (15 min)
        'csrf_token_lifetime' => 1800,          // Vida del token CSRF en segundos (30 min)
        'password_min_length' => 8               // Longitud mínima de contraseña
    ];
}

// ==========================================
// CONFIGURACIÓN DE IA
// ==========================================
function get_ai_config() {
    return [
        'default_provider' => 'groq',           // Proveedor por defecto: 'groq', 'huggingface', 'openai'
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

// ==========================================
// CONFIGURACIÓN DEL SISTEMA
// ==========================================
function get_system_config() {
    return [
        'app_name' => 'Portfolio JCMS',
        'app_version' => get_app_version(),      // Versión obtenida dinámicamente de Git
        'timezone' => 'Europe/Madrid',             // Ajusta según tu zona horaria
        'debug_mode' => true,                   // ⚠️  Cambiar a false en producción
        'log_level' => 'INFO',                  // DEBUG, INFO, WARNING, ERROR
        'maintenance_mode' => false             // Modo mantenimiento
    ];
}

// ==========================================
// FUNCIONES AUXILIARES
// ==========================================

/**
 * Obtener la versión de la aplicación desde Git
 * @return string Versión actual (tag más reciente o commit)
 */
function get_app_version() {
    $version = 'unknown';
    
    try {
        // Obtener el directorio raíz del proyecto (2 niveles arriba desde admin/config/)
        $project_root = realpath(__DIR__ . '/../..');
        
        // Cambiar al directorio del proyecto para ejecutar comandos Git
        $original_dir = getcwd();
        if ($project_root && is_dir($project_root . '/.git')) {
            chdir($project_root);
            
            // Intentar obtener el último tag de Git
            $git_tag = @shell_exec('git describe --tags --abbrev=0 2>nul');
            if ($git_tag && trim($git_tag)) {
                $version = trim($git_tag);
                
                // Verificar si hay commits después del último tag
                $commits_since_tag = @shell_exec('git rev-list ' . trim($git_tag) . '..HEAD --count 2>nul');
                if ($commits_since_tag && trim($commits_since_tag) > 0) {
                    $short_commit = @shell_exec('git rev-parse --short HEAD 2>nul');
                    if ($short_commit && trim($short_commit)) {
                        $version .= '-dev.' . trim($commits_since_tag) . '+' . trim($short_commit);
                    }
                }
            } else {
                // Si no hay tags, usar el hash del commit actual
                $git_commit = @shell_exec('git rev-parse --short HEAD 2>nul');
                if ($git_commit && trim($git_commit)) {
                    $version = 'dev-' . trim($git_commit);
                    
                    // Añadir información de la rama si no es main/master
                    $branch = @shell_exec('git branch --show-current 2>nul');
                    if ($branch && trim($branch) && !in_array(trim($branch), ['main', 'master'])) {
                        $version .= '-' . trim($branch);
                    }
                } else {
                    // Fallback final: usar versión estática
                    $version = '1.0.5-static';
                }
            }
            
            // Restaurar directorio original
            chdir($original_dir);
        } else {
            // No es un repositorio Git, usar versión por defecto
            $version = '1.0.5-nogit';
        }
    } catch (Exception $e) {
        // En caso de error, usar versión por defecto
        $version = '1.0.5-error';
        if (function_exists('error_log')) {
            error_log("Error getting version: " . $e->getMessage());
        }
    }
    
    return $version;
}

/**
 * Obtener información adicional de la versión para debugging
 * @return array Información detallada de la versión
 */
function get_version_info() {
    $info = [
        'version' => get_app_version(),
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => detect_environment_config()
    ];
    
    try {
        $project_root = realpath(__DIR__ . '/../..');
        $original_dir = getcwd();
        
        if ($project_root && is_dir($project_root . '/.git')) {
            chdir($project_root);
            
            $info['git'] = [
                'last_tag' => trim(@shell_exec('git describe --tags --abbrev=0 2>nul') ?: 'none'),
                'commit' => trim(@shell_exec('git rev-parse HEAD 2>nul') ?: 'unknown'),
                'short_commit' => trim(@shell_exec('git rev-parse --short HEAD 2>nul') ?: 'unknown'),
                'branch' => trim(@shell_exec('git branch --show-current 2>nul') ?: 'unknown'),
                'last_commit_date' => trim(@shell_exec('git log -1 --format=%cd --date=iso 2>nul') ?: 'unknown')
            ];
            
            chdir($original_dir);
        } else {
            $info['git'] = 'not_available';
        }
    } catch (Exception $e) {
        $info['git'] = 'error: ' . $e->getMessage();
    }
    
    return $info;
}

// ==========================================
// DETECCIÓN AUTOMÁTICA DE ENTORNO
// ==========================================
function detect_environment_config() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    if (strpos($host, 'perfil.in') !== false) {
        return 'local';
    } elseif (strpos($host, 'juancarlosmacias.es') !== false) {
        return 'production';
    } else {
        return 'development';
    }
}

// ==========================================
// DETECCIÓN AUTOMÁTICA DE ENTORNO
// ==========================================
function is_development() {
    $local_hosts = ['localhost', '127.0.0.1', '::1'];
    $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return in_array($current_host, $local_hosts) || 
           strpos($current_host, '.local') !== false ||
           strpos($current_host, '.test') !== false;
}

function is_production() {
    return !is_development();
}

// ==========================================
// CONFIGURACIÓN DINÁMICA POR ENTORNO
// ==========================================
function get_environment_config() {
    $env = detect_environment_config();
    
    if ($env === 'local') {
        // Configuración para desarrollo local
        return [
            'debug_mode' => true,
            'log_level' => 'DEBUG',
            'show_errors' => true,
            'cache_enabled' => false
        ];
    } elseif ($env === 'production') {
        // Configuración para producción
        return [
            'debug_mode' => false,
            'log_level' => 'ERROR',
            'show_errors' => false,
            'cache_enabled' => true
        ];
    } else {
        // Configuración para desarrollo general
        return [
            'debug_mode' => true,
            'log_level' => 'DEBUG',
            'show_errors' => true,
            'cache_enabled' => false
        ];
    }
}

// ==========================================
// INICIALIZACIÓN
// ==========================================
// Configurar zona horaria
$system_config = get_system_config();
date_default_timezone_set($system_config['timezone']);

// Aplicar configuración de entorno
$env_config = get_environment_config();
if ($env_config['show_errors']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>