<?php
/**
 * Gestión de Prompts RAG - Integrado en Admin
 * Panel completo para CRUD de prompts conversacionales
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../config/config.local.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new AdminAuth();

// Verificar autenticación
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = $auth->getUser();

// Incluir clases necesarias
require_once __DIR__ . '/../../classes/RAG/PromptManager.php';

$promptManager = new PromptManager();

// Procesar acciones
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_prompt':
            $result = $promptManager->createPrompt([
                'name' => $_POST['name'],
                'prompt_text' => $_POST['prompt_text'],
                'context_prompt' => $_POST['context_prompt'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ]);
            
            if ($result['success']) {
                $message = 'Prompt creado exitosamente.';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $result['error'];
                $messageType = 'error';
            }
            break;
            
        case 'update_prompt':
            $result = $promptManager->updatePrompt($_POST['prompt_id'], [
                'name' => $_POST['name'],
                'prompt_text' => $_POST['prompt_text'],
                'context_prompt' => $_POST['context_prompt'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ]);
            
            if ($result['success']) {
                $message = 'Prompt actualizado exitosamente.';
                $messageType = 'success';
            } else {
                $message = 'Error: ' . $result['error'];
                $messageType = 'error';
            }
            break;
            
        case 'toggle_prompt':
            $promptId = intval($_POST['prompt_id']);
            $isActive = intval($_POST['is_active']);
            
            $result = $promptManager->togglePromptStatus($promptId);
            
            if ($result['success']) {
                $message = 'Estado del prompt actualizado.';
                $messageType = 'success';
            } else {
                $message = 'Error al actualizar el estado.';
                $messageType = 'error';
            }
            break;
            
        case 'delete_prompt':
            $result = $promptManager->deletePrompt($_POST['prompt_id']);
            
            if ($result['success']) {
                $message = 'Prompt eliminado exitosamente.';
                $messageType = 'success';
            } else {
                $message = 'Error al eliminar el prompt.';
                $messageType = 'error';
            }
            break;
    }
}

// Obtener prompts
$prompts = $promptManager->getAllPrompts();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💬 Gestión de Prompts - Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { color: #333; }
        .breadcrumb { color: #666; font-size: 0.9rem; }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        
        .user-info { display: flex; align-items: center; gap: 15px; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover { background: #5a6fd8; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 5px 10px; font-size: 0.8rem; }
        
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .tabs {
            display: flex;
            background: white;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            margin-bottom: 0;
        }
        
        .tab {
            flex: 1;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            border-right: 1px solid #dee2e6;
        }
        
        .tab:last-child { border-right: none; }
        .tab.active { background: white; border-bottom: 3px solid #667eea; }
        .tab:hover:not(.active) { background: #e9ecef; }
        
        .tab-content {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea { min-height: 150px; resize: vertical; }
        
        .prompts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .prompt-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .prompt-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        .prompt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .prompt-title { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 5px; }
        .prompt-meta { font-size: 0.9rem; color: #666; }
        
        .prompt-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            max-height: 120px;
            overflow-y: auto;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .prompt-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .prompts-grid { grid-template-columns: 1fr; }
            .prompt-actions { flex-direction: column; }
            .tabs { flex-direction: column; }
        }
        
        /* Modal de Ayuda de Variables */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            border-radius: 12px;
            width: 90%;
            max-width: 1000px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.6em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 50%;
            transition: background 0.3s;
        }
        
        .close:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .modal-body {
            padding: 30px;
            max-height: 75vh;
            overflow-y: auto;
        }
        
        .variable-section {
            margin-bottom: 35px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #007bff;
        }
        
        .variable-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .variable-item {
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: box-shadow 0.3s;
        }
        
        .variable-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .variable-name {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #e83e8c;
            background: #f8f9fa;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 1em;
            border: 1px solid #e9ecef;
        }
        
        .variable-description {
            margin-top: 12px;
            color: #666;
            line-height: 1.6;
            font-size: 0.95em;
        }
        
        .example-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-top: 25px;
            border: 1px solid #e9ecef;
        }
        
        .example-section h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .example-code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            line-height: 1.5;
            overflow-x: auto;
            white-space: pre-wrap;
            border: 1px solid #4a5568;
        }
        
        .btn-help {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            margin-left: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        
        .btn-help:hover {
            background: #138496;
        }
        
        .copy-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background: #218838;
        }
        
        .variable-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>💬 Gestión de Prompts RAG</h1>
            <div class="breadcrumb">
                <a href="../dashboard.php">📊 Dashboard Principal</a> / 
                <a href="dashboard.php">🎛️ Dashboard RAG</a> / 
                Prompts
            </div>
        </div>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <button onclick="openHelpModal()" class="btn-help">❓ Variables de Prompts</button>
            <a href="../logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('create')">➕ Crear Prompt</button>
            <button class="tab" onclick="switchTab('manage')">📋 Gestionar Prompts</button>
        </div>
        
        <div class="tab-content">
            <!-- Crear Prompt -->
            <div id="create-tab" class="tab-pane active">
                <h2>➕ Crear Nuevo Prompt</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_prompt">
                    
                    <div class="form-group">
                        <label for="name">Nombre del Prompt</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="Ej: Asistente Portfolio, Especialista Técnico...">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Descripción (Opcional)</label>
                        <input type="text" id="description" name="description" 
                               placeholder="Breve descripción del propósito de este prompt">
                    </div>
                    
                    <div class="form-group">
                        <label for="prompt_text">
                            Texto del Prompt
                            <button type="button" onclick="openHelpModal()" class="btn-help" style="margin-left: 10px; padding: 5px 10px; font-size: 0.8em;">
                                ❓ Ver Variables Disponibles
                            </button>
                        </label>
                        <textarea id="prompt_text" name="prompt_text" required 
                                  placeholder="Escribe el prompt completo. Puedes usar variables como {user_name}, {context}, {portfolio_data}..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success">💾 Crear Prompt</button>
                </form>
            </div>
            
            <!-- Gestionar Prompts -->
            <div id="manage-tab" class="tab-pane">
                <h2>📋 Gestionar Prompts Existentes</h2>
                
                <div class="prompts-grid">
                    <?php foreach ($prompts as $prompt): ?>
                        <div class="prompt-card">
                            <div class="prompt-header">
                                <div>
                                    <div class="prompt-title"><?php echo htmlspecialchars($prompt['name']); ?></div>
                                    <div class="prompt-meta">
                                        📅 <?php echo date('d/m/Y', strtotime($prompt['created_at'])); ?> · 
                                        <span class="status-indicator status-<?php echo $prompt['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $prompt['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="prompt-preview">
                                <?php echo nl2br(htmlspecialchars(substr($prompt['prompt_text'], 0, 200))); ?>
                                <?php if (strlen($prompt['prompt_text']) > 200): ?>
                                    <span style="color: #666;">...</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="prompt-actions">
                                <button class="btn btn-small" onclick="editPrompt(<?php echo $prompt['id']; ?>)">
                                    ✏️ Editar
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_prompt">
                                    <input type="hidden" name="prompt_id" value="<?php echo $prompt['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $prompt['is_active'] ? 0 : 1; ?>">
                                    <button type="submit" class="btn btn-small" style="background: <?php echo $prompt['is_active'] ? '#ffc107' : '#28a745'; ?>">
                                        <?php echo $prompt['is_active'] ? '⏸️ Desactivar' : '▶️ Activar'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('¿Estás seguro de eliminar este prompt?')">
                                    <input type="hidden" name="action" value="delete_prompt">
                                    <input type="hidden" name="prompt_id" value="<?php echo $prompt['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small">🗑️ Eliminar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($prompts)): ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <h3>📭 No hay prompts creados</h3>
                        <p>Crea tu primer prompt para comenzar</p>
                        <button class="btn" onclick="switchTab('create')">➕ Crear Primer Prompt</button>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <!-- Modal de Edición -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Editar Prompt</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="editPromptForm">
                    <input type="hidden" name="action" value="update_prompt">
                    <input type="hidden" name="prompt_id" id="edit_prompt_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Nombre del Prompt</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_context_prompt">Contexto del Prompt (Opcional)</label>
                        <textarea id="edit_context_prompt" name="context_prompt" rows="3"
                                  placeholder="Contexto adicional o instrucciones previas..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_prompt_text">Texto del Prompt</label>
                        <textarea id="edit_prompt_text" name="prompt_text" required rows="8"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                            Activo
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn" onclick="closeEditModal()">Cancelar</button>
                        <button type="submit" class="btn btn-success">💾 Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const prompts = <?php echo json_encode($prompts); ?>;
        
        function switchTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            // Mostrar tab seleccionado
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function editPrompt(promptId) {
            // Buscar el prompt
            const prompt = prompts.find(p => p.id == promptId);
            if (!prompt) {
                alert('Prompt no encontrado');
                return;
            }
            
            // Llenar el formulario
            document.getElementById('edit_prompt_id').value = prompt.id;
            document.getElementById('edit_name').value = prompt.name;
            document.getElementById('edit_context_prompt').value = prompt.context_prompt || '';
            document.getElementById('edit_prompt_text').value = prompt.prompt_text;
            document.getElementById('edit_is_active').checked = prompt.is_active == 1;
            
            // Mostrar modal
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
