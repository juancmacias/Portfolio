-- ==================================================
-- SCRIPT SQL: Verificación de Contenido para AdSense
-- ==================================================
-- Descripción: Este script verifica la cantidad y calidad
-- del contenido en la base de datos para cumplir con
-- los requisitos de Google AdSense.
-- 
-- Fecha: 19 de febrero de 2026
-- ==================================================

-- 1. CONTADOR DE ARTÍCULOS PUBLICADOS
-- Google requiere mínimo 15-20 páginas de contenido original
SELECT 
    COUNT(*) as total_articulos_publicados,
    COUNT(DISTINCT slug) as urls_unicas
FROM articles 
WHERE status = 'published';

-- 2. ANÁLISIS DE LONGITUD DE CONTENIDO
-- Cada artículo debe tener 500+ palabras (≈2500 caracteres)
SELECT 
    COUNT(*) as total_articulos,
    AVG(LENGTH(content)) as promedio_caracteres,
    ROUND(AVG(LENGTH(content) - LENGTH(REPLACE(content, ' ', '')) + 1)) as promedio_palabras,
    MIN(LENGTH(content)) as minimo_caracteres,
    MAX(LENGTH(content)) as maximo_caracteres
FROM articles 
WHERE status = 'published';

-- 3. ARTÍCULOS CORTOS QUE NECESITAN AMPLIARSE
-- Artículos con menos de 500 palabras (2500 caracteres aproximados)
SELECT 
    id,
    title,
    slug,
    LENGTH(content) as caracteres,
    ROUND(LENGTH(content) - LENGTH(REPLACE(content, ' ', '')) + 1) as palabras_aprox,
    created_at,
    CASE 
        WHEN LENGTH(content) < 1000 THEN 'MUY CORTO - Ampliar URGENTE'
        WHEN LENGTH(content) < 2500 THEN 'CORTO - Ampliar recomendado'
        ELSE 'Longitud adecuada'
    END as estado
FROM articles 
WHERE status = 'published' AND LENGTH(content) < 2500
ORDER BY LENGTH(content) ASC;

-- 4. DISTRIBUCIÓN DE ARTÍCULOS POR RANGO DE LONGITUD
SELECT 
    CASE 
        WHEN LENGTH(content) < 1000 THEN '1. Muy corto (<1000 chars)'
        WHEN LENGTH(content) < 2500 THEN '2. Corto (1000-2500 chars)'
        WHEN LENGTH(content) < 5000 THEN '3. Medio (2500-5000 chars)'
        WHEN LENGTH(content) < 10000 THEN '4. Largo (5000-10000 chars)'
        ELSE '5. Muy largo (>10000 chars)'
    END as rango_longitud,
    COUNT(*) as cantidad_articulos,
    ROUND(AVG(LENGTH(content))) as promedio_caracteres
FROM articles 
WHERE status = 'published'
GROUP BY rango_longitud
ORDER BY rango_longitud;

-- 5. ARTÍCULOS SIN META DESCRIPCIÓN
-- Importante para SEO y AdSense
SELECT 
    id,
    title,
    slug,
    CASE 
        WHEN meta_description IS NULL OR meta_description = '' THEN 'Sin meta descripción'
        WHEN LENGTH(meta_description) < 120 THEN 'Meta descripción muy corta'
        WHEN LENGTH(meta_description) > 160 THEN 'Meta descripción muy larga'
        ELSE 'Meta descripción OK'
    END as estado_meta,
    LENGTH(meta_description) as longitud_meta
FROM articles 
WHERE status = 'published'
ORDER BY 
    CASE 
        WHEN meta_description IS NULL OR meta_description = '' THEN 1
        WHEN LENGTH(meta_description) < 120 THEN 2
        WHEN LENGTH(meta_description) > 160 THEN 3
        ELSE 4
    END,
    created_at DESC;

-- 6. ARTÍCULOS SIN IMAGEN DESTACADA
-- Importante para compartir en redes sociales y SEO
SELECT 
    id,
    title,
    slug,
    featured_image,
    created_at
FROM articles 
WHERE status = 'published' 
    AND (featured_image IS NULL OR featured_image = '' OR featured_image = 'NULL')
ORDER BY created_at DESC;

-- 7. ARTÍCULOS RECIENTES (ÚLTIMOS 30 DÍAS)
-- Google valora contenido actualizado regularmente
SELECT 
    COUNT(*) as articulos_ultimos_30_dias,
    MIN(created_at) as primer_articulo,
    MAX(created_at) as ultimo_articulo,
    DATEDIFF(NOW(), MAX(created_at)) as dias_desde_ultimo_articulo
FROM articles 
WHERE status = 'published' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 8. FRECUENCIA DE PUBLICACIÓN (POR MES)
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as mes,
    COUNT(*) as articulos_publicados,
    GROUP_CONCAT(title SEPARATOR ' | ') as titulos
