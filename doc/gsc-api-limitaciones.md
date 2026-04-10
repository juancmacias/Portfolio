# Limitaciones de Google Search Console API con Service Account

## 📋 Resumen

La **Google Search Console API** tiene limitaciones importantes cuando se usa con **Service Account** (autenticación servidor a servidor):

## ✅ Disponible con Service Account

| Funcionalidad | Método API | Estado |
|--------------|------------|--------|
| **Lista de Sitemaps** | `sitemaps.list()` | ✅ Disponible |
| **Estadísticas de Búsqueda** | `searchanalytics.query()` | ✅ Disponible |
| **Verificación de Sitio** | `sites.get()` | ✅ Disponible |

## ❌ NO Disponible con Service Account

| Funcionalidad | Requiere | Motivo |
|--------------|----------|--------|
| **Index Coverage Report** | OAuth 2.0 Usuario | No expuesto en API v1 |
| **URL Inspection API** | OAuth 2.0 Usuario | Requiere permisos de propietario |
| **Crawl Errors Report** | Deprecado | API eliminada en 2019 |
| **Motivos de No Indexación** | Interfaz Web | No disponible en API |

## 🔍 Información del Index Coverage Report

El reporte que muestra:
```
✗ Página alternativa con etiqueta canónica adecuada - 7 páginas
✗ Soft 404 - 1 página  
✗ Descubierta: actualmente sin indexar - 6 páginas
✗ Página con redirección - 0 páginas
✗ Duplicada: Google eligió diferente canónica - 0 páginas
✗ Rastreada: actualmente sin indexar - 0 páginas
```

**Solo está disponible en**:
- Interfaz web de Google Search Console: https://search.google.com/search-console
- Sección: **Indexación → Páginas**

## 🛠️ Solución Híbrida

Nuestra herramienta combina:

### API (Automatizado)
- ✅ Ver sitemaps enviados y estado
- ✅ Analíticas de búsqueda (clics, impresiones)
- ✅ Comparar URLs en sitemap vs URLs con tráfico

### Interfaz Web (Manual)
- 🌐 Index Coverage Report completo
- 🌐 Motivos detallados de no indexación
- 🌐 Validación de correcciones
- 🌐 URL Inspection tool individual

## 📊 Análisis que SÍ Podemos Hacer

Con los datos disponibles en API, podemos identificar:

### 1. URLs en Sitemap pero sin Tráfico
```php
// Comparar URLs del sitemap.xml vs analytics
$sitemapUrls = getAllUrlsFromSitemap();
$analyticsUrls = getUrlsWithImpressions(); // Últimos 90 días

$noTraffic = array_diff($sitemapUrls, $analyticsUrls);
// Estas URLs pueden tener problemas de indexación
```

### 2. URLs con Impresiones pero sin Clics
```php
// URLs indexadas pero no atractivas
$lowCtr = array_filter($analyticsData, function($row) {
    return $row['ctr'] < 0.01; // CTR < 1%
});
```

### 3. Análisis de Tendencias
- Comparar períodos (últimos 7 vs 30 días)
- Detectar caídas súbitas de impresiones
- Identificar páginas que perdieron visibilidad

## 🚀 Workflow Recomendado

### Paso 1: Usar Nuestra Herramienta (API)
1. Ir a **Admin → Herramientas SEO → Inspector GSC**
2. Ver sitemaps enviados y validados
3. Revisar analíticas (páginas con/sin tráfico)
4. Identificar URLs sospechosas

### Paso 2: Usar Search Console Web (Manual)
1. Ir a https://search.google.com/search-console
2. Seleccionar propiedad: **juancarlosmacias.es**
3. Ir a **Indexación → Páginas**
4. Ver motivos detallados:
   - Soft 404
   - Descubierta sin indexar
   - Canónica incorrecta
   - Redirecciones
5. Usar **URL Inspection Tool** para URLs específicas

### Paso 3: Validar Correcciones
1. Corregir problemas identificados
2. En Search Console web: **Validar corrección**
3. Esperar re-rastreo (puede tardar días/semanas)
4. Monitorear en nuestro Inspector (analytics)

## 📖 Motivos Comunes de No Indexación

| Motivo GSC | Causa Común | Solución |
|-----------|-------------|----------|
| **Soft 404** | Página vacía o contenido insuficiente | Agregar contenido relevante (300+ palabras) |
| **Descubierta sin indexar** | Google encontró la URL pero no la rastrea todavía | Esperar o solicitar indexación manual |
| **Canónica incorrecta** | Tag canonical apunta a otra URL | Revisar `<link rel="canonical">` |
| **Rastreada sin indexar** | Contenido de baja calidad | Mejorar contenido o eliminar página |
| **Bloqueada por robots.txt** | `Disallow:` en robots.txt | Revisar robots.txt |
| **Noindex en meta** | `<meta name="robots" content="noindex">` | Quitar meta tag |

## 🔗 Enlaces Útiles

- **Search Console**: https://search.google.com/search-console
- **API Documentation**: https://developers.google.com/webmaster-tools
- **OAuth Setup**: https://console.cloud.google.com/apis/credentials
- **Index Coverage Help**: https://support.google.com/webmasters/answer/7440203

## 💡 Alternativa: OAuth User Authentication

Si quisieras acceso completo a URL Inspection API, necesitarías:

1. Cambiar de Service Account a **OAuth 2.0**
2. Usuario debe autorizar acceso con Google Sign-In
3. Implementar flujo OAuth en admin panel
4. Almacenar refresh tokens de usuario

**Complejidad**: Alta  
**Beneficio**: Acceso a URL Inspection API  
**Limitación**: Cada usuario admin debe autorizar individualmente

## ✅ Conclusión

Por ahora, la **solución híbrida** es óptima:
- Usa nuestra herramienta para monitoreo automático
- Usa Search Console web para diagnóstico detallado
- No requiere OAuth complejo
- Menos mantenimiento

---

**Actualizado**: 10 de abril de 2026
