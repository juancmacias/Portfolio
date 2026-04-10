# Inspector Google Search Console - Guía de Uso

## 📋 Descripción General

El **Inspector Google Search Console** es una herramienta integrada en el admin del Portfolio que permite diagnosticar problemas de indexación y analizar el rendimiento del sitio en Google Search directamente desde el panel de administración.

## 🎯 Funcionalidades Principales

### 1. Dashboard
- Vista general de todas las herramientas disponibles
- Acceso rápido a cada sección
- Información sobre el retraso de datos de GSC (2-3 días)

### 2. Inspeccionar URL
Permite verificar el estado de indexación de URLs específicas.

**Qué puedes hacer:**
- Introducir cualquier URL del sitio
- Verificar si está indexada en Google
- Ver el estado de robots.txt
- Consultar la última fecha de rastreo
- Identificar problemas de canonical

**Limitaciones:**
- La API de URL Inspection requiere permisos especiales de Google
- Para análisis detallados, usar la interfaz web de Search Console

### 3. Estado de Sitemaps
Muestra todos los sitemaps enviados a Google Search Console.

**Información disponible:**
- Ruta del sitemap
- Tipo de sitemap (web/sitemap-index)
- Fecha del último envío
- Fecha de la última descarga por Google
- Estado (Pendiente/Procesado)
- Número de errores y advertencias

**Uso:**
1. Hacer clic en "Cargar Sitemaps"
2. Revisar la tabla con todos los sitemaps
3. Verificar que no haya errores
4. Comprobar que Google haya descargado el sitemap recientemente

### 4. Analíticas de Búsqueda
Consulta las estadísticas de búsqueda de tu sitio.

**Métricas disponibles:**
- **Clics:** Número de veces que los usuarios hicieron clic en tu sitio desde los resultados de búsqueda
- **Impresiones:** Número de veces que tu sitio apareció en los resultados de búsqueda
- **CTR (Click Through Rate):** Porcentaje de impresiones que resultaron en clics
- **Posición:** Posición promedio del sitio en los resultados de búsqueda

**Períodos disponibles:**
- Últimos 7 días
- Últimos 30 días (default)
- Últimos 90 días

**Análisis:**
- Las URLs se ordenan automáticamente por rendimiento
- Posiciones ≤10 se marcan en verde (primera página de Google)
- Detecta qué páginas reciben más tráfico orgánico

### 5. Análisis de Cobertura
Compara las URLs del sitemap con las páginas realmente indexadas.

**Información proporcionada:**
- Número de sitemaps enviados
- Número total de páginas indexadas
- Lista de las primeras 100 páginas indexadas

**Interpretación:**
- Si hay menos páginas indexadas que URLs en el sitemap → posibles problemas
- Si una URL importante no está indexada → requiere investigación

## 🛠️ Casos de Uso Comunes

### Diagnosticar por qué una URL no aparece en Google

**Pasos:**
1. Ir a la pestaña "Inspeccionar URL"
2. Introducir la URL completa (ej: `https://www.juancarlosmacias.es/project/mi-proyecto`)
3. Hacer clic en "Inspeccionar"
4. Revisar el resultado:
   - Si muestra error → usar interfaz web de GSC para más detalles
   - Si está pendiente → esperar 2-3 días y volver a verificar

### Identificar páginas con bajo rendimiento

**Pasos:**
1. Ir a "Analíticas de Búsqueda"
2. Seleccionar período (30 o 90 días)
3. Ordenar la tabla por:
   - CTR bajo → títulos/descripciones poco atractivos
   - Posición alta pero pocos clics → problema de relevancia
   - Muchas impresiones pero baja posición → oportunidad de optimización

### Verificar que el sitemap se ha procesado

**Pasos:**
1. Ir a "Estado de Sitemaps"
2. Hacer clic en "Cargar Sitemaps"
3. Verificar:
   - ✅ `isPending = false` → Sitemap procesado correctamente
   - ⚠️ `isPending = true` → Google aún no lo ha procesado (esperar)
   - ❌ `errors > 0` → Revisar el sitemap en la interfaz web de GSC

### Monitorear el crecimiento de indexación

**Pasos:**
1. Ir a "Análisis de Cobertura"
2. Hacer clic en "Iniciar Análisis"
3. Anotar el número de páginas indexadas
4. Repetir semanalmente para ver evolución
5. Si el número baja → investigar posibles bloqueos

## 📊 Interpretación de Resultados

### Estado de Sitemaps

| Estado | Significado | Acción |
|--------|-------------|--------|
| Procesado, 0 errores | ✅ Todo correcto | Ninguna |
| Pendiente | ⏳ Google aún no lo ha procesado | Esperar 24-48h |
| Errores > 0 | ❌ Problemas en el sitemap | Revisar en GSC web |
| Advertencias > 0 | ⚠️ URLs con problemas menores | Revisar detalles en GSC |

### CTR en Analíticas

