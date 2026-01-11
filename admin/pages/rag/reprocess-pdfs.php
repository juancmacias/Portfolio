<?php
/**
 * Script para reprocesar PDFs que tienen contenido binario
 * Extrae texto de los archivos PDF y actualiza la base de datos
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

// Cargar el procesador de documentos
require_once __DIR__ . '/documents.php';

$db = Database::getInstance();

// Clase helper para reprocesamiento
class PdfReprocessor {
    private $db;
    private $uploadDir;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../../../uploads/documents/';
    }
    
    /**
     * Encontrar documentos PDF con contenido binario
     */
    public function findBinaryPdfs() {
        $sql = "
            SELECT id, title, file_name, content, file_type 
            FROM reference_documents 
            WHERE file_type LIKE '%pdf%'
            AND (
                content LIKE '%\\\\x%'
                OR content LIKE '%\\\\%'
                OR content REGEXP '[^[:print:][:space:]]'
                OR LENGTH(content) - LENGTH(REPLACE(content, '\\\\', '')) > 50
            )
            ORDER BY upload_date DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Reprocesar un PDF específico
     */
    public function reprocessPdf($documentId, $fileName) {
        $filePath = $this->uploadDir . $fileName;
        
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'Archivo no encontrado: ' . $fileName
            ];
        }
        
        // Usar el mismo método de extracción mejorado
        $processor = new DocumentProcessor();
        $reflection = new ReflectionClass($processor);
        $method = $reflection->getMethod('extractPdfText');
        $method->setAccessible(true);
        
        $extractedText = $method->invoke($processor, $filePath);
        
        if (empty($extractedText) || strlen($extractedText) < 50) {
            return [
                'success' => false,
                'error' => 'No se pudo extraer texto válido',
                'extracted_length' => strlen($extractedText)
            ];
        }
        
        // Actualizar en la base de datos
        $sql = "UPDATE reference_documents SET content = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$extractedText, $documentId]);
        
        // Recrear chunks
        $this->recreateChunks($documentId, $extractedText);
        
        return [
            'success' => true,
            'extracted_length' => strlen($extractedText),
            'preview' => substr($extractedText, 0, 200)
        ];
    }
    
    /**
     * Recrear chunks para un documento
     */
    private function recreateChunks($documentId, $text) {
        // Eliminar chunks existentes
        $this->db->query("DELETE FROM document_chunks WHERE document_id = ?", [$documentId]);
        
        // Crear nuevos chunks
        $chunkSize = 500;
        $overlap = 50;
        $chunks = [];
        $textLength = mb_strlen($text);
        
        for ($i = 0; $i < $textLength; $i += ($chunkSize - $overlap)) {
            $chunk = mb_substr($text, $i, $chunkSize);
            
            if (!empty(trim($chunk))) {
                $sql = "
                    INSERT INTO document_chunks 
                    (document_id, chunk_text, chunk_order, char_start, char_end) 
                    VALUES (?, ?, ?, ?, ?)
                ";
                
                $chunkOrder = count($chunks);
                $charEnd = min($i + $chunkSize, $textLength);
                
                $this->db->query($sql, [
                    $documentId,
                    $chunk,
                    $chunkOrder,
                    $i,
                    $charEnd
                ]);
                
                $chunks[] = [
                    'order' => $chunkOrder,
                    'start' => $i,
                    'end' => $charEnd
                ];
            }
        }
        
        return count($chunks);
    }
}

$reprocessor = new PdfReprocessor();
$message = '';
$messageType = '';

// Procesar acción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reprocess') {
        $documentId = intval($_POST['document_id']);
        $fileName = $_POST['file_name'];
        
        $result = $reprocessor->reprocessPdf($documentId, $fileName);
        
        if ($result['success']) {
            $message = "✅ PDF reprocesado exitosamente. Texto extraído: {$result['extracted_length']} caracteres";
            $messageType = 'success';
        } else {
            $message = "❌ Error: {$result['error']}";
            $messageType = 'error';
        }
    }
}

// Obtener PDFs con problemas
$binaryPdfs = $reprocessor->findBinaryPdfs();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reprocesar PDFs - RAG Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .pdf-item { border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
        .pdf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .pdf-title { font-weight: bold; font-size: 1.1em; color: #333; }
        .content-preview { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 0.9em; max-height: 150px; overflow-y: auto; margin-bottom: 15px; word-break: break-all; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.95em; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-danger { background: #dc3545; color: white; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-info { background: #17a2b8; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 2.5em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Reprocesar PDFs con Contenido Binario</h1>
            <p>Esta herramienta detecta PDFs con contenido binario mal extraído y los reprocesa</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo count($binaryPdfs); ?></div>
                <div class="stat-label">PDFs con Problemas</div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">📄 PDFs Detectados con Contenido Binario</h2>

            <?php if (empty($binaryPdfs)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    ✅ No se encontraron PDFs con problemas de contenido binario
                </p>
            <?php else: ?>
                <?php foreach ($binaryPdfs as $pdf): ?>
                    <div class="pdf-item">
                        <div class="pdf-header">
                            <div>
                                <div class="pdf-title"><?php echo htmlspecialchars($pdf['title']); ?></div>
                                <div style="margin-top: 5px;">
                                    <span class="badge badge-warning">Binario Detectado</span>
                                    <span class="badge badge-info"><?php echo $pdf['file_name']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="content-preview">
                            <strong>Vista previa del contenido actual:</strong><br>
                            <?php echo htmlspecialchars(substr($pdf['content'], 0, 300)); ?>...
                        </div>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reprocess">
                            <input type="hidden" name="document_id" value="<?php echo $pdf['id']; ?>">
                            <input type="hidden" name="file_name" value="<?php echo $pdf['file_name']; ?>">
                            <button type="submit" class="btn btn-primary" 
                                    onclick="return confirm('¿Reprocesar este PDF?')">
                                🔄 Reprocesar PDF
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="documents.php" class="btn btn-primary">⬅️ Volver a Documentos</a>
        </div>
    </div>
</body>
</html>
