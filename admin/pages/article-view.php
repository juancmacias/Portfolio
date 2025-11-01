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
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: articles.php');
    exit;
}

// Obtener el artículo
try {
    $article = $articleManager->getArticle($id);
    if (!$article) {
        header('Location: articles.php?error=' . urlencode('Artículo no encontrado'));
        exit;
    }
} catch (Exception $e) {
    header('Location: articles.php?error=' . urlencode('Error al cargar el artículo: ' . $e->getMessage()));
    exit;
}

$pageTitle = "Ver Artículo";

// Función mejorada para convertir Markdown a HTML
function markdownToHtml($text) {
    // Escapar caracteres HTML primero, excepto los que vamos a procesar
    $text = htmlspecialchars($text, ENT_NOQUOTES);
    
    // Procesar bloques de código (```codigo```)
    $text = preg_replace('/```([a-z]*)\n?(.*?)```/s', '<pre><code class="language-$1">$2</code></pre>', $text);
    
    // Procesar código inline (`codigo`)
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    
    // Headers (deben ir antes que negritas para evitar conflictos)
    $text = preg_replace('/^#### (.*$)/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $text);
    
    // Listas no ordenadas
    $text = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text);
    
    // Listas ordenadas
    $text = preg_replace('/^\d+\. (.*)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>)/s', '<ol>$1</ol>', $text);
    
    // Blockquotes
    $text = preg_replace('/^> (.*)$/m', '<blockquote>$1</blockquote>', $text);
    
    // Líneas horizontales
    $text = preg_replace('/^---$|^\*\*\*$/m', '<hr>', $text);
    
    // Bold y cursiva (después de headers)
    $text = preg_replace('/\*\*\*(.*?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    
    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
    
    // Imágenes
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" style="max-width: 100%; height: auto; margin: 10px 0;">', $text);
    
    // Convertir saltos de línea dobles en párrafos
    $paragraphs = explode("\n\n", $text);
    $result = '';
    
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if (!empty($paragraph)) {
            // No envolver en <p> si ya es un elemento de bloque
            if (preg_match('/^<(h[1-6]|ul|ol|blockquote|pre|hr)/', $paragraph)) {
                $result .= $paragraph . "\n";
            } else {
                // Convertir saltos de línea simples en <br>
                $paragraph = str_replace("\n", "<br>\n", $paragraph);
                $result .= '<p>' . $paragraph . '</p>' . "\n";
            }
        }
    }
    
    // Limpiar párrafos vacíos y espacios extra
    $result = preg_replace('/<p><\/p>/', '', $result);
    $result = preg_replace('/\s+/', ' ', $result);
    $result = str_replace('<br> ', '<br>', $result);
    
    return $result;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - Admin Portfolio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
            color: #333;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { color: #333; font-size: 24px; }
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Botones */
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: #007cba; color: white; }
        .btn-primary:hover { background: #005a87; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .btn-outline { background: transparent; color: #007cba; border: 1px solid #007cba; }
        .btn-outline:hover { background: #007cba; color: white; }
        
        /* Layout */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            .sidebar {
                order: -1;
            }
        }
        
        /* Article content */
        .article-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .article-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .article-title {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .article-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .article-excerpt {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007cba;
            border-radius: 4px;
            font-style: italic;
            color: #555;
            margin-bottom: 20px;
        }
        
        .article-content {
            font-size: 16px;
            line-height: 1.8;
            color: #333;
        }
        
        .article-content h1,
        .article-content h2,
        .article-content h3,
        .article-content h4 {
            margin: 25px 0 15px 0;
            color: #333;
            font-weight: 600;
        }
        
        .article-content h1 { font-size: 28px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .article-content h2 { font-size: 24px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .article-content h3 { font-size: 20px; }
        .article-content h4 { font-size: 18px; }
        
        .article-content p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        .article-content a {
            color: #007cba;
            text-decoration: none;
            border-bottom: 1px dotted #007cba;
        }
        .article-content a:hover {
            text-decoration: none;
            border-bottom: 1px solid #007cba;
            background-color: rgba(0, 124, 186, 0.05);
        }
        
        /* Listas */
        .article-content ul,
        .article-content ol {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        .article-content li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        /* Código */
        .article-content code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            color: #d63384;
            border: 1px solid #e9ecef;
        }
        
        .article-content pre {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
            margin: 20px 0;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .article-content pre code {
            background: none;
            padding: 0;
            border: none;
            color: #333;
            font-size: inherit;
        }
        
        /* Blockquotes */
        .article-content blockquote {
            background: #f8f9fa;
            border-left: 4px solid #007cba;
            margin: 20px 0;
            padding: 15px 20px;
            font-style: italic;
            color: #555;
            border-radius: 0 8px 8px 0;
        }
        
        .article-content blockquote p {
            margin-bottom: 0;
        }
        
        /* Líneas horizontales */
        .article-content hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ddd, transparent);
            margin: 30px 0;
        }
        
        /* Imágenes dentro del contenido */
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: block;
        }
        
        /* Tablas si las hay */
        .article-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .article-content th,
        .article-content td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .article-content th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .article-content tr:hover {
            background: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .sidebar-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sidebar-section h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
        }
        
        .meta-item {
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .meta-label {
            font-weight: 600;
            color: #333;
        }
        
        .meta-value {
            color: #666;
        }
        
        /* Estados */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-published { background: #d4edda; color: #155724; }
        .status-archived { background: #f8d7da; color: #721c24; }
        
        /* AI Badge */
        .ai-badge {
            background: #e7f3ff;
            color: #0366d6;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Tags */
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        /* Imagen destacada */
        .featured-image {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Acciones rápidas */
        .quick-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .quick-actions .btn {
            font-size: 12px;
            padding: 6px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>👁️ <?= $pageTitle ?></h1>
            <div class="header-actions">
                <a href="articles.php" class="btn btn-outline">
                    ← Volver a Artículos
                </a>
                <a href="article-create.php?id=<?= $article['id'] ?>" class="btn btn-primary">
                    ✏️ Editar Artículo
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <!-- Contenido del artículo -->
            <div class="article-container">
                <div class="article-header">
                    <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                    
                    <div class="article-meta">
                        <span>👤 <?= htmlspecialchars($article['author']) ?></span>
                        <span>📅 <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?></span>
                        <span>⏱️ <?= $article['reading_time'] ?> min lectura</span>
                        <span>👁️ <?= number_format($article['views']) ?> vistas</span>
                        <?php if ($article['ai_generated']): ?>
                            <span class="ai-badge">🤖 Generado con IA</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($article['excerpt']): ?>
                        <div class="article-excerpt">
                            <strong>Extracto:</strong> <?= htmlspecialchars($article['excerpt']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($article['featured_image']): ?>
                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($article['title']) ?>" 
                         class="featured-image">
                <?php endif; ?>
                
                <div class="article-content">
                    <?= markdownToHtml($article['content']) ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Información del artículo -->
                <div class="sidebar-section">
                    <h3>📊 Información</h3>
                    
                    <div class="meta-item">
                        <span class="meta-label">Estado:</span>
                        <span class="status-badge status-<?= $article['status'] ?>">
                            <?= ucfirst($article['status']) ?>
                        </span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label">Slug:</span>
                        <span class="meta-value"><?= htmlspecialchars($article['slug']) ?></span>
                    </div>
                    
                    <?php if ($article['published_at']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Publicado:</span>
                            <span class="meta-value"><?= date('d/m/Y H:i', strtotime($article['published_at'])) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <span class="meta-label">Última modificación:</span>
                        <span class="meta-value"><?= date('d/m/Y H:i', strtotime($article['updated_at'])) ?></span>
                    </div>
                    
                    <?php if ($article['ai_generated'] && $article['ai_model']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Modelo IA:</span>
                            <span class="meta-value"><?= htmlspecialchars($article['ai_model']) ?></span>
                        </div>
                        
                        <?php if ($article['ai_tokens_used']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Tokens usados:</span>
                                <span class="meta-value"><?= number_format($article['ai_tokens_used']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($article['ai_cost_estimated']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Costo estimado:</span>
                                <span class="meta-value">$<?= number_format($article['ai_cost_estimated'], 4) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- SEO -->
                <?php if ($article['meta_description']): ?>
                    <div class="sidebar-section">
                        <h3>🔍 SEO</h3>
                        <div class="meta-item">
                            <span class="meta-label">Meta Description:</span>
                            <span class="meta-value"><?= htmlspecialchars($article['meta_description']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tags -->
                <?php if ($article['tags']): ?>
                    <div class="sidebar-section">
                        <h3>🏷️ Etiquetas</h3>
                        <div class="tags">
                            <?php foreach (explode(',', $article['tags']) as $tag): ?>
                                <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Acciones rápidas -->
                <div class="sidebar-section">
                    <h3>⚡ Acciones Rápidas</h3>
                    
                    <div class="quick-actions">
                        <?php if ($article['status'] === 'draft'): ?>
                            <a href="article-actions.php?action=publish&id=<?= $article['id'] ?>" 
                               class="btn btn-success"
                               onclick="return confirm('¿Publicar este artículo?')">
                                📤 Publicar
                            </a>
                        <?php elseif ($article['status'] === 'published'): ?>
                            <a href="article-actions.php?action=draft&id=<?= $article['id'] ?>" 
                               class="btn btn-warning"
                               onclick="return confirm('¿Convertir a borrador?')">
                                📝 Borrador
                            </a>
                        <?php endif; ?>
                        
                        <a href="../../api/portfolio/articles.php?slug=<?= urlencode($article['slug']) ?>" 
                           class="btn btn-outline" target="_blank">
                            🌐 Ver API
                        </a>
                        
                        <a href="article-actions.php?action=delete&id=<?= $article['id'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('¿Eliminar este artículo permanentemente?')">
                            🗑️ Eliminar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Incrementar contador de vistas automáticamente
        fetch('../api/articles.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'increment_views',
                id: <?= $article['id'] ?>
            })
        }).catch(err => console.log('Error updating views:', err));
    </script>
</body>
</html>