FROM articles 
WHERE status = 'published'
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY mes DESC
LIMIT 12;

-- 9. ANÁLISIS DE TAGS
-- Artículos sin tags o con pocos tags afectan SEO
SELECT 
    id,
    title,
    tags,
    CASE 
        WHEN tags IS NULL OR tags = '' OR tags = '[]' THEN 'Sin tags'
        WHEN JSON_LENGTH(tags) < 3 THEN 'Pocos tags'
        ELSE 'Tags OK'
    END as estado_tags
FROM articles 
WHERE status = 'published'
    AND (tags IS NULL OR tags = '' OR tags = '[]' OR JSON_LENGTH(tags) < 3)
ORDER BY created_at DESC;

-- 10. RESUMEN EJECUTIVO PARA ADSENSE
SELECT 
    'RESUMEN DE CONTENIDO PARA ADSENSE' as reporte,
    COUNT(*) as total_articulos,
    SUM(CASE WHEN LENGTH(content) >= 2500 THEN 1 ELSE 0 END) as articulos_longitud_ok,
    SUM(CASE WHEN LENGTH(content) < 2500 THEN 1 ELSE 0 END) as articulos_cortos,
    SUM(CASE WHEN meta_description IS NOT NULL AND meta_description != '' THEN 1 ELSE 0 END) as con_meta_descripcion,
    SUM(CASE WHEN featured_image IS NOT NULL AND featured_image != '' AND featured_image != 'NULL' THEN 1 ELSE 0 END) as con_imagen_destacada,
    CASE 
        WHEN COUNT(*) >= 20 THEN '✅ Cantidad suficiente'
        WHEN COUNT(*) >= 15 THEN '⚠️ Cantidad mínima'
        ELSE '❌ Contenido insuficiente'
    END as evaluacion_cantidad,
    CASE 
        WHEN SUM(CASE WHEN LENGTH(content) >= 2500 THEN 1 ELSE 0 END) / COUNT(*) >= 0.8 THEN '✅ Calidad OK'
        WHEN SUM(CASE WHEN LENGTH(content) >= 2500 THEN 1 ELSE 0 END) / COUNT(*) >= 0.6 THEN '⚠️ Mejorar calidad'
        ELSE '❌ Calidad insuficiente'
    END as evaluacion_calidad
FROM articles 
WHERE status = 'published';

-- ==================================================
-- RECOMENDACIONES BASADAS EN RESULTADOS:
-- ==================================================
-- 
-- Si total_articulos < 15:
--   ❌ CRÍTICO: Necesitas publicar más artículos (mínimo 15-20)
--   Acción: Crear 1-2 artículos nuevos por semana
--
-- Si articulos_cortos > 5:
--   ⚠️ IMPORTANTE: Ampliar artículos existentes
--   Acción: Agregar 300-500 palabras más a cada artículo corto
--
-- Si con_meta_descripcion < total_articulos:
--   ⚠️ SEO: Agregar meta descripciones únicas (150-160 caracteres)
--   Acción: Editar artículos en admin y agregar meta_description
--
-- Si con_imagen_destacada < total_articulos:
--   ⚠️ VISUAL: Agregar imágenes destacadas
--   Acción: Subir imágenes relevantes y atractivas
--
-- Si dias_desde_ultimo_articulo > 30:
--   ⚠️ ACTIVIDAD: Publicar nuevo contenido
--   Acción: Mantener ritmo de 1 artículo semanal mínimo
-- 
-- ==================================================

-- 11. IDENTIFICAR ARTÍCULOS PARA AMPLIAR (TOP PRIORITARIOS)
-- Estos son los artículos que deberías ampliar PRIMERO
SELECT 
    id,
    title,
    slug,
    LENGTH(content) as caracteres_actuales,
    ROUND((2500 - LENGTH(content))) as caracteres_faltantes,
    ROUND((2500 - LENGTH(content)) / 5) as palabras_a_agregar_aprox,
    CONCAT('/article/', slug) as url_frontend,
    CONCAT('admin/pages/article-view.php?id=', id) as url_admin
FROM articles 
WHERE status = 'published' 
    AND LENGTH(content) < 2500
ORDER BY LENGTH(content) ASC
LIMIT 10;

-- ==================================================
-- CÓMO USAR ESTOS RESULTADOS:
-- ==================================================
-- 1. Ejecuta cada query individualmente o todas juntas
-- 2. Exporta resultados a Excel/CSV para análisis
-- 3. Usa la query 11 para priorizar artículos a ampliar
-- 4. Verifica el RESUMEN EJECUTIVO (query 10) para evaluación general
-- 5. Ejecuta este script mensualmente para monitoreo continuo
-- ==================================================
