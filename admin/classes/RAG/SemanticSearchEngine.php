<?php
/**
 * Motor de Búsqueda Semántica Simplificado
 * Utiliza MySQL Full-Text Search para RAG sin vectores complejos
 * 
 * @package PortfolioRAG
 * @author Juan Carlos Macías
 * @version 1.0
 */

// Evitar acceso directo
if (!defined('ADMIN_ACCESS')) {
    die('Acceso directo no permitido');
}

class SimplifiedRAGEngine {
    private $db;
    private $maxResults;
    private $similarityThreshold;
    
    public function __construct($maxResults = 5, $similarityThreshold = 0.3) {
        $this->db = Database::getInstance();
        $this->maxResults = $maxResults;
        $this->similarityThreshold = $similarityThreshold;
    }
    
    /**
     * Búsqueda principal de contenido relevante
     * Combina múltiples fuentes: portfolio, documentos, chunks
     */
    public function searchRelevantContent($query, $options = []) {
        $limit = $options['limit'] ?? $this->maxResults;
        $enableDocuments = $options['enable_documents'] ?? true;
        $enablePortfolio = $options['enable_portfolio'] ?? true;
        
        $results = [];
        
        try {
            // 1. Búsqueda en embeddings del portfolio
            if ($enablePortfolio) {
                $portfolioResults = $this->searchPortfolioEmbeddings($query, $limit);
                $results = array_merge($results, $this->formatResults($portfolioResults, 'portfolio'));
            }
            
            // 2. Búsqueda en documentos de referencia
            if ($enableDocuments) {
                $documentResults = $this->searchDocuments($query, $limit);
                $results = array_merge($results, $this->formatResults($documentResults, 'documents'));
            }
            
            // 3. Búsqueda en chunks de documentos
            if ($enableDocuments) {
                $chunkResults = $this->searchDocumentChunks($query, $limit);
                $results = array_merge($results, $this->formatResults($chunkResults, 'chunks'));
            }
            
            // 4. Rankear y filtrar resultados
            $rankedResults = $this->rankAndFilterResults($results, $query);
            
            // 5. Limitar resultados finales
            return array_slice($rankedResults, 0, $limit);
            
        } catch (Exception $e) {
            error_log("Error en búsqueda RAG: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Búsqueda en embeddings del portfolio usando Full-Text Search
     * NOTA: simple_embeddings no contiene texto completo, solo referencias
     */
    private function searchPortfolioEmbeddings($query, $limit) {
        try {
            // La tabla simple_embeddings no tiene content_text, solo referencias
            // Buscaremos directamente en documents/chunks que sí tienen el contenido
            return [];
            
        } catch (Exception $e) {
            error_log("Error en searchPortfolioEmbeddings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Búsqueda en documentos de referencia
     */
    private function searchDocuments($query, $limit) {
        $sql = "
            SELECT 
                rd.id,
                rd.title as document_name,
                rd.file_type as document_type,
                rd.content as content_text,
                rd.tags as content_summary,
                rd.tags as keywords,
                MATCH(rd.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score,
                'document' as source_type
            FROM reference_documents rd
            WHERE rd.is_active = 1 
                AND MATCH(rd.content) AGAINST(? IN NATURAL LANGUAGE MODE)
                AND MATCH(rd.content) AGAINST(? IN NATURAL LANGUAGE MODE) > ?
            ORDER BY relevance_score DESC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [
            $query, 
            $query, 
            $query,
            $this->similarityThreshold, 
            $limit
        ]);
    }
    
    /**
     * Búsqueda en chunks de documentos (más granular)
     */
    private function searchDocumentChunks($query, $limit) {
        $sql = "
            SELECT 
                dc.id,
                dc.chunk_text as content_text,
                '' as chunk_summary,
                '' as keywords,
                rd.title as document_name,
                rd.file_type as document_type,
                MATCH(dc.chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score,
                'chunk' as source_type
            FROM document_chunks dc
            JOIN reference_documents rd ON dc.document_id = rd.id
            WHERE rd.is_active = 1 
                AND MATCH(dc.chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                AND MATCH(dc.chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE) > ?
            ORDER BY relevance_score DESC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [
            $query, 
            $query, 
            $query,
            $this->similarityThreshold, 
            $limit
        ]);
    }
    
    /**
     * Búsqueda por keywords cuando Full-Text no encuentra resultados
     */
    public function searchByKeywords($query, $limit = null) {
        $limit = $limit ?? $this->maxResults;
        $keywords = $this->extractKeywords($query);
        
        if (empty($keywords)) {
            return [];
        }
        
        $results = [];
        
        // Buscar en documentos por keywords
        foreach ($keywords as $keyword) {
            $sql = "
                SELECT 
                    id as content_id,
                    content as content_text,
                    file_type as content_type,
                    tags as keywords,
                    CASE 
                        WHEN content LIKE ? THEN 0.8
                        WHEN tags LIKE ? THEN 0.6
                        ELSE 0.3
                    END as relevance_score,
                    'document' as source_type
                FROM reference_documents 
                WHERE is_active = 1 AND (
                    content LIKE ? 
                    OR tags LIKE ?
                    OR title LIKE ?
                )
                ORDER BY relevance_score DESC
                LIMIT ?
            ";
            
            $keywordPattern = '%' . $keyword . '%';
            $keywordResults = $this->db->fetchAll($sql, [
                $keywordPattern, $keywordPattern, $keywordPattern, $keywordPattern, $keywordPattern, $limit
            ]);
            
            $results = array_merge($results, $keywordResults);
        }
        
        // Eliminar duplicados y rankear
        return $this->removeDuplicatesAndRank($results);
    }
    
    /**
     * Construir contexto textual a partir de resultados
     */
    public function buildContextFromResults($results, $maxTokens = 2000) {
        if (empty($results)) {
            return "No se encontró información específica en la base de conocimientos.";
        }
        
        $context = "INFORMACIÓN RELEVANTE DEL PORTFOLIO DE JUAN CARLOS MACÍAS:\n\n";
        $tokenCount = 0;
        $sourcesUsed = [];
        
        foreach ($results as $result) {
            $content = $result['content_text'] ?? '';
            $source = $result['source_type'] ?? 'unknown';
            $relevance = $result['relevance_score'] ?? 0;
            
            // Limitar contenido si es muy largo
            if (strlen($content) > 800) {
                $content = substr($content, 0, 800) . '...';
            }
            
            $tokens = str_word_count($content);
            
            if ($tokenCount + $tokens > $maxTokens) {
                break;
            }
            
            // Formatear según el tipo de fuente
            switch ($source) {
                case 'portfolio':
                    $context .= "📋 PORTFOLIO: " . $content . "\n\n";
                    break;
                case 'document':
                    $docName = $result['document_name'] ?? 'Documento';
                    $context .= "📄 DOCUMENTO ({$docName}): " . $content . "\n\n";
                    break;
                case 'chunk':
                    $docName = $result['document_name'] ?? 'Documento';
                    $context .= "📎 DETALLE ({$docName}): " . $content . "\n\n";
                    break;
                default:
                    $context .= "ℹ️  INFORMACIÓN: " . $content . "\n\n";
            }
            
            $tokenCount += $tokens;
            $sourcesUsed[] = [
                'type' => $source,
                'relevance' => $relevance,
                'id' => $result['id'] ?? $result['content_id'] ?? null
            ];
        }
        
        return [
            'context_text' => $context,
            'sources_used' => $sourcesUsed,
            'token_count' => $tokenCount
        ];
    }
    
    /**
     * Formatear resultados de diferentes fuentes
     */
    private function formatResults($results, $sourceType) {
        return array_map(function($result) use ($sourceType) {
            // Asegurar que siempre haya un campo 'content' disponible
            $content = $result['content_text'] ?? $result['content'] ?? $result['chunk_text'] ?? '';
            
            return array_merge($result, [
                'source_group' => $sourceType,
                'content' => $content,  // Normalizar el campo content
                'source' => $result['document_name'] ?? $result['title'] ?? 'Portfolio'
            ]);
        }, $results);
    }
    
    /**
     * Rankear y filtrar resultados por relevancia
     */
    private function rankAndFilterResults($results, $query) {
        // Filtrar por threshold mínimo
        $filtered = array_filter($results, function($result) {
            return ($result['relevance_score'] ?? 0) >= $this->similarityThreshold;
        });
        
        // Aplicar boost según tipo de fuente
        foreach ($filtered as &$result) {
            $boost = 1.0;
            
            switch ($result['source_type'] ?? '') {
                case 'portfolio':
                    $boost = 1.2; // Boost para información del portfolio
                    break;
                case 'document':
                    $boost = 1.1; // Boost para documentos completos
                    break;
                case 'chunk':
                    $boost = 1.0; // Sin boost para chunks
                    break;
            }
            
            $result['relevance_score'] = ($result['relevance_score'] ?? 0) * $boost;
        }
        
        // Ordenar por relevancia
        usort($filtered, function($a, $b) {
            return ($b['relevance_score'] ?? 0) <=> ($a['relevance_score'] ?? 0);
        });
        
        return $filtered;
    }
    
    /**
     * Extraer keywords de una consulta
     */
    private function extractKeywords($query) {
        // Limpiar y normalizar
        $query = strtolower($query);
        $query = preg_replace('/[^\w\sáéíóúñü]/u', ' ', $query);
        
        // Palabras a excluir (stop words en español)
        $stopWords = [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 
            'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'al', 'del', 'me',
            'una', 'sobre', 'tiene', 'como', 'puede', 'qué', 'cómo', 'cuál'
        ];
        
        $words = explode(' ', $query);
        $keywords = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Eliminar duplicados y mantener los de mayor relevancia
     */
    private function removeDuplicatesAndRank($results) {
        $unique = [];
        $seen = [];
        
        foreach ($results as $result) {
            $key = $result['content_id'] ?? $result['id'] ?? md5($result['content_text'] ?? '');
            
            if (!isset($seen[$key]) || 
                ($result['relevance_score'] ?? 0) > ($seen[$key]['relevance_score'] ?? 0)) {
                $seen[$key] = $result;
                $unique[$key] = $result;
            }
        }
        
        // Ordenar por relevancia
        uasort($unique, function($a, $b) {
            return ($b['relevance_score'] ?? 0) <=> ($a['relevance_score'] ?? 0);
        });
        
        return array_values($unique);
    }
    
    /**
     * Obtener estadísticas de búsqueda
     */
    public function getSearchStats() {
        try {
            $stats = [];
            
            // Contar embeddings del portfolio
            $portfolioCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM simple_embeddings");
            $stats['portfolio_embeddings'] = $portfolioCount['count'] ?? 0;
            
            // Contar documentos activos
            $docsCount = $this->db->fetchOne("
                SELECT COUNT(*) as count 
                FROM reference_documents 
                WHERE is_active = 1 AND processing_status = 'completed'
            ");
            $stats['active_documents'] = $docsCount['count'] ?? 0;
            
            // Contar chunks de documentos
            $chunksCount = $this->db->fetchOne("
                SELECT COUNT(*) as count 
                FROM document_chunks dc
                JOIN reference_documents rd ON dc.document_id = rd.id
                WHERE rd.is_active = 1 AND rd.processing_status = 'completed'
            ");
            $stats['document_chunks'] = $chunksCount['count'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas RAG: " . $e->getMessage());
            return [
                'portfolio_embeddings' => 0,
                'active_documents' => 0,
                'document_chunks' => 0
            ];
        }
    }
    
    /**
     * Configurar parámetros de búsqueda
     */
    public function setSearchParameters($maxResults = null, $similarityThreshold = null) {
        if ($maxResults !== null) {
            $this->maxResults = max(1, min(20, $maxResults));
        }
        
        if ($similarityThreshold !== null) {
            $this->similarityThreshold = max(0.0, min(1.0, $similarityThreshold));
        }
    }
    
}
?>