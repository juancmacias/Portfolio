<?php
/**
 * RSS Feed Generator para Artículos
 * Genera un feed RSS 2.0 válido con todos los artículos publicados
 * URL: https://www.juancarlosmacias.es/rss.php
 */

// Headers para XML RSS
header('Content-Type: application/rss+xml; charset=utf-8');

// Configuración
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
        || strpos($_SERVER['HTTP_HOST'] ?? '', 'frontend.pru') !== false;

if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // No mostrar errores en XML
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Función para obtener la URL base
function getBaseUrl() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false || strpos($host, 'frontend.pru') !== false) {
        return 'http://' . $host;
    }
    return 'https://www.juancarlosmacias.es';
}

// Función para localizar database.php
function findDatabasePhp(): ?string {
    // Detectar document root desde $_SERVER
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    
    $candidates = [
        // Rutas relativas desde rss.php (root)
        __DIR__ . '/admin/config/database.php',
        __DIR__ . '/../admin/config/database.php',
        dirname(__DIR__) . '/admin/config/database.php',
        
        // Rutas desde document root detectado
        $docRoot . '/admin/config/database.php',
        $docRoot . '/Portfolio/admin/config/database.php',
        $docRoot . '/../admin/config/database.php',
        
        // Rutas absolutas comunes Linux
        '/var/www/html/admin/config/database.php',
        '/var/www/Portfolio/admin/config/database.php',
        '/home/*/public_html/admin/config/database.php',
        
        // Rutas absolutas comunes Windows (desarrollo local)
        'E:/wwwserver/N_JCMS/Portfolio/admin/config/database.php',
        'C:/wwwserver/Portfolio/admin/config/database.php',
    ];
    
    foreach ($candidates as $path) {
        // Expandir comodines si existen
        if (strpos($path, '*') !== false) {
            $matches = glob($path);
            if (!empty($matches) && file_exists($matches[0])) {
                return $matches[0];
            }
        } elseif (file_exists($path)) {
            return $path;
        }
    }
    
    // Log de debug
    error_log('RSS findDatabasePhp() - Document Root: ' . $docRoot);
    error_log('RSS findDatabasePhp() - __DIR__: ' . __DIR__);
    
    return null;
}

// Función para escapar texto XML
function xmlEscape($text) {
    return htmlspecialchars($text ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// Función para generar CDATA (para contenido HTML)
function xmlCData($text) {
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $text ?? '') . ']]>';
}

try {
    $baseUrl = getBaseUrl();
    $currentDate = date(DATE_RFC2822);
    
    // Conectar a la base de datos
    $dbPath = findDatabasePhp();
    if (!$dbPath) {
        throw new Exception('No se encontró database.php');
    }
    
    if (!defined('ADMIN_ACCESS')) {
        define('ADMIN_ACCESS', true);
    }
    
    require_once $dbPath;
    $db = Database::getInstance();
    
    // Obtener los últimos 50 artículos publicados
    $articles = $db->fetchAll(
        "SELECT id, title, slug, excerpt, content, created_at, updated_at, published_at, 
                featured_image, tags, reading_time, views_count, author
         FROM articles 
         WHERE status = 'published' 
         ORDER BY COALESCE(published_at, created_at) DESC 
         LIMIT 50"
    );
    
    // Comenzar XML RSS 2.0
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    ?>
<rss version="2.0" 
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>Juan Carlos Macías - Blog de IA y Desarrollo Full Stack</title>
    <link><?php echo xmlEscape($baseUrl); ?></link>
    <description>Artículos sobre Inteligencia Artificial, desarrollo Full Stack, Python, React, PHP y tecnologías emergentes por Juan Carlos Macías.</description>
    <language>es</language>
    <lastBuildDate><?php echo $currentDate; ?></lastBuildDate>
    <pubDate><?php echo $currentDate; ?></pubDate>
    <ttl>60</ttl>
    <atom:link href="<?php echo xmlEscape($baseUrl . '/rss.php'); ?>" rel="self" type="application/rss+xml" />
    <image>
        <url><?php echo xmlEscape($baseUrl . '/Assets/avatar.png'); ?></url>
        <title>Juan Carlos Macías</title>
        <link><?php echo xmlEscape($baseUrl); ?></link>
    </image>
    <copyright>Copyright <?php echo date('Y'); ?> Juan Carlos Macías. Todos los derechos reservados.</copyright>
    <managingEditor>info@juancarlosmacias.es (Juan Carlos Macías)</managingEditor>
    <webMaster>info@juancarlosmacias.es (Juan Carlos Macías)</webMaster>
    
<?php
    // Generar items por cada artículo
    foreach ($articles as $article) {
        $title = $article['title'] ?? 'Sin título';
        $slug = $article['slug'] ?? '';
        $link = $baseUrl . '/article/' . $slug;
        $description = $article['excerpt'] ?? substr(strip_tags($article['content'] ?? ''), 0, 300);
        $content = $article['content'] ?? '';
        $pubDate = !empty($article['published_at']) ? $article['published_at'] : $article['created_at'];
        $pubDateRFC = date(DATE_RFC2822, strtotime($pubDate));
        $author = $article['author'] ?? 'Juan Carlos Macías';
        $image = $article['featured_image'] ?? $baseUrl . '/Assets/avatar.png';
        $tags = !empty($article['tags']) ? json_decode($article['tags'], true) : [];
        
        // GUID único para el artículo
        $guid = $baseUrl . '/article/' . $slug;
        
        echo "    <item>\n";
        echo "        <title>" . xmlCData($title) . "</title>\n";
        echo "        <link>" . xmlEscape($link) . "</link>\n";
        echo "        <guid isPermaLink=\"true\">" . xmlEscape($guid) . "</guid>\n";
        echo "        <description>" . xmlCData($description) . "</description>\n";
        echo "        <content:encoded>" . xmlCData($content) . "</content:encoded>\n";
        echo "        <pubDate>" . xmlEscape($pubDateRFC) . "</pubDate>\n";
        echo "        <dc:creator>" . xmlCData($author) . "</dc:creator>\n";
        
        // Agregar imagen destacada si existe
        if (!empty($image)) {
            echo "        <enclosure url=\"" . xmlEscape($image) . "\" type=\"image/jpeg\" />\n";
        }
        
        // Agregar categorías (tags)
        if (is_array($tags) && !empty($tags)) {
            foreach ($tags as $tag) {
                echo "        <category>" . xmlCData($tag) . "</category>\n";
            }
        }
        
        echo "    </item>\n";
    }
?>
</channel>
</rss>
<?php

} catch (Exception $e) {
    // Log del error
    error_log('RSS Feed Error: ' . $e->getMessage());
    
    // Generar RSS vacío válido en caso de error
    $baseUrl = getBaseUrl();
    $currentDate = date(DATE_RFC2822);
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>Juan Carlos Macías - Blog</title>
    <link><?php echo xmlEscape($baseUrl); ?></link>
    <description>Blog de tecnología y desarrollo</description>
    <language>es</language>
    <lastBuildDate><?php echo $currentDate; ?></lastBuildDate>
    <atom:link href="<?php echo xmlEscape($baseUrl . '/rss.php'); ?>" rel="self" type="application/rss+xml" />
</channel>
</rss>
<?php
}
