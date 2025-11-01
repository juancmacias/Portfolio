<?php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/ArticleManager.php';

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$articleManager = new ArticleManager();

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$id || !$action) {
    header('Location: articles.php');
    exit;
}

$error = null;
$success = null;

try {
    switch ($action) {
        case 'publish':
            $articleManager->changeStatus($id, 'published');
            $success = "Artículo publicado exitosamente";
            break;
            
        case 'draft':
            $articleManager->changeStatus($id, 'draft');
            $success = "Artículo convertido a borrador";
            break;
            
        case 'archive':
            $articleManager->changeStatus($id, 'archived');
            $success = "Artículo archivado";
            break;
            
        case 'delete':
            // Confirmar eliminación
            if (!isset($_GET['confirm'])) {
                // Mostrar página de confirmación
                $article = $articleManager->getArticle($id);
                if (!$article) {
                    header('Location: articles.php');
                    exit;
                }
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Confirmar Eliminación - Admin Portfolio</title>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { 
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                            background: #f8f9fa;
                            line-height: 1.6;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                        }
                        .confirm-container {
                            background: white;
                            padding: 40px;
                            border-radius: 12px;
                            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                            max-width: 500px;
                            text-align: center;
                        }
                        .warning-icon {
                            font-size: 64px;
                            color: #dc3545;
                            margin-bottom: 20px;
                        }
                        h1 {
                            color: #333;
                            margin-bottom: 15px;
                            font-size: 24px;
                        }
                        .article-info {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 8px;
                            margin: 20px 0;
                            text-align: left;
                        }
                        .article-info h3 {
                            color: #333;
                            margin-bottom: 10px;
                        }
                        .article-info p {
                            color: #666;
                            margin: 5px 0;
                        }
                        .warning-text {
                            color: #856404;
                            background: #fff3cd;
                            padding: 15px;
                            border-radius: 6px;
                            margin: 20px 0;
                            border: 1px solid #ffeaa7;
                        }
                        .actions {
                            display: flex;
                            gap: 15px;
                            justify-content: center;
                            margin-top: 30px;
                        }
                        .btn {
                            padding: 12px 24px;
                            border: none;
                            border-radius: 6px;
                            cursor: pointer;
                            text-decoration: none;
                            font-size: 14px;
                            font-weight: 500;
                            transition: all 0.2s;
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                        }
                        .btn-danger { background: #dc3545; color: white; }
                        .btn-danger:hover { background: #c82333; }
                        .btn-secondary { background: #6c757d; color: white; }
                        .btn-secondary:hover { background: #545b62; }
                    </style>
                </head>
                <body>
                    <div class="confirm-container">
                        <div class="warning-icon">⚠️</div>
                        <h1>Confirmar Eliminación</h1>
                        <p>¿Estás seguro de que quieres eliminar este artículo permanentemente?</p>
                        
                        <div class="article-info">
                            <h3><?= htmlspecialchars($article['title']) ?></h3>
                            <p><strong>Autor:</strong> <?= htmlspecialchars($article['author']) ?></p>
                            <p><strong>Estado:</strong> <?= ucfirst($article['status']) ?></p>
                            <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?></p>
                            <p><strong>Vistas:</strong> <?= number_format($article['views']) ?></p>
                        </div>
                        
                        <div class="warning-text">
                            <strong>⚠️ Esta acción no se puede deshacer.</strong><br>
                            El artículo y todos sus datos asociados serán eliminados permanentemente.
                        </div>
                        
                        <div class="actions">
                            <a href="?action=delete&id=<?= $id ?>&confirm=1" class="btn btn-danger">
                                🗑️ Sí, Eliminar Permanentemente
                            </a>
                            <a href="articles.php" class="btn btn-secondary">
                                ↩️ Cancelar
                            </a>
                        </div>
                    </div>
                </body>
                </html>
                <?php
                exit;
            } else {
                $articleManager->deleteArticle($id);
                $success = "Artículo eliminado permanentemente";
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Redirigir de vuelta a la lista con mensaje
$params = [];
if ($success) {
    $params['success'] = urlencode($success);
}
if ($error) {
    $params['error'] = urlencode($error);
}

$queryString = $params ? '?' . http_build_query($params) : '';
header('Location: articles.php' . $queryString);
exit;
?>