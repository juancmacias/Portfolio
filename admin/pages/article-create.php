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
$isEdit = !empty($_GET['id']);
$article = null;
$error = null;
$success = null;

// Si es edición, cargar el artículo
if ($isEdit) {
    try {
        $article = $articleManager->getArticle($_GET['id']);
        if (!$article) {
            header('Location: articles.php');
            exit;
        }
    } catch (Exception $e) {
        $error = "Error al cargar el artículo: " . $e->getMessage();
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'author' => trim($_POST['author'] ?? ''),
            'featured_image' => trim($_POST['featured_image'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'reading_time' => (int)($_POST['reading_time'] ?? 0)
        ];
        
        // Validaciones básicas
        if (empty($data['title'])) {
            throw new Exception('El título es requerido');
        }
        
        if (empty($data['content'])) {
            throw new Exception('El contenido es requerido');
        }
        
        if ($isEdit) {
            $articleManager->updateArticle($_GET['id'], $data);
            $success = "Artículo actualizado exitosamente";
            
            // Recargar el artículo actualizado
            $article = $articleManager->getArticle($_GET['id']);
        } else {
            $newId = $articleManager->createArticle($data);
            $success = "Artículo creado exitosamente";
            
            // Opcional: redirigir a edición del nuevo artículo
            // header("Location: article-edit.php?id={$newId}&success=1");
            // exit;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = $isEdit ? "Editar Artículo" : "Nuevo Artículo";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Portfolio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
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
        .header h1 { color: #333; font-size: 28px; }
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Layout */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* Formulario */
        .form-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
        }
        
        .form-control-lg {
            min-height: 300px;
            resize: vertical;
            font-family: inherit;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
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
            justify-content: center;
        }
        .btn-primary { background: #007cba; color: white; }
        .btn-primary:hover { background: #005a87; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .btn-outline { background: transparent; color: #007cba; border: 1px solid #007cba; }
        .btn-outline:hover { background: #007cba; color: white; }
        .btn-lg { padding: 12px 20px; font-size: 16px; }
        .btn-block { width: 100%; }
        
        /* Alertas */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Auto-generate indicators */
        .auto-gen {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 12px;
            color: #004085;
            margin-top: 5px;
        }
        
        /* Character counters */
        .char-counter {
            font-size: 11px;
            color: #666;
            text-align: right;
            margin-top: 2px;
        }
        .char-counter.warning { color: #856404; }
        .char-counter.danger { color: #721c24; }
        
        /* Preview */
        .preview-content {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            background: #fafafa;
            max-height: 200px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Status select styling */
        .status-draft { background: #fff3cd; }
        .status-published { background: #d4edda; }
        .status-archived { background: #f8d7da; }
        
        /* Content editor toolbar */
        .content-editor-toolbar {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            padding: 8px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px 6px 0 0;
            border-bottom: none;
        }
        .content-editor-toolbar + textarea {
            border-radius: 0 0 6px 6px;
        }
        .content-editor-toolbar .btn {
            padding: 4px 8px;
            font-size: 12px;
            min-width: 32px;
        }
        
        /* Image gallery styles */
        .image-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 8px;
            background: #fafafa;
            transition: all 0.2s;
        }
        .image-item:hover {
            background: #f0f0f0;
            border-color: #007cba;
        }
        .image-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #6c757d;
        }
        .image-thumbnail.loaded {
            background: none;
        }
        .image-thumbnail.error {
            background: #f8f9fa;
        }
        .image-thumbnail.error::after {
            content: '🖼️';
            font-size: 16px;
        }
        .image-info {
            flex: 1;
            min-width: 0;
        }
        .image-name {
            font-size: 12px;
            font-weight: 500;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .image-size {
            font-size: 10px;
            color: #666;
        }
        .image-actions {
            display: flex;
            gap: 4px;
        }
        .image-actions button {
            padding: 2px 6px;
            font-size: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-insert {
            background: #007cba;
            color: white;
        }
        .btn-insert:hover {
            background: #005a87;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-copy {
            background: #6c757d;
            color: white;
        }
        .btn-copy:hover {
            background: #545b62;
        }
        
        /* Upload progress */
        .upload-progress {
            width: 100%;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin: 10px 0;
            overflow: hidden;
        }
        .upload-progress-bar {
            height: 100%;
            background: #007cba;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* Image modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        .image-modal.show {
            display: flex;
        }
        .image-modal-content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
        }
        .image-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .image-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
        .image-modal-close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?= $isEdit ? '✏️' : '➕' ?> <?= $pageTitle ?></h1>
            <div class="header-actions">
                <a href="articles.php" class="btn btn-outline">
                    ← Volver a Artículos
                </a>
                <?php if ($isEdit): ?>
                    <a href="article-view.php?id=<?= $article['id'] ?>" class="btn btn-secondary">
                        👁️ Ver Artículo
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="main-content">
            <!-- Formulario principal -->
            <div class="form-container">
                <form method="POST" id="articleForm">
                    <!-- Título -->
                    <div class="form-group">
                        <label for="title">Título *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?= htmlspecialchars($article['title'] ?? '') ?>" 
                               required maxlength="255">
                        <div class="char-counter" id="titleCounter">0/255</div>
                    </div>
                    
                    <!-- Slug -->
                    <div class="form-group">
                        <label for="slug">Slug (URL amigable)</label>
                        <input type="text" id="slug" name="slug" class="form-control" 
                               value="<?= htmlspecialchars($article['slug'] ?? '') ?>" 
                               maxlength="255" pattern="[a-z0-9\-]+">
                        <div class="form-text">Solo letras minúsculas, números y guiones. Se genera automáticamente si se deja vacío.</div>
                        <div class="auto-gen" id="slugPreview" style="display:none;">
                            URL: <span id="slugUrl"></span>
                        </div>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="form-group">
                        <label for="content">Contenido *</label>
                        <div class="content-editor-toolbar">
                            <button type="button" class="btn btn-outline" onclick="insertMarkdown('**', '**', 'Texto en negrita')">
                                <strong>B</strong>
                            </button>
                            <button type="button" class="btn btn-outline" onclick="insertMarkdown('*', '*', 'Texto en cursiva')">
                                <em>I</em>
                            </button>
                            <button type="button" class="btn btn-outline" onclick="insertMarkdown('`', '`', 'código')">
                                &lt;/&gt;
                            </button>
                            <button type="button" class="btn btn-outline" onclick="insertMarkdown('[', '](url)', 'texto del enlace')">
                                🔗
                            </button>
                            <button type="button" class="btn btn-primary" onclick="showImageGallery()">
                                🖼️ Insertar Imagen
                            </button>
                        </div>
                        <textarea id="content" name="content" class="form-control form-control-lg" 
                                  required><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
                        <div class="form-text">Puedes usar HTML básico y Markdown</div>
                    </div>
                    
                    <!-- Excerpt y Meta Description -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="excerpt">Extracto</label>
                            <textarea id="excerpt" name="excerpt" class="form-control" 
                                      rows="4" maxlength="300"><?= htmlspecialchars($article['excerpt'] ?? '') ?></textarea>
                            <div class="char-counter" id="excerptCounter">0/300</div>
                            <div class="auto-gen" style="display:none;" id="excerptAuto">
                                Se generará automáticamente desde el contenido si se deja vacío
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="meta_description">Meta Description (SEO)</label>
                            <textarea id="meta_description" name="meta_description" class="form-control" 
                                      rows="4" maxlength="160"><?= htmlspecialchars($article['meta_description'] ?? '') ?></textarea>
                            <div class="char-counter" id="metaCounter">0/160</div>
                            <div class="auto-gen" style="display:none;" id="metaAuto">
                                Se generará automáticamente desde el contenido si se deja vacío
                            </div>
                        </div>
                    </div>
                    
                    <!-- Autor y Tiempo de lectura -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author">Autor</label>
                            <input type="text" id="author" name="author" class="form-control" 
                                   value="<?= htmlspecialchars($article['author'] ?? 'Juan Carlos Macías') ?>" 
                                   maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="reading_time">Tiempo de Lectura (minutos)</label>
                            <input type="number" id="reading_time" name="reading_time" class="form-control" 
                                   value="<?= $article['reading_time'] ?? '' ?>" 
                                   min="1" max="120">
                            <div class="auto-gen" style="display:none;" id="readingTimeAuto">
                                Se calculará automáticamente si se deja vacío
                            </div>
                        </div>
                    </div>
                    
                    <!-- Imagen destacada y Tags -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="featured_image">Imagen Destacada (URL)</label>
                            <input type="url" id="featured_image" name="featured_image" class="form-control" 
                                   value="<?= htmlspecialchars($article['featured_image'] ?? '') ?>" 
                                   maxlength="500">
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" class="form-control" 
                                   value="<?= htmlspecialchars($article['tags'] ?? '') ?>" 
                                   placeholder="react, javascript, desarrollo">
                            <div class="form-text">Separar con comas</div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div style="margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="submit" name="status" value="draft" class="btn btn-secondary">
                            💾 Guardar como Borrador
                        </button>
                        <button type="submit" name="status" value="published" class="btn btn-success">
                            📤 <?= $isEdit ? 'Actualizar y ' : '' ?>Publicar
                        </button>
                        <?php if ($isEdit): ?>
                            <button type="submit" name="status" value="archived" class="btn btn-outline">
                                📦 Archivar
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Estado actual -->
                <?php if ($isEdit): ?>
                    <div class="sidebar-section">
                        <h3>📊 Estado Actual</h3>
                        <p><strong>Estado:</strong> 
                            <span class="status-badge status-<?= $article['status'] ?>">
                                <?= ucfirst($article['status']) ?>
                            </span>
                        </p>
                        <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?></p>
                        <p><strong>Modificado:</strong> <?= date('d/m/Y H:i', strtotime($article['updated_at'])) ?></p>
                        <?php if ($article['published_at']): ?>
                            <p><strong>Publicado:</strong> <?= date('d/m/Y H:i', strtotime($article['published_at'])) ?></p>
                        <?php endif; ?>
                        <p><strong>Vistas:</strong> <?= number_format($article['views']) ?></p>
                        <?php if ($article['ai_generated']): ?>
                            <p><strong>IA:</strong> <?= htmlspecialchars($article['ai_model']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Generación con IA -->
                <div class="sidebar-section">
                    <h3>🤖 Herramientas IA</h3>
                    <button type="button" class="btn btn-outline btn-block" onclick="generateWithAI('title', event)">
                        Generar Título
                    </button>
                    <button type="button" class="btn btn-outline btn-block" onclick="generateWithAI('content', event)">
                        Generar Contenido
                    </button>
                    <button type="button" class="btn btn-outline btn-block" onclick="generateWithAI('excerpt', event)">
                        Generar Extracto
                    </button>
                    <button type="button" class="btn btn-outline btn-block" onclick="generateWithAI('meta', event)">
                        Generar Meta Description
                    </button>
                    <button type="button" class="btn btn-outline btn-block" onclick="generateWithAI('tags', event)">
                        Sugerir Tags
                    </button>
                </div>
                
                <!-- Galería de Imágenes -->
                <div class="sidebar-section">
                    <h3>🖼️ Galería de Imágenes</h3>
                    <div style="margin-bottom: 15px;">
                        <input type="file" id="imageUpload" accept="image/*" style="display: none;" onchange="uploadImage()">
                        <button type="button" class="btn btn-primary btn-block" onclick="const uploadEl = document.getElementById('imageUpload'); if (uploadEl) uploadEl.click();">
                            📤 Subir Nueva Imagen
                        </button>
                    </div>
                    
                    <div id="imageGallery" style="max-height: 300px; overflow-y: auto;">
                        <div id="imageLoading" style="text-align: center; padding: 20px; color: #666;">
                            Cargando imágenes...
                        </div>
                    </div>
                    
                    <div style="margin-top: 10px; font-size: 12px; color: #666;">
                        <strong>Formatos:</strong> JPG, PNG, GIF, WEBP<br>
                        <strong>Tamaño máximo:</strong> 10MB<br>
                        <strong>Optimización:</strong> Automática para redes sociales
                    </div>
                </div>
                
                <!-- Preview -->
                <div class="sidebar-section">
                    <h3>👁️ Vista Previa</h3>
                    <div id="titlePreview" style="font-weight: bold; margin-bottom: 10px;"></div>
                    <div id="excerptPreview" class="preview-content" style="display:none;"></div>
                    <div id="contentPreview" class="preview-content" style="display:none;"></div>
                </div>
                
                <!-- Ayuda -->
                <div class="sidebar-section">
                    <h3>💡 Consejos</h3>
                    <ul style="font-size: 13px; color: #666; line-height: 1.5;">
                        <li>Un buen título debe ser claro y descriptivo (50-60 caracteres)</li>
                        <li>El extracto debe resumir el artículo en 1-2 oraciones</li>
                        <li>La meta description es crucial para SEO (140-160 caracteres)</li>
                        <li>Usa tags relevantes para categorizar el contenido</li>
                        <li>El tiempo de lectura se calcula automáticamente (200 palabras/min)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Galería de Imágenes -->
    <div id="imageModal" class="image-modal">
        <div class="image-modal-content" style="width: 80%; max-width: 800px;">
            <div class="image-modal-header">
                <h3>🖼️ Galería de Imágenes</h3>
                <button class="image-modal-close" onclick="closeImageModal()">&times;</button>
            </div>
            
            <div style="margin-bottom: 20px;">
                <input type="file" id="modalImageUpload" accept="image/*" style="display: none;" onchange="uploadImageFromModal()">
                <button type="button" class="btn btn-primary" onclick="const modalUploadEl = document.getElementById('modalImageUpload'); if (modalUploadEl) modalUploadEl.click();">
                    📤 Subir Nueva Imagen
                </button>
                <div id="modalUploadProgress" class="upload-progress" style="display: none;">
                    <div id="modalUploadProgressBar" class="upload-progress-bar"></div>
                </div>
            </div>
            
            <div id="modalImageGallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; max-height: 400px; overflow-y: auto;">
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #666;">
                    Cargando imágenes...
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Variables globales para imágenes
        let imagesData = [];
        // Character counters
        function updateCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            
            if (!input || !counter) {
                console.warn(`Elementos no encontrados: input ${inputId} o counter ${counterId}`);
                return;
            }
            
            const length = input.value.length;
            
            counter.textContent = length + '/' + maxLength;
            counter.className = 'char-counter';
            
            if (length > maxLength * 0.8) {
                counter.classList.add('warning');
            }
            if (length > maxLength * 0.95) {
                counter.classList.add('danger');
            }
        }
        
        // Initialize counters
        document.addEventListener('DOMContentLoaded', function() {
            const counters = [
                ['title', 'titleCounter', 255],
                ['excerpt', 'excerptCounter', 300],
                ['meta_description', 'metaCounter', 160]
            ];
            
            counters.forEach(([inputId, counterId, maxLength]) => {
                const input = document.getElementById(inputId);
                if (input) {
                    updateCounter(inputId, counterId, maxLength);
                    input.addEventListener('input', () => updateCounter(inputId, counterId, maxLength));
                }
            });
            
            // Auto-generate slug from title
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const slugPreview = document.getElementById('slugPreview');
            const slugUrl = document.getElementById('slugUrl');
            
            titleInput.addEventListener('input', function() {
                if (!slugInput.value) {
                    const slug = this.value
                        .toLowerCase()
                        .replace(/[áéíóúñ]/g, match => ({'á':'a','é':'e','í':'i','ó':'o','ú':'u','ñ':'n'}[match]))
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                    
                    if (slug && slugUrl && slugPreview) {
                        slugUrl.textContent = window.location.origin + '/article/' + slug;
                        slugPreview.style.display = 'block';
                    } else if (slugPreview) {
                        slugPreview.style.display = 'none';
                    }
                }
                
                // Update preview
                const titlePreviewEl = document.getElementById('titlePreview');
                if (titlePreviewEl) {
                    titlePreviewEl.textContent = this.value;
                }
            });
            
            // Show auto-generation hints
            const autoFields = ['excerpt', 'meta_description', 'reading_time'];
            autoFields.forEach(field => {
                const input = document.getElementById(field);
                const auto = document.getElementById(field.replace('_', '') + 'Auto');
                if (input && auto) {
                    input.addEventListener('focus', () => {
                        if (!input.value) auto.style.display = 'block';
                    });
                    input.addEventListener('blur', () => {
                        auto.style.display = 'none';
                    });
                }
            });
            
            // Preview updates
            const contentInput = document.getElementById('content');
            const excerptInput = document.getElementById('excerpt');
            
            function updatePreviews() {
                const contentPreview = document.getElementById('contentPreview');
                const excerptPreview = document.getElementById('excerptPreview');
                
                if (contentInput && contentInput.value && contentPreview) {
                    contentPreview.innerHTML = contentInput.value.substring(0, 300) + (contentInput.value.length > 300 ? '...' : '');
                    contentPreview.style.display = 'block';
                } else if (contentPreview) {
                    contentPreview.style.display = 'none';
                }
                
                if (excerptInput && excerptInput.value && excerptPreview) {
                    excerptPreview.textContent = excerptInput.value;
                    excerptPreview.style.display = 'block';
                } else if (excerptPreview) {
                    excerptPreview.style.display = 'none';
                }
            }
            
            contentInput.addEventListener('input', updatePreviews);
            excerptInput.addEventListener('input', updatePreviews);
            updatePreviews();
            
            // Cargar imágenes al inicializar
            loadImages();
        });
        
        // === FUNCIONES DE GESTIÓN DE IMÁGENES ===
        
        // Cargar lista de imágenes
        async function loadImages() {
            try {
                // API de imágenes del admin panel
                const possibleUrls = [
                    '../api/images.php?action=list',
                    '/admin/api/images.php?action=list',
                    window.location.origin + '/admin/api/images.php?action=list'
                ];
                
                let lastError = null;
                let successfulUrl = null;
                
                for (let url of possibleUrls) {
                    try {
                        console.log('Probando URL:', url);
                        const response = await fetch(url);
                        console.log('Response status para', url, ':', response.status);
                        
                        if (response.ok) {
                            successfulUrl = url;
                            console.log('URL exitosa:', url);
                            
                            const responseText = await response.text();
                            console.log('Raw response:', responseText);
                            
                            let data;
                            try {
                                data = JSON.parse(responseText);
                            } catch (parseError) {
                                console.error('JSON Parse Error:', parseError);
                                console.error('Response was:', responseText);
                                const imageLoadingEl = document.getElementById('imageLoading');
                                if (imageLoadingEl) {
                                    imageLoadingEl.innerHTML = 
                                        '<div style="color: #dc3545;">Error: Respuesta no válida del servidor<br>' +
                                        '<small>Revisa la consola del navegador para más detalles</small></div>';
                                }
                                return;
                            }
                            
                            console.log('Parsed data:', data);
                            
                            if (data.success) {
                                imagesData = data.images || [];
                                renderImageGallery();
                                renderModalImageGallery();
                                
                                // Mostrar información de debug
                                if (data.directories) {
                                    console.log('Directory info:', data.directories);
                                }
                                console.log('✅ API funcionando con URL:', successfulUrl);
                                return; // Éxito, salir del bucle
                            } else {
                                console.error('Error del servidor:', data.message);
                                const imageLoadingEl = document.getElementById('imageLoading');
                                if (imageLoadingEl) {
                                    imageLoadingEl.innerHTML = 
                                        '<div style="color: #dc3545;">Error: ' + (data.message || 'Error desconocido') + '</div>';
                                }
                                return;
                            }
                        } else {
                            console.warn('URL falló:', url, 'Status:', response.status);
                            const errorText = await response.text();
                            console.warn('Error response:', errorText);
                            lastError = `${url}: ${response.status} ${response.statusText}`;
                        }
                    } catch (urlError) {
                        console.warn('Error con URL:', url, urlError.message);
                        lastError = `${url}: ${urlError.message}`;
                        continue;
                    }
                }
                
                // Si llegamos aquí, ninguna URL funcionó
                console.error('Todas las URLs fallaron. Último error:', lastError);
                const imageLoadingEl = document.getElementById('imageLoading');
                if (imageLoadingEl) {
                    imageLoadingEl.innerHTML = 
                        '<div style="color: #dc3545;">Error: No se pudo conectar con la API<br>' +
                        '<small>URLs probadas: ' + possibleUrls.join(', ') + '</small></div>';
                }
                    
            } catch (error) {
                console.error('Error general:', error);
                const imageLoadingEl = document.getElementById('imageLoading');
                if (imageLoadingEl) {
                    imageLoadingEl.innerHTML = 
                        '<div style="color: #dc3545;">Error de red: ' + error.message + '</div>';
                }
            }
        }
        
        // Renderizar galería en sidebar
        function renderImageGallery() {
            const gallery = document.getElementById('imageGallery');
            const loading = document.getElementById('imageLoading');
            
            console.log('Renderizando galería con', imagesData.length, 'imágenes');
            console.log('Datos de imágenes:', imagesData);
            
            if (imagesData.length === 0) {
                if (loading) {
                    loading.innerHTML = '<div style="color: #666;">No hay imágenes</div>';
                }
                return;
            }
            
            if (loading) {
                loading.style.display = 'none';
            }
            
            const html = imagesData.slice(0, 5).map(image => {
                const imgSrc = image.thumbnail || image.url;
                console.log('Imagen:', image.name, 'URL:', imgSrc);
                
                return `
                <div class="image-item">
                    <img src="${imgSrc}" alt="${image.name}" class="image-thumbnail" 
                         onload="console.log('Imagen cargada:', '${imgSrc}'); this.classList.add('loaded');" 
                         onerror="console.error('Error cargando imagen:', '${imgSrc}'); this.classList.add('error'); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="image-thumbnail error" style="display:none;">
                        🖼️
                    </div>
                    <div class="image-info">
                        <div class="image-name" title="${image.name}">${image.name}</div>
                        <div class="image-size">${formatFileSize(image.size)}</div>
                        <div class="image-url" style="font-size: 9px; color: #999; word-break: break-all; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="${imgSrc}">
                            ${imgSrc.length > 30 ? imgSrc.substring(0, 30) + '...' : imgSrc}
                        </div>
                    </div>
                    <div class="image-actions">
                        <button class="btn-insert" onclick="insertImageMarkdown('${image.url}', '${image.name}')" title="Insertar">+</button>
                        <button class="btn-copy" onclick="copyToClipboard('${image.url}')" title="Copiar URL">📋</button>
                        <button class="btn-delete" onclick="deleteImage('${image.filename}')" title="Eliminar">🗑️</button>
                    </div>
                </div>
            `;
            }).join('');
            
            if (gallery) {
                gallery.innerHTML = html + (imagesData.length > 5 ? 
                    '<div style="text-align: center; margin-top: 10px;"><button class="btn btn-outline" onclick="showImageGallery()">Ver todas las imágenes</button></div>' : 
                    '');
            }
        }
        
        // Renderizar galería en modal
        function renderModalImageGallery() {
            const gallery = document.getElementById('modalImageGallery');
            
            if (!gallery) {
                console.error('Elemento modalImageGallery no encontrado');
                return;
            }
            
            if (imagesData.length === 0) {
                gallery.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #666;">No hay imágenes disponibles</div>';
                return;
            }
            
            const html = imagesData.map(image => `
                <div class="image-card" style="border: 1px solid #ddd; border-radius: 6px; overflow: hidden; background: white;">
                    <img src="${image.thumbnail || image.url}" alt="${image.name}" 
                         style="width: 100%; height: 100px; object-fit: cover; cursor: pointer;"
                         onclick="insertImageMarkdown('${image.url}', '${image.name}'); closeImageModal();">
                    <div style="padding: 8px;">
                        <div style="font-size: 12px; font-weight: 500; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${image.name}">
                            ${image.name}
                        </div>
                        <div style="font-size: 10px; color: #666; margin-bottom: 8px;">
                            ${formatFileSize(image.size)}
                        </div>
                        <div style="display: flex; gap: 4px;">
                            <button onclick="insertImageMarkdown('${image.url}', '${image.name}'); closeImageModal();" 
                                    style="flex: 1; padding: 4px; font-size: 10px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;">
                                Insertar
                            </button>
                            <button onclick="copyToClipboard('${image.url}')" 
                                    style="padding: 4px 6px; font-size: 10px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;" title="Copiar URL">
                                📋
                            </button>
                            <button onclick="deleteImage('${image.filename}')" 
                                    style="padding: 4px 6px; font-size: 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;" title="Eliminar">
                                🗑️
                            </button>
                        </div>
                        ${image.versions ? `
                            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">
                                <div style="font-size: 10px; color: #666; margin-bottom: 4px;">Versiones:</div>
                                <div style="display: flex; gap: 2px; flex-wrap: wrap;">
                                    ${Object.entries(image.versions).map(([social, url]) => `
                                        <button onclick="insertImageMarkdown('${url}', '${image.name} (${social})')"
                                                style="padding: 2px 4px; font-size: 9px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 2px; cursor: pointer;"
                                                title="Insertar versión ${social}">
                                            ${social.substring(0, 2)}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
            
            gallery.innerHTML = html;
        }
        
        // Subir imagen desde sidebar
        async function uploadImage() {
            const input = document.getElementById('imageUpload');
            const file = input.files[0];
            
            if (!file) return;
            
            await uploadImageFile(file);
            input.value = ''; // Reset input
        }
        
        // Subir imagen desde modal
        async function uploadImageFromModal() {
            const input = document.getElementById('modalImageUpload');
            const file = input.files[0];
            
            if (!file) return;
            
            // Mostrar progress bar
            const progressContainer = document.getElementById('modalUploadProgress');
            const progressBar = document.getElementById('modalUploadProgressBar');
            
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            
            await uploadImageFile(file, (progress) => {
                progressBar.style.width = progress + '%';
            });
            
            // Ocultar progress bar
            setTimeout(() => {
                progressContainer.style.display = 'none';
            }, 1000);
            
            input.value = ''; // Reset input
        }
        
        // Función común para subir archivos
        async function uploadImageFile(file, progressCallback = null) {
            // Validaciones
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Tipo de archivo no válido. Solo se permiten: JPG, PNG, GIF, WEBP');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Máximo 10MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('image', file);
            
            try {
                // Simular progreso si hay callback
                if (progressCallback) {
                    progressCallback(20);
                }
                
                const response = await fetch('../api/images.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                
                if (progressCallback) {
                    progressCallback(80);
                }
                
                const data = await response.json();
                
                if (progressCallback) {
                    progressCallback(100);
                }
                
                if (data.success) {
                    showNotification('success', '✅ Imagen subida exitosamente');
                    await loadImages(); // Recargar lista
                } else {
                    throw new Error(data.message || 'Error al subir la imagen');
                }
                
            } catch (error) {
                console.error('Error uploading image:', error);
                showNotification('error', 'Error: ' + error.message);
            }
        }
        
        // Eliminar imagen
        async function deleteImage(filename) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
                return;
            }
            
            try {
                const response = await fetch('../api/images.php?action=delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ filename })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', '✅ Imagen eliminada exitosamente');
                    await loadImages(); // Recargar lista
                } else {
                    throw new Error(data.message || 'Error al eliminar la imagen');
                }
                
            } catch (error) {
                console.error('Error deleting image:', error);
                showNotification('error', 'Error: ' + error.message);
            }
        }
        
        // === FUNCIONES DEL EDITOR ===
        
        // Insertar markdown en el textarea
        function insertMarkdown(before, after, placeholder = '') {
            const textarea = document.getElementById('content');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            const text = selectedText || placeholder;
            const newText = before + text + after;
            
            textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
            
            // Posicionar cursor
            const newCursorPos = start + before.length + text.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            textarea.focus();
            
            // Disparar evento para actualizar preview
            textarea.dispatchEvent(new Event('input'));
        }
        
        // Insertar imagen en markdown
        function insertImageMarkdown(url, altText) {
            const markdown = `![${altText}](${url})`;
            const textarea = document.getElementById('content');
            const start = textarea.selectionStart;
            
            textarea.value = textarea.value.substring(0, start) + markdown + textarea.value.substring(start);
            
            // Posicionar cursor después de la imagen
            const newCursorPos = start + markdown.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            textarea.focus();
            
            // Disparar evento para actualizar preview
            textarea.dispatchEvent(new Event('input'));
            
            showNotification('success', '✅ Imagen insertada en el contenido');
        }
        
        // === FUNCIONES DEL MODAL ===
        
        // Mostrar modal de galería
        function showImageGallery() {
            document.getElementById('imageModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        // Cerrar modal de galería
        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
        
        // === FUNCIONES UTILITARIAS ===
        
        // Formatear tamaño de archivo
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
        
        // Copiar al portapapeles
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('success', '📋 URL copiada al portapapeles');
            }).catch(() => {
                // Fallback para navegadores que no soportan clipboard API
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showNotification('success', '📋 URL copiada al portapapeles');
            });
        }
        
        // AI Generation functions
        async function generateWithAI(type, evt) {
            // Obtener el botón de forma robusta
            const button = (evt && (evt.target || evt.currentTarget)) || document.activeElement || null;
            const originalText = button ? button.textContent : '';

            // Deshabilitar botón y mostrar loading (si existe)
            if (button) {
                button.disabled = true;
                button.textContent = '⏳ Generando...';
            }

            try {
                let requestData = {
                    action: '',
                    csrf_token: '<?= $auth->generateCSRFToken() ?>'
                };

                switch (type) {
                    case 'title':
                        const topic = prompt('¿Sobre qué tema quieres generar el título?');
                        if (!topic) return;

                        const keywords = prompt('Palabras clave (opcional):') || '';

                        requestData.action = 'generate_title';
                        requestData.topic = topic;
                        requestData.keywords = keywords;
                        break;

                    case 'content':
                        const title = document.getElementById('title').value;
                        if (!title) {
                            alert('Primero ingresa un título para generar el contenido');
                            return;
                        }

                        const contentKeywords = document.getElementById('tags').value || '';
                        const wordCount = prompt('¿Aproximadamente cuántas palabras? (por defecto 800)', '800');

                        requestData.action = 'generate_article';
                        requestData.title = title;
                        requestData.keywords = contentKeywords;
                        requestData.word_count = parseInt(wordCount) || 800;
                        break;

                    case 'excerpt':
                        const content = document.getElementById('content').value;
                        if (!content) {
                            alert('Primero escribe o genera el contenido');
                            return;
                        }

                        requestData.action = 'generate_excerpt';
                        requestData.content = content;
                        requestData.max_length = 150;
                        break;

                    case 'meta':
                        const metaContent = document.getElementById('content').value;
                        if (!metaContent) {
                            alert('Primero escribe o genera el contenido');
                            return;
                        }

                        const metaKeywords = document.getElementById('tags').value || '';

                        requestData.action = 'generate_meta_description';
                        requestData.content = metaContent;
                        requestData.keywords = metaKeywords;
                        break;

                    case 'tags':
                        const tagsContent = document.getElementById('content').value;
                        if (!tagsContent) {
                            alert('Primero escribe o genera el contenido');
                            return;
                        }

                        requestData.action = 'generate_tags';
                        requestData.content = tagsContent;
                        requestData.max_tags = 8;
                        break;

                    default:
                        throw new Error('Tipo de generación no válido');
                }

                // Realizar petición AJAX (incluir credenciales same-origin explícitamente)
                const response = await fetch('../api/ai.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Error desconocido');
                }

                // Procesar resultado según el tipo
                switch (type) {
                    case 'title':
                        document.getElementById('title').value = result.content;
                        updateCounter('title', 'titleCounter', 255);
                        document.getElementById('titlePreview').textContent = result.content;
                        break;

                    case 'content':
                        document.getElementById('content').value = result.content;
                        // Disparar evento input para que los listeners actualicen la vista previa
                        document.getElementById('content').dispatchEvent(new Event('input'));
                        break;

                    case 'excerpt':
                        document.getElementById('excerpt').value = result.content;
                        updateCounter('excerpt', 'excerptCounter', 300);
                        document.getElementById('excerpt').dispatchEvent(new Event('input'));
                        break;

                    case 'meta':
                        document.getElementById('meta_description').value = result.content;
                        updateCounter('meta_description', 'metaCounter', 160);
                        break;

                    case 'tags':
                        document.getElementById('tags').value = result.content;
                        break;
                }

                // Mostrar información de la generación
                const infoMsg = `✅ Generado con ${result.provider} usando ${result.model}\n`;
                const costInfo = `Tokens: ${result.tokens_used}, Costo: $${result.cost_estimated.toFixed(4)}, Tiempo: ${result.execution_time}ms`;

                // Mostrar notificación temporal
                showNotification('success', infoMsg + costInfo);

            } catch (error) {
                console.error('Error generando contenido:', error);
                showNotification('error', 'Error: ' + error.message);
            } finally {
                // Restaurar botón (si existe)
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        }
        
        // Función para mostrar notificaciones
        function showNotification(type, message) {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 6px;
                color: white;
                font-size: 14px;
                max-width: 400px;
                z-index: 9999;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                ${type === 'success' ? 'background: #28a745;' : 'background: #dc3545;'}
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animar entrada
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remover después de 5 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
        
        // Form validation
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            
            if (!title) {
                alert('El título es requerido');
                e.preventDefault();
                return;
            }
            
            if (!content) {
                alert('El contenido es requerido');
                e.preventDefault();
                return;
            }
            
            // Confirm if publishing
            const submitButton = e.submitter;
            if (submitButton && submitButton.value === 'published') {
                if (!confirm('¿Estás seguro de que quieres publicar este artículo?')) {
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>