<?php
/**
 * Actualizar manualmente el texto de un documento
 * Para PDFs donde la extracción automática falló
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

$db = Database::getInstance();
$message = '';
$messageType = '';
$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información del documento
$document = null;
if ($documentId > 0) {
    $sql = "SELECT * FROM reference_documents WHERE id = ?";
    $document = $db->fetchOne($sql, [$documentId]);
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_text'])) {
    $documentId = intval($_POST['document_id']);
    $newText = trim($_POST['document_text']);
    
    if (empty($newText)) {
        $message = 'El texto no puede estar vacío';
        $messageType = 'error';
    } else {
        try {
            // Actualizar el contenido del documento
            $sql = "UPDATE reference_documents SET content = ?, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$newText, $documentId]);
            
            // Recrear chunks
            // Primero eliminar chunks existentes
            $db->query("DELETE FROM document_chunks WHERE document_id = ?", [$documentId]);
            
            // Crear nuevos chunks
            $chunkSize = 500;
            $overlap = 50;
            $textLength = mb_strlen($newText);
            $chunksCreated = 0;
            
            for ($i = 0; $i < $textLength; $i += ($chunkSize - $overlap)) {
                $chunk = mb_substr($newText, $i, $chunkSize);
                
                if (!empty(trim($chunk))) {
                    $sql = "
                        INSERT INTO document_chunks 
                        (document_id, chunk_text, chunk_order, char_start, char_end) 
                        VALUES (?, ?, ?, ?, ?)
                    ";
                    
                    $charEnd = min($i + $chunkSize, $textLength);
                    
                    $db->query($sql, [
                        $documentId,
                        $chunk,
                        $chunksCreated,
                        $i,
                        $charEnd
                    ]);
                    
                    $chunksCreated++;
                }
            }
            
            $message = "✅ Documento actualizado exitosamente. {$chunksCreated} chunks creados.";
            $messageType = 'success';
            
            // Recargar documento
            $document = $db->fetchOne("SELECT * FROM reference_documents WHERE id = ?", [$documentId]);
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Listar documentos con contenido problemático
$problemDocs = [];
$sql = "
    SELECT id, title, file_name, 
           SUBSTRING(content, 1, 100) as preview,
           LENGTH(content) as content_length,
           file_type
    FROM reference_documents 
    WHERE content LIKE '%PDF cargado pero no se pudo%'
       OR content LIKE '%Error:%'
       OR (file_type LIKE '%pdf%' AND LENGTH(content) < 200)
    ORDER BY upload_date DESC
";
$problemDocs = $db->fetchAll($sql);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Texto del Documento - RAG Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .card { background: white; border-radius: 12px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; font-size: 1.05em; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; transition: border-color 0.3s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .form-group textarea { min-height: 400px; font-family: 'Courier New', monospace; line-height: 1.6; resize: vertical; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: 600; transition: all 0.3s; display: inline-block; text-decoration: none; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .doc-list { display: grid; gap: 15px; }
        .doc-item { border: 2px solid #e0e0e0; padding: 20px; border-radius: 10px; transition: all 0.3s; cursor: pointer; }
        .doc-item:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .doc-item.selected { border-color: #667eea; background: #f8f9ff; }
        .doc-title { font-weight: bold; color: #333; margin-bottom: 8px; font-size: 1.1em; }
        .doc-preview { background: #f8f9fa; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 0.9em; color: #666; margin-top: 10px; max-height: 100px; overflow: hidden; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 5px; font-size: 0.85em; font-weight: 600; margin-right: 5px; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .instructions { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .instructions h3 { color: #1976D2; margin-bottom: 12px; }
        .instructions ol { margin-left: 20px; line-height: 1.8; }
        .stats-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 25px; }
        .stats-number { font-size: 2.5em; font-weight: bold; }
        .stats-label { font-size: 1em; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Actualizar Texto del Documento</h1>
            <p>Actualiza manualmente el contenido de documentos donde la extracción automática falló</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($problemDocs)): ?>
            <div class="stats-box">
                <div class="stats-number"><?php echo count($problemDocs); ?></div>
                <div class="stats-label">Documentos que requieren actualización</div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="instructions">
                <h3>📋 Instrucciones</h3>
                <ol>
                    <li>Selecciona un documento de la lista abajo (o usa el ID en la URL)</li>
                    <li>Copia el texto del PDF usando una herramienta externa (Adobe Reader, navegador, etc.)</li>
                    <li>Pega el texto en el área de texto</li>
                    <li>Haz clic en "Actualizar Documento"</li>
                    <li>El sistema actualizará el contenido y recreará los chunks para búsqueda</li>
                </ol>
            </div>

            <?php if ($document): ?>
                <h2 style="margin-bottom: 20px;">📄 Editando: <?php echo htmlspecialchars($document['title']); ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                    
                    <div class="form-group">
                        <label>Archivo Original:</label>
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 6px; color: #666;">
                            📎 <?php echo htmlspecialchars($document['file_name']); ?>
                            (<?php echo number_format($document['file_size'] / 1024, 2); ?> KB)
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contenido Actual:</label>
                        <div class="doc-preview" style="max-height: 150px; overflow-y: auto;">
                            <?php echo htmlspecialchars(substr($document['content'], 0, 500)); ?>
                            <?php if (strlen($document['content']) > 500): ?>
                                <br><em>... (<?php echo strlen($document['content']); ?> caracteres totales)</em>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="document_text">
                            Nuevo Texto del Documento: *
                            <span style="font-weight: normal; color: #666;">(Pega aquí el texto extraído del PDF)</span>
                        </label>
                        <textarea id="document_text" name="document_text" required 
                                  placeholder="Pega aquí el texto completo extraído del documento PDF..."><?php echo htmlspecialchars($document['content']); ?></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Tip: Puedes abrir el PDF con Adobe Reader o tu navegador, seleccionar todo (Ctrl+A) y copiar el texto
                        </small>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="update_text" class="btn btn-primary">
                            💾 Actualizar Documento
                        </button>
                        <a href="documents.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>
            <?php else: ?>
                <h2 style="margin-bottom: 20px;">📋 Documentos que Requieren Actualización</h2>
                
                <?php if (empty($problemDocs)): ?>
                    <div class="message info">
                        ✅ No hay documentos que requieran actualización manual
                    </div>
                <?php else: ?>
                    <div class="doc-list">
                        <?php foreach ($problemDocs as $doc): ?>
                            <a href="?id=<?php echo $doc['id']; ?>" style="text-decoration: none; color: inherit;">
                                <div class="doc-item">
                                    <div class="doc-title">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </div>
                                    <div>
                                        <span class="badge badge-warning">Requiere Actualización</span>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                                    </div>
                                    <div class="doc-preview">
                                        <?php echo htmlspecialchars($doc['preview']); ?>...
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="documents.php" class="btn btn-secondary">⬅️ Volver a Documentos</a>
        </div>
    </div>
</body>
</html>
