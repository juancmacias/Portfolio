/**
 * Configuración de URLs - Detección automática de entorno
 * Detecta si estamos en local o en producción
 */

// Detectar entorno automáticamente
const isLocal = () => {
  const hostname = window.location.hostname;
  return hostname === 'localhost' || 
         hostname === '127.0.0.1' || 
         hostname === 'perfil.in' ||
         hostname.includes('.local') ||
         hostname.includes('.test') ||
         process.env.NODE_ENV === 'development';
};

// URLs según el entorno
const API_URLS = {
  local: 'http://www.perfil.in/',
  production: 'https://www.juancarlosmacias.es/'  // Con www para consistencia
};

// Exportar URL según el entorno detectado
export const urlApi = isLocal() ? API_URLS.local : API_URLS.production;

// Debug: Siempre mostrar información del entorno
console.log('🔍 URL Detection Debug:', {
  hostname: typeof window !== 'undefined' ? window.location.hostname : 'SSR',
  isLocal: isLocal(),
  selectedUrl: urlApi,
  nodeEnv: process.env.NODE_ENV
});

// Endpoints de la API
export const API_ENDPOINTS = {
  portfolio: {
    base: `${urlApi}api/portfolio/`,
    metadata: `${urlApi}api/portfolio/metadata.php`,
    version: `${urlApi}api/portfolio/version.php`,
    projects: `${urlApi}api/portfolio/projects.php`,
    articles: `${urlApi}api/portfolio/articles.php`,
    viewArticle: `${urlApi}api/portfolio/view-article.php`,
    chatRag: `${urlApi}api/portfolio/chat-rag.php`
  }
};

// Configuración por defecto para las peticiones
export const getApiConfig = (options = {}) => ({
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers
  },
  ...options
});

// Construir URL con parámetros de consulta
export const buildApiUrl = (baseUrl, params = {}) => {
  const url = new URL(baseUrl);
  
  Object.keys(params).forEach(key => {
    if (params[key] !== undefined && params[key] !== null) {
      url.searchParams.append(key, params[key]);
    }
  });
  
  return url.toString();
};

// Exportar función para debugging
export const getEnvironmentInfo = () => ({
  hostname: window.location.hostname,
  isLocal: isLocal(),
  apiUrl: urlApi,
  nodeEnv: process.env.NODE_ENV || 'unknown',
  endpoints: API_ENDPOINTS
});

// Log del entorno en desarrollo
if (process.env.NODE_ENV === 'development') {
  console.log('🌐 API Environment:', getEnvironmentInfo());
}



