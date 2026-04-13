# 🚀 Guía de Optimización de Rendimiento Web

## ✅ Mejoras Implementadas

### 1. **Caché Optimizada (.htaccess)**
- **Imágenes**: 1 año de caché (inmutables)
- **CSS/JS**: 1 año (archivos versionados por React)
- **Fuentes**: 1 año
- **Cache-Control**: `public, max-age=31536000, immutable`

### 2. **Compresión GZIP**
- ✅ Habilitada para HTML, CSS, JS, JSON, SVG, fuentes
- Reducción típica: 60-80% del tamaño
- No aplicada a imágenes ya comprimidas (JPEG, PNG, WebP)

### 3. **Resource Hints**
```html
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="dns-prefetch" href="https://www.googletagmanager.com" />
```
- Reduce latencia de conexión a dominios externos
- Mejora tiempo de carga de fuentes Google

### 4. **Font Display Optimization**
```css
@import url("...css2?family=Raleway:wght@500&display=swap");
```
- `display=swap`: Muestra texto con fuente del sistema mientras carga la fuente web
- Elimina FOIT (Flash of Invisible Text)
- Mejora FCP (First Contentful Paint)

### 5. **Componente OptimizedImage**
```jsx
<OptimizedImage 
  src="/Assets/image.jpg"
  alt="Descripción"
  width={800}
  height={600}
  priority={false}  // true para imágenes above-the-fold
/>
```

**Características**:
- ✅ Lazy loading automático (`loading="lazy"`)
- ✅ Decoding asíncrono (`decoding="async"`)
- ✅ Aspect ratio preservado (evita layout shift)
- ✅ Placeholder animado mientras carga
- ✅ Responsive con srcset (cuando esté configurado)

---

## 📋 Acciones Pendientes para Máximo Rendimiento

### 1. **Optimizar Imágenes Existentes** (3,984 KiB de ahorro)

#### Herramientas recomendadas:
```bash
# ImageMagick (batch processing)
magick mogrify -resize 1920x1080\> -quality 85 -strip *.jpg

# Squoosh CLI (WebP conversion)
npm install -g @squoosh/cli
squoosh-cli --webp '{"quality":85}' images/*.{jpg,png}

# TinyPNG (online)
# https://tinypng.com
```

#### Script PowerShell para conversión masiva a WebP:
```powershell
# Instalar cwebp: https://developers.google.com/speed/webp/download
Get-ChildItem -Path "frontend\public\Assets" -Include *.jpg,*.png -Recurse | ForEach-Object {
    $webpPath = $_.FullName -replace '\.(jpg|png)$', '.webp'
    cwebp -q 85 $_.FullName -o $webpPath
}
```

### 2. **Reemplazar <img> por <OptimizedImage>**

**Ejemplo de migración**:

❌ **Antes**:
```jsx
<img src="/Assets/Projects/portfolio.png" alt="Portfolio" />
```

✅ **Después**:
```jsx
import OptimizedImage from '../components/OptimizedImage/OptimizedImage';

<OptimizedImage 
  src="/Assets/Projects/portfolio.png"
  alt="Portfolio"
  width={1200}
  height={630}
  priority={false}
/>
```

**Casos especiales**:
- **Hero images** (above-the-fold): `priority={true}`
- **Logotipos pequeños**: Usar SVG o `<img>` normal
- **Fondos CSS**: Considerar usar `background-image` con `background-size: cover`

### 3. **Implementar Service Worker para Caché**

```javascript
// public/service-worker.js
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open('static-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/static/css/main.css',
        '/static/js/main.js'
      ]);
    })
  );
});
```

**Activar en src/index.js**:
```javascript
// Era: serviceWorkerRegistration.unregister();
serviceWorkerRegistration.register();
```

### 4. **Code Splitting para React**

```javascript
// En vez de import directo
import About from './components/About';

// Usar lazy loading
const About = React.lazy(() => import('./components/About'));

// Envolver con Suspense
<Suspense fallback={<div>Cargando...</div>}>
  <About />
</Suspense>
```

