<?php
/**
 * Gestión de Proyectos - Acciones (Eliminar, Cambiar Estado, etc.)
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$action = $_GET['action'] ?? '';
$projectId = intval($_GET['id'] ?? 0);

if (empty($action) || $projectId <= 0) {
    header('Location: projects.php?error=' . urlencode('Acción o ID inválido'));
    exit();
}

try {
    $db = Database::getInstance();
    
    // Verificar que el proyecto existe
    $project = $db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
    if (!$project) {
        header('Location: projects.php?error=' . urlencode('Proyecto no encontrado'));
        exit();
    }
    
    switch ($action) {
        case 'delete':
            // Confirmar eliminación
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
                $result = $db->query("DELETE FROM projects WHERE id = ?", [$projectId]);
                if ($result) {
                    header('Location: projects.php?success=' . urlencode('Proyecto eliminado correctamente'));
                } else {
                    header('Location: projects.php?error=' . urlencode('Error al eliminar el proyecto'));
                }
                exit();
            }
            
            // Mostrar página de confirmación
            showDeleteConfirmation($project);
            break;
            
        case 'toggle-status':
            // Cambiar entre active/archived
            $newStatus = $project['status'] === 'active' ? 'archived' : 'active';
            $result = $db->query("UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $projectId]);
            
            if ($result) {
                $statusText = $newStatus === 'active' ? 'activado' : 'archivado';
                header('Location: projects.php?success=' . urlencode("Proyecto $statusText correctamente"));
            } else {
                header('Location: projects.php?error=' . urlencode('Error al cambiar el estado del proyecto'));
            }
            exit();
            break;
            
        case 'duplicate':
            // Duplicar proyecto
            $newTitle = $project['title'] . ' (Copia)';
            $newSlug = $project['slug'] . '-copia';
            
            // Verificar que el slug no exista
            $counter = 1;
            $baseSlug = $newSlug;
            while ($db->fetchOne("SELECT id FROM projects WHERE slug = ?", [$newSlug])) {
                $newSlug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $sql = "INSERT INTO projects (title, slug, description, image_path, github_link, demo_link, project_type, status, sort_order, created_at, updated_at) 
                    SELECT ?, ?, description, image_path, github_link, demo_link, project_type, 'archived', sort_order, NOW(), NOW() 
                    FROM projects WHERE id = ?";
            
            $result = $db->query($sql, [$newTitle, $newSlug, $projectId]);
            
            if ($result) {
                header('Location: projects.php?success=' . urlencode('Proyecto duplicado correctamente'));
            } else {
                header('Location: projects.php?error=' . urlencode('Error al duplicar el proyecto'));
            }
            exit();
            break;
            
        default:
            header('Location: projects.php?error=' . urlencode('Acción no válida'));
            exit();
    }
    
} catch (Exception $e) {
    header('Location: projects.php?error=' . urlencode('Error: ' . $e->getMessage()));
    exit();
}

/**
 * Mostrar página de confirmación de eliminación
 */
function showDeleteConfirmation($project) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmar Eliminación - Admin Portfolio</title>
        <style>
            body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #dc3545; margin: 0; }
            .project-info { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .project-info h3 { margin-top: 0; color: #007acc; }
            .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ffeaa7; }
            .actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
            .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; font-weight: bold; }
            .btn-danger { background: #dc3545; color: white; }
            .btn-secondary { background: #6c757d; color: white; }
            .btn:hover { opacity: 0.9; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🗑️ Confirmar Eliminación</h1>
                <p>¿Estás seguro de que deseas eliminar este proyecto?</p>
            </div>
            
            <div class="project-info">
                <h3><?= htmlspecialchars($project['title']) ?></h3>
                <p><strong>Slug:</strong> <?= htmlspecialchars($project['slug']) ?></p>
                <p><strong>Tipo:</strong> <?= ucfirst($project['project_type']) ?></p>
                <p><strong>Estado:</strong> <?= ucfirst($project['status']) ?></p>
                <p><strong>Descripción:</strong> <?= htmlspecialchars(substr($project['description'], 0, 200)) ?><?= strlen($project['description']) > 200 ? '...' : '' ?></p>
            </div>
            
            <div class="warning">
                <strong>⚠️ Advertencia:</strong> Esta acción no se puede deshacer. El proyecto será eliminado permanentemente de la base de datos.
            </div>
            
            <div class="actions">
                <a href="projects.php" class="btn btn-secondary">❌ Cancelar</a>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás COMPLETAMENTE seguro?')">
                        🗑️ Sí, Eliminar Proyecto
                    </button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>