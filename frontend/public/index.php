<?php
/**
 * Dynamic Rendering Entry Point
 * - Usuarios reales → sirve index.html (React SPA sin SSR)
 * - Bots / crawlers → PHP genera HTML optimizado para SEO
 *
 * Rutas manejadas (deben coincidir con App.js):
 *   /                 → Home
 *   /about            → About
 *   /project          → Projects
 *   /resume           → Resume
 *   /articles         → ArticlesPage
 *   /article/:slug    → ArticleView (con datos de DB)
 *   /politics         → Política de privacidad
 *   /terminos         → Términos de uso
 *   /contacto         → Contacto
 *   *                 → Fallback a index.html (React SPA)
 */

// Evitar ejecución cuando Apache sirve un asset estático
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|map|json|pdf|webp)$/i', $uri)) {
    return false;
}

// ──────────────────────────────────────────────
// Configuración de errores
// ──────────────────────────────────────────────
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
        || strpos($_SERVER['HTTP_HOST'] ?? '', 'frontend.pru') !== false;

if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ──────────────────────────────────────────────
// Dynamic Rendering: usuarios reales → index.html, bots → PHP SSR
// ──────────────────────────────────────────────

function isBot(): bool {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (empty($ua)) return false;
    $bots = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'sogou', 'exabot', 'facebot', 'facebookexternalhit',
        'twitterbot', 'linkedinbot', 'whatsapp', 'telegrambot', 'slackbot',
        'discordbot', 'applebot', 'semrushbot', 'ahrefsbot', 'mj12bot',
        'dotbot', 'rogerbot', 'screaming frog', 'sitebulb',
        'ia_archiver', 'archive.org_bot', 'ccbot',
        'chrome-lighthouse', 'gtmetrix', 'pingdom', 'uptimerobot',
        'spider', 'crawler', 'bot/',
    ];
    foreach ($bots as $bot) {
        if (strpos($ua, $bot) !== false) return true;
    }
    return false;
}

if (!isBot()) {
    // Usuario real → servir React SPA directamente
    $indexHtml = __DIR__ . '/index.html';
    if (file_exists($indexHtml)) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Rendered-By: React-SPA');
        readfile($indexHtml);
        exit;
    }
    // index.html no existe aún (sin build) → continuar con PHP como fallback
}

// ──────────────────────────────────────────────
// Solo bots llegan aquí → Cargar templates SSR
// ──────────────────────────────────────────────
$templateDir = __DIR__ . '/templates';
require_once $templateDir . '/Layout.php';
require_once $templateDir . '/ArticleView.php';

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function getBaseUrl() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false || strpos($host, 'frontend.pru') !== false) {
        return 'http://' . $host;
    }
    return 'https://www.juancarlosmacias.es';
}

function getRoute() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Eliminar query string
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }
    // Eliminar trailing slash (excepto raíz)
    if ($uri !== '/' && substr($uri, -1) === '/') {
        $uri = rtrim($uri, '/');
    }
    return $uri;
}

// ──────────────────────────────────────────────
// Renderizadores de sección
// ──────────────────────────────────────────────

function renderHome() {
    $baseUrl = getBaseUrl();
    $initialState = [
        'route'       => '/',
        'title'       => 'Juan Carlos Macías | Ingeniero Full Stack de IA Generativa',
        'description' => 'Portfolio de Juan Carlos Macías - Desarrollador Full Stack especializado en Inteligencia Artificial.',
        'url'         => $baseUrl . '/',
        'ogImage'     => $baseUrl . '/Assets/avatar.png',
        'isSSR'       => true,
    ];

    $content = <<<HTML
<div class="home-section">
    <div class="container">
        <div class="row py-5">
            <div class="col-md-12 text-center">
                <img
                    src="/Assets/b1.png"
                    alt="Juan Carlos Macías"
                    aria-label="Desarrollador Full Stack IA"
                    class="img-fluid mb-4"
                    fetchpriority="high"
                    decoding="async"
                    width="450" height="450"
                    style="max-height:450px; border-radius:120px;"
                >
                <h1 class="heading">Juan Carlos Macías Salvador</h1>
                <h2 class="heading-subtitle">Ingeniero Full Stack de IA Generativa</h2>
                <p class="lead mt-4">
                    Desarrollador Full Stack especializado en Inteligencia Artificial,
                    con experiencia en React, PHP, Python y tecnologías de IA generativa.
                </p>
            </div>
        </div>
    </div>
</div>
HTML;

    return renderLayout($content, $initialState);
}

