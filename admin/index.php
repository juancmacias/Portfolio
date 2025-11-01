<?php
/**
 * Panel Administrativo - Punto de Entrada
 */

define('ADMIN_ACCESS', true);

// Configuración de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Cargar configuración básica
    require_once __DIR__ . '/config/config.local.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/auth.php';
    
    // Inicializar autenticación
    $auth = new AdminAuth();
    
    // Verificar estado de autenticación
    if ($auth->isLoggedIn()) {
        // Usuario autenticado - ir al dashboard
        header('Location: pages/dashboard.php');
    } else {
        // Usuario no autenticado - ir al login
        header('Location: pages/login.php');
    }
    exit();
    
} catch (Exception $e) {
    // Error del sistema
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html><head><title>Error del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .error { background: #fff; padding: 20px; border-radius: 8px; max-width: 600px; margin: 50px auto; }
        .error h2 { color: #e74c3c; }
        .error-details { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style></head><body>
        <div class='error'>
            <h2>❌ Error del Sistema</h2>
            <p>Se ha producido un error al cargar el panel administrativo.</p>
            <div class='error-details'>
                <strong>Mensaje:</strong> <?php echo htmlspecialchars($e->getMessage()); ?><br>
                <strong>Archivo:</strong> <?php echo htmlspecialchars($e->getFile()); ?><br>
                <strong>Línea:</strong> <?php echo $e->getLine(); ?>
            </div>
            <p><a href="pages/login.php">🔑 Intentar Login</a> | <a href="../">🏠 Volver al Portfolio</a></p>
        </div>
    </body></html>
    <?php
}
?>