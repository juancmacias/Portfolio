<?php
/**
 * Clase ArticleManager - Gestión completa de artículos
 */

class ArticleManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todos los artículos con filtros y paginación
     */
    public function getArticles($filters = [], $page = 1, $perPage = 10) {
        $where = ["1=1"];
        $params = [];
        
        // Filtros
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR content LIKE ? OR tags LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($filters['ai_generated'])) {
            $where[] = "ai_generated = ?";
            $params[] = $filters['ai_generated'] === 'true' ? 1 : 0;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total para paginación
        $countSQL = "SELECT COUNT(*) as total FROM articles WHERE {$whereClause}";
        $totalResult = $this->db->fetchOne($countSQL, $params);
        $total = $totalResult['total'];
        
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Obtener artículos
        $sql = "
            SELECT 
                id, title, slug, excerpt, status, author, featured_image,
                tags, reading_time, views, ai_generated, ai_model,
                created_at, updated_at, published_at
            FROM articles 
            WHERE {$whereClause}
            ORDER BY created_at DESC 
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        $articles = $this->db->fetchAll($sql, $params);
        
        return [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Obtener un artículo por ID
     */
    public function getArticle($id) {
        $sql = "SELECT * FROM articles WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Obtener un artículo por slug
     */
    public function getArticleBySlug($slug) {
        $sql = "SELECT * FROM articles WHERE slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }
    
    /**
     * Crear un nuevo artículo
     */
    public function createArticle($data) {
        // Validar datos requeridos
        if (empty($data['title']) || empty($data['content'])) {
            throw new Exception('Título y contenido son requeridos');
        }
        
        // Generar slug automático si no se proporciona
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
        
        // Verificar que el slug sea único
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);
        
        // Calcular tiempo de lectura automáticamente
        if (empty($data['reading_time'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }
        
        // Generar excerpt automático si no se proporciona
        if (empty($data['excerpt'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }
        
        // Establecer meta description automática si no se proporciona
        if (empty($data['meta_description'])) {
            $data['meta_description'] = $this->generateMetaDescription($data['content']);
        }
        
        // Si se publica, establecer fecha de publicación
        if (!empty($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        $sql = "
            INSERT INTO articles (
                title, slug, content, excerpt, meta_description, status, author,
                featured_image, tags, reading_time, ai_generated, ai_model,
                ai_prompt, ai_tokens_used, ai_cost_estimated, published_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['excerpt'],
            $data['meta_description'],
            $data['status'] ?? 'draft',
            $data['author'] ?? 'Juan Carlos Macías',
            $data['featured_image'] ?? null,
            $data['tags'] ?? null,
            $data['reading_time'],
            !empty($data['ai_generated']) ? 1 : 0,
            $data['ai_model'] ?? null,
            $data['ai_prompt'] ?? null,
            $data['ai_tokens_used'] ?? 0,
            $data['ai_cost_estimated'] ?? 0.0000,
            $data['published_at'] ?? null
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Actualizar un artículo existente
     */
    public function updateArticle($id, $data) {
        // Verificar que el artículo existe
        $existing = $this->getArticle($id);
        if (!$existing) {
            throw new Exception('Artículo no encontrado');
        }
        
        // Si se está cambiando el título, regenerar slug
        if (!empty($data['title']) && $data['title'] !== $existing['title']) {
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);
        }
        
        // Recalcular tiempo de lectura si el contenido cambió
        if (!empty($data['content']) && $data['content'] !== $existing['content']) {
            if (empty($data['reading_time'])) {
                $data['reading_time'] = $this->calculateReadingTime($data['content']);
            }
            
            // Regenerar excerpt si no se proporciona uno nuevo
            if (empty($data['excerpt'])) {
                $data['excerpt'] = $this->generateExcerpt($data['content']);
            }
            
            // Regenerar meta description si no se proporciona una nueva
            if (empty($data['meta_description'])) {
                $data['meta_description'] = $this->generateMetaDescription($data['content']);
            }
        }
        
        // Si se está publicando por primera vez
        if (!empty($data['status']) && $data['status'] === 'published' && 
            $existing['status'] !== 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        // Construir query de actualización dinámicamente
        $fields = [];
        $params = [];
        
        $allowedFields = [
            'title', 'slug', 'content', 'excerpt', 'meta_description', 'status',
            'author', 'featured_image', 'tags', 'reading_time', 'published_at'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            throw new Exception('No hay campos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Eliminar un artículo
     */
    public function deleteArticle($id) {
        // Verificar que el artículo existe
        $existing = $this->getArticle($id);
        if (!$existing) {
            throw new Exception('Artículo no encontrado');
        }
        
        // Eliminar logs de IA relacionados
        $this->db->query("DELETE FROM ai_logs WHERE article_id = ?", [$id]);
        
        // Eliminar el artículo
        return $this->db->query("DELETE FROM articles WHERE id = ?", [$id]);
    }
    
    /**
     * Cambiar el estado de un artículo
     */
    public function changeStatus($id, $status) {
        $allowedStatuses = ['draft', 'published', 'archived'];
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Estado no válido');
        }
        
        $updateData = ['status' => $status];
        
        // Si se publica, establecer fecha de publicación
        if ($status === 'published') {
            $existing = $this->getArticle($id);
            if ($existing && empty($existing['published_at'])) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
        }
        
        return $this->updateArticle($id, $updateData);
    }
    
    /**
     * Incrementar contador de vistas
     */
    public function incrementViews($id) {
        return $this->db->query("UPDATE articles SET views = views + 1 WHERE id = ?", [$id]);
    }
    
    /**
     * Obtener estadísticas de artículos
     */
    public function getStats() {
        $stats = [];
        
        // Total de artículos
        $stats['total'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM articles")['count'];
        
        // Por estado
        $statusStats = $this->db->fetchAll("
            SELECT status, COUNT(*) as count 
            FROM articles 
            GROUP BY status
        ");
        
        $stats['by_status'] = [];
        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = $stat['count'];
        }
        
        // Artículos generados por IA
        $stats['ai_generated'] = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM articles 
            WHERE ai_generated = 1
        ")['count'];
        
        // Total de vistas
        $stats['total_views'] = $this->db->fetchOne("
            SELECT SUM(views) as total 
            FROM articles
        ")['total'] ?? 0;
        
        // Artículos más populares
        $stats['most_viewed'] = $this->db->fetchAll("
            SELECT title, views 
            FROM articles 
            WHERE status = 'published'
            ORDER BY views DESC 
            LIMIT 5
        ");
        
        // Actividad reciente
        $stats['recent_activity'] = $this->db->fetchAll("
            SELECT title, status, created_at, updated_at
            FROM articles 
            ORDER BY updated_at DESC 
            LIMIT 10
        ");
        
        return $stats;
    }
    
    /**
     * Generar slug a partir del título
     */
    private function generateSlug($title) {
        $slug = strtolower($title);
        
        // Reemplazar caracteres especiales
        $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
        
        // Mantener solo letras, números y espacios
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        
        // Reemplazar espacios y múltiples guiones con un solo guión
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        
        // Eliminar guiones del inicio y final
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Asegurar que el slug sea único
     */
    private function ensureUniqueSlug($slug, $excludeId = null) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $params = [$slug];
            $sql = "SELECT id FROM articles WHERE slug = ?";
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $existing = $this->db->fetchOne($sql, $params);
            
            if (!$existing) {
                return $slug;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
    
    /**
     * Calcular tiempo de lectura estimado
     */
    private function calculateReadingTime($content) {
        $wordCount = str_word_count(strip_tags($content));
        $wordsPerMinute = 200; // Promedio de lectura
        $readingTime = ceil($wordCount / $wordsPerMinute);
        
        return max(1, $readingTime); // Mínimo 1 minuto
    }
    
    /**
     * Generar excerpt automático
     */
    private function generateExcerpt($content, $length = 150) {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $excerpt = substr($text, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
    
    /**
     * Generar meta description automática
     */
    private function generateMetaDescription($content, $length = 155) {
        return $this->generateExcerpt($content, $length);
    }
}
?>