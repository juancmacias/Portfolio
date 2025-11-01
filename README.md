<h2>
  Porfolio personal - Juan Carlos Macías<br/>
  <a href="https://juancarlosmacias.es/" target="_blank">juancarlosmacias.es</a>
</h2>

<br/>

[![forthebadge](https://forthebadge.com/images/badges/built-with-love.svg)](https://forthebadge.com) &nbsp;
[![forthebadge](https://forthebadge.com/images/badges/made-with-javascript.svg)](https://forthebadge.com) &nbsp;
[![forthebadge](https://forthebadge.com/images/badges/open-source.svg)](https://forthebadge.com) &nbsp;
[![forthebadge](https://forthebadge.com/images/badges/ctrl-c-ctrl-v.svg)](https://forthebadge.com)

<h3>
    🔹
    <a href="https://github.com/juancmacias/Portfolio/issues">Algún bug, dimelo aquí.
</h3>

## 🚀 Nuevas Funcionalidades v1.0.6

### ✨ Sistema de Artículos y Blog
- **📝 Gestión completa de artículos**: Crea, edita y publica artículos desde un panel de administración intuitivo
- **🤖 Generación automática con IA**: Integración con múltiples proveedores de IA (Groq, Hugging Face, OpenAI) para generar contenido automáticamente
- **🔍 Búsqueda inteligente**: Busca artículos en tiempo real por título, contenido o etiquetas
- **📱 Vista adaptativa**: Cambia entre vista de cuadrícula y lista para una mejor experiencia de lectura
- **🏷️ Sistema de etiquetas**: Organiza y filtra artículos por categorías y temas

### 🖼️ Galería de Imágenes Mejorada
- **📁 Explorador de imágenes**: Navega y selecciona imágenes fácilmente desde el panel de administración
- **⬆️ Subida de archivos**: Sube nuevas imágenes directamente desde la interfaz
- **🖱️ Selección modal**: Interfaz elegante para seleccionar imágenes para proyectos y artículos
- **📏 Optimización automática**: Las imágenes se optimizan automáticamente para web

### 🏗️ Panel de Administración Completo
- **🔐 Sistema de autenticación**: Acceso seguro con gestión de sesiones
- **📊 Dashboard informativo**: Vista general de proyectos, artículos y estadísticas
- **⚡ Interfaz responsive**: Funciona perfectamente en desktop, tablet y móvil
- **🎨 Diseño moderno**: Interfaz limpia y profesional con navegación intuitiva

### 🔄 API REST Mejorada
- **📄 Paginación inteligente**: Navega por proyectos y artículos con controles de paginación
- **🔍 Filtros avanzados**: Filtra proyectos por tipo (web, app) con contadores dinámicos
- **⚡ Rendimiento optimizado**: Consultas optimizadas para cargas rápidas
- **🌐 CORS configurado**: Acceso seguro desde diferentes dominios

### 🏠 Homepage Renovada
- **📰 Artículos recientes**: Muestra automáticamente los últimos artículos publicados
- **🔗 Navegación mejorada**: Nueva sección de artículos en el menú principal
- **📱 Diseño responsive**: Adaptación perfecta a todos los dispositivos
- **⚡ Carga optimizada**: Mejores tiempos de carga y fluidez general

## Código

Puedes modificar el código de la forma y manera que te apetezca, eres libre de copiar y modificar a tu gusto, y si quieres puedes nombrarme [juancmacias](https://github.com/juancmacias/Portfolio).

## Tecnología 

Mi portafolio personal <a href="https://juancarlosmacias.es/" target="_blank">juancarlosmacias.es</a> presenta algunos de mis proyectos en Github, así como mi currículum y habilidades técnicas.<br/>

Este proyecto utiliza tecnologías modernas para ofrecer una experiencia completa tanto en `frontend` como en `backend`.

### Frontend
- **React.js** - Framework principal para la interfaz de usuario
- **Bootstrap** - Framework CSS para diseño responsive
- **React Router** - Navegación entre páginas
- **CSS3** - Estilos personalizados y animaciones
- **JavaScript ES6+** - Funcionalidades modernas

### Backend
- **PHP 8+** - Lenguaje del servidor con programación orientada a objetos
- **MySQL/MySQLi** - Base de datos con compatibilidad automática
- **API REST** - Endpoints para proyectos, artículos e imágenes
- **JSON** - Formato de intercambio de datos

### Integraciones de IA
- **Groq API** - Generación rápida de contenido
- **Hugging Face** - Modelos de lenguaje natural
- **OpenAI** - Generación de texto avanzada

### Herramientas de Desarrollo
- **Git** - Control de versiones con etiquetado semántico
- **VS Code** - Editor principal con extensiones optimizadas
- **Composer** - Gestor de dependencias PHP (futuro)
- **npm** - Gestor de paquetes Node.js


## Para empezar

Clona este repositorio. Necesitaras `node.js`, `git` y un servidor PHP instalados globalmente en tu máquina.

## Instrucciones de instalación y configuración

### Configuración del Frontend

1. **Instala las dependencias**: `npm install`

2. **Configura las URLs**: 
   - Revisa `frontend/src/Services/urls.js` 
   - Ajusta las URLs según tu entorno (local/producción)

3. **Inicia el desarrollo**: `npm start`

Ejecuta la aplicación en modo de desarrollo.
Abre [http://localhost:3000](http://localhost:3000) para verlo en el navegador.
La página se recargará si realizas modificaciones.

### Configuración del Backend

1. **Servidor PHP**: Asegúrate de tener PHP 8+ con extensiones MySQLi/PDO

2. **Base de datos**: 
   - Importa `admin/sql/init-database.sql` en tu MySQL
   - Configura la conexión en `admin/config/config.local.php`

3. **Configuración local**:
   ```bash
   cp admin/config/config.local.example.php admin/config/config.local.php
   ```
   Luego edita `config.local.php` con tus datos reales.

4. **Panel de administración**: 
   - Accede a `http://tudominio.com/admin`
   - Usa las credenciales configuradas en la base de datos

### Configuración de IA (Opcional)

Para usar las funcionalidades de generación automática de contenido:

1. **Obtén API keys** de los proveedores que quieras usar:
   - [Groq](https://groq.com/) (recomendado - más rápido)
   - [Hugging Face](https://huggingface.co/)
   - [OpenAI](https://openai.com/)

2. **Configura las keys** en `admin/config/config.local.php`:
   ```php
   'api_keys' => [
       'groq' => 'tu-api-key-aqui',
       'huggingface' => 'tu-api-key-aqui',
       'openai' => 'tu-api-key-aqui'
   ]
   ```

## Instrucciones de uso

### Estructura del Proyecto

```
📦 Portfolio/
├── 🎨 frontend/          # Aplicación React
│   ├── src/components/   # Componentes reutilizables
│   ├── src/Services/     # Configuración de APIs y URLs
│   └── public/Assets/    # Imágenes y recursos estáticos
├── 🔧 admin/            # Panel de administración
│   ├── pages/           # Páginas del admin (login, dashboard, etc.)
│   ├── api/             # APIs para imágenes y funciones IA
│   └── config/          # Configuración de la aplicación
├── 🌐 api/portfolio/    # API REST principal
│   ├── projects.php     # Endpoint de proyectos
│   ├── articles.php     # Endpoint de artículos
│   └── config.php       # Configuración de la API
└── 📚 doc/             # Documentación técnica
```

### Personalización

1. **Contenido personal**: 
   - Edita `frontend/src/components/` para cambiar textos e información
   - Actualiza `frontend/public/Assets/` con tus imágenes

2. **Proyectos**: 
   - Usa el panel admin para agregar/editar proyectos
   - O edita directamente `api/portfolio/datos_proyectos.json`

3. **Artículos**: 
   - Accede al panel admin → Sección "Artículos"
   - Crea nuevo contenido manualmente o con ayuda de IA

4. **Estilos**: 
   - Modifica `frontend/src/style.css` para cambios visuales
   - Ajusta `admin/assets/css/` para el panel de administración

### Funcionalidades del Panel Admin

- **🏠 Dashboard**: Vista general con estadísticas
- **📁 Proyectos**: Gestión completa de tu portfolio
- **📝 Artículos**: Sistema de blog con editor y IA
- **🖼️ Galería**: Explorador y gestor de imágenes
- **⚙️ Configuración**: Ajustes del sistema y APIs

## Stats
 
![Alt](https://repobeats.axiom.co/api/embed/0cba6738361413e81d5f8270161155fe9d9385fd.svg "Repobeats analytics image")

### Muestra tu apoyo

Si te gusto dale una estrella y puedes invitarme a un café.

<a href="https://www.buymeacoffee.com/juancmaciau" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-blue.png" alt="Invitarme a café" style="height: 60px !important;width: 217px !important;" ></a>

# Control de versions

## ✅ Checklist SemVer + Tags

### 1. 🧪 Antes de versionar

- [ ] Todo el código fue **probado y funciona correctamente**
- [ ] Todos los **tests pasan**
- [ ] El proyecto está en un estado **estable o importante** (lanzamiento, demo, entrega, etc.)

---

### 2. 📄 Decide la nueva versión

- ¿Rompiste compatibilidad con versiones anteriores? → **Incrementa `MAJOR`**
- ¿Añadiste nuevas funcionalidades compatibles? → **Incrementa `MINOR`**
- ¿Solo corregiste errores o hiciste mejoras menores? → **Incrementa `PATCH`**

> 🧠 Ejemplo:  
> Si la versión actual es `v1.2.3` y corriges un bug, la nueva sería `v1.2.4`.


```bash

git tag -a v1.0.0 -m "Release: versión inicial"
git push origin v1.0.0

# para obtener la última versión
git describe

```