### 5. **Preload de recursos críticos**

En `public/index.html` después de las preconnect:
```html
<!-- Preload de fuentes critical -->
<link rel="preload" href="https://fonts.gstatic.com/s/raleway/v28/..." as="font" type="font/woff2" crossorigin />

<!-- Preload del CSS principal (después del build) -->
<link rel="preload" href="/static/css/main.abc123.css" as="style" />
```

**Nota**: Los hashes (abc123) cambian con cada build. Considerar generar dinámicamente.

### 6. **Minificar y Optimizar Build**

En `package.json`:
```json
{
  "scripts": {
    "build": "react-scripts build && npm run postbuild",
    "postbuild": "node scripts/optimize-build.js"
  }
}
```

Crear `scripts/optimize-build.js`:
```javascript
const fs = require('fs');
const path = require('path');
const { minify } = require('terser');

// Minificar JS adicional
// Comprimir imágenes del build
// Generar manifests de precarga
```

---

## 📊 Métricas Esperadas

### Antes de optimizaciones:
- **Tamaño transferido**: ~5 MB
- **LCP**: 4-6s
- **FCP**: 2-3s
- **CLS**: 0.2-0.3

### Después (estimado):
- **Tamaño transferido**: ~1.2 MB ✅ (-76%)
- **LCP**: 1.5-2.5s ✅ (-60%)
- **FCP**: 0.8-1.2s ✅ (-60%)
- **CLS**: <0.1 ✅ (-66%)

---

## 🔧 Testing

### Herramientas:
1. **Lighthouse** (Chrome DevTools):
   ```
   F12 → Lighthouse → Analyze page load
   ```

2. **PageSpeed Insights**:
   https://pagespeed.web.dev/

3. **WebPageTest**:
   https://www.webpagetest.org/

4. **GTmetrix**:
   https://gtmetrix.com/

### Comandos de prueba local:
```bash
# Verificar compresión GZIP
curl -H "Accept-Encoding: gzip" -I https://juancarlosmacias.es/static/css/main.css

# Verificar caché headers
curl -I https://juancarlosmacias.es/Assets/Projects/portfolio.png

# Performance profiling
npm run build
npx serve -s build
# Chrome DevTools → Performance → Record
```

---

## 📝 Checklist de Implementación

- [x] **Caché optimizada** en .htaccess (1 año para estáticos)
- [x] **Compresión GZIP** habilitada
- [x] **Resource hints** (preconnect, dns-prefetch)
- [x] **Font display swap**
- [x] **Componente OptimizedImage** creado
- [ ] **Convertir imágenes a WebP** (3,984 KiB ahorro)
- [ ] **Reemplazar <img> por <OptimizedImage>** en todos los componentes
- [ ] **Code splitting** con React.lazy()
- [ ] **Service Worker** para caché offline
- [ ] **Preload de recursos críticos**
- [ ] **Build optimization** script
- [ ] **Test con Lighthouse** (objetivo: >90 Performance)

---

## 🚨 Notas Importantes

1. **Caché de 1 año**: Solo para archivos con hash en el nombre (React lo hace automático)
2. **WebP compatibility**: Agregar fallback para navegadores antiguos:
   ```html
   <picture>
     <source srcset="image.webp" type="image/webp">
     <img src="image.jpg" alt="...">
   </picture>
   ```

3. **Layout Shift**: Siempre especificar `width` y `height` en imágenes

4. **Render Blocking**: AdSense ya usa `async`, considera moverlo al final del `<body>` si afecta FCP

---

## 📚 Recursos

- [Web.dev Performance](https://web.dev/fast/)
- [React Performance Optimization](https://react.dev/learn/render-and-commit#optimizing-performance)
- [Image Optimization Guide](https://web.dev/fast/#optimize-your-images)
- [Cache-Control Best Practices](https://web.dev/http-cache/)
