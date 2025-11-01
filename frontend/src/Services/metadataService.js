/**
 * Servicio de Metadatos - Portfolio Frontend
 * Gestiona la obtención de metadatos desde la nueva API
 */

import { API_ENDPOINTS, getApiConfig, buildApiUrl } from './urls.js';

class MetadataService {
  constructor() {
    this.cache = new Map();
    this.cacheTimeout = 5 * 60 * 1000; // 5 minutos
  }

  /**
   * Obtener metadatos generales del portfolio
   */
  async getMetadata() {
    const cacheKey = 'metadata';
    
    // Verificar cache
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey);
      if (Date.now() - cached.timestamp < this.cacheTimeout) {
        return cached.data;
      }
    }

    try {
      const response = await fetch(API_ENDPOINTS.portfolio.metadata, getApiConfig());
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
      }

      const result = await response.json();
      
      if (!result.success) {
        throw new Error(result.message || 'Error al obtener metadatos');
      }

      // Guardar en cache
      this.cache.set(cacheKey, {
        data: result.data,
        timestamp: Date.now()
      });

      return result.data;
    } catch (error) {
      console.error('Error fetching metadata:', error);
      
      // Retornar metadatos por defecto en caso de error
      return {
        title: 'Portfolio JCMS',
        description: 'Portfolio personal de Juan Carlos Macías',
        author: 'Juan Carlos Macías',
        keywords: ['portfolio', 'desarrollo web', 'react', 'php'],
        lang: 'es',
        theme_color: '#0070f3',
        background_color: '#ffffff'
      };
    }
  }

  /**
   * Obtener información de versión del sistema
   */
  async getVersionInfo() {
    try {
      const response = await fetch(API_ENDPOINTS.portfolio.version, getApiConfig());
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const result = await response.json();
      return result.success ? result.data : null;
    } catch (error) {
      console.error('Error fetching version info:', error);
      return null;
    }
  }

  /**
   * Obtener proyectos del portfolio
   */
  async getProjects(params = {}) {
    const cacheKey = `projects_${JSON.stringify(params)}`;
    
    // Verificar cache
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey);
      if (Date.now() - cached.timestamp < this.cacheTimeout) {
        return cached.data;
      }
    }

    try {
      const url = buildApiUrl(API_ENDPOINTS.portfolio.projects, params);
      const response = await fetch(url, getApiConfig());
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const result = await response.json();
      
      if (!result.success) {
        throw new Error(result.message || 'Error al obtener proyectos');
      }

      // Guardar en cache
      this.cache.set(cacheKey, {
        data: result.data,
        timestamp: Date.now()
      });

      return result.data;
    } catch (error) {
      console.error('Error fetching projects:', error);
      throw error;
    }
  }

  /**
   * Obtener un proyecto específico por ID
   */
  async getProject(id) {
    return this.getProjects({ id });
  }

  /**
   * Obtener artículos (para futuro uso)
   */
  async getArticles(params = {}) {
    try {
      const url = buildApiUrl(API_ENDPOINTS.portfolio.articles, params);
      const response = await fetch(url, getApiConfig());
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const result = await response.json();
      return result.success ? result.data : null;
    } catch (error) {
      console.error('Error fetching articles:', error);
      return null;
    }
  }

  /**
   * Limpiar cache
   */
  clearCache() {
    this.cache.clear();
  }

  /**
   * Obtener información general de la API
   */
  async getApiInfo() {
    try {
      const response = await fetch(API_ENDPOINTS.portfolio.base, getApiConfig());
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const result = await response.json();
      return result.success ? result.data : null;
    } catch (error) {
      console.error('Error fetching API info:', error);
      return null;
    }
  }
}

// Instancia singleton
const metadataService = new MetadataService();

export default metadataService;

// Exportar también funciones específicas para compatibilidad
export const getMetadata = () => metadataService.getMetadata();
export const getProjects = (params) => metadataService.getProjects(params);
export const getProject = (id) => metadataService.getProject(id);
export const getVersionInfo = () => metadataService.getVersionInfo();
export const getArticles = (params) => metadataService.getArticles(params);
