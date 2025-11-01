<?php
/**
 * Logout - Cerrar Sesión
 * Termina la sesión administrativa de forma segura
 * 
 * @package Portfolio
 * @author Juan Carlos Macías
 * @version 1.0.0
 */

// Definir constante de acceso
define('ADMIN_ACCESS', true);

// Incluir archivos necesarios
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Inicializar autenticación
$auth = new AdminAuth();

// Obtener usuario actual antes del logout (para log)
$user = $auth->getUser();

// Realizar logout
$auth->logout();

// Log del logout (opcional)
if ($user) {
    error_log("Admin logout: " . $user['username']);
}

// Redireccionar al login
header("Location: login.php");
exit;
?>