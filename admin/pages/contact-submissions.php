<?php
/**
 * Panel de Administración - Solicitudes de Contacto
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

/**
 * Manejar acciones POST
 */
function handleAction($action, $id, $db) {
    if (!$id) {
        header('Location: ?error=missing_id');
        exit();
    }
    
    switch ($action) {
        case 'mark_read':
            $db->execute("UPDATE contact_submissions SET status = 'read' WHERE id = ?", [$id]);
            break;
            
        case 'mark_replied':
            $db->execute("UPDATE contact_submissions SET status = 'replied', replied_at = NOW() WHERE id = ?", [$id]);
            break;
            
        case 'archive':
            $db->execute("UPDATE contact_submissions SET status = 'archived' WHERE id = ?", [$id]);
            break;
            
        case 'delete':
            $db->execute("DELETE FROM contact_submissions WHERE id = ?", [$id]);
            break;
            
        case 'add_note':
            $notes = $_POST['admin_notes'] ?? '';
            $db->execute("UPDATE contact_submissions SET admin_notes = ? WHERE id = ?", [$notes, $id]);
            header("Location: ?action=view&id=$id&success=note_saved");
            exit();
    }
    
    header('Location: ?success=' . $action);
    exit();
}

// Manejar acciones
$action = $_GET['action'] ?? '';
$submissionId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    handleAction($action, $submissionId, $db);
}

// Obtener filtro de estado
$statusFilter = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Construir query con filtro
$whereClause = '';
$params = [];

if ($statusFilter !== 'all') {
    $whereClause = 'WHERE status = ?';
    $params[] = $statusFilter;
}

