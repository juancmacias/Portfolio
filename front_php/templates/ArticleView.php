<?php
/**
 * Template PHP que replica el componente React ArticleView
 * Debe mantener la misma estructura HTML para que la hidratación funcione
 */

// Función para formatear fecha igual que React (español con hora)
function formatDateSpanish($dateString) {
    $timestamp = strtotime($dateString);
    $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $day = (int)date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    $hour = date('H', $timestamp);
    $minute = date('i', $timestamp);
    
    return "{$day} de {$month} de {$year}, {$hour}:{$minute}";
}

// Función para calcular tiempo de lectura igual que React
function calculateReadTime($content) {
    $words = str_word_count(strip_tags($content));
    $readTime = ceil($words / 250);
    return $readTime < 1 ? 1 : $readTime;
}

// Función para obtener color de tag igual que React
function getCategoryColor($tag) {
    $tagLower = strtolower($tag);
    $colors = [
        'javascript' => 'warning',
        'react' => 'info',
        'php' => 'primary',
        'python' => 'success',
        'ia' => 'danger',
        'css' => 'info',
        'html' => 'warning',
        'node' => 'success',
        'database' => 'secondary'
    ];
    return $colors[$tagLower] ?? 'outline-secondary';
}

function renderArticleView($article) {
    // Sanitizar todos los datos
    $title = htmlspecialchars($article['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $excerpt = htmlspecialchars($article['excerpt'] ?? '', ENT_QUOTES, 'UTF-8');
    $content = $article['content'] ?? '';
    $featuredImage = htmlspecialchars($article['featured_image'] ?? '', ENT_QUOTES, 'UTF-8');
    $slug = $article['slug'] ?? '';
    
    // Fechas
    $publishedAt = $article['published_at'] ?? null;
    $createdAt = $article['created_at'] ?? date('Y-m-d H:i:s');
    $updatedAt = $article['updated_at'] ?? $createdAt;
    $displayDate = $publishedAt ?: $createdAt;
    
    // Calcular reading time dinámicamente
    $readTime = calculateReadTime($content);
    
    // Procesar tags
    $tags = [];
    if (!empty($article['tags'])) {
        $tagsData = is_string($article['tags']) ? json_decode($article['tags'], true) : $article['tags'];
        if (is_array($tagsData)) {
            $tags = $tagsData;
        }
    }
    
    // Base URL de la API
    $urlApi = 'http://www.frontend.pru/';
    $articleUrl = $urlApi . 'article/' . $slug;
    $defaultImage = $urlApi . 'Assets/Projects/portfolio.png';
    $imageUrl = $featuredImage ?: $defaultImage;
    
    ob_start();
    ?>
    <div class="article-view-section container-fluid">
        <div class="container">
            
            <!-- Navegación superior -->
            <div class="row mb-4">
                <div class="col">
                    <a href="/articles" class="btn btn-outline-secondary btn-sm">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 448 512" class="me-2" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M257.5 445.1l-22.2 22.2c-9.4 9.4-24.6 9.4-33.9 0L7 273c-9.4-9.4-9.4-24.6 0-33.9L201.4 44.7c9.4-9.4 24.6-9.4 33.9 0l22.2 22.2c9.5 9.5 9.3 25-.4 34.3L136.6 216H424c13.3 0 24 10.7 24 24v32c0 13.3-10.7 24-24 24H136.6l120.5 114.8c9.8 9.3 10 24.8.4 34.3z"></path></svg>
                        Volver a artículos
                    </a>
                </div>
            </div>
            
            <!-- Contenido del artículo -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    
                    <!-- Imagen destacada -->
                    <?php if (!empty($featuredImage)): ?>
                    <div class="article-featured-image mb-4">
                        <img 
                            src="<?php echo $featuredImage; ?>" 
                            alt="<?php echo $title; ?>" 
                            width="100%" 
                            height="400" 
                            class="img-fluid rounded shadow" 
                            style="max-height: 400px; object-fit: cover;"
                            fetchpriority="high"
                            decoding="async"
                        />
                    </div>
                    <?php endif; ?>
                    
                    <!-- Meta información (Tags) -->
                    <div class="article-meta mb-3">
                        <?php if (!empty($tags)): ?>
                        <div class="mb-2">
                            <?php foreach ($tags as $index => $tag): ?>
                            <span class="badge bg-<?php echo getCategoryColor($tag); ?> me-2 mb-1">
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" class="me-1" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M0 252.118V48C0 21.49 21.49 0 48 0h204.118a48 48 0 0 1 33.941 14.059l211.882 211.882c18.745 18.745 18.745 49.137 0 67.882L293.823 497.941c-18.745 18.745-49.137 18.745-67.882 0L14.059 286.059A48 48 0 0 1 0 252.118zM112 64c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48z"></path></svg>
                                <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex flex-wrap gap-3 text-muted">
                            <small>
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 448 512" class="me-1" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M148 288h-40c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12zm108-12v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 96v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm192 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96-260v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48zm-48 346V160H48v298c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"></path></svg>
                                <?php echo formatDateSpanish($displayDate); ?>
                            </small>
                            <small>
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" class="me-1" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-104.4l-84.9-61.7c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v141.7l66.8 48.6c5.4 3.9 6.5 11.4 2.6 16.8L334.6 349c-3.9 5.3-11.4 6.5-16.8 2.6z"></path></svg>
                                <?php echo $readTime; ?> min de lectura
                            </small>
                            <button class="btn btn-link btn-sm p-0 text-muted" type="button">
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" class="me-1" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M503.691 189.836L327.687 37.851C312.281 24.546 288 35.347 288 56.015v80.053C127.371 137.907 0 170.1 0 322.326c0 61.441 39.581 122.309 83.333 154.132 13.653 9.931 33.111-2.533 28.077-18.631C66.066 312.814 132.917 274.316 288 272.085V360c0 20.7 24.3 31.453 39.687 18.164l176.004-152c11.071-9.562 11.086-26.753 0-36.328z"></path></svg>
                                Compartir
                            </button>
                        </div>
                    </div>
                    
                    <!-- Título -->
                    <h1 class="article-title mb-4"><?php echo $title; ?></h1>
                    
                    <!-- Excerpt -->
                    <?php if (!empty($excerpt)): ?>
                    <div class="article-excerpt lead text-muted mb-4">
                        <?php echo $excerpt; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Contenido -->
                    <div class="article-content">
                        <?php echo nl2br($content); ?>
                    </div>
                    
                    <!-- Footer del artículo -->
                    <hr class="my-5" />
                    
                    <div class="article-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    Publicado el <?php echo formatDateSpanish($displayDate); ?>
                                    <?php if ($updatedAt !== $createdAt): ?>
                                    <br />
                                    Actualizado el <?php echo formatDateSpanish($updatedAt); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <a href="/articles" class="btn btn-primary">
                                    Ver más artículos
                                </a>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