function renderArticle($slug) {
    $baseUrl = getBaseUrl();

    try {
        $dbPath = findDatabasePhp();
        if (!$dbPath) {
            throw new Exception('No se encontró database.php en ninguna ruta conocida.');
        }

        require_once $dbPath;
        $db = Database::getInstance();

        $article = $db->fetchOne(
            "SELECT * FROM articles WHERE slug = ? AND status = 'published'",
            [$slug]
        );

        if (!$article) {
            return serveSpaFallback();
        }

        $initialState = [
            'route'       => '/article/' . $slug,
            'title'       => $article['title'] . ' | Juan Carlos Macías',
            'description' => $article['excerpt'] ?? substr(strip_tags($article['content'] ?? ''), 0, 160),
            'url'         => $baseUrl . '/article/' . $slug,
            'ogImage'     => $article['featured_image'] ?? $baseUrl . '/Assets/avatar.png',
            'article'     => $article,
            'isSSR'       => true,
            'timestamp'   => date('c'),
        ];

        $content = renderArticleView($article);
        return renderLayout($content, $initialState);

    } catch (Exception $e) {
        error_log("SSR Error en artículo '$slug': " . $e->getMessage());
        return serveSpaFallback();
    }
}

/**
 * Página /about — contenido completo del perfil profesional.
 * Los bots indexan todo el texto; React hidrata encima.
 */
function renderAbout() {
    $baseUrl = getBaseUrl();
    $initialState = [
        'route'       => '/about',
        'title'       => 'Sobre mí | Juan Carlos Macías — Desarrollador Full Stack e IA',
        'description' => 'Desarrollador Full Stack y especialista en Inteligencia Artificial y MLOps con sede en Madrid. Experiencia en React, PHP, Python, machine learning y ciencia de datos.',
        'url'         => $baseUrl . '/about',
        'ogImage'     => $baseUrl . '/Assets/avatar.png',
        'isSSR'       => true,
    ];

    $content = <<<'HTML'
<article>
  <header>
    <h1>Juan Carlos Macías Salvador — Desarrollador Full Stack e Inteligencia Artificial - Comunidad de Madrid</h1>
  </header>
  <section id="about" class="about-section">
    <p>Soy <strong>Juan Carlos</strong>, <strong>desarrollador full stack y especialista en inteligencia artificial (IA) y MLOps</strong>
    con sede en Madrid, España. A lo largo de mi trayectoria he combinado mi experiencia en <strong>desarrollo web y móvil</strong>
    con una sólida formación en <strong>machine learning, automatización y ciencia de datos</strong>, participando en proyectos
    que unen tecnología, innovación y utilidad real.</p>

    <p>Mi enfoque se centra en crear soluciones que mejoren la vida de las personas, especialmente en sectores como la
    <strong>salud, la educación y la electrónica</strong>. He desarrollado múltiples aplicaciones y servicios, integrando
    <strong>modelos de IA, sistemas distribuidos y APIs inteligentes</strong> en entornos productivos. Mi experiencia abarca
    desde el backend con <strong>Python (FastAPI, Flask, Pandas, Scikit-learn)</strong> hasta el frontend con
    <strong>React.js, Next.js y JavaScript moderno</strong>, además del desarrollo nativo en <strong>Java para Android</strong>.</p>

    <p>En los últimos años he trabajado de forma <strong>freelance</strong> y en proyectos personales, desarrollando
    <strong>modelos de clasificación binaria y multiclase</strong> aplicados a la detección de phishing y análisis en salud.
    Entre mis proyectos más destacados se encuentra <strong>Konglu.es</strong>, una aplicación de seguimiento glucémico
    y deportivo personalizada, desarrollada junto a un equipo médico y docente. También he participado en iniciativas
    de innovación como <strong>We The Humans</strong> y la aceleradora <strong>La Nave</strong>, centradas en el
    desarrollo ético de la IA según la normativa europea.</p>

    <p>Cuento con una amplia base técnica en <strong>Python, Java, PHP, JavaScript y SQL</strong>, trabajando con
    frameworks como <strong>React, Node.js, FastAPI</strong> y entornos como <strong>Docker y GitHub</strong>.
    Tengo experiencia en bases de datos <strong>PostgreSQL, MySQL y MongoDB</strong>, además de conocimientos en
    <strong>DevOps, LLMs, web scraping, UX/UI y sistemas Linux</strong>.</p>

    <p>He completado recientemente un <strong>bootcamp intensivo en Inteligencia Artificial</strong> (Factoría F5, UE, 2025),
    junto a certificaciones en <strong>ciberseguridad, desarrollo web full stack y programación orientada a objetos</strong>.
    Este recorrido me ha permitido consolidar una visión integral del ciclo completo de desarrollo, desde el diseño y
    la arquitectura hasta la implementación, despliegue y mantenimiento de sistemas inteligentes.</p>

    <p>Además, he trabajado en el ámbito de la electrónica durante más de 15 años, desempeñando roles técnicos en
    empresas como <strong>Fokus Reparaciones, Fnac y MediaMarkt</strong>, lo que me aporta una perspectiva práctica
    sobre hardware, reparación y optimización de procesos.</p>

    <p>Fuera del trabajo, participo como <strong>voluntario tecnológico</strong> en proyectos de alfabetización digital
    y sostenibilidad, colaborando con organizaciones como <strong>CiberVoluntarios</strong> y <strong>WeSumPlus</strong>.
    También me apasionan el <strong>running (10K), la electrónica con Arduino y Raspberry Pi, la lectura técnica
    y los proyectos DIY</strong>.</p>

    <p>Me defino como una persona <strong>curiosa, resolutiva y orientada a la mejora continua</strong>, con un fuerte
    interés en la integración de la inteligencia artificial y el desarrollo de software para crear productos con impacto
    real. Mi objetivo es seguir creciendo como profesional y aportar valor en proyectos donde la tecnología se use
    para mejorar el futuro.</p>
  </section>
</article>
HTML;

    return renderLayout($content, $initialState);
}