// Verificar si la tabla existe
try {
    $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'contact_submissions'");
    if (!$tableCheck) {
        die('
            <div style="text-align: center; padding: 50px; font-family: Arial;">
                <h1 style="color: #dc3545;">⚠️ Sistema de Contacto no Instalado</h1>
                <p>La tabla <code>contact_submissions</code> no existe en la base de datos.</p>
                <p><a href="../run-contact-migration.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                    Instalar Sistema de Contacto
                </a></p>
            </div>
        ');
    }
    
    // Obtener total de registros
    $totalQuery = "SELECT COUNT(*) as total FROM contact_submissions $whereClause";
    $totalResult = $db->fetchOne($totalQuery, $params);
    $total = $totalResult['total'] ?? 0;
    $totalPages = ceil($total / $perPage);

    // Obtener solicitudes paginadas
    $query = "
        SELECT * FROM contact_submissions 
        $whereClause
        ORDER BY 
            CASE status 
                WHEN 'new' THEN 1 
                WHEN 'read' THEN 2 
                WHEN 'replied' THEN 3 
                WHEN 'archived' THEN 4 
            END,
            created_at DESC
        LIMIT ? OFFSET ?
    ";
    $tempParams = $params;
    $tempParams[] = $perPage;
    $tempParams[] = $offset;

    $submissions = $db->fetchAll($query, $tempParams);

    // Obtener estadísticas
    $stats = $db->fetchOne("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
        FROM contact_submissions
    ");
    
} catch (Exception $e) {
    die('Error de base de datos: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Contacto - Panel Administrativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f5f6fa; color: #2c3e50; line-height: 1.6; }
        
        /* Header */
        .header { background: white; padding: 15px 30px; border-bottom: 1px solid #e1e8ed; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header h1 { color: #2c3e50; font-size: 1.5rem; font-weight: 600; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info span { color: #5a6c7d; }
        
        /* Container */
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .stat-card h3 { font-size: 2rem; margin-bottom: 8px; font-weight: 700; }
        .stat-card.new h3 { color: #667eea; }
        .stat-card.read h3 { color: #ffa502; }
        .stat-card.replied h3 { color: #2ed573; }
        .stat-card.archived h3 { color: #a4b0be; }
        .stat-card p { color: #5a6c7d; font-size: 0.95rem; }
        
        /* Filters */
        .filters { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .filter-buttons { display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-btn { padding: 10px 20px; border: 2px solid #e1e8ed; background: white; color: #5a6c7d; border-radius: 8px; cursor: pointer; transition: all 0.3s; text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .filter-btn:hover { border-color: #667eea; color: #667eea; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        
        /* Table */
        .table-container { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8f9fa; }
        th { padding: 15px; text-align: left; color: #2c3e50; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #f1f3f5; }
        tr:hover { background: #f8f9fa; }
        
        /* Status badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge.new { background: #e7e9fc; color: #667eea; }
        .badge.read { background: #fff3e0; color: #ffa502; }
        .badge.replied { background: #d4f8e8; color: #2ed573; }  
        .badge.archived { background: #f1f3f5; color: #a4b0be; }
        
        /* Action buttons */
        .btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.3s; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd8; }
        .btn-success { background: #2ed573; color: white; }
        .btn-success:hover { background: #26be62; }
        .btn-warning { background: #ffa502; color: white; }
        .btn-warning:hover { background: #ff9000; }
        .btn-danger { background: #ff4757; color: white; }
        .btn-danger:hover { background: #ee394b; }
        .btn-secondary { background: #ced6e0; color: #2c3e50; }
        .btn-secondary:hover { background: #b8c2cc; }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; padding: 20px; }
        .page-btn { padding: 8px 12px; border-radius: 6px; background: white; color: #667eea; text-decoration: none; border: 2px solid #e1e8ed; font-weight: 500; }
        .page-btn:hover { border-color: #667eea; background: #f8f9fa; }
        .page-btn.active { background: #667eea; color: white; border-color: #667eea; }
        
        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: #a4b0be; }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; }
        .empty-state h3 { color: #5a6c7d; margin-bottom: 10px; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 50px auto; padding: 30px; width: 90%; max-width: 600px; border-radius: 12px; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { color: #2c3e50; }
        .close { font-size: 28px; font-weight: bold; color: #a4b0be; cursor: pointer; }
        .close:hover { color: #ff4757; }
        .detail-row { margin: 15px 0; padding: 10px 0; border-bottom: 1px solid #f1f3f5; }
        .detail-label { font-weight: 600; color: #5a6c7d; margin-bottom: 5px; }
        .detail-value { color: #2c3e50; }
        textarea { width: 100%; padding: 10px; border: 2px solid #e1e8ed; border-radius: 8px; font-family: inherit; resize: vertical; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>📬 Solicitudes de Contacto</h1>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
            <a href="logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card new">
                <h3><?php echo $stats['new_count'] ?? 0; ?></h3>
                <p>📬 Nuevos</p>
            </div>
            <div class="stat-card read">
                <h3><?php echo $stats['read_count'] ?? 0; ?></h3>
                <p>👁️ Leídos</p>
            </div>
            <div class="stat-card replied">
                <h3><?php echo $stats['replied_count'] ?? 0; ?></h3>
                <p>✉️ Respondidos</p>
            </div>
            <div class="stat-card archived">
                <h3><?php echo $stats['archived_count'] ?? 0; ?></h3>
                <p>📦 Archivados</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                <p>📊 Total</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-buttons">
                <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">🔍 Todos</a>
                <a href="?status=new" class="filter-btn <?php echo $statusFilter === 'new' ? 'active' : ''; ?>">📬 Nuevos</a>
                <a href="?status=read" class="filter-btn <?php echo $statusFilter === 'read' ? 'active' : ''; ?>">👁️ Leídos</a>
                <a href="?status=replied" class="filter-btn <?php echo $statusFilter === 'replied' ? 'active' : ''; ?>">✉️ Respondidos</a>  
                <a href="?status=archived" class="filter-btn <?php echo $statusFilter === 'archived' ? 'active' : ''; ?>">📦 Archivados</a>
            </div>
        </div>

        <!-- Table -->
        <?php if (empty($submissions)): ?>
            <div class="table-container">
                <div class="empty-state">
                    <h3>No hay mensajes de contacto</h3>
                    <p>Cuando recibas mensajes del formulario aparecerán aquí</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($submission['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($submission['email']); ?></td>
                                <td><?php echo htmlspecialchars($submission['phone'] ?? '-'); ?></td>
                                <td><span class="badge <?php echo $submission['status']; ?>">
                                    <?php 
                                    $statusLabels = ['new' => 'Nuevo', 'read' => 'Leído', 'replied' => 'Respondido', 'archived' => 'Archivado'];
                                    echo $statusLabels[$submission['status']] ?? $submission['status'];
                                    ?>
                                </span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($submission['created_at'])); ?></td>
                                <td>
                                    <button onclick="viewDetails(<?php echo $submission['id']; ?>)" class="btn btn-primary">👁️</button>
                                    <?php if ($submission['status'] === 'new'): ?>
                                        <a href="?action=mark_read&id=<?php echo $submission['id']; ?>" class="btn btn-warning" onclick="return confirm('¿Marcar como leído?')">✓</a>
                                    <?php endif; ?>
                                    <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>" class="btn btn-success">📧</a>
                                    <a href="?action=delete&id=<?php echo $submission['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este mensaje?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?status=<?php echo $statusFilter; ?>&page=<?php echo $page - 1; ?>" class="page-btn">← Anterior</a>
                    <?php endif; ?>
                    
                    <span>Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?status=<?php echo $statusFilter; ?>&page=<?php echo $page + 1; ?>" class="page-btn">Siguiente →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal para ver detalles -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalles del Mensaje</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>

    <script>
        function viewDetails(id) {
            const submissions = <?php echo json_encode($submissions); ?>;
            const submission = submissions.find(s => s.id == id);
            
            if (!submission) return;
            
            const html = `
                <div class="detail-row">
                    <div class="detail-label">Nombre:</div>
                    <div class="detail-value">${submission.name}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><a href="mailto:${submission.email}">${submission.email}</a></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Teléfono:</div>
                    <div class="detail-value">${submission.phone || '-'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Mensaje:</div>
                    <div class="detail-value" style="white-space: pre-wrap;">${submission.message}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">IP:</div>
                    <div class="detail-value">${submission.user_ip || '-'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Fecha:</div>
                    <div class="detail-value">${new Date(submission.created_at).toLocaleString('es-ES')}</div>
                </div>
                <div style="margin-top: 20px;">
                    <a href="?action=mark_replied&id=${submission.id}" class="btn btn-success">✓ Marcar como respondido</a>
                    <a href="?action=archive&id=${submission.id}" class="btn btn-secondary">📦 Archivar</a>
                    <a href="mailto:${submission.email}" class="btn btn-primary">📧 Responder</a>
                </div>
            `;
            
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>