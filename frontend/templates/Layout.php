<?php
/**
 * Layout principal para SSR PHP + React Hydration
 * Este template genera el HTML shell que React hidratará después
 */

function renderLayout($content, $initialState = [], $cssFiles = [], $jsFiles = []) {
    // Escapar state para JSON seguro
    $stateJson = json_encode($initialState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    
    // Meta tags desde initialState
    $title = htmlspecialchars($initialState['title'] ?? 'Portfolio', ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($initialState['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $ogImage = htmlspecialchars($initialState['ogImage'] ?? '/Assets/avatar.png', ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars($initialState['url'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Detectar si es un artículo para cambiar el tipo Open Graph
    $isArticle = isset($initialState['article']) && !empty($initialState['article']);
    $ogType = $isArticle ? 'article' : 'website';
    $article = $initialState['article'] ?? null;
    
    // Archivos CSS por defecto
    $defaultCss = [
        '/static/css/main.css'
    ];
    $allCss = array_merge($defaultCss, $cssFiles);
    
    // Solo cargar main.js — los chunks los gestiona webpack internamente.
    // NO pre-cargar chunks como <script> tags: se ejecutarían dos veces
    // y romperían el sistema de módulos de webpack.
    $defaultJs = ['/static/js/main.js'];
    $allJs = array_merge($defaultJs, $jsFiles);
    
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2542111598458706"
         crossorigin="anonymous"></script>
    
    <!-- SEO Meta Tags -->
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo $description; ?>">
    <meta name="author" content="Juan Carlos Macías">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo $ogType; ?>">
    <meta property="og:url" content="<?php echo $url; ?>">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $description; ?>">
    <meta property="og:image" content="<?php echo $ogImage; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Juan Carlos Macías - Portfolio">
    <meta property="og:locale" content="es_ES">
    
    <?php if ($isArticle): ?>
    <!-- Article-specific Open Graph tags -->
    <?php if (!empty($article['published_at'])): ?>
    <meta property="article:published_time" content="<?php echo date('c', strtotime($article['published_at'])); ?>">
    <?php elseif (!empty($article['created_at'])): ?>
    <meta property="article:published_time" content="<?php echo date('c', strtotime($article['created_at'])); ?>">
    <?php endif; ?>
    <?php if (!empty($article['updated_at'])): ?>
    <meta property="article:modified_time" content="<?php echo date('c', strtotime($article['updated_at'])); ?>">
    <?php endif; ?>
    <meta property="article:author" content="Juan Carlos Macías">
    <?php 
    // Tags del artículo como categorías Open Graph
    if (!empty($article['tags'])) {
        $tags = is_string($article['tags']) ? json_decode($article['tags'], true) : $article['tags'];
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                echo '    <meta property="article:tag" content="' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }
    }
    ?>
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $url; ?>">
    <meta property="twitter:title" content="<?php echo $title; ?>">
    <meta property="twitter:description" content="<?php echo $description; ?>">
    <meta property="twitter:image" content="<?php echo $ogImage; ?>">
    
    <!-- Canonical URL - Solo si hay URL válida para evitar duplicados -->
    <?php if (!empty($url)): ?>
    <link rel="canonical" href="<?php echo $url; ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="/Assets/favicon.ico" type="image/x-icon">
    
    <!-- RSS Feed -->
    <link rel="alternate" type="application/rss+xml" title="Juan Carlos Macías - RSS Feed" href="/rss.php">
    
    <!-- Preconnect para optimización - establece conexiones DNS/TLS antes de descargar recursos -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <?php if (isset($initialState['route']) && $initialState['route'] === '/'): ?>
    <!-- Preload LCP image solo para home -->
    <link rel="preload" as="image" href="/Assets/b1.png" fetchpriority="high">
    <?php endif; ?>
    
    <!-- CSS -->
    <?php foreach ($allCss as $css): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
    
    <!-- Bootstrap CSS (si no está en bundle) -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" 
        rel="stylesheet" 
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" 
        crossorigin="anonymous"
    >
    
    <!-- Font Awesome - carga asíncrona para no bloquear render inicial -->
    <link 
        rel="preload" 
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" 
        as="style" 
        onload="this.onload=null;this.rel='stylesheet'"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" 
        crossorigin="anonymous" 
        referrerpolicy="no-referrer"
    >
    <!-- Fallback para navegadores sin JS -->
    <noscript>
        <link 
            rel="stylesheet" 
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" 
            integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" 
            crossorigin="anonymous" 
            referrerpolicy="no-referrer"
        >
    </noscript>
    
    <!-- Initial State para React Hydration -->
    <script id="__INITIAL_STATE__" type="application/json">
<?php echo $stateJson; ?>
    </script>
    
    <!-- Inicialización de hidratación - DEBE ejecutarse ANTES que React -->
    <script>
        // Hacer disponible el state inicial globalmente para React
        (function() {
            try {
                const initialStateElement = document.getElementById('__INITIAL_STATE__');
                if (initialStateElement) {
                    window.__INITIAL_STATE__ = JSON.parse(initialStateElement.textContent);
                    console.log('✅ Initial state cargado para hidratación:', window.__INITIAL_STATE__.route);
                } else {
                    console.warn('⚠️ No se encontró __INITIAL_STATE__');
                    window.__INITIAL_STATE__ = {};
                }
            } catch (error) {
                console.error('❌ Error parseando initial state:', error);
                window.__INITIAL_STATE__ = {};
            }
        })();
    </script>
</head>
<body>
    <!-- Contenido prerenderizado por PHP que React hidratará -->
    <div id="root"><?php echo $content; ?></div>
    
    <!-- React Scripts (defer para garantizar que el DOM esté listo) -->
    <?php foreach ($allJs as $js): ?>
    <script defer src="<?php echo htmlspecialchars($js, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
    
    <!-- Bootstrap Bundle - defer para no bloquear render inicial -->
    <script 
        defer
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" 
        crossorigin="anonymous"
    ></script>
</body>
</html>
<?php
    return ob_get_clean();
}
