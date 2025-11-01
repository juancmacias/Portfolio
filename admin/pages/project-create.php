<?php
/**
 * Gestión de Proyectos - Crear/Editar
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$isEdit = isset($_GET['id']);
$project = null;
$errors = [];

// Si es edición, cargar el proyecto
if ($isEdit) {
    $projectId = intval($_GET['id']);
    try {
        $db = Database::getInstance();
        $project = $db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
        if (!$project) {
            header('Location: projects.php?error=' . urlencode('Proyecto no encontrado'));
            exit();
        }
    } catch (Exception $e) {
        header('Location: projects.php?error=' . urlencode('Error al cargar proyecto: ' . $e->getMessage()));
        exit();
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_path = trim($_POST['image_path'] ?? '');
    $github_link = trim($_POST['github_link'] ?? '');
    $demo_link = trim($_POST['demo_link'] ?? '');
    $project_type = $_POST['project_type'] ?? 'web';
    $status = $_POST['status'] ?? 'active';
    $sort_order = intval($_POST['sort_order'] ?? 0);
    
    // Validaciones
    if (empty($title)) {
        $errors[] = 'El título es obligatorio';
    }
    
    if (empty($slug)) {
        // Generar slug automáticamente
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
    } else {
        // Validar formato del slug
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = 'El slug solo puede contener letras minúsculas, números y guiones';
        }
    }
    
    if (empty($description)) {
        $errors[] = 'La descripción es obligatoria';
    }
    
    if (!in_array($project_type, ['web', 'app', 'other'])) {
        $errors[] = 'Tipo de proyecto inválido';
    }
    
    if (!in_array($status, ['active', 'archived', 'maintenance'])) {
        $errors[] = 'Estado inválido';
    }
    
    // Validar URLs si se proporcionan
    if (!empty($github_link) && !filter_var($github_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL de GitHub no es válida';
    }
    
    if (!empty($demo_link) && !filter_var($demo_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL de demo no es válida';
    }
    
    // Verificar slug único
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            $checkSlugQuery = "SELECT id FROM projects WHERE slug = ?" . ($isEdit ? " AND id != ?" : "");
            $checkSlugParams = [$slug];
            if ($isEdit) {
                $checkSlugParams[] = $projectId;
            }
            
            $existingProject = $db->fetchOne($checkSlugQuery, $checkSlugParams);
            if ($existingProject) {
                $errors[] = 'Ya existe un proyecto con ese slug';
            }
        } catch (Exception $e) {
            $errors[] = 'Error al verificar slug: ' . $e->getMessage();
        }
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            if ($isEdit) {
                // Actualizar proyecto
                $sql = "UPDATE projects SET 
                        title = ?, slug = ?, description = ?, image_path = ?, 
                        github_link = ?, demo_link = ?, project_type = ?, 
                        status = ?, sort_order = ?, updated_at = NOW() 
                        WHERE id = ?";
                $params = [$title, $slug, $description, $image_path, $github_link, $demo_link, $project_type, $status, $sort_order, $projectId];
                
                $result = $db->query($sql, $params);
                if ($result) {
                    header('Location: projects.php?success=' . urlencode('Proyecto actualizado correctamente'));
                    exit();
                } else {
                    $errors[] = 'Error al actualizar el proyecto';
                }
                
            } else {
                // Crear nuevo proyecto
                $sql = "INSERT INTO projects (title, slug, description, image_path, github_link, demo_link, project_type, status, sort_order, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $params = [$title, $slug, $description, $image_path, $github_link, $demo_link, $project_type, $status, $sort_order];
                
                $result = $db->query($sql, $params);
                if ($result) {
                    header('Location: projects.php?success=' . urlencode('Proyecto creado correctamente'));
                    exit();
                } else {
                    $errors[] = 'Error al crear el proyecto';
                }
                header('Location: projects.php?success=' . urlencode('Proyecto creado correctamente'));
                exit();
            }
            
        } catch (Exception $e) {
            $errors[] = 'Error al guardar proyecto: ' . $e->getMessage();
        }
    }
}

// Valores por defecto para el formulario
$formData = [
    'title' => $project['title'] ?? $_POST['title'] ?? '',
    'slug' => $project['slug'] ?? $_POST['slug'] ?? '',
    'description' => $project['description'] ?? $_POST['description'] ?? '',
    'image_path' => $project['image_path'] ?? $_POST['image_path'] ?? '',
    'github_link' => $project['github_link'] ?? $_POST['github_link'] ?? '',
    'demo_link' => $project['demo_link'] ?? $_POST['demo_link'] ?? '',
    'project_type' => $project['project_type'] ?? $_POST['project_type'] ?? 'web',
    'status' => $project['status'] ?? $_POST['status'] ?? 'active',
    'sort_order' => $project['sort_order'] ?? $_POST['sort_order'] ?? 0
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar' : 'Crear' ?> Proyecto - Admin Portfolio</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #007acc; padding-bottom: 15px; }
        .header h1 { color: #007acc; margin: 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: #007acc; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:hover { opacity: 0.9; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; 
            font-size: 14px; box-sizing: border-box; 
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            outline: none; border-color: #007acc; box-shadow: 0 0 5px rgba(0,122,204,0.3); 
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-help { font-size: 12px; color: #666; margin-top: 5px; }
        .slug-preview { background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; font-family: monospace; }
        
        .image-preview { margin-top: 10px; max-width: 200px; border-radius: 4px; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        
        /* Image modal styles */
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
        .image-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        .image-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .image-card:hover {
            border-color: #007acc;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .image-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        .image-card-info {
            padding: 8px;
        }
        .image-card-name {
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .image-card-size {
            font-size: 10px;
            color: #666;
        }
        .upload-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }
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
            background: #007acc;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $isEdit ? '✏️ Editar' : '➕ Crear' ?> Proyecto</h1>
            <a href="projects.php" class="btn btn-secondary">← Volver a Lista</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>❌ Errores encontrados:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="projectForm">
            <div class="form-group">
                <label for="title">📝 Título del Proyecto *</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($formData['title']) ?>" required maxlength="255">
                <div class="form-help">Nombre descriptivo del proyecto</div>
            </div>

            <div class="form-group">
                <label for="slug">🔗 Slug (URL amigable)</label>
                <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($formData['slug']) ?>" pattern="^[a-z0-9-]+$" maxlength="255">
                <div class="form-help">Solo letras minúsculas, números y guiones. Se genera automáticamente si se deja vacío.</div>
                <div id="slugPreview" class="slug-preview" style="display: none;"></div>
            </div>

            <div class="form-group">
                <label for="description">📄 Descripción *</label>
                <textarea id="description" name="description" required maxlength="1000"><?= htmlspecialchars($formData['description']) ?></textarea>
                <div class="form-help">Descripción detallada del proyecto (máximo 1000 caracteres)</div>
            </div>

            <div class="form-group">
                <label for="image_path">🖼️ URL de Imagen</label>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <input type="url" id="image_path" name="image_path" value="<?= htmlspecialchars($formData['image_path']) ?>" style="flex: 1;">
                    <button type="button" class="btn btn-primary" onclick="showImageGallery()" style="white-space: nowrap;">
                        📁 Galería
                    </button>
                </div>
                <div class="form-help">URL completa de la imagen del proyecto</div>
                <img id="imagePreview" class="image-preview" style="display: none;" alt="Vista previa">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="github_link">⚡ Enlace GitHub</label>
                    <input type="url" id="github_link" name="github_link" value="<?= htmlspecialchars($formData['github_link']) ?>">
                    <div class="form-help">Repositorio del código fuente</div>
                </div>
                <div class="form-group">
                    <label for="demo_link">🌐 Enlace Demo</label>
                    <input type="url" id="demo_link" name="demo_link" value="<?= htmlspecialchars($formData['demo_link']) ?>">
                    <div class="form-help">Demostración en vivo del proyecto</div>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="project_type">📱 Tipo de Proyecto</label>
                    <select id="project_type" name="project_type" required>
                        <option value="web" <?= $formData['project_type'] === 'web' ? 'selected' : '' ?>>Web</option>
                        <option value="app" <?= $formData['project_type'] === 'app' ? 'selected' : '' ?>>App Móvil</option>
                        <option value="other" <?= $formData['project_type'] === 'other' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">📊 Estado</label>
                    <select id="status" name="status" required>
                        <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                        <option value="archived" <?= $formData['status'] === 'archived' ? 'selected' : '' ?>>Archivado</option>
                        <option value="maintenance" <?= $formData['status'] === 'maintenance' ? 'selected' : '' ?>>Mantenimiento</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_order">🔢 Orden</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?= htmlspecialchars($formData['sort_order']) ?>" min="0" max="999">
                    <div class="form-help">Orden de visualización (0 = primero)</div>
                </div>
            </div>

            <div class="form-actions">
                <a href="projects.php" class="btn btn-secondary">❌ Cancelar</a>
                <button type="submit" class="btn btn-success">
                    ✅ <?= $isEdit ? 'Actualizar' : 'Crear' ?> Proyecto
                </button>
            </div>
        </form>
    </div>

    <!-- Modal de Galería de Imágenes -->
    <div id="imageModal" class="image-modal">
        <div class="image-modal-content" style="width: 80%; max-width: 800px;">
            <div class="image-modal-header">
                <h3>🖼️ Seleccionar Imagen</h3>
                <button class="image-modal-close" onclick="closeImageModal()">&times;</button>
            </div>
            
            <div class="upload-section">
                <input type="file" id="modalImageUpload" accept="image/*" style="display: none;" onchange="uploadImageFromModal()">
                <button type="button" class="btn btn-primary" onclick="const modalUploadEl = document.getElementById('modalImageUpload'); if (modalUploadEl) modalUploadEl.click();">
                    📤 Subir Nueva Imagen
                </button>
                <div id="modalUploadProgress" class="upload-progress" style="display: none;">
                    <div id="modalUploadProgressBar" class="upload-progress-bar"></div>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 8px;">
                    Formatos: JPG, PNG, GIF, WEBP | Tamaño máximo: 10MB
                </div>
            </div>
            
            <div id="modalImageGallery" class="image-gallery-grid">
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #666;">
                    Cargando imágenes...
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales para imágenes
        let imagesData = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const slugPreview = document.getElementById('slugPreview');
            const imageInput = document.getElementById('image_path');
            const imagePreview = document.getElementById('imagePreview');
            
            // Auto-generar slug desde el título
            function generateSlug(text) {
                return text
                    .toLowerCase()
                    .trim()
                    .replace(/[áàäâ]/g, 'a')
                    .replace(/[éèëê]/g, 'e')
                    .replace(/[íìïî]/g, 'i')
                    .replace(/[óòöô]/g, 'o')
                    .replace(/[úùüû]/g, 'u')
                    .replace(/[ñ]/g, 'n')
                    .replace(/[ç]/g, 'c')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
            
            function updateSlugPreview() {
                const slugValue = slugInput.value || generateSlug(titleInput.value);
                if (slugValue) {
                    slugPreview.textContent = `URL: /proyectos/${slugValue}`;
                    slugPreview.style.display = 'block';
                } else {
                    slugPreview.style.display = 'none';
                }
            }
            
            // Auto-completar slug cuando se escribe el título
            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function() {
                    if (!slugInput.value) {
                        updateSlugPreview();
                    }
                });
                
                slugInput.addEventListener('input', updateSlugPreview);
                
                // Inicializar preview
                updateSlugPreview();
            }
            
            // Vista previa de imagen
            if (imageInput && imagePreview) {
                function updateImagePreview() {
                    const url = imageInput.value.trim();
                    if (url) {
                        imagePreview.src = url;
                        imagePreview.style.display = 'block';
                        imagePreview.onerror = function() {
                            this.style.display = 'none';
                        };
                    } else {
                        imagePreview.style.display = 'none';
                    }
                }
                
                imageInput.addEventListener('input', updateImagePreview);
                updateImagePreview(); // Inicializar
            }
            
            // Validación del formulario
            document.getElementById('projectForm').addEventListener('submit', function(e) {
                const title = titleInput.value.trim();
                const description = document.getElementById('description').value.trim();
                
                if (!title) {
                    alert('El título es obligatorio');
                    e.preventDefault();
                    titleInput.focus();
                    return;
                }
                
                if (!description) {
                    alert('La descripción es obligatoria');
                    e.preventDefault();
                    document.getElementById('description').focus();
                    return;
                }
                
                // Auto-completar slug si está vacío
                if (!slugInput.value.trim()) {
                    slugInput.value = generateSlug(title);
                }
            });
            
            // Cargar imágenes al inicializar
            loadImages();
        });
        
        // === FUNCIONES DE GESTIÓN DE IMÁGENES ===
        
        // Cargar lista de imágenes
        async function loadImages() {
            try {
                const possibleUrls = [
                    '../api/images.php?action=list',
                    '/admin/api/images.php?action=list',
                    window.location.origin + '/admin/api/images.php?action=list'
                ];
                
                for (let url of possibleUrls) {
                    try {
                        const response = await fetch(url);
                        
                        if (response.ok) {
                            const data = await response.json();
                            
                            if (data.success) {
                                imagesData = data.images || [];
                                console.log('✅ Imágenes cargadas:', imagesData.length);
                                return;
                            }
                        }
                    } catch (urlError) {
                        console.warn('Error con URL:', url, urlError.message);
                        continue;
                    }
                }
                
                console.warn('No se pudo cargar la galería de imágenes');
                    
            } catch (error) {
                console.error('Error general:', error);
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
                <div class="image-card" onclick="selectImage('${image.url}', '${image.name}')">
                    <img src="${image.thumbnail || image.url}" alt="${image.name}">
                    <div class="image-card-info">
                        <div class="image-card-name" title="${image.name}">
                            ${image.name}
                        </div>
                        <div class="image-card-size">
                            ${formatFileSize(image.size)}
                        </div>
                    </div>
                </div>
            `).join('');
            
            gallery.innerHTML = html;
        }
        
        // Seleccionar imagen y cerrar modal
        function selectImage(url, name) {
            const imageInput = document.getElementById('image_path');
            const imagePreview = document.getElementById('imagePreview');
            
            if (imageInput) {
                imageInput.value = url;
                
                // Actualizar vista previa
                if (imagePreview) {
                    imagePreview.src = url;
                    imagePreview.style.display = 'block';
                }
                
                showNotification('success', '✅ Imagen seleccionada: ' + name);
            }
            
            closeImageModal();
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
                if (progressCallback) progressCallback(20);
                
                const response = await fetch('../api/images.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                
                if (progressCallback) progressCallback(80);
                
                const data = await response.json();
                
                if (progressCallback) progressCallback(100);
                
                if (data.success) {
                    showNotification('success', '✅ Imagen subida exitosamente');
                    await loadImages(); // Recargar lista
                    renderModalImageGallery(); // Actualizar modal
                } else {
                    throw new Error(data.message || 'Error al subir la imagen');
                }
                
            } catch (error) {
                console.error('Error uploading image:', error);
                showNotification('error', 'Error: ' + error.message);
            }
        }
        
        // === FUNCIONES DEL MODAL ===
        
        // Mostrar modal de galería
        function showImageGallery() {
            renderModalImageGallery();
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
        
        // Función para mostrar notificaciones
        function showNotification(type, message) {
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
            
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>