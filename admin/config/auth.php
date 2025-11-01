<?php
/**
 * Sistema de Autenticación Simplificado
 */

if (!defined('ADMIN_ACCESS')) {
    die('Acceso directo no permitido');
}

require_once __DIR__ . '/database.php';

class AdminAuth {
    private $db;
    private $sessionTimeout = 3600; // 1 hora
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }
    
    /**
     * Iniciar sesión segura
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Verificar si el usuario está logueado
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true &&
               $this->checkSessionTimeout();
    }
    
    /**
     * Verificar timeout de sesión
     */
    private function checkSessionTimeout() {
        if (!isset($_SESSION['last_activity'])) {
            return false;
        }
        
        if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Intentar login
     */
    public function login($username, $password) {
        try {
            // Verificar si hay usuarios admin primero
            if (!$this->hasAdminUsers()) {
                // No hay usuarios, crear el primero
                return $this->createFirstAdmin($username, $password);
            }
            
            // Buscar usuario en la base de datos
            $sql = "SELECT * FROM admin_users WHERE username = ? AND active = 1";
            $user = $this->db->fetchOne($sql, [$username]);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            // Verificar que el usuario tenga el campo password
            if (!isset($user['password']) || empty($user['password'])) {
                return ['success' => false, 'error' => 'Usuario sin contraseña válida'];
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Contraseña incorrecta'];
            }
            
            // Login exitoso
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['last_activity'] = time();
            
            // Actualizar último login
            $this->db->query(
                "UPDATE admin_users SET last_login = NOW() WHERE id = ?", 
                [$user['id']]
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error del sistema: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        return true;
    }
    
    /**
     * Requerir autenticación - Redirige a login si no está autenticado
     */
    public static function requireAuth() {
        $auth = new self();
        if (!$auth->isLoggedIn()) {
            // Determinar la ruta correcta al login
            $loginPath = 'login.php';
            if (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
                $loginPath = 'login.php';
            } else {
                $loginPath = 'pages/login.php';
            }
            
            header("Location: $loginPath");
            exit;
        }
    }
    
    /**
     * Obtener datos del usuario actual
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $sql = "SELECT id, username, email, name FROM admin_users WHERE username = ?";
            return $this->db->fetchOne($sql, [$_SESSION['admin_user']]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Verificar si existe al menos un usuario admin
     */
    public function hasAdminUsers() {
        try {
            // Primero verificar si la tabla existe
            $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'admin_users'");
            if (!$tableExists) {
                return false;
            }
            
            $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM admin_users WHERE active = 1");
            return ($result && $result['count'] > 0);
        } catch (Exception $e) {
            // Si hay error (tabla no existe, etc.), asumir que no hay usuarios
            return false;
        }
    }
    
    /**
     * Crear primer usuario admin (solo si no existen usuarios)
     */
    public function createFirstAdmin($username, $password, $email = '') {
        try {
            // Verificar que no existan usuarios
            if ($this->hasAdminUsers()) {
                return ['success' => false, 'error' => 'Ya existen usuarios administradores'];
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO admin_users (username, password, email, name, active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())";
            
            $this->db->query($sql, [$username, $hashedPassword, $email, $username]);
            
            return ['success' => true, 'message' => 'Usuario administrador creado correctamente'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al crear usuario: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generar token CSRF
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar token CSRF
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>