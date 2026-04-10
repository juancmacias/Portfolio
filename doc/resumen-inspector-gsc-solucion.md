# Resumen: Inspector GSC - Solución a Limitaciones API

## 📊 Problema Identificado

El usuario reportó que la herramienta **no muestra la información de Index Coverage** que aparece en Google Search Console web:
- ❌ Página alternativa con etiqueta canónica adecuada - 7 páginas
- ❌ Soft 404 - 1 página
- ❌ Descubierta: actualmente sin indexar - 6 páginas
- ❌ Página con redirección - 0 páginas
- ❌ Duplicada: Google eligió diferente canónica - 0 páginas
- ❌ Rastreada: actualmente sin indexar - 0 páginas

## 🔍 Causa Raíz

**Google Search Console API** con **Service Account** tiene limitaciones severas:

| Funcionalidad | Estado with Service Account |
|--------------|----------------------------|
| Sitemaps list | ✅ Disponible |
| Search Analytics | ✅ Disponible |
| **Index Coverage Report** | ❌ **NO disponible** |
| **URL Inspection API** | ❌ Requiere OAuth de usuario |

El Index Coverage Report solo está disponible en:
- Interfaz web de Search Console
- OAuth 2.0 con autorización de usuario (complejidad alta)

## ✅ Solución Implementada

### Enfoque Híbrido: API + Guía Web

He actualizado la herramienta para combinar automatización + acceso web:

### 1. Dashboard Mejorado
- ✅ Alerta clara sobre limitaciones de la API
- ✅ 4 tarjetas de acceso rápido: Sitemaps, Analíticas, Cobertura, Index Coverage
- ✅ Enlace directo a Search Console web

### 2. Nueva Pestaña: "Index Coverage"
Guía paso a paso para usar Search Console web:

**Paso 1: Acceso directo**
- Botón → "Abrir Search Console → Indexación → Páginas"
- URL pre-configurada: `sc-domain:juancarlosmacias.es`

**Paso 2: Tabla de Motivos**
Tabla completa con 6 motivos comunes:
| Motivo | Significado | Solución |
|--------|-------------|----------|
| Soft 404 | Contenido escaso | Agregar 300+ palabras |
| Descubierta sin indexar | Pendiente rastreo | Esperar o solicitar indexación |
| Canónica incorrecta | Duplicate content | Revisar `<link rel="canonical">` |
| Rastreada sin indexar | Baja calidad | Mejorar contenido significativamente |
| ... | ... | ... |

**Paso 3: URL Inspection Tool**
- Cómo inspeccionar URLs individuales
- Botón "Solicitar indexación"
- Interpretar resultados

**Paso 4: Validar Correcciones**
- Proceso completo de validación
- Cómo monitorear re-rastreos
- Notificaciones por email

### 3. Enlaces Directos en Todas las Secciones

**Sitemaps:**
- Botón → "Ver en Search Console" (sección sitemaps)

**Analíticas:**
- Botón → "Ver en Search Console" (rendimiento)
- Filtros: 7, 30, 90 días
- Alerta: "URLs con 0 impresiones pueden tener problemas → Ver Index Coverage"

**Coverage:**
- Análisis básico (sitemaps enviados vs páginas indexadas)

### 4. Workflow Recomendado

```
1. Usar Inspector GSC (esta herramienta)
   ↓ Monitoreo automático de sitemaps y analíticas
   
2. Usar Search Console Web
   ↓ Diagnóstico detallado de no indexación
   
3. Corregir Problemas
   ↓ Modificar sitio (contenido, canónicas, etc.)
   
4. Validar en Search Console
   ↓ Botón "Validar corrección" → esperar re-rastreo
   
5. Monitorear en Inspector GSC
   ↓ Tab Analíticas → verificar aumento de impresiones
```

## 📁 Archivos Modificados

### 1. `admin/pages/gsc-inspector.php`
**Antes:**
- 3 pestañas: Dashboard, Sitemaps, Analíticas, Cobertura
- Sin explicación de limitaciones
- Sin enlaces a Search Console

**Después:**
- 5 pestañas: + "Index Coverage"
- Alerta de limitaciones en dashboard
- Tabla explicativa de motivos de no indexación
- Enlaces directos a Search Console en cada sección
- Filtros de período en analíticas (7/30/90 días)
- Workflow visual completo

### 2. `doc/gsc-api-limitaciones.md` (NUEVO)
Documentación técnica completa:
- Tabla comparativa API disponible/no disponible
- Explicación de Service Account vs OAuth
- Análisis alternativosque SÍ podemos hacer
- Workflow recomendado
- Motivos comunes de no indexación con soluciones
- Enlaces a recursos oficiales

## 🎯 Para Resolver Problemas Actuales

### Soft 404 (1 página)
1. Ir a Search Console → Indexación → Páginas
2. Click en "Soft 404" → ver lista de URLs
3. Para cada URL:
   - Agregar mínimo 300 palabras de contenido relevante
   - Asegurar que tiene título, meta description, imágenes
   - O eliminar la página si no es útil
4. Validar corrección en Search Console

### Descubierta sin Indexar (6 páginas)
1. Ver URLs afectadas en Search Console
2. Opciones:
   - **Esperar:** Google las rastreará eventualmente (semanas)
   - **Forzar:** URL Inspection Tool → "Solicitar indexación" (solo 10/día)
   - **Verificar:** Que no estén bloqueadas en robots.txt
3. Monitorear en 2-4 semanas

### Páginas Alternativas con Canónica (7 páginas)
1. Verificar si es esperado (ej: versiones con parámetros)
2. Si NO es esperado:
   - Revisar tag `<link rel="canonical" href="...">`
   - Asegurar que apunta a la versión correcta
3. Si ES esperado:
   - ✅ Normal, no requiere acción

## 🚀 Próximos Pasos

1. **Subir archivos al servidor:**
   - `admin/pages/gsc-inspector.php` (actualizado)
   - `doc/gsc-api-limitaciones.md` (nuevo)

2. **Probar la herramienta:**
   - Ir a Admin → Herramientas SEO → Inspector GSC
   - Click en pestaña "Index Coverage"
   - Seguir la guía paso a paso

3. **Usar Search Console Web:**
   - Click en "Abrir Search Console"
   - Revisar motivos de no indexación
   - Tomar acciones correctivas

4. **Monitorear resultados:**
   - Después de correcciones, volver a Inspector GSC
   - Tab "Analíticas" → verificar aumento de impresiones
   - Puede tardar días/semanas en reflejarse

## 💡 Notas Importantes

- **API Limitations son permanentes:** No hay forma de obtener Index Coverage via API con Service Account
- **OAuth Alternative:** Posible pero complejo (requiere autorización de cada usuario)
- **Enfoque híbrido es óptimo:** Automatización donde es posible + guía clara para web interface
- **Search Console web es la herramienta oficial:** Siempre será más completa que la API

## 📚 Referencias

- Search Console: https://search.google.com/search-console
- API Documentation: https://developers.google.com/webmaster-tools
- Index Coverage Help: https://support.google.com/webmasters/answer/7440203
- Documentación local: `doc/gsc-api-limitaciones.md`

---

**Fecha:** 10 de abril de 2026  
**Estado:** ✅ Implementación completa
