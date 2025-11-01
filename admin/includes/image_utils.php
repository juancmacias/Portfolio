<?php
/**
 * Funciones para transformar rutas de imágenes según el contexto
 */

/**
 * Transformar ruta de imagen según el contexto
 * 
 * @param string $originalPath Ruta original del JSON (ej: "../../Assets/Projects/imagen.png")
 * @param string $context Contexto: 'admin', 'api', 'frontend'
 * @return string Ruta transformada
 */
function transformImagePath($originalPath, $context = 'frontend') {
    if (empty($originalPath)) {
        return null;
    }
    
    // Extraer nombre del archivo
    $fileName = basename($originalPath);
    
    switch ($context) {
        case 'admin':
            // Para el admin local - ruta absoluta del servidor
            return "/N_JCMS/Portfolio/frontend/public/Assets/Projects/" . $fileName;
            
        case 'api':
            // Para la API - ruta relativa desde la raíz del frontend
            return "/Assets/Projects/" . $fileName;
            
        case 'frontend':
        case 'react':
            // Para React - ruta relativa desde public
            return "./Assets/Projects/" . $fileName;
            
        case 'production':
            // Para producción - URL completa
            return "/Assets/Projects/" . $fileName;
            
        default:
            return $originalPath;
    }
}

/**
 * Detectar automáticamente el contexto según la URL actual
 */
function detectContext() {
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    
    if (strpos($currentPath, '/admin/') !== false) {
        return 'admin';
    } elseif (strpos($currentPath, '/api/') !== false) {
        return 'api';
    } else {
        return 'frontend';
    }
}

/**
 * Transformar automáticamente según el contexto actual
 */
function autoTransformImagePath($originalPath) {
    $context = detectContext();
    return transformImagePath($originalPath, $context);
}

/**
 * Procesar array de proyectos transformando las imágenes
 */
function processProjectsImages($projects, $context = null) {
    if ($context === null) {
        $context = detectContext();
    }
    
    return array_map(function($project) use ($context) {
        if (isset($project['image_path'])) {
            $project['image_url'] = transformImagePath($project['image_path'], $context);
        }
        return $project;
    }, $projects);
}

// Test de las funciones
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "<h2>🧪 Test de Transformación de Rutas</h2>";
    
    $testPath = "../../Assets/Projects/portfolio.png";
    
    echo "<h3>Ruta original:</h3>";
    echo "<code>" . htmlspecialchars($testPath) . "</code><br><br>";
    
    echo "<h3>Transformaciones por contexto:</h3>";
    $contexts = ['admin', 'api', 'frontend', 'production'];
    
    foreach ($contexts as $context) {
        $transformed = transformImagePath($testPath, $context);
        echo "<strong>$context:</strong> <code>" . htmlspecialchars($transformed) . "</code><br>";
    }
    
    echo "<hr>";
    echo "<h3>🔍 Contexto actual detectado:</h3>";
    $currentContext = detectContext();
    echo "<strong>Contexto:</strong> $currentContext<br>";
    echo "<strong>URL actual:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . "<br>";
    
    $autoTransformed = autoTransformImagePath($testPath);
    echo "<strong>Transformación automática:</strong> <code>" . htmlspecialchars($autoTransformed) . "</code><br>";
}
?>