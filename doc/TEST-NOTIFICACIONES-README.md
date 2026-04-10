# Scripts de Test - Notificaciones a Buscadores

## 📋 Descripción

Dos scripts complementarios para probar y diagnosticar las configuraciones de notificaciones a motores de búsqueda (Google Search Console, Bing Webmaster, IndexNow).

---

## 🌐 Test Web (Interfaz Gráfica)

### Ubicación
```
admin/pages/test-search-engine-notification.php
```

### Acceso
- **URL**: `https://www.juancarlosmacias.es/admin/pages/test-search-engine-notification.php`
- **Menú Admin**: Herramientas SEO → Test Notificaciones
- **Requiere**: Sesión de administrador activa

### Características
✅ Interfaz visual con Bootstrap 5  
✅ Resultados con códigos de colores (éxito/error/advertencia)  
✅ Información detallada de cada proveedor  
✅ Mensajes de ayuda y soluciones  
✅ Verifica archivos de configuración  
✅ Prueba autenticación con APIs  
✅ Muestra información de Service Accounts  

### Pruebas que realiza

#### Google Search Console
1. ✓ Verifica `admin/config/service-account-credentials.json`
2. ✓ Valida formato JSON
3. ✓ Comprueba campos requeridos
4. ✓ Muestra información del Service Account
5. ✓ Verifica Google Client Library instalada
6. ✓ Prueba autenticación y generación de token
7. ✓ Proporciona instrucciones para agregar permisos

#### Bing Webmaster Tools
1. ✓ Verifica `admin/config/bing-api-key.txt`
2. ✓ Valida longitud y formato de API Key
3. ✓ Prueba conexión con endpoint oficial
4. ✓ Analiza códigos de respuesta HTTP
5. ✓ Diagnóstico de errores comunes (401, 410, etc.)
6. ✓ Proporciona soluciones específicas

#### IndexNow
1. ✓ Verifica `admin/config/indexnow-key.txt`
2. ✓ Valida formato de clave hexadecimal
3. ✓ Comprueba archivo público de verificación
4. ✓ Verifica accesibilidad del archivo público
5. ✓ Prueba envío a API de IndexNow
6. ✓ Confirma notificación a múltiples buscadores

### Códigos de Color
- 🟢 **Verde**: Test pasado correctamente
- 🔴 **Rojo**: Error crítico que requiere acción
- 🟡 **Amarillo**: Advertencia o configuración pendiente
- 🔵 **Azul**: Información adicional

---

## 💻 Test CLI (Línea de Comandos)

### Ubicación
```
admin/pages/test-notifications-cli.php
```

### Uso
```bash
# Desde la raíz del proyecto
cd admin/pages

# Probar todos los proveedores
php test-notifications-cli.php all

# Probar solo Google
php test-notifications-cli.php google

# Probar solo Bing
php test-notifications-cli.php bing

# Probar solo IndexNow
php test-notifications-cli.php indexnow
```

### Características
✅ Ejecución rápida desde terminal  
✅ Salida con colores ANSI  
✅ Ideal para scripts automatizados  
✅ Sin dependencias de navegador  
✅ Resumen final de resultados  
✅ Códigos de salida para CI/CD  

### Ejemplo de Salida
```
╔════════════════════════════════════════════════════════════╗
║   TEST DE NOTIFICACIONES A BUSCADORES - PORTFOLIO JCMS    ║
╚════════════════════════════════════════════════════════════╝

Entorno: PRODUCCIÓN
URL Base: https://www.juancarlosmacias.es

═══ GOOGLE SEARCH CONSOLE API ═══

→ Verificando credenciales...
  ✓ Archivo encontrado
→ Validando JSON...
  ✓ JSON válido
→ Verificando campos requeridos...
  ✓ Todos los campos presentes
    Project: dondereparar
    Email: portfolio-sitemap@dondereparar.iam.gserviceaccount.com
...
```

---

## 🔍 Casos de Uso

### 1. Configuración Inicial
**Escenario**: Acabas de configurar las credenciales por primera vez.

**Acción**: 
1. Ejecutar test web para diagnóstico visual completo
2. Seguir instrucciones mostradas en pantalla
3. Corregir errores según indicaciones

### 2. Debugging Rápido
**Escenario**: Las notificaciones fallan pero no sabes por qué.

