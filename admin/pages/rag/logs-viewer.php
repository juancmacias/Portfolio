<?php
/**
 * Visor de Logs del Chat RAG
 * Lee y muestra los archivos de log de forma estructurada
 */

// Definir acceso admin
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}

$logDir = __DIR__ . '/../../logs/chat';
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedFile = $logDir . '/chat_' . $selectedDate . '.log';

// Obtener lista de archivos de log disponibles
$logFiles = glob($logDir . '/chat_*.log');
$availableDates = [];

foreach ($logFiles as $file) {
    if (preg_match('/chat_(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
        $availableDates[] = $matches[1];
    }
}

rsort($availableDates); // Más recientes primero

// Leer el archivo de log seleccionado
$logContent = '';
if (file_exists($selectedFile)) {
    $logContent = file_get_contents($selectedFile);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Visor de Logs - Chat RAG</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace; 
            background: #1e1e1e; 
            color: #d4d4d4; 
            padding: 20px;
        }
        
        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 { color: #4ec9b0; font-size: 1.5rem; }
        
        .controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        select, button {
            background: #3c3c3c;
            color: #d4d4d4;
            border: 1px solid #555;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
        }
        
        select:hover, button:hover {
            background: #4c4c4c;
        }
        
        .log-container {
            background: #2d2d2d;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        .log-entry {
            background: #1e1e1e;
            border-left: 4px solid #4ec9b0;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .log-section {
            margin-bottom: 15px;
        }
        
        .log-title {
            color: #4ec9b0;
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .log-content {
            background: #252526;
            padding: 10px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.5;
        }
        
        .separator {
            border-top: 2px solid #3c3c3c;
            margin: 30px 0;
        }
        
        .metadata {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            padding: 10px;
            background: #252526;
            border-radius: 4px;
        }
        
        .metadata-item {
            display: flex;
            flex-direction: column;
        }
        
        .metadata-label {
            color: #9cdcfe;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .metadata-value {
            color: #ce9178;
            font-weight: bold;
        }
        
        .rag-result {
            background: #1e1e1e;
            padding: 8px;
            margin: 5px 0;
            border-left: 3px solid #569cd6;
            border-radius: 3px;
        }
        
        .rag-header {
            color: #569cd6;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .no-logs {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #252526;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            color: #4ec9b0;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Visor de Logs - Chat RAG</h1>
        <div class="controls">
            <label style="color: #888;">Fecha:</label>
            <select id="dateSelector" onchange="changeDate(this.value)">
                <?php foreach ($availableDates as $date): ?>
                    <option value="<?php echo $date; ?>" <?php echo $date === $selectedDate ? 'selected' : ''; ?>>
                        <?php echo date('d/m/Y', strtotime($date)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button onclick="location.reload()">🔄 Recargar</button>
            <button onclick="downloadLog()">💾 Descargar</button>
        </div>
    </div>
    
    <div class="log-container">
        <?php if (!empty($logContent)): ?>
            <?php
            // Contar conversaciones
            $conversations = substr_count($logContent, 'NUEVA CONVERSACIÓN');
            $totalTokens = 0;
            $avgResponseLength = 0;
            
            if (preg_match_all('/Tokens usados: (\d+)/', $logContent, $matches)) {
                $totalTokens = array_sum($matches[1]);
            }
            ?>
            
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo $conversations; ?></span>
                    <span class="stat-label">Conversaciones</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($totalTokens); ?></span>
                    <span class="stat-label">Tokens Totales</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $conversations > 0 ? round($totalTokens / $conversations) : 0; ?></span>
                    <span class="stat-label">Tokens/Conv</span>
                </div>
            </div>
            
            <pre class="log-content"><?php echo htmlspecialchars($logContent); ?></pre>
        <?php else: ?>
            <div class="no-logs">
                <h2>📭 No hay logs para esta fecha</h2>
                <p>Los logs se generarán automáticamente cuando haya conversaciones en el chat.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function changeDate(date) {
            window.location.href = '?date=' + date;
        }
        
        function downloadLog() {
            const date = document.getElementById('dateSelector').value;
            window.location.href = '../../logs/chat/chat_' + date + '.log';
        }
    </script>
</body>
</html>
