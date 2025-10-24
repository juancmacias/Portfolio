<?php
// Asegurar que no hay salida antes de los headers
ob_start();

// Headers CORS y Content-Type
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Limpiar cualquier salida previa
ob_clean();

try {
    // Verificar si el archivo existe
    $jsonFile = __DIR__ . '/datos_proyectos.json';
    
    if (!file_exists($jsonFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Archivo no encontrado', 'file' => $jsonFile]);
        exit;
    }
    
    // Leer el archivo JSON
    $inp = file_get_contents($jsonFile);
    
    if ($inp === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al leer el archivo']);
        exit;
    }
    
    // Verificar que es JSON válido
    $decoded = json_decode($inp);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['error' => 'JSON inválido', 'json_error' => json_last_error_msg()]);
        exit;
    }
    
    // Devolver el JSON
    echo $inp;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor', 'message' => $e->getMessage()]);
}
