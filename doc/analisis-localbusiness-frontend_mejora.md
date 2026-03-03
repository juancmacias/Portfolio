# Análisis: datos estructurados `LocalBusiness` para `frontend_mejora`

Fecha: 2026-02-27  
Referencia oficial: Google Search Central — LocalBusiness (ES-419)  
URL: https://developers.google.com/search/docs/appearance/structured-data/local-business?hl=es-419#guidelines

## 1) Objetivo

Evaluar si en `frontend_mejora/` se puede incorporar marcado estructurado tipo `LocalBusiness` (o subtipo) cumpliendo lineamientos de Google, y dejar un plan de implementación posterior.

---

## 2) Estado actual en `frontend_mejora`

### Hallazgos de código

- Existe JSON-LD en `frontend_mejora/public/index.html`:
  - `Organization`
  - `BreadcrumbList`
- Existe generación dinámica de JSON-LD para artículos en `frontend_mejora/src/Services/MetaData.js` (`@type: Article`).
- En `frontend_mejora/src/components/Contact/Contact.js` hay datos útiles para negocio local:
  - ubicación general: "Madrid, España"
  - horario de respuesta: lunes-viernes 9:00–18:00 CET
  - email de contacto
  - perfiles sociales

### Conclusión del estado actual

La base técnica está preparada (ya se usa JSON-LD), pero **no hay todavía marcado `LocalBusiness`** y faltan algunos datos de negocio local para una implementación robusta.

---

## 3) Resumen de lineamientos relevantes de Google (LocalBusiness)

De acuerdo con la documentación oficial:

- Debes cumplir:
  - Search Essentials
  - políticas generales de datos estructurados
- Para elegibilidad de resultado enriquecido, deben incluirse propiedades obligatorias.
- Google recomienda usar el subtipo más específico posible de `LocalBusiness` (p. ej. `ProfessionalService`, `Restaurant`, etc.).
- Para calidad de resultado se recomiendan propiedades adicionales (`geo`, `openingHoursSpecification`, `telephone`, `url`, etc.).
- Validación obligatoria recomendada:
  - Rich Results Test
  - inspección de URL en Search Console

### Propiedades críticas para empezar

Según la guía, para `LocalBusiness` son especialmente importantes:

- `name`
- `address` (`PostalAddress`)
- `url`
- `telephone` (recomendado fuerte)
- `openingHoursSpecification` (recomendado)
- `geo` con precisión suficiente (recomendado)

---

## 4) Gap analysis (brechas)

## 4.1 Lo que ya existe y reutiliza

- `name`: disponible a nivel marca/nombre personal.
- `url`: disponible (`juancarlosmacias.es`).
- `sameAs`: ya existe en `Organization`.

## 4.2 Lo que falta o está incompleto para `LocalBusiness`

- `address` postal completo:
  - actualmente solo se muestra "Madrid, España" (sin calle/código postal).
- `telephone` principal visible y consistente en frontend (aparece en JSON-LD de `Organization` en `index.html`, pero no está integrado como fuente única para todos los schemas).
- `geo` (`latitude`/`longitude`) no está declarado.
- `openingHoursSpecification` no está en JSON-LD (solo texto visible en contacto).
- Definir con precisión el subtipo: para este caso parece más adecuado `ProfessionalService` (subtipo de `LocalBusiness`).

---

## 5) Viabilidad

**Sí, es viable** incorporar `LocalBusiness` en `frontend_mejora/`, con bajo riesgo técnico.

Motivos:

- ya hay patrón de metadatos/JSON-LD activo (`MetaData.js` + scripts en `index.html`),
- React SPA puede inyectar JSON-LD de forma válida,
- existe una página de contacto que concentra la semántica de negocio.

Riesgo principal no técnico: **calidad/veracidad de datos de negocio** (dirección física exacta, teléfono principal, coordenadas y horario real).

---

## 6) Recomendación de diseño para implementación posterior

## 6.1 Tipo recomendado

Usar:

- `@type: "ProfessionalService"`

(en lugar de `LocalBusiness` genérico, por ser más específico para servicios profesionales).

## 6.2 Ubicación recomendada del marcado

En dos capas:

1. **Global base** (en `public/index.html` o equivalente central):
   - mantener `Organization`.
2. **Página de contacto** (`/contacto`) con JSON-LD específico:
   - `ProfessionalService` con datos locales.

Esto evita sobrecargar todas las rutas con un schema local cuando la URL más coherente es la de contacto.

## 6.3 Estrategia técnica sugerida

- Extender `src/Services/MetaData.js` para soportar un nuevo modo, por ejemplo `props._type === "localbusiness"`.
- Pasar un objeto con datos estructurados explícitos desde `Contact.js`.
- Centralizar constantes de negocio (nombre, teléfono, dirección, geo, horario) en un archivo de configuración SEO único para evitar inconsistencias.

---

## 7) Plantilla base propuesta (borrador JSON-LD)

> Nota: valores de dirección/geo son ejemplos y deben sustituirse por datos reales/verificables.

```json
{
  "@context": "https://schema.org",
  "@type": "ProfessionalService",
  "name": "Juan Carlos Macías - Desarrollo Full Stack e IA",
  "url": "https://www.juancarlosmacias.es/contacto",
  "image": "https://www.juancarlosmacias.es/Assets/Projects/portfolio.png",
  "telephone": "+34 618309775",
  "email": "juancmaciassalvador@gmail.com",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Madrid",
    "addressRegion": "Comunidad de Madrid",
    "addressCountry": "ES"
  },
  "areaServed": {
    "@type": "Country",
    "name": "España"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday"
      ],
      "opens": "09:00",
      "closes": "18:00"
    }
  ],
  "sameAs": [
    "https://www.linkedin.com/in/juancarlosmacias/",
    "https://github.com/juancmacias"
  ]
}
```

---

## 8) Checklist previa a implementación

- Confirmar si se publicará dirección postal completa o solo ciudad/región.
- Confirmar teléfono principal público único.
- Confirmar horario de atención oficial para clientes.
- (Opcional recomendado) obtener latitud/longitud precisas de la ubicación pública.
- Validar JSON-LD en Rich Results Test.
- Revisar Search Console tras despliegue.

---

## 9) Plan de implementación sugerido (fase siguiente)

1. Crear `src/config/seoBusiness.js` con datos canónicos del negocio.
2. Extender `MetaData.js` para generar schema `ProfessionalService`.
3. Integrar en `Contact.js` mediante `MetaData`.
4. Mantener `Organization` en `public/index.html` y evitar duplicidades conflictivas.
5. Ejecutar validación externa (Rich Results Test + inspección de URL).

---

## 10) Decisión recomendada

- **Implementar `ProfessionalService` en `/contacto`** y mantener `Organization` global.
- No usar `Restaurant Carousel` ni variantes restringidas (no aplican al caso).
- Priorizar consistencia de datos reales frente a cantidad de campos.