| CTR | Interpretación | Acción |
|-----|---------------|--------|
| < 1% | 🔴 Muy bajo, títulos/descripciones no atractivos | Optimizar meta tags |
| 1-3% | 🟡 Promedio, hay margen de mejora | Revisar títulos |
| > 3% | 🟢 Bueno, contenido relevante | Mantener estrategia |

### Posición Promedio

| Posición | Interpretación | Oportunidad |
|----------|---------------|-------------|
| 1-3 | 🏆 Top 3, excelente | Mantener contenido actualizado |
| 4-10 | 🟢 Primera página | Optimizar para subir al top 3 |
| 11-20 | 🟡 Segunda página | Mejorar contenido y backlinks |
| > 20 | 🔴 Baja visibilidad | Revisar estrategia de keywords |

## 🚨 Solución de Problemas Comunes

### Error: "Google Client Library not installed"
**Solución:**
```bash
cd e:\wwwserver\N_JCMS
composer require google/apiclient:"^2.0"
```

### Error: "Google Service Account credentials not found"
**Solución:**
1. Verificar que existe `admin/config/service-account-credentials.json`
2. Descargar credenciales desde Google Cloud Console
3. Asegurarse de que el Service Account tiene permisos en Search Console

### Error: "Permission denied" en API requests
**Solución:**
1. Ir a Google Search Console
2. Obtener email del Service Account (ej: `portfolio-sitemap@xxx.iam.gserviceaccount.com`)
3. Agregar como **Propietario** en la propiedad `sc-domain:juancarlosmacias.es`

### "URL Inspection API requires additional permissions"
**Solución:**
- La API de URL Inspection tiene requisitos especiales
- Usar la interfaz web: https://search.google.com/search-console/inspect
- Alternativamente, usar "Analíticas" para ver si la URL recibe tráfico

### No hay datos en Analíticas
**Posibles causas:**
1. Período seleccionado demasiado reciente (GSC tiene delay de 2-3 días)
2. Sitio nuevo sin suficiente historial
3. Problemas de permisos del Service Account

**Solución:**
- Seleccionar un período más antiguo (últimos 30-90 días)
- Verificar permisos del Service Account
- Esperar al menos 1 semana después de verificar el sitio

## 🔗 Enlaces Útiles

- **Google Search Console (web):** https://search.google.com/search-console
- **Coverage Report:** https://search.google.com/search-console/coverage
- **URL Inspection Tool:** https://search.google.com/search-console/inspect
- **Search Analytics:** https://search.google.com/search-console/performance
- **API Documentation:** https://developers.google.com/webmaster-tools/v1/api_reference_index

## 💡 Mejores Prácticas

### Frecuencia de Uso

| Tarea | Recomendación |
|-------|---------------|
| Verificar sitemaps | Semanal |
| Revisar analíticas | Semanal o mensual |
| Análisis de cobertura | Mensual |
| Inspección de URLs | Solo cuando hay problemas específicos |

### Workflow Recomendado

1. **Lunes:** Revisar analíticas de la semana anterior
2. **Identificar:** URLs con problemas o bajo rendimiento
3. **Analizar:** Usar inspector para diagnosticar causas
4. **Optimizar:** Mejorar contenido/meta tags de páginas problemáticas
5. **Monitorear:** Verificar mejoras en semanas siguientes

### Alertas a Monitorear

- ⚠️ Disminución significativa (>20%) en páginas indexadas
- ⚠️ Aumento de errores en sitemaps
- ⚠️ CTR por debajo de 1% en páginas importantes
- ⚠️ Páginas clave sin impresiones en 30 días

## 🎓 Conceptos Clave

### Impresiones vs Clics
- **Impresión:** Tu sitio apareció en algún resultado, sin importar si el usuario lo vio
- **Clic:** El usuario efectivamente hizo clic en tu resultado
- **CTR = (Clics / Impresiones) × 100**

### Posición Promedio
- No es la posición visual, sino la **posición en la lista de resultados**
- Una posición "3" puede estar en la parte baja si hay ads encima
- Posiciones bajas (<20) necesitan aparecer en más resultados para mejorar

### Coverage vs Indexación
- **Coverage:** Qué páginas Google puede rastrear e indexar
- **Indexación:** Qué páginas realmente están en el índice de Google
- Una página puede ser "rastreable" pero no estar indexada por contenido duplicado/bajo valor

## 📝 Registro de Actividad

Todas las operaciones del inspector se registran en:
```
logs/search-engines/gsc-inspector_YYYYMMDD.log
```

Formato de logs:
```
[2024-01-15 10:30:45] INFO: Getting sitemaps for: sc-domain:juancarlosmacias.es
[2024-01-15 10:30:46] SUCCESS: Found 1 sitemaps
```

---

**Nota:** Esta herramienta complementa pero no reemplaza la interfaz web de Google Search Console. Para análisis avanzados, reportes de cobertura detallados o solicitudes de re-indexación, usar siempre la interfaz oficial.

**Última actualización:** 10 de abril de 2026