**Acción**:
```bash
php test-notifications-cli.php all
```
Resultado inmediato en terminal sin abrir navegador.

### 3. Verificación Periódica
**Escenario**: Quieres verificar que todo sigue funcionando.

**Acción**: Agregar a cron job:
```bash
# Ejecutar todos los días a las 9 AM
0 9 * * * cd /ruta/admin/pages && php test-notifications-cli.php all >> /var/log/search-engines-test.log 2>&1
```

### 4. Antes de Deploy
**Escenario**: Vas a subir cambios a producción.

**Acción**: Ejecutar tests para asegurar que las configuraciones están OK.

---

## 🛠️ Troubleshooting

### El test web muestra "Login required"
**Causa**: No hay sesión de administrador activa.  
**Solución**: Iniciar sesión en `/admin/pages/login.php` primero.

### El test CLI da error de archivos no encontrados
**Causa**: Ejecutando desde directorio incorrecto.  
**Solución**: Ejecutar desde `admin/pages/`:
```bash
cd e:\wwwserver\N_JCMS\Portfolio\admin\pages
php test-notifications-cli.php all
```

### Google muestra "Library not installed"
**Causa**: Falta instalar Google Client Library.  
**Solución**:
```bash
composer require google/apiclient
```

### IndexNow muestra "NOT configured"
**Causa**: No se ha ejecutado el setup.  
**Solución**:
```bash
# Desde navegador
https://www.juancarlosmacias.es/setup-indexnow.php

# Esto generará:
# - admin/config/indexnow-key.txt
# - {clave}.txt (archivo público en raíz)
```

---

## 🔐 Seguridad

### ⚠️ Advertencias

1. **Test Web**: Contiene información sensible (project IDs, email de Service Account)
   - Requiere autenticación de administrador
   - Considerar eliminar o restringir después de configuración inicial

2. **Test CLI**: Ejecutar solo desde servidor seguro
   - No exponer en web pública
   - Proteger logs generados

3. **No commitear archivos sensibles**:
   ```gitignore
   admin/config/service-account-credentials.json
   admin/config/bing-api-key.txt
   admin/config/indexnow-key.txt
   ```

### Recomendaciones

✓ Ejecutar tests solo en entornos de desarrollo/staging  
✓ No dejar el script web accesible permanentemente  
✓ Revisar logs después de cada test  
✓ Rotar API Keys periódicamente  

---

## 📊 Interpretación de Resultados

### Google Search Console

| Resultado | Significado | Acción Requerida |
|-----------|-------------|------------------|
| ✅ Access Token generado | Configuración correcta | Agregar Service Account como Owner en Search Console |
| ❌ JSON inválido | Archivo corrupto | Descargar nuevamente desde Google Cloud |
| ❌ Library not installed | Falta dependencia | `composer require google/apiclient` |
| ❌ Campos faltantes | JSON incompleto | Verificar que el archivo sea Service Account JSON |

### Bing Webmaster

| Resultado | Significado | Acción Requerida |
|-----------|-------------|------------------|
| ✅ HTTP 200 | Funcionando correctamente | Ninguna |
| ❌ HTTP 401 | API Key inválida | Regenerar desde Bing Webmaster Tools |
| ❌ HTTP 410 | Sitio no verificado | Verificar sitio en Bing Webmaster |
| ❌ API Key vacía | Archivo sin contenido | Obtener API Key y guardar |

### IndexNow

| Resultado | Significado | Acción Requerida |
|-----------|-------------|------------------|
| ✅ HTTP 200/202 | Funcionando correctamente | Ninguna |
| ❌ Key not found | No configurado | Ejecutar `setup-indexnow.php` |
| ❌ Public file missing | Archivo de verificación ausente | Re-ejecutar setup |
| ❌ Content mismatch | Clave pública/privada no coinciden | Regenerar configuración |

---

## 📚 Referencias

- [Guía de Configuración Completa](GUIA-CONFIGURACION-NOTIFICACIONES.md)
- [Google Search Console API](https://developers.google.com/webmaster-tools)
- [Bing Webmaster Tools API](https://docs.microsoft.com/en-us/bingwebmaster/)
- [IndexNow Protocol](https://www.indexnow.org/)

---

## 🔄 Actualización

**Última actualización**: 2026-04-10  
**Versión**: 1.0.0  
**Compatibilidad**: PHP 7.4+, Google Client Library 2.x