/**
 * Página /articles — lista los últimos artículos publicados (con consulta DB).
 */
function renderArticlesList() {
    $baseUrl = getBaseUrl();
    $initialState = [
        'route'       => '/articles',
        'title'       => 'Artículos | Juan Carlos Macías',
        'description' => 'Blog de tecnología, inteligencia artificial y desarrollo Full Stack por Juan Carlos Macías.',
        'url'         => $baseUrl . '/articles',
        'isSSR'       => true,
    ];

    $articlesHtml = '';
    try {
        $dbPath = findDatabasePhp();
        if ($dbPath) {
            require_once $dbPath;
            $db = Database::getInstance();
            $articles = $db->fetchAll(
                "SELECT title, slug, excerpt, created_at, tags, reading_time FROM articles
                 WHERE status = 'published' ORDER BY created_at DESC LIMIT 10"
            );
            foreach ($articles as $a) {
                $title   = htmlspecialchars($a['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $slug    = htmlspecialchars($a['slug']  ?? '', ENT_QUOTES, 'UTF-8');
                $excerpt = htmlspecialchars(substr($a['excerpt'] ?? strip_tags($a['content'] ?? ''), 0, 160), ENT_QUOTES, 'UTF-8');
                $date    = htmlspecialchars($a['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                $readTime = (int)($a['reading_time'] ?? 1);
                $articlesHtml .= <<<ITEM
<article class="article-card mb-4">
  <header>
    <h2><a href="/article/{$slug}">{$title}</a></h2>
    <time datetime="{$date}">{$date}</time> · {$readTime} min de lectura
  </header>
  <p>{$excerpt}</p>
</article>
ITEM;
            }
        }
    } catch (Exception $e) {
        error_log('SSR renderArticlesList error: ' . $e->getMessage());
    }

    if (empty($articlesHtml)) {
        $articlesHtml = '<p>Cargando artículos…</p>';
    }

    $content = '<section id="articles"><h1>Artículos</h1>' . $articlesHtml . '</section>';
    return renderLayout($content, $initialState);
}

/**
 * Helper reutilizable: localiza database.php probando varias rutas.
 */
function findDatabasePhp(): ?string {
    $candidates = [
        __DIR__ . '/../../admin/config/database.php',          // build/ → 2 niveles arriba
        __DIR__ . '/../../../admin/config/database.php',       // 3 niveles
        dirname(dirname(dirname(__DIR__))) . '/admin/config/database.php',
        dirname(dirname(__DIR__)) . '/admin/config/database.php',
        '/var/www/html/admin/config/database.php',             // ruta absoluta típica Linux
        '/var/www/Portfolio/admin/config/database.php',
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

/**
 * Páginas estáticas restantes: Projects, Resume, Politics, Terminos, Contacto.
 * Devuelven meta SEO correcta + contenido mínimo (React carga el detalle).
 */
function renderStaticPage($route) {
    $baseUrl = getBaseUrl();

    $meta = [
        '/project'  => ['Proyectos',              'Proyectos de desarrollo Full Stack e Inteligencia Artificial por Juan Carlos Macías.'],
        '/resume'   => ['Currículum',             'Currículum vitae de Juan Carlos Macías — Desarrollador Full Stack e IA.'],
        '/politics' => ['Política de privacidad', 'Política de privacidad del portfolio de Juan Carlos Macías.'],
        '/terminos' => ['Términos de uso',        'Términos y condiciones de uso del portfolio de Juan Carlos Macías.'],
        '/contacto' => ['Contacto',               'Contacta con Juan Carlos Macías, desarrollador Full Stack e IA en Madrid.'],
    ];

    [$pageTitle, $pageDesc] = $meta[$route] ?? ['Portfolio', 'Portfolio de Juan Carlos Macías'];

    $initialState = [
        'route'       => $route,
        'title'       => $pageTitle . ' | Juan Carlos Macías',
        'description' => $pageDesc,
        'url'         => $baseUrl . $route,
        'isSSR'       => true,
    ];

    $titleSafe = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    $content = <<<HTML
<section>
    <h1>{$titleSafe}</h1>
    <p>{$pageDesc}</p>
</section>
HTML;

    return renderLayout($content, $initialState);
}

/**
 * Fallback: sirve el index.html de React para que el SPA maneje la ruta.
 * Se usa cuando la ruta no es conocida o falla la carga de DB.
 */
function serveSpaFallback() {
    // index.html está en el mismo directorio que index.php (build/)
    $indexPath = __DIR__ . '/index.html';
    if (file_exists($indexPath)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($indexPath);
        exit;
    }
    // Si tampoco existe, responder 404 estándar HTTP sin PHP HTML
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>404</title></head><body><p>Página no encontrada.</p></body></html>';
    exit;
}

// ──────────────────────────────────────────────
// Router principal
// ──────────────────────────────────────────────

function renderRoute($route) {
    // Página de inicio
    if ($route === '/') {
        return renderHome();
    }

    // Artículo individual — acepta letras, números, guiones y guiones bajos
    if (preg_match('#^/article/([a-zA-Z0-9_\-]+)$#', $route, $matches)) {
        return renderArticle($matches[1]);
    }

    // About — contenido completo del perfil
    if ($route === '/about') {
        return renderAbout();
    }

    // Articles — lista paginada con datos de BD
    if ($route === '/articles') {
        return renderArticlesList();
    }

    // Páginas estáticas restantes
    $staticRoutes = ['/project', '/resume', '/politics', '/terminos', '/contacto'];
    if (in_array($route, $staticRoutes, true)) {
        return renderStaticPage($route);
    }

    // Ruta desconocida → dejar que React SPA la maneje
    serveSpaFallback();
}

// ──────────────────────────────────────────────
// Ejecución
// ──────────────────────────────────────────────

try {
    $route = getRoute();

    if ($isLocal) {
        error_log('SSR Request: ' . $route);
    }

    $html = renderRoute($route);

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Powered-By: PHP-SSR-React');
    header('X-SSR-Route: ' . $route);

    echo $html;

} catch (Throwable $e) {
    error_log('SSR fatal error: ' . $e->getMessage());
    serveSpaFallback();
}
