# Implementación de Google AdSense con Cumplimiento RGPD/GDPR

## 📋 Índice
1. [Análisis de Arquitectura Actual](#análisis-de-arquitectura-actual)
2. [Requisitos RGPD y AdSense](#requisitos-rgpd-y-adsense)
3. [Solución Recomendada: Google Funding Choices](#solución-recomendada-google-funding-choices)
4. [Plan de Implementación](#plan-de-implementación)
5. [Código de Implementación](#código-de-implementación)
6. [Configuración en Google AdSense](#configuración-en-google-adsense)
7. [Ubicaciones de Anuncios Recomendadas](#ubicaciones-de-anuncios-recomendadas)
8. [Testing y Validación](#testing-y-validación)
9. [Consideraciones de Rendimiento](#consideraciones-de-rendimiento)
10. [Checklist de Implementación](#checklist-de-implementación)

---

## 1. Análisis de Arquitectura Actual

### 🏗️ Arquitectura Híbrida PHP + React

**Entorno de Desarrollo:**
- `frontend/`: React SPA puro (localhost:3000)
- Build output: `frontend/build/`

**Entorno de Producción (SSR):**
- `front_php/`: PHP templates + React hydration
- Entry point: `front_php/index.php`
- Templates: `front_php/templates/Layout.php`, `ArticleView.php`
- Build React: `front_php/build/`

### 🎯 Flujo de Renderizado

```
Usuario → Apache → front_php/index.php
                      ↓
                  Template PHP (SSR)
                      ↓
                  HTML con placeholders
                      ↓
                  React hydration (CSR)
                      ↓
                  App Interactiva
```

### 📊 Páginas a Monetizar

1. **Artículos individuales** (`/articles/:slug`) - ⭐ Alta prioridad
   - Mayor tiempo de permanencia
   - Contenido de valor
   - Ideal para anuncios In-article y Display

2. **Listado de artículos** (`/articles`) - Media prioridad
   - Tráfico de exploración
   - Anuncios tipo feed

3. **Home** (`/`) - Media prioridad
   - Primera impresión
   - Anuncios discretos (sidebar, footer)

4. **Proyectos** (`/project`) - Baja prioridad
   - Enfoque portfolio
   - Anuncios no intrusivos

---

## 2. Requisitos RGPD y AdSense

### ⚖️ Obligaciones Legales (RGPD/GDPR)

#### **Artículo 6 RGPD - Base Legal**
El consentimiento debe ser:
- ✅ **Libre**: No puede ser obligatorio para acceder al contenido (excepto con "Consent Wall")
- ✅ **Específico**: Diferenciar entre anuncios personalizados y no personalizados
- ✅ **Informado**: Explicar claramente qué datos se recopilan y para qué
- ✅ **Inequívoco**: Acción afirmativa clara (checkbox, botón "Aceptar")

#### **Artículo 7 RGPD - Condiciones del Consentimiento**
- Poder retirar consentimiento fácilmente
- Registro de consentimientos con timestamp
- Información accesible antes del consentimiento

### 🔐 Requisitos de Google AdSense

#### **Políticas de Privacidad Obligatorias:**
1. **Cookies de Google AdSense**
   - DoubleClick cookies
   - DART cookies
   - Cookies de publicidad personalizadas

2. **Proveedores de Publicidad de Google**
   - Lista actualizada: https://adssettings.google.com/authenticated
   - Derecho a opt-out

3. **Google Consent Mode v2** (Obligatorio desde marzo 2024)
   - `ad_storage`: Cookies de publicidad
   - `ad_user_data`: Datos de usuario para publicidad
   - `ad_personalization`: Personalización de anuncios
   - `analytics_storage`: Google Analytics

#### **CMP (Consent Management Platform) Certificado**
Debe estar en la lista de proveedores certificados por Google:
- https://support.google.com/fundingchoices/answer/9995435

---

## 3. Solución Recomendada: Gestión de Consentimiento Integrada en AdSense

### ✅ ¿Por Qué la Gestión de Consentimiento de AdSense?

> **⚠️ Actualización 2024**: Google Funding Choices ahora está integrado directamente en la pestaña "Privacidad y mensajes" de AdSense, AdMob y Ad Manager. Ya no es una plataforma separada.

**Ventajas:**

1. **Certificación Google**: Integración nativa con AdSense
2. **Consent Mode v2**: Soporte automático (obligatorio desde marzo 2024)
3. **Gratis**: Sin costes adicionales
4. **Multi-idioma**: Soporte español automático
5. **Cumplimiento RGPD**: Certificado IAB TCF v2.2
6. **Todo-en-uno**: Configuración desde el panel de AdSense

### 🆚 Alternativas Evaluadas

| CMP | Precio | Certificación | Complejidad | Recomendación |
|-----|---------|--------------|-------------|---------------|
| **AdSense Consent (ex-Funding Choices)** | Gratis | ✅ Nativa | Baja | ⭐⭐⭐⭐⭐ |
| Cookiebot | €9/mes | ✅ IAB TCF | Media | ⭐⭐⭐ |
| OneTrust | €200+/mes | ✅ IAB TCF | Alta | ⭐⭐ (Empresas) |
| Cookie Notice (manual) | Gratis | ❌ No certificado | Alta | ❌ No recomendado |

---

## 4. Plan de Implementación

### 📅 Fases de Implementación

#### **Fase 1: Configuración AdSense + Gestión de Consentimiento** (1-2 horas)
1. Crear cuenta Google AdSense (si no existe)
2. Verificar dominio `juancarlosmacias.es`
3. Configurar gestión de consentimiento en **AdSense → Privacidad y mensajes**
4. Crear mensaje de consentimiento personalizado para UE/EEA
5. Obtener código de AdSense con soporte Consent Mode v2

#### **Fase 2: Actualizar Política de Privacidad** (30 min)
1. Añadir sección "Publicidad y Cookies"
2. Incluir lista de proveedores de Google
3. Explicar uso de datos para publicidad
4. Añadir enlace a configuración de anuncios

#### **Fase 3: Implementación en Código** (2-3 horas)
1. Crear componente `AdSenseConsent.js`
2. Inyectar script en `<head>` (SSR y CSR)
3. Crear componente `AdUnit.js` reutilizable
4. Integrar en páginas prioritarias
5. Implementar lazy loading de anuncios

#### **Fase 4: Testing** (1 hora)
1. Verificar banner de consentimiento
2. Probar flujo "Aceptar" y "Rechazar"
3. Validar Consent Mode en Chrome DevTools
4. Comprobar carga de anuncios
5. Test en móvil y desktop

#### **Fase 5: Deploy y Monitoreo** (30 min)
1. Deploy a producción
2. Verificar en AdSense (72h para aprobación)
3. Configurar alertas de rendimiento
4. Monitorear CLS (Core Web Vitals)

---

## 5. Código de Implementación

### 📝 5.1. Script de AdSense + Gestión de Consentimiento

#### **Ubicación: `frontend/public/index.html` y `front_php/templates/Layout.php`**

> **📌 Importante**: El código exacto lo obtendrás desde **AdSense → Privacidad y mensajes** tras configurar el mensaje de consentimiento. El código generado incluirá automáticamente el soporte para Consent Mode v2.

**Código de ejemplo (AdSense 2024+):**

```html
<!-- Google AdSense con Consent Mode v2 -->
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXX"
     crossorigin="anonymous"></script>

<!-- Script de Consentimiento (se genera automáticamente desde AdSense) -->
<!-- Este código aparecerá en AdSense → Privacidad y mensajes → Código del mensaje -->
<script async src="https://fundingchoicesmessages.google.com/i/pub-XXXXXXXXXX?ers=1" nonce="RANDOM_NONCE"></script>
<script nonce="RANDOM_NONCE">(function() {function signalGooglefcPresent() {if (!window.frames['googlefcPresent']) {if (document.body) {const iframe = document.createElement('iframe'); iframe.style = 'width: 0; height: 0; border: none; z-index: -1000; left: -1000px; top: -1000px;'; iframe.style.display = 'none'; iframe.name = 'googlefcPresent'; document.body.appendChild(iframe);} else {setTimeout(signalGooglefcPresent, 0);}}}signalGooglefcPresent();})();</script>
```

**⚠️ Notas**:
- Reemplazar `ca-pub-XXXXXXXXXX` con tu Publisher ID real de AdSense
- El código del mensaje de consentimiento se genera automáticamente al configurar en AdSense
- Si no ves el script del mensaje, verifica que hayas creado un mensaje en "Privacidad y mensajes"

### 🎨 5.2. Componente React: AdSenseConsent

#### **Ubicación: `frontend/src/components/AdSense/AdSenseConsent.js`**

```javascript
import { useEffect } from 'react';

/**
 * Componente para gestionar Google AdSense Consent (Funding Choices)
 * Se integra automáticamente con Consent Mode v2
 */
function AdSenseConsent() {
  useEffect(() => {
    // Verificar si el script de Funding Choices está cargado
    const checkConsentLoaded = setInterval(() => {
      if (window.googlefc) {
        console.log('✅ Google Funding Choices cargado');
        clearInterval(checkConsentLoaded);
        
        // Listener para cambios en el consentimiento
        window.googlefc.callbackQueue.push({
          'CONSENT_DATA_READY': () => {
            const consentData = window.googlefc.getConsentData();
            console.log('📊 Consent Data:', consentData);
            
            // Puedes guardar el consentimiento en tu backend si quieres registro
            // fetch('/api/consent-log', { method: 'POST', body: JSON.stringify(consentData) });
          }
        });
      }
    }, 100);

    // Cleanup
    return () => clearInterval(checkConsentLoaded);
  }, []);

  return null; // Este componente no renderiza nada visual
}

export default AdSenseConsent;
```

### 📺 5.3. Componente React: AdUnit (Anuncio Reutilizable)

#### **Ubicación: `frontend/src/components/AdSense/AdUnit.js`**

```javascript
import React, { useEffect, useRef } from 'react';

/**
 * Componente de anuncio AdSense reutilizable
 * 
 * @param {string} slot - ID del slot de AdSense (data-ad-slot)
 * @param {string} format - Formato del anuncio: 'auto', 'rectangle', 'vertical', 'horizontal'
 * @param {boolean} responsive - Si el anuncio es responsive (default: true)
 * @param {string} style - Estilos CSS inline
 */
function AdUnit({ 
  slot, 
  format = 'auto', 
  responsive = true, 
  style = { display: 'block' },
  className = ''
}) {
  const adRef = useRef(null);

  useEffect(() => {
    // Esperar a que AdSense esté disponible
    const pushAd = () => {
      try {
        if (window.adsbygoogle && adRef.current) {
          // Solo push si no se ha inicializado antes
          if (!adRef.current.dataset.adsbygoogleStatus) {
            (window.adsbygoogle = window.adsbygoogle || []).push({});
            console.log('✅ AdSense ad pushed:', slot);
          }
        }
      } catch (e) {
        console.error('❌ Error loading AdSense:', e);
      }
    };

    // Esperar 100ms para asegurar que el DOM está listo
    const timer = setTimeout(pushAd, 100);

    return () => clearTimeout(timer);
  }, [slot]);

  return (
    <div className={`adsense-container ${className}`}>
      <ins
        ref={adRef}
        className="adsbygoogle"
        style={style}
        data-ad-client="ca-pub-XXXXXXXXXX"
        data-ad-slot={slot}
        data-ad-format={format}
        data-full-width-responsive={responsive ? 'true' : 'false'}
      />
    </div>
  );
}

export default AdUnit;
```

### 🎯 5.4. Implementación en ArticleView (Artículos)

#### **Ubicación: `frontend/src/components/Articles/ArticleView.js`**

```javascript
import AdUnit from '../AdSense/AdUnit';
import AdSenseConsent from '../AdSense/AdSenseConsent';

function ArticleView() {
  // ... código existente ...

  return (
    <Container className="article-view-page">
      <AdSenseConsent />
      
      {/* Anuncio superior (después del título) */}
      <Row className="mb-4">
        <Col>
          <AdUnit 
            slot="1234567890" 
            format="horizontal"
            style={{ display: 'block', textAlign: 'center', minHeight: '90px' }}
          />
        </Col>
      </Row>

      {/* Contenido del artículo */}
      <Row>
        <Col lg={8}>
          {/* Título, metadata, etc. */}
          {article && (
            <>
              <h1>{article.title}</h1>
              
              {/* Anuncio In-Article (después del primer párrafo) */}
              <AdUnit 
                slot="0987654321" 
                format="fluid"
                className="my-4"
                style={{ display: 'block' }}
              />
              
              {/* Contenido Markdown */}
              <SafeMarkdownRenderer content={article.content} />
            </>
          )}
        </Col>

        {/* Sidebar con anuncio vertical */}
        <Col lg={4} className="d-none d-lg-block">
          <div className="sticky-top" style={{ top: '100px' }}>
            <AdUnit 
              slot="1122334455" 
              format="vertical"
              style={{ display: 'block', minHeight: '600px' }}
            />
          </div>
        </Col>
      </Row>

      {/* Anuncio inferior (antes del footer) */}
      <Row className="mt-5">
        <Col>
          <AdUnit 
            slot="5566778899" 
            format="horizontal"
            style={{ display: 'block', textAlign: 'center', minHeight: '90px' }}
          />
        </Col>
      </Row>
    </Container>
  );
}
```

### 🖥️ 5.5. Integración en SSR (PHP Template)

#### **Ubicación: `front_php/templates/Layout.php`**

```php
<?php
// Obtener Publisher ID desde config o env
$adsensePublisherId = getenv('ADSENSE_PUBLISHER_ID') ?: 'ca-pub-XXXXXXXXXX';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google AdSense -->
    <script async 
            src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($adsensePublisherId) ?>"
            crossorigin="anonymous"></script>
    
    <!-- Google Funding Choices (CMP) -->
    <?php 
    // Extraer solo el número del Publisher ID (pub-XXXXXXXXXX)
    $pubId = str_replace('ca-', '', $adsensePublisherId);
    ?>
    <script async 
            src="https://fundingchoicesmessages.google.com/i/<?= htmlspecialchars($pubId) ?>?ers=1" 
            nonce="<?= bin2hex(random_bytes(16)) ?>"></script>
    <script nonce="<?= bin2hex(random_bytes(16)) ?>">
    (function() {
        function signalGooglefcPresent() {
            if (!window.frames['googlefcPresent']) {
                if (document.body) {
                    const iframe = document.createElement('iframe');
                    iframe.style = 'width: 0; height: 0; border: none; z-index: -1000; left: -1000px; top: -1000px;';
                    iframe.style.display = 'none';
                    iframe.name = 'googlefcPresent';
                    document.body.appendChild(iframe);
                } else {
                    setTimeout(signalGooglefcPresent, 0);
                }
            }
        }
        signalGooglefcPresent();
    })();
    </script>
    
    <!-- Resto de meta tags -->
    <?= $head ?? '' ?>
</head>
<body>
    <?= $content ?? '' ?>
    
    <!-- Scripts React -->
    <script src="/build/static/js/main.js"></script>
</body>
</html>
```

---

## 6. Configuración en Google AdSense

### 🔧 Paso a Paso en AdSense Console (2024+)

#### **6.1. Configurar Gestión de Consentimiento en AdSense**

> **💡 Actualización**: Desde 2024, la configuración de consentimiento se gestiona directamente desde la pestaña "Privacidad y mensajes" de AdSense.

**Pasos:**

1. **Acceder a la configuración**:
   - Ir a **Google AdSense Console**: https://www.google.com/adsense/
   - Click en **"Privacidad y mensajes"** (menú lateral izquierdo)
   - Seleccionar **"Gestión de consentimiento de la UE"**

2. **Crear mensaje de consentimiento**:
   - Click en **"Crear un mensaje"** o **"Gestionar mensajes"**
   - Seleccionar **"Mensaje de consentimiento"** (no "Mensaje de financiación")

3. **Configurar detalles del mensaje**:
   ```
   Ubicación: EEA (Espacio Económico Europeo) + Reino Unido
   Idioma principal: Español
   Idiomas adicionales: Inglés (recomendado para turistas)
   Diseño: Adaptativo (se ajusta al diseño de tu sitio)
   Posición: Banner inferior (menos intrusivo)
   ```

4. **Verificar Consent Mode v2**:
   - Asegúrate de que esté activado **"Consent Mode v2"** (debería estar por defecto)
   - Verifica que los parámetros estén configurados:
     - ✅ `ad_storage`
     - ✅ `ad_user_data` 
     - ✅ `ad_personalization`
     - ✅ `analytics_storage`

#### **6.2. Personalizar Mensaje de Consentimiento**

**Texto recomendado:**
```
Título: Personaliza tu experiencia

Mensaje:
Utilizamos cookies y tecnologías similares para personalizar anuncios 
y contenido, medir el rendimiento de anuncios y contenido, y obtener 
información sobre la audiencia. Al hacer clic en "Aceptar", aceptas 
el uso de estas tecnologías. Puedes gestionar tus preferencias en 
cualquier momento.

Botones:
- Aceptar (acepta todos)
- Rechazar (anuncios no personalizados)
- Más opciones (abre configuración detallada)
```

#### **6.3. Verificar Configuración de Consent Mode v2**

> **✅ Automático**: Desde 2024, Consent Mode v2 se configura automáticamente al crear el mensaje de consentimiento.

**Verificación manual** (si necesitas revisar):

1. En **Privacidad y mensajes** → Click en tu mensaje creado → **"Configuración avanzada"**
2. Verificar que Consent Mode v2 esté **activado**
3. Confirmar parámetros habilitados:
   - ✅ `ad_storage`: Cookies de publicidad
   - ✅ `ad_user_data`: Datos de usuario para ads
   - ✅ `ad_personalization`: Personalización de anuncios
   - ✅ `analytics_storage`: Google Analytics (opcional)

4. **Valores por defecto** (se configuran automáticamente):
   ```
   Antes del consentimiento (denied):
   ad_storage: 'denied'
   ad_user_data: 'denied'
   ad_personalization: 'denied'
   analytics_storage: 'denied'
   
   Tras aceptar (granted):
   ad_storage: 'granted'
   ad_user_data: 'granted'
   ad_personalization: 'granted'
   analytics_storage: 'granted'
   ```

**No necesitas configurar esto manualmente** - AdSense lo hace automáticamente al crear el mensaje.

#### **6.4. Crear Unidades de Anuncios**

1. Ir a **Anuncios** → **Por unidad de anuncio**
2. Crear las siguientes unidades:

**Unidad 1: Article Header (Horizontal)**
```
Nombre: Article_Header_Horizontal
Tipo: Display adaptable
Tamaño: Horizontal (728x90, 970x90, 970x250)
```

**Unidad 2: Article In-Feed (Fluid)**
```
Nombre: Article_InFeed_Fluid
Tipo: In-article
Tamaño: Adaptable fluido
```

**Unidad 3: Sidebar Vertical**
```
Nombre: Sidebar_Vertical_Desktop
Tipo: Display adaptable
Tamaño: Vertical (300x600, 160x600, 120x600)
```

**Unidad 4: Article Footer (Horizontal)**
```
Nombre: Article_Footer_Horizontal
Tipo: Display adaptable
Tamaño: Horizontal (728x90, 320x100)
```

**Unidad 5: Homepage Feed (Multiplex)**
```
Nombre: Homepage_Feed_Multiplex
Tipo: Anuncios multiplex
Tamaño: Responsive
```

3. Copiar los **IDs de slot** (data-ad-slot) para cada unidad

---

## 7. Ubicaciones de Anuncios Recomendadas

### 📍 Artículos (Alta Prioridad)

```
┌─────────────────────────────────────┐
│  Header + Navbar                    │
├─────────────────────────────────────┤
│  Breadcrumb                         │
├─────────────────────────────────────┤
│  [AD] Horizontal Header             │ ← 728x90 / 970x90
├──────────────────┬──────────────────┤
│  Título          │                  │
│  Metadata        │  [AD]            │
│                  │  Sidebar         │ ← 300x600 (sticky)
│  Párrafo 1       │  Vertical        │
│                  │                  │
│  [AD]            │                  │
│  In-Article      │                  │ ← Fluid (después 1er párrafo)
│  Fluid           │                  │
│                  │                  │
│  Párrafo 2-N     │                  │
│                  │                  │
│  Contenido...    │                  │
│                  │                  │
├──────────────────┴──────────────────┤
│  [AD] Horizontal Footer             │ ← 728x90 / 320x100
├─────────────────────────────────────┤
│  Related Articles                   │
├─────────────────────────────────────┤
│  Footer                             │
└─────────────────────────────────────┘
```

### 🏠 Homepage

```
┌─────────────────────────────────────┐
│  Header + Hero Section              │
├──────────────────┬──────────────────┤
│  About Preview   │  [AD]            │
│                  │  Sidebar         │
│  Latest Projects │  Vertical        │
│                  │                  │
│  [AD]            │                  │
│  Multiplex       │                  │ ← Feed de anuncios
│  Feed            │                  │
│                  │                  │
│  Blog Preview    │                  │
├──────────────────┴──────────────────┤
│  Footer                             │
└─────────────────────────────────────┘
```

### 📊 Listado de Artículos

```
┌─────────────────────────────────────┐
│  Header                             │
├─────────────────────────────────────┤
│  [AD] Horizontal                    │
├──────────────────┬──────────────────┤
│  Article Card 1  │  [AD]            │
│  Article Card 2  │  Sidebar         │
│  Article Card 3  │  Vertical        │
│                  │                  │
│  [AD] Multiplex  │                  │
│  Feed            │                  │
│                  │                  │
│  Article Card 4  │                  │
│  Article Card 5  │                  │
├──────────────────┴──────────────────┤
│  Pagination                         │
├─────────────────────────────────────┤
│  Footer                             │
└─────────────────────────────────────┘
```

---

## 8. Testing y Validación

### ✅ Checklist de Testing

#### **8.1. Consent Banner**
- [ ] Banner aparece en primera visita
- [ ] Banner se muestra correctamente en móvil
- [ ] Botón "Aceptar" guarda consentimiento
- [ ] Botón "Rechazar" muestra anuncios no personalizados
- [ ] "Más opciones" abre configuración detallada
- [ ] Banner no vuelve a aparecer tras dar consentimiento
- [ ] Se puede revocar consentimiento desde Política de Privacidad

#### **8.2. Consent Mode v2**
- [ ] Verificar en Chrome DevTools:
  ```javascript
  // Consola de Chrome
  dataLayer.filter(e => e[0] === 'consent')
  
  // Debería mostrar:
  // ['consent', 'default', { ad_storage: 'denied', ... }]
  // ['consent', 'update', { ad_storage: 'granted', ... }] (tras aceptar)
  ```

- [ ] Extensión recomendada: **Google Tag Assistant**
  - https://tagassistant.google.com/

#### **8.3. Carga de Anuncios**
- [ ] Anuncios se cargan tras dar consentimiento
- [ ] Placeholders tienen altura mínima (evitar CLS)
- [ ] Anuncios son responsive en móvil
- [ ] No hay anuncios duplicados en misma página
- [ ] Anuncios se cargan de forma lazy (scroll)

#### **8.4. Rendimiento (Core Web Vitals)**

**Herramientas:**
- PageSpeed Insights: https://pagespeed.web.dev/
- Lighthouse (Chrome DevTools)

**Objetivos:**
- LCP (Largest Contentful Paint): < 2.5s
- FID (First Input Delay): < 100ms
- CLS (Cumulative Layout Shift): < 0.1 ⚠️ **Crítico con ads**

**Medición CLS:**
```javascript
// Agregar en consola de Chrome
new PerformanceObserver((list) => {
  for (const entry of list.getEntries()) {
    if (!entry.hadRecentInput) {
      console.log('CLS:', entry.value);
    }
  }
}).observe({type: 'layout-shift', buffered: true});
```

#### **8.5. Validación en AdSense**
- [ ] Account → **Sites** → Verificar estado (puede tardar 24-72h)
- [ ] Verificar en **Policy Center**: Sin violaciones
- [ ] Verificar en **Reports**: Impresiones registrándose

---

## 9. Consideraciones de Rendimiento

### ⚡ Optimizaciones Implementadas

#### **9.1. Lazy Loading de Anuncios**

```javascript
// En AdUnit.js - Agregar Intersection Observer
function AdUnit({ slot, format, responsive, style, className }) {
  const adRef = useRef(null);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsVisible(true);
          observer.disconnect();
        }
      },
      { rootMargin: '200px' } // Cargar 200px antes de ser visible
    );

    if (adRef.current) {
      observer.observe(adRef.current);
    }

    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    if (isVisible) {
      // Cargar anuncio solo cuando es visible
      try {
        if (window.adsbygoogle && adRef.current) {
          (window.adsbygoogle = window.adsbygoogle || []).push({});
        }
      } catch (e) {
        console.error('Error loading AdSense:', e);
      }
    }
  }, [isVisible, slot]);

  return (
    <div className={`adsense-container ${className}`}>
      <ins
        ref={adRef}
        className="adsbygoogle"
        style={style}
        data-ad-client="ca-pub-XXXXXXXXXX"
        data-ad-slot={slot}
        data-ad-format={format}
        data-full-width-responsive={responsive ? 'true' : 'false'}
      />
    </div>
  );
}
```

#### **9.2. Reservar Espacio para Anuncios (Evitar CLS)**

```css
/* En styles/adsense.css */
.adsense-container {
  min-height: 90px; /* Para horizontal */
  margin: 20px 0;
  background: #f8f9fa;
  border: 1px dashed #dee2e6;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.adsense-container.vertical {
  min-height: 600px;
}

.adsense-container.fluid {
  min-height: 250px;
}

/* Mostrar placeholder antes de cargar */
.adsense-container:empty::before {
  content: 'Anuncio';
  color: #adb5bd;
  font-size: 12px;
}

/* Ocultar placeholder cuando carga */
.adsense-container:not(:empty)::before {
  display: none;
}
```

#### **9.3. Async/Defer Scripts**

Scripts ya configurados como `async` (no bloquean el render):
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js">
```

#### **9.4. Limitar Anuncios por Página**

**Recomendaciones de Google:**
- ❌ No más de 3 anuncios Display por página
- ✅ 1 In-article ad por artículo
- ✅ 1 Multiplex ad en feeds
- ❌ No anuncios en `<header>` o `<footer>` globales (policy violation)

---

## 10. Checklist de Implementación

### 📋 Pre-Implementación

- [ ] Crear cuenta Google AdSense
- [ ] Verificar dominio `juancarlosmacias.es`
- [ ] Leer políticas de AdSense: https://support.google.com/adsense/answer/48182
- [ ] Actualizar Política de Privacidad
- [ ] Crear página "Gestionar Consentimiento" en footer

### 🔧 Configuración AdSense

- [ ] Ir a **AdSense → Privacidad y mensajes**
- [ ] Crear mensaje de consentimiento para UE/EEA
- [ ] Verificar que Consent Mode v2 esté activado (automático)
- [ ] Copiar código del mensaje generado por AdSense
- [ ] Crear 5 unidades de anuncios
- [ ] Copiar Publisher ID y Slot IDs
- [ ] Personalizar texto del mensaje en español

### 💻 Implementación Código

- [ ] Agregar script AdSense en `<head>` (dev + prod)
- [ ] Agregar script Funding Choices
- [ ] Crear `components/AdSense/AdSenseConsent.js`
- [ ] Crear `components/AdSense/AdUnit.js` con lazy loading
- [ ] Integrar en `ArticleView.js`
- [ ] Integrar en `Home.js`
- [ ] Integrar en `ArticlesPage.js`
- [ ] Crear CSS para placeholders (evitar CLS)
- [ ] Agregar variable env `ADSENSE_PUBLISHER_ID`

### 🧪 Testing Local

- [ ] Test consent banner en primera visita
- [ ] Test botón "Aceptar"
- [ ] Test botón "Rechazar"
- [ ] Verificar Consent Mode en DevTools
- [ ] Test carga de anuncios (puede mostrar placeholder en dev)
- [ ] Test responsive en móvil
- [ ] Medir CLS con Lighthouse

### 🚀 Deploy Producción

- [ ] Commit: `feat: Implementar Google AdSense con cumplimiento RGPD`
- [ ] Build frontend: `npm run build`
- [ ] Copiar a `front_php/build/`
- [ ] Verificar scripts en `front_php/templates/Layout.php`
- [ ] Deploy a servidor producción
- [ ] Limpiar caché del navegador

### ✅ Post-Deploy

- [ ] Verificar banner de consentimiento en producción
- [ ] Comprobar carga de anuncios (24-72h para aprobación)
- [ ] Verificar en AdSense Console (estado del sitio)
- [ ] Configurar alertas de rendimiento
- [ ] Monitorear ingresos en Dashboard (7-14 días para primeros datos)
- [ ] Ejecutar PageSpeed Insights
- [ ] Verificar CLS < 0.1

### 📊 Monitoreo Continuo

- [ ] Revisar AdSense Reports semanalmente
- [ ] Verificar Policy Center mensualmente
- [ ] Monitorear Core Web Vitals
- [ ] A/B testing de ubicaciones de anuncios
- [ ] Optimizar según CTR y RPM

---

## 📚 Recursos Adicionales

### Documentación Oficial
- **AdSense Policies**: https://support.google.com/adsense/answer/48182
- **Funding Choices Setup**: https://support.google.com/fundingchoices/answer/9180084
- **Consent Mode v2**: https://support.google.com/analytics/answer/9976101
- **RGPD Info**: https://gdpr.eu/cookies/

### Herramientas
- **Tag Assistant**: https://tagassistant.google.com/
- **PageSpeed Insights**: https://pagespeed.web.dev/
- **Google Publisher Toolbar**: https://chrome.google.com/webstore (extensión)

### Comunidad
- **AdSense Community**: https://support.google.com/adsense/community
- **Google Ad Manager Forum**: https://support.google.com/admanager/community

---

## ⚠️ Advertencias Importantes

1. **Clicks Propios**: ❌ NUNCA hacer click en tus propios anuncios (suspensión de cuenta)
2. **Click Fraud**: Implementar protección contra bots y clicks fraudulentos
3. **Contenido**: Asegurar que todo el contenido cumple con políticas de AdSense
4. **Cookies**: Obligatorio informar sobre cookies de terceros en Política de Privacidad
5. **Tiempo de Aprobación**: 24-72 horas para aprobación inicial, 7-14 días para ingresos

---

## 🎯 Próximos Pasos Recomendados

1. **Fase 1 (Inmediato)**: Configurar AdSense + Funding Choices
2. **Fase 2 (Esta semana)**: Implementar código en artículos
3. **Fase 3 (Próxima semana)**: Expandir a homepage y listados
4. **Fase 4 (Mes 1)**: Optimizar ubicaciones según métricas
5. **Fase 5 (Mes 2)**: Implementar anuncios automáticos (opcional)

---

## 💰 Estimación de Ingresos

**Factores clave:**
- Tráfico mensual
- Nicho (IA/Desarrollo = CPM alto)
- Geo (España/UE = CPM medio-alto)
- CTR (Click-Through Rate)
- Calidad del contenido

**Fórmula estimada:**
```
Ingresos mensuales = (Pageviews × CTR × CPC) + (Pageviews × RPM/1000)

Ejemplo (conservador):
- 10,000 pageviews/mes
- CTR: 1% (100 clicks)
- CPC: €0.30
- RPM: €2.00

= (100 × €0.30) + (10,000 × €2.00/1000)
= €30 + €20
= €50/mes
```

**Con crecimiento a 50k pageviews/mes: ~€250-400/mes**

---

**Fecha de creación**: 5 de febrero de 2026  
**Última actualización**: 5 de febrero de 2026  
**Versión**: 1.0.0  
**Autor**: GitHub Copilot + Juan Carlos Macías
