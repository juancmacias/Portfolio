<?php
/**
 * Dashboard - Panel Administrativo
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/config.local.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();

// Verificar autenticación
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getUser();
$db = Database::getInstance();

// Obtener estadísticas básicas
try {
    $stats = [
        'articles_total' => $db->fetchOne("SELECT COUNT(*) as count FROM articles")['count'] ?? 0,
        'articles_published' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'] ?? 0,
        'articles_draft' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'")['count'] ?? 0,
        'projects_total' => $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'] ?? 0,
        'projects_active' => $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")['count'] ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'articles_total' => 0, 
        'articles_published' => 0, 
        'articles_draft' => 0,
        'projects_total' => 0,
        'projects_active' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Administrativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size: 2rem; color: #667eea; margin-bottom: 10px; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .action-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .action-card h3 { margin-bottom: 15px; color: #333; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin: 5px; transition: background 0.3s; }
        .btn:hover { background: #5a6fd8; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Dashboard</h1>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <a href="logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['articles_total']; ?></h3>
                <p>Total Artículos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['articles_published']; ?></h3>
                <p>Publicados</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['articles_draft']; ?></h3>
                <p>Borradores</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['projects_total']; ?></h3>
                <p>Total Proyectos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['projects_active']; ?></h3>
                <p>Proyectos Activos</p>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="actions-grid">
            <div class="action-card">
                <h3>📝 Gestión de Artículos</h3>
                <a href="articles.php" class="btn">📋 Ver Todos los Artículos</a>
                <a href="article-create.php" class="btn btn-success">➕ Nuevo Artículo</a>
                <a href="article-create.php?ai=1" class="btn btn-info">🤖 Generar con IA</a>
            </div>

            <div class="action-card">
                <h3>🚀 Gestión de Proyectos</h3>
                <a href="projects.php" class="btn">📋 Ver Todos los Proyectos</a>
                <a href="project-create.php" class="btn btn-success">➕ Nuevo Proyecto</a>
                <a href="../sql/migrate_projects.php" class="btn btn-info">📊 Migrar Datos JSON</a>
            </div>

            <div class="action-card">
                <h3>⚙️ Configuración</h3>
                <a href="settings.php" class="btn">🔧 Configuración General</a>
                <a href="settings.php#ai" class="btn btn-info">🤖 Configurar IA</a>
            </div>

            <div class="action-card">
                <h3>🔍 Herramientas SEO</h3>
                <a href="sitemap-manager.php" class="btn">🗺️ Generador de Sitemap</a>
                <a href="sitemap-manager.php?action=info" class="btn btn-info">📊 Estado del Sitemap</a>
            </div>

        </div>
    </div>
</body>
</html>