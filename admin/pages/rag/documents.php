<?php
/**
 * Gestión de Documentos RAG - Integrado en Admin
 * Sistema de subida, procesamiento y gestión de documentos
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
$db = Database::getInstance();

// Clase DocumentProcessor (versión simplificada integrada)
class DocumentProcessor {
    private $db;
    private $uploadDir;
    private $allowedTypes = ['pdf', 'txt', 'doc', 'docx'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../../../uploads/documents/';
        
        // Crear directorio si no existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Procesar archivo subido
     */
    public function processUpload($file, $title = '', $description = '') {
        try {
            // Validar archivo
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                throw new Exception($validation['error']);
            }
            
            // Generar nombre único
            $originalName = basename($file['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileName = uniqid('doc_') . '.' . $extension;
            $filePath = $this->uploadDir . $fileName;
            
            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('Error al guardar el archivo');
            }
            
            // Extraer texto
            $textContent = $this->extractText($filePath, $extension);
            if (empty($textContent)) {
                throw new Exception('No se pudo extraer texto del documento');
            }
            
            // Guardar en base de datos
            $documentId = $this->saveDocument([
                'title' => $title ?: $originalName,
                'description' => $description,
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'content' => $textContent
            ]);
            
            // Crear chunks
            $chunks = $this->createChunks($textContent, $documentId);
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'chunks_created' => count($chunks),
                'file_name' => $fileName,
                'text_length' => strlen($textContent)
            ];
            
        } catch (Exception $e) {
            // Limpiar archivo si hubo error
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar archivo subido
     */
    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Error en la subida del archivo'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'Archivo demasiado grande (máximo 10MB)'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'Tipo de archivo no permitido'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Extraer texto según tipo de archivo
     */
    private function extractText($filePath, $extension) {
        switch ($extension) {
            case 'txt':
                return file_get_contents($filePath);
                
            case 'pdf':
                return $this->extractPdfText($filePath);
                
            default:
                return 'Contenido del archivo - Requiere procesamiento especializado';
        }
    }
    
    /**
     * Extraer texto de PDF (versión mejorada con múltiples métodos)
     */
    private function extractPdfText($filePath) {
        $text = '';
        error_log("=== INICIANDO EXTRACCIÓN DE PDF: $filePath ===");
        
        // MÉTODO 1: Intentar usar pdftotext (si está disponible en el sistema)
        error_log("Método 1: Verificando pdftotext...");
        if ($this->isPdfToTextAvailable()) {
            error_log("pdftotext disponible, intentando extraer...");
            $text = $this->extractWithPdfToText($filePath);
            $textLength = $text ? strlen(trim($text)) : 0;
            if ($textLength > 50) {
                error_log("✓ PDF extraído con pdftotext: " . $textLength . " caracteres");
                return $this->cleanExtractedText($text);
            }
            error_log("✗ pdftotext no extrajo suficiente texto: " . $textLength . " caracteres");
        } else {
            error_log("✗ pdftotext no está disponible");
        }
        
        // MÉTODO 2: Descomprimir streams FlateDecode
        error_log("Método 2: Intentando descomprimir streams FlateDecode...");
        $text = $this->extractPdfTextDecompressed($filePath);
        $textLength = $text ? strlen(trim($text)) : 0;
        if ($textLength > 50) {
            error_log("✓ PDF extraído con descompresión: " . $textLength . " caracteres");
            return $this->cleanExtractedText($text);
        }
        error_log("✗ Descompresión no extrajo suficiente texto: " . $textLength . " caracteres");
        
        // MÉTODO 3: Regex mejorado para PDFs simples
        error_log("Método 3: Intentando extracción con regex...");
        $text = $this->extractPdfTextWithRegex($filePath);
        $textLength = $text ? strlen(trim($text)) : 0;
        if ($textLength > 50) {
            error_log("✓ PDF extraído con regex: " . $textLength . " caracteres");
            return $this->cleanExtractedText($text);
        }
        error_log("✗ Regex no extrajo suficiente texto: " . $textLength . " caracteres");
        
        // MÉTODO 4: Extracción agresiva - intentar obtener cualquier texto legible
        error_log("Método 4: Intentando extracción agresiva...");
        $text = $this->extractPdfTextAggressive($filePath);
        $textLength = $text ? strlen(trim($text)) : 0;
        if ($textLength > 50) {
            error_log("✓ PDF extraído con método agresivo: " . $textLength . " caracteres");
            return $this->cleanExtractedText($text);
        }
        error_log("✗ Método agresivo no extrajo suficiente texto: " . $textLength . " caracteres");
        
        // Si fallan todos los métodos, devolver mensaje de advertencia
        error_log("✗✗✗ NO SE PUDO EXTRAER TEXTO DEL PDF: $filePath ✗✗✗");
        return "⚠️ PDF cargado pero no se pudo extraer texto automáticamente. " .
               "El documento está disponible pero no podrá ser buscado por su contenido. " .
               "Para mejor indexación, considera convertir el PDF a formato de texto o usar un PDF con capa de texto.";
    }
    
    /**
     * Verificar si pdftotext está disponible en el sistema
     */
    private function isPdfToTextAvailable() {
        $output = [];
        $returnVar = 0;
        
        // Intentar ejecutar pdftotext --version
        @exec('pdftotext -v 2>&1', $output, $returnVar);
        
        return $returnVar === 0 || $returnVar === 1; // pdftotext retorna 1 con -v pero existe
    }
    
    /**
     * Extraer texto usando pdftotext (herramienta de línea de comandos)
     */
    private function extractWithPdfToText($filePath) {
        $outputFile = sys_get_temp_dir() . '/' . uniqid('pdf_') . '.txt';
        $command = sprintf('pdftotext %s %s 2>&1', 
            escapeshellarg($filePath), 
            escapeshellarg($outputFile)
        );
        
        @exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($outputFile)) {
            $text = file_get_contents($outputFile);
            @unlink($outputFile);
            return $text;
        }
        
        if (file_exists($outputFile)) {
            @unlink($outputFile);
        }
        
        return '';
    }
    
    /**
     * Extraer texto de PDF descomprimiendo streams FlateDecode
     * Este método es esencial para PDFs con contenido comprimido
     */
    private function extractPdfTextDecompressed($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        
        error_log("Descompresión: Extrayendo ToUnicode CMap...");
        
        // PASO 1: Extraer el CMap para mapear códigos hex a Unicode
        $charMap = $this->extractToUnicodeCMap($content);
        error_log("Descompresión: CMap extraído con " . count($charMap) . " mappings");
        
        // PASO 2: Descomprimir todos los streams FlateDecode
        $allDecompressedText = '';
        
        if (preg_match_all('/\/FlateDecode.*?stream\s+(.*?)\s+endstream/s', $content, $matches, PREG_SET_ORDER)) {
            error_log("Descompresión: Encontrados " . count($matches) . " streams comprimidos");
            
            foreach ($matches as $match) {
                $compressed = trim($match[1]);
                $decompressed = @gzuncompress($compressed);
                
                if ($decompressed === false) {
                    $decompressed = @gzinflate($compressed);
                }
                
                if ($decompressed !== false && strlen($decompressed) > 0) {
                    $allDecompressedText .= $decompressed . "\n";
                }
            }
            
            error_log("Descompresión: Total descomprimido: " . strlen($allDecompressedText) . " bytes");
        }
        
        // PASO 3: Extraer y decodificar texto usando el CMap
        if (strlen($allDecompressedText) > 100 && !empty($charMap)) {
            $extractedStrings = [];
            
            // Buscar arrays hexadecimales con comando TJ: [<0056016A>...] TJ
            if (preg_match_all('/\[((?:<[0-9A-Fa-f]+>(?:-?\d+)?)+)\]\s*TJ/', $allDecompressedText, $hexMatches)) {
                error_log("Descompresión: Encontrados " . count($hexMatches[1]) . " arrays hexadecimales con TJ");
                
                foreach ($hexMatches[1] as $hexArray) {
                    // Extraer todos los valores hex
                    if (preg_match_all('/<([0-9A-Fa-f]+)>/', $hexArray, $hexValues)) {
                        $decodedChars = [];
                        
                        foreach ($hexValues[1] as $hexValue) {
                            // Los códigos pueden tener longitud variable (2, 4, 6, 8 dígitos)
                            // Primero intentar buscar el código completo en el CMap
                            $hexCode = strtoupper($hexValue);
                            
                            // Intentar con padding a 4 dígitos
                            $paddedCode = str_pad($hexCode, 4, '0', STR_PAD_LEFT);
                            
                            if (isset($charMap[$paddedCode])) {
                                $unicodeHex = $charMap[$paddedCode];
                                $unicode = hexdec($unicodeHex);
                                
                                if ($unicode > 31 && $unicode < 65535) {
                                    $char = mb_chr($unicode, 'UTF-8');
                                    if ($char && trim($char) !== '') {
                                        $decodedChars[] = $char;
                                    }
                                }
                            } else if (isset($charMap[$hexCode])) {
                                // Intentar sin padding
                                $unicodeHex = $charMap[$hexCode];
                                $unicode = hexdec($unicodeHex);
                                
                                if ($unicode > 31 && $unicode < 65535) {
                                    $char = mb_chr($unicode, 'UTF-8');
                                    if ($char && trim($char) !== '') {
                                        $decodedChars[] = $char;
                                    }
                                }
                            } else {
                                // Si el código tiene múltiples caracteres (ej: 0056016A = 2 chars)
                                // Dividir en bloques de 4 dígitos
                                $length = strlen($hexValue);
                                if ($length > 4 && $length % 4 === 0) {
                                    for ($i = 0; $i < $length; $i += 4) {
                                        $subCode = strtoupper(substr($hexValue, $i, 4));
                                        
                                        if (isset($charMap[$subCode])) {
                                            $unicodeHex = $charMap[$subCode];
                                            $unicode = hexdec($unicodeHex);
                                            
                                            if ($unicode > 31 && $unicode < 65535) {
                                                $char = mb_chr($unicode, 'UTF-8');
                                                if ($char && trim($char) !== '') {
                                                    $decodedChars[] = $char;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if (!empty($decodedChars)) {
                            $decodedString = implode('', $decodedChars);
                            if (strlen($decodedString) >= 2) {
                                $extractedStrings[] = $decodedString;
                            }
                        }
                    }
                }
            }
            
            // También buscar strings normales entre paréntesis
            if (preg_match_all('/\(((?:[^()\\\\]|\\\\.)+)\)\s*T[jJ]/', $allDecompressedText, $normalMatches)) {
                foreach ($normalMatches[1] as $match) {
                    $decoded = $this->decodePdfString($match);
                    if (strlen($decoded) >= 2) {
                        $extractedStrings[] = $decoded;
                    }
                }
            }
            
            $text = implode(' ', $extractedStrings);
            error_log("Descompresión: Texto extraído: " . strlen($text) . " caracteres, " . count($extractedStrings) . " fragmentos");
        }
        
        return $text;
    }
    
    /**
     * Extraer ToUnicode CMap del PDF para mapear códigos a Unicode
     */
    private function extractToUnicodeCMap($content) {
        $charMap = [];
        
        // Buscar referencia al ToUnicode CMap
        if (preg_match('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $content, $cmapRef)) {
            $objectId = $cmapRef[1];
            
            // Buscar el objeto CMap
            if (preg_match('/' . $objectId . '\s+0\s+obj.*?endobj/s', $content, $cmapObj)) {
                $cmapContent = $cmapObj[0];
                
                // Si está comprimido, descomprimir
                if (preg_match('/stream\s+(.*?)\s+endstream/s', $cmapContent, $stream)) {
                    $compressed = trim($stream[1]);
                    $decompressed = @gzuncompress($compressed);
                    
                    if ($decompressed === false) {
                        $decompressed = @gzinflate($compressed);
                    }
                    
                    if ($decompressed !== false) {
                        $cmapContent = $decompressed;
                    }
                }
                
                // Extraer mappings individuales: <0001> <0041>
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $cmapContent, $mappings)) {
                    for ($i = 0; $i < count($mappings[1]); $i++) {
                        $from = strtoupper($mappings[1][$i]);
                        $to = $mappings[2][$i];
                        $charMap[$from] = $to;
                    }
                }
                
                // Extraer rangos: <0061> <0062> <004D>
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $cmapContent, $ranges)) {
                    for ($i = 0; $i < count($ranges[1]); $i++) {
                        $start = hexdec($ranges[1][$i]);
                        $end = hexdec($ranges[2][$i]);
                        $targetStart = hexdec($ranges[3][$i]);
                        
                        for ($code = $start; $code <= $end; $code++) {
                            $from = strtoupper(sprintf('%04X', $code));
                            $to = sprintf('%04X', $targetStart + ($code - $start));
                            $charMap[$from] = $to;
                        }
                    }
                }
            }
        }
        
        return $charMap;
    }
    
    /**
     * Extraer texto de PDF usando regex (método básico para PDFs simples)
     */
    private function extractPdfTextWithRegex($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        $textFragments = [];
        
        error_log("Regex: Tamaño del archivo: " . strlen($content) . " bytes");
        error_log("Regex: Buscando bloques BT/ET...");
        
        // PASO 1: Buscar streams de texto entre BT y ET (comandos de texto en PDF)
        // Usar PREG_SET_ORDER para obtener coincidencias una por una y evitar límites de memoria
        $btEtPattern = '/BT\s+(.*?)\s+ET/s';
        preg_match_all($btEtPattern, $content, $matches, PREG_SET_ORDER);
        
        if (!empty($matches)) {
            error_log("Regex: Encontrados " . count($matches) . " bloques BT/ET");
            
            foreach ($matches as $match) {
                $textBlock = $match[1];
                
                // Extraer texto entre paréntesis
                preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/s', $textBlock, $parenMatches);
                if (!empty($parenMatches[1])) {
                    foreach ($parenMatches[1] as $textMatch) {
                        $decoded = $this->decodePdfString($textMatch);
                        if (!empty($decoded) && strlen($decoded) > 0) {
                            $textFragments[] = $decoded;
                        }
                    }
                }
                
                // También buscar arrays de texto [(...) (...)]
                preg_match_all('/\[\s*\(((?:[^()\\\\]|\\\\.)*)\)/s', $textBlock, $arrayMatches);
                if (!empty($arrayMatches[1])) {
                    foreach ($arrayMatches[1] as $textMatch) {
                        $decoded = $this->decodePdfString($textMatch);
                        if (!empty($decoded) && strlen($decoded) > 0) {
                            $textFragments[] = $decoded;
                        }
                    }
                }
            }
            
            error_log("Regex: Fragmentos extraídos de BT/ET: " . count($textFragments));
        }
        
        // PASO 2: Si no encontramos suficiente texto, buscar TODOS los paréntesis en el documento completo
        if (count($textFragments) < 10) {
            error_log("Regex: Pocos fragmentos en BT/ET, buscando todos los paréntesis...");
            
            // Dividir el contenido en chunks para evitar límites de regex
            $chunkSize = 100000; // 100KB por chunk
            $contentLength = strlen($content);
            $totalFragments = 0;
            
            for ($offset = 0; $offset < $contentLength; $offset += $chunkSize) {
                $chunk = substr($content, $offset, $chunkSize + 1000); // Overlap de 1000 bytes
                
                preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/s', $chunk, $allMatches);
                
                if (!empty($allMatches[1])) {
                    foreach ($allMatches[1] as $match) {
                        $decoded = $this->decodePdfString($match);
                        if (!empty($decoded) && strlen($decoded) > 0) {
                            // Evitar duplicados de overlap
                            $hash = md5($decoded);
                            if (!isset($seenHashes[$hash])) {
                                $textFragments[] = $decoded;
                                $seenHashes[$hash] = true;
                                $totalFragments++;
                            }
                        }
                    }
                }
            }
            
            error_log("Regex: Fragmentos adicionales extraídos: " . $totalFragments);
        }
        
        // Unir todos los fragmentos
        $text = implode(' ', $textFragments);
        error_log("Regex: Total de fragmentos: " . count($textFragments) . ", longitud total: " . strlen($text) . " caracteres");
        
        // Limpiar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text ?? '');
    }
    
    /**
     * Decodificar string de PDF
     */
    private function decodePdfString($string) {
        // Reemplazar escapes comunes
        $string = str_replace('\\n', "\n", $string);
        $string = str_replace('\\r', "\r", $string);
        $string = str_replace('\\t', "\t", $string);
        $string = str_replace('\\(', '(', $string);
        $string = str_replace('\\)', ')', $string);
        $string = str_replace('\\\\', '\\', $string);
        
        // Decodificar caracteres octales
        $string = preg_replace_callback('/\\\\([0-7]{3})/', function($matches) {
            return chr(octdec($matches[1]));
        }, $string);
        
        // Filtrar caracteres no imprimibles
        $string = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', ' ', $string);
        
        return trim($string ?? '');
    }
    
    /**
     * Limpiar texto extraído
     */
    private function cleanExtractedText($text) {
        // Verificar que no sea null
        if ($text === null) {
            return '';
        }
        
        // Normalizar espacios en blanco
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Eliminar caracteres de control
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Normalizar saltos de línea
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Eliminar múltiples saltos de línea
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Extracción agresiva - buscar cualquier texto legible en el PDF
     */
    private function extractPdfTextAggressive($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        $extractedStrings = [];
        
        // Buscar todos los strings entre paréntesis en todo el documento
        if (preg_match_all('/\(((?:[^()\\\\]|\\\\.|\\\\[0-7]{3})*)\)/s', $content, $matches)) {
            error_log("Método agresivo: Encontrados " . count($matches[1]) . " strings entre paréntesis");
            
            foreach ($matches[1] as $match) {
                $decoded = $this->decodePdfString($match);
                // Solo agregar si tiene al menos 2 caracteres y contiene letras
                if (strlen($decoded) >= 2 && preg_match('/[a-zA-ZáéíóúñÁÉÍÓÚÑ0-9]/', $decoded)) {
                    $extractedStrings[] = $decoded;
                }
            }
            
            // Unir todos los strings con espacios
            $text = implode(' ', $extractedStrings);
            error_log("Método agresivo: Texto extraído de strings: " . strlen($text) . " caracteres");
        }
        
        // Si aún no tenemos suficiente texto, buscar palabras individuales
        if (strlen(trim($text ?? '')) < 100) {
            error_log("Método agresivo: Texto insuficiente, buscando palabras individuales...");
            
            // Buscar secuencias de caracteres legibles (palabras de al menos 3 letras)
            if (preg_match_all('/[a-zA-ZáéíóúñÁÉÍÓÚÑ]{3,}/', $content, $wordMatches)) {
                $words = array_unique($wordMatches[0]);
                // Filtrar palabras comunes de metadatos PDF
                $excludeWords = ['Obj', 'endobj', 'xref', 'trailer', 'startxref', 'Type', 'Page', 'Font', 'null', 'true', 'false', 'stream', 'endstream'];
                $words = array_filter($words, function($word) use ($excludeWords) {
                    return !in_array($word, $excludeWords) && strlen($word) < 50;
                });
                
                if (count($words) > 10) {
                    // Tomar TODAS las palabras, no solo 500
                    $text .= ' ' . implode(' ', $words);
                    error_log("Método agresivo: Añadidas " . count($words) . " palabras individuales");
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Guardar documento en base de datos
     */
    private function saveDocument($data) {
        $sql = "
            INSERT INTO reference_documents 
            (title, content, file_type, file_size, tags) 
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $this->db->query($sql, [
            $data['title'],
            $data['content'],
            $data['mime_type'] ?? 'text/plain',
            $data['file_size'] ?? 0,
            $data['description'] ?? ''
        ]);
        
        $documentId = $this->db->lastInsertId();
        
        // Generar embedding para el documento completo
        $this->generateEmbeddingForDocument($documentId, $data['content'], $data['title']);
        
        return $documentId;
    }
    
    /**
     * Generar embedding para un documento completo
     */
    private function generateEmbeddingForDocument($documentId, $content, $title) {
        try {
            // Combinar título y contenido para análisis
            $fullText = $title . '. ' . $content;
            $embedding = $this->createSimpleEmbedding($fullText);
            $keywords = $this->extractKeywords($fullText);
            
            // Usar estructura real de la tabla
            $sql = "
                INSERT INTO simple_embeddings 
                (content_id, content_type, embedding_summary, keywords) 
                VALUES (?, 'document', ?, ?)
                ON DUPLICATE KEY UPDATE
                embedding_summary = VALUES(embedding_summary),
                keywords = VALUES(keywords)
            ";
            
            // Crear resumen del contenido completo
            $summary = substr($fullText, 0, 497) . '...';
            $keywordsStr = implode(', ', array_slice($keywords, 0, 20));
            
            $this->db->query($sql, [
                $documentId,
                $summary,
                $keywordsStr
            ]);
            
        } catch (Exception $e) {
            error_log("Error generando embedding del documento: " . $e->getMessage());
        }
    }
    
    /**
     * Crear chunks de texto
     */
    private function createChunks($text, $documentId, $chunkSize = 500) {
        $chunks = [];
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $currentChunk = '';
        $chunkIndex = 0;
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            if (strlen($currentChunk . ' ' . $sentence) > $chunkSize && !empty($currentChunk)) {
                $chunks[] = $this->saveChunk($documentId, $currentChunk, $chunkIndex);
                $currentChunk = $sentence;
                $chunkIndex++;
            } else {
                $currentChunk .= (empty($currentChunk) ? '' : ' ') . $sentence;
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = $this->saveChunk($documentId, $currentChunk, $chunkIndex);
        }
        
        return $chunks;
    }
    
    /**
     * Guardar chunk en base de datos
     */
    private function saveChunk($documentId, $content, $chunkIndex) {
        $sql = "
            INSERT INTO document_chunks 
            (document_id, chunk_text, chunk_order) 
            VALUES (?, ?, ?)
        ";

        $this->db->query($sql, [
            $documentId,
            $content,
            $chunkIndex
        ]);

        $chunkId = $this->db->lastInsertId();
        
        // Generar embedding para el chunk
        $this->generateEmbeddingForChunk($chunkId, $content);

        return [
            'id' => $chunkId,
            'content' => $content,
            'word_count' => str_word_count($content)
        ];
    }
    
    /**
     * Generar embedding para un chunk
     */
    private function generateEmbeddingForChunk($chunkId, $content) {
        try {
            // Crear embedding usando técnicas básicas (TF-IDF simulado)
            $embedding = $this->createSimpleEmbedding($content);
            $keywords = $this->extractKeywords($content);
            
            // Usar estructura real de la tabla: content_id (INT), content_type, embedding_summary, keywords
            $sql = "
                INSERT INTO simple_embeddings 
                (content_id, content_type, embedding_summary, keywords) 
                VALUES (?, 'chunk', ?, ?)
                ON DUPLICATE KEY UPDATE
                embedding_summary = VALUES(embedding_summary),
                keywords = VALUES(keywords)
            ";
            
            // Crear resumen del contenido (máximo 500 chars para TEXT)
            $summary = substr($content, 0, 497) . '...';
            $keywordsStr = implode(', ', array_slice($keywords, 0, 20)); // VARCHAR(500)
            
            $this->db->query($sql, [
                $chunkId,
                $summary,
                $keywordsStr
            ]);
            
        } catch (Exception $e) {
            error_log("Error generando embedding: " . $e->getMessage());
        }
    }
    
    /**
     * Crear embedding simple basado en palabras clave
     */
    private function createSimpleEmbedding($text) {
        // Normalizar texto
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        // Palabras vacías en español
        $stopWords = [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 
            'da', 'su', 'por', 'son', 'con', 'para', 'al', 'una', 'del', 'los', 'las', 'como',
            'pero', 'sus', 'me', 'ya', 'muy', 'mi', 'sin', 'sobre', 'este', 'todo', 'también',
            'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'
        ];
        
        $words = explode(' ', $text);
        $filteredWords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        // Calcular frecuencias
        $wordFreq = array_count_values($filteredWords);
        arsort($wordFreq);
        
        return array_slice($wordFreq, 0, 20); // Top 20 palabras
    }
    
    /**
     * Extraer palabras clave del contenido
     */
    private function extractKeywords($text) {
        $embedding = $this->createSimpleEmbedding($text);
        
        // Categorizar palabras clave por relevancia
        $keywords = [];
        $weight = 1.0;
        
        foreach ($embedding as $word => $freq) {
            if ($freq > 1) {
                $keywords[] = $word;
            }
            $weight -= 0.05;
        }
        
        return array_slice($keywords, 0, 15); // Top 15 keywords
    }
    
    /**
     * Regenerar embeddings para documentos existentes
     */
    public function regenerateAllEmbeddings() {
        try {
            // Limpiar embeddings existentes de documentos y chunks
            $this->db->query("DELETE FROM simple_embeddings WHERE content_type IN ('document', 'chunk')");
            
            // Regenerar para documentos
            $documents = $this->db->fetchAll("SELECT id, title, content FROM reference_documents WHERE is_active = 1");
            foreach ($documents as $doc) {
                $this->generateEmbeddingForDocument($doc['id'], $doc['content'], $doc['title']);
            }
            
            // Regenerar para chunks
            $chunks = $this->db->fetchAll("SELECT id, chunk_text FROM document_chunks");
            foreach ($chunks as $chunk) {
                $this->generateEmbeddingForChunk($chunk['id'], $chunk['chunk_text']);
            }
            
            return [
                'success' => true,
                'documents' => count($documents),
                'chunks' => count($chunks)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de embeddings
     */
    public function getEmbeddingStats() {
        $stats = [
            'total_embeddings' => 0,
            'document_embeddings' => 0,
            'chunk_embeddings' => 0,
            'recent_activity' => []
        ];
        
        // Contar embeddings por tipo usando la estructura real
        $results = $this->db->fetchAll("
            SELECT content_type, COUNT(*) as count 
            FROM simple_embeddings 
            GROUP BY content_type
        ");
        
        foreach ($results as $result) {
            $stats['total_embeddings'] += $result['count'];
            $stats[$result['content_type'] . '_embeddings'] = $result['count'];
        }
        
        // Actividad reciente usando estructura real
        $stats['recent_activity'] = $this->db->fetchAll("
            SELECT se.content_type, se.content_id, se.created_at,
                   CASE 
                       WHEN se.content_type = 'document' THEN rd.title
                       WHEN se.content_type = 'chunk' THEN CONCAT('Chunk #', se.content_id)
                       ELSE CONCAT(se.content_type, ' #', se.content_id)
                   END as title
            FROM simple_embeddings se
            LEFT JOIN reference_documents rd ON se.content_type = 'document' AND se.content_id = rd.id
            ORDER BY se.created_at DESC
            LIMIT 10
        ");
        
        return $stats;
    }    /**
     * Obtener todos los documentos
     */
    public function getAllDocuments() {
        $sql = "
            SELECT d.*, 
                   COUNT(c.id) as chunk_count
            FROM reference_documents d
            LEFT JOIN document_chunks c ON d.id = c.document_id
            WHERE d.is_active = 1
            GROUP BY d.id
            ORDER BY d.upload_date DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Eliminar documento
     */
    public function deleteDocument($documentId) {
        try {
            // Eliminar chunks
            $this->db->query("DELETE FROM document_chunks WHERE document_id = ?", [$documentId]);
            
            // Eliminar documento
            $this->db->query("DELETE FROM reference_documents WHERE id = ?", [$documentId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error eliminando documento: " . $e->getMessage());
            return false;
        }
    }
}

// Inicializar procesador
$processor = new DocumentProcessor();

// Procesar acciones
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_document':
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                $result = $processor->processUpload($_FILES['document'], $title, $description);
                
                if ($result['success']) {
                    $message = "Documento procesado exitosamente. {$result['chunks_created']} chunks creados.";
                    $messageType = 'success';
                } else {
                    $message = "Error: " . $result['error'];
                    $messageType = 'error';
                }
            } else {
                $message = 'Error: No se recibió ningún archivo válido.';
                $messageType = 'error';
            }
            break;
            
        case 'delete_document':
            $documentId = intval($_POST['document_id'] ?? 0);
            if ($processor->deleteDocument($documentId)) {
                $message = 'Documento eliminado exitosamente.';
                $messageType = 'success';
            } else {
                $message = 'Error al eliminar el documento.';
                $messageType = 'error';
            }
            break;
            
        case 'regenerate_embeddings':
            $result = $processor->regenerateAllEmbeddings();
            if ($result['success']) {
                $message = "Embeddings regenerados exitosamente. {$result['documents']} documentos y {$result['chunks']} chunks procesados.";
                $messageType = 'success';
            } else {
                $message = "Error regenerando embeddings: " . $result['error'];
                $messageType = 'error';
            }
            break;
    }
}

// Obtener documentos
$documents = $processor->getAllDocuments();

// Obtener estadísticas de embeddings
$embeddingStats = $processor->getEmbeddingStats();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📁 Gestión de Documentos - Admin Panel</title>
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
        
        .upload-section {
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .upload-section:hover { border-color: #667eea; }
        .upload-section.drag-over { border-color: #667eea; background: #f0f4ff; }
        
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea { min-height: 80px; resize: vertical; }
        
        .file-input { display: none; }
        .file-label {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .file-label:hover { background: #5a6fd8; }
        
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .document-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .document-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        .document-title { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 10px; }
        .document-meta { font-size: 0.9rem; color: #666; margin-bottom: 15px; }
        
        .document-stats {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .document-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            max-height: 120px;
            overflow-y: auto;
            font-size: 0.9rem;
        }
        
        .document-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-number { font-size: 2rem; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .stat-label { color: #666; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .documents-grid { grid-template-columns: 1fr; }
            .document-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📁 Gestión de Documentos RAG</h1>
            <div class="breadcrumb">
                <a href="../dashboard.php">📊 Dashboard Principal</a> / 
                <a href="dashboard.php">🎛️ Dashboard RAG</a> / 
                Documentos
            </div>
        </div>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-summary">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($documents); ?></div>
                <div class="stat-label">📄 Documentos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo array_sum(array_column($documents, 'chunk_count')); ?></div>
                <div class="stat-label">🧩 Chunks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round(array_sum(array_column($documents, 'file_size')) / 1024 / 1024, 1); ?>MB</div>
                <div class="stat-label">💾 Almacenamiento</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $embeddingStats['total_embeddings']; ?></div>
                <div class="stat-label">🧠 Embeddings</div>
            </div>
        </div>
        
        <!-- Herramientas Rápidas -->
        <div class="upload-section" style="margin-bottom: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3 style="margin-bottom: 15px; color: #856404;">🛠️ Herramientas de Documentos</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="update-document-text.php" class="btn" style="background: #28a745; color: white;">
                    ✏️ Actualizar Texto Manualmente
                </a>
                <a href="reprocess-pdfs.php" class="btn" style="background: #17a2b8; color: white;">
                    🔄 Reprocesar PDFs
                </a>
                <button onclick="window.open('../../../check_pdfs.php', '_blank')" class="btn" style="background: #6c757d; color: white;">
                    🔍 Diagnóstico de PDFs
                </button>
            </div>
            <p style="color: #856404; margin-top: 10px; font-size: 0.9em;">
                💡 Usa estas herramientas si un PDF no se pudo extraer automáticamente
            </p>
        </div>
        
        <!-- Sección de Embeddings -->
        <div class="upload-section" style="margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px; color: #333;">🧠 Gestión de Embeddings</h2>
            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <div>
                    <p style="color: #666; margin-bottom: 5px;">
                        📊 <strong><?php echo $embeddingStats['document_embeddings']; ?></strong> embeddings de documentos
                    </p>
                    <p style="color: #666;">
                        🧩 <strong><?php echo $embeddingStats['chunk_embeddings']; ?></strong> embeddings de chunks
                    </p>
                </div>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="regenerate_embeddings">
                    <button type="submit" class="btn btn-warning" 
                            onclick="return confirm('¿Regenerar todos los embeddings? Esto puede tardar unos minutos.')">
                        🔄 Regenerar Embeddings
                    </button>
                </form>
            </div>
            <?php if (!empty($embeddingStats['recent_activity'])): ?>
                <div style="margin-top: 15px;">
                    <p style="color: #666; margin-bottom: 10px;"><strong>📈 Actividad Reciente:</strong></p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php foreach (array_slice($embeddingStats['recent_activity'], 0, 5) as $activity): ?>
                            <span style="background: #e3f2fd; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                                <?php echo htmlspecialchars($activity['title'] ?? 'Sin título'); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sección de subida -->
        <div class="upload-section" id="upload-section">
            <h2 style="margin-bottom: 20px; color: #333;">📤 Subir Nuevo Documento</h2>
            <p style="margin-bottom: 30px; color: #666;">Soporta: PDF, TXT, DOC, DOCX (máximo 10MB)</p>
            
            <form method="POST" enctype="multipart/form-data" id="upload-form">
                <input type="hidden" name="action" value="upload_document">
                
                <label for="document" class="file-label">📁 Seleccionar Archivo</label>
                <input type="file" id="document" name="document" class="file-input" 
                       accept=".pdf,.txt,.doc,.docx" required>
                
                <div id="file-info" style="display: none; margin-bottom: 20px; color: #333;">
                    <strong>Archivo seleccionado:</strong> <span id="file-name"></span>
                </div>
                
                <div class="form-group">
                    <label for="title">Título del Documento</label>
                    <input type="text" id="title" name="title" 
                           placeholder="Ej: Manual de Usuario, CV Juan Carlos...">
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción (opcional)</label>
                    <textarea id="description" name="description" 
                              placeholder="Breve descripción del contenido..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">🚀 Procesar Documento</button>
            </form>
        </div>
        
        <!-- Lista de documentos -->
        <div class="documents-grid">
            <?php foreach ($documents as $doc): ?>
                <div class="document-card">
                    <div class="document-title"><?php echo htmlspecialchars($doc['title'] ?? 'Sin título'); ?></div>
                    <div class="document-meta">
                        📅 <?php echo date('d/m/Y H:i', strtotime($doc['upload_date'])); ?><br>
                        � <?php echo htmlspecialchars($doc['file_type'] ?? 'Texto'); ?><br>
                        💾 <?php echo round(($doc['file_size'] ?? 0) / 1024, 1); ?> KB
                    </div>
                    
                    <?php if (!empty($doc['description'])): ?>
                        <div style="margin-bottom: 15px; color: #666; font-style: italic;">
                            <?php echo htmlspecialchars($doc['description'] ?? ''); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="document-stats">
                        <span>🧩 <?php echo $doc['chunk_count']; ?> chunks</span>
                        <span>📝 <?php echo str_word_count($doc['content'] ?? ''); ?> palabras</span>
                    </div>
                    
                    <div class="document-content">
                        <?php echo nl2br(htmlspecialchars(substr($doc['content'] ?? '', 0, 200))); ?>
                        <?php if (strlen($doc['content'] ?? '') > 200): ?>
                            <span style="color: #666;">...</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="document-actions">
                        <button class="btn btn-small" onclick="viewDocument(<?php echo $doc['id']; ?>)">
                            👁️ Ver
                        </button>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('¿Eliminar este documento?')">
                            <input type="hidden" name="action" value="delete_document">
                            <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-small">🗑️ Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($documents)): ?>
            <div style="text-align: center; padding: 60px; color: #666;">
                <h3>📭 No hay documentos cargados</h3>
                <p>Sube tu primer documento para alimentar el sistema RAG</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal para ver documento completo -->
    <div id="viewModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); overflow: auto;">
        <div style="background-color: #fefefe; margin: 2% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 1000px; max-height: 90vh; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;">
                <h2 style="margin: 0; font-size: 1.5em;" id="modalTitle">📄 Documento</h2>
                <span onclick="closeViewModal()" style="color: white; font-size: 30px; font-weight: bold; cursor: pointer; padding: 5px 10px; border-radius: 50%; transition: background 0.3s; line-height: 1;">&times;</span>
            </div>
            <div style="padding: 30px; max-height: 70vh; overflow-y: auto;">
                <div id="modalMetadata" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;"></div>
                <div id="modalContent" style="line-height: 1.8; color: #333; white-space: pre-wrap; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;"></div>
            </div>
            <div style="background: #f8f9fa; padding: 20px; text-align: right; border-radius: 0 0 12px 12px;">
                <button onclick="closeViewModal()" class="btn btn-secondary">Cerrar</button>
            </div>
        </div>
    </div>
    
    <script>
        // Datos de documentos en JSON para acceso desde JavaScript
        const documentsData = <?php echo json_encode($documents); ?>;
        
        // Manejo de archivos
        const fileInput = document.getElementById('document');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                fileInfo.style.display = 'block';
                
                // Auto-rellenar título si está vacío
                if (!document.getElementById('title').value) {
                    const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");
                    document.getElementById('title').value = nameWithoutExt;
                }
            }
        });
        
        function viewDocument(documentId) {
            // Buscar el documento en los datos
            const doc = documentsData.find(d => d.id == documentId);
            
            if (!doc) {
                alert('Documento no encontrado');
                return;
            }
            
            // Actualizar título del modal
            document.getElementById('modalTitle').textContent = '📄 ' + doc.title;
            
            // Actualizar metadata
            const uploadDate = new Date(doc.upload_date);
            const formattedDate = uploadDate.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const wordCount = doc.content ? doc.content.split(/\s+/).filter(w => w.length > 0).length : 0;
            const charCount = doc.content ? doc.content.length : 0;
            
            document.getElementById('modalMetadata').innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong>📅 Fecha de carga:</strong><br>
                        ${formattedDate}
                    </div>
                    <div>
                        <strong>📎 Archivo:</strong><br>
                        ${doc.file_name || 'N/A'}
                    </div>
                    <div>
                        <strong>📄 Tipo:</strong><br>
                        ${doc.file_type || 'Texto'}
                    </div>
                    <div>
                        <strong>💾 Tamaño:</strong><br>
                        ${(doc.file_size / 1024).toFixed(1)} KB
                    </div>
                    <div>
                        <strong>🧩 Chunks:</strong><br>
                        ${doc.chunk_count || 0}
                    </div>
                    <div>
                        <strong>📊 Estadísticas:</strong><br>
                        ${wordCount.toLocaleString()} palabras, ${charCount.toLocaleString()} caracteres
                    </div>
                </div>
                ${doc.description ? `
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                        <strong>📝 Descripción:</strong><br>
                        <em style="color: #666;">${doc.description}</em>
                    </div>
                ` : ''}
            `;
            
            // Actualizar contenido
            document.getElementById('modalContent').textContent = doc.content || 'Sin contenido disponible';
            
            // Mostrar modal
            document.getElementById('viewModal').style.display = 'block';
            
            // Scroll al inicio del contenido
            document.getElementById('modalContent').scrollTop = 0;
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeViewModal();
            }
        }
        
        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeViewModal();
            }
        });
    </script>
</body>
</html>