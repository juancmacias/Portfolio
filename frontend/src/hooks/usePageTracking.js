import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { API_ENDPOINTS } from '../Services/urls';

/**
 * Hook para tracking de visitas a páginas del portfolio
 * Envía notificaciones a Telegram cuando se visita una página
 * 
 * Características:
 * - Detecta cambios de ruta en React Router
 * - Envía tracking solo en producción (opcional)
 * - Incluye referrer y metadata
 * - No bloquea la navegación (asíncrono)
 * - Rate limiting backend (10 min)
 * 
 * Uso:
 * ```jsx
 * function App() {
 *   usePageTracking(); // En el componente principal
 *   return <Router>...</Router>
 * }
 * ```
 */
export const usePageTracking = (options = {}) => {
  const location = useLocation();
  const {
    enabled = true,           // Habilitar tracking
    onlyProduction = false,   // Solo en producción
    debug = false             // Modo debug
  } = options;

  useEffect(() => {
    // No hacer nada si está deshabilitado
    if (!enabled) {
      if (debug) console.log('📊 Page tracking disabled');
      return;
    }

    // Solo producción si está configurado así
    // Considera producción todo excepto localhost, 127.0.0.1 y dominios locales
    const isLocalhost = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname === 'perfil.in' ||
                       window.location.hostname === 'frontend.pru';
    
    if (onlyProduction && isLocalhost) {
      if (debug) console.log('📊 Page tracking: Only production mode, skipping (localhost detected)');
      return;
    }

    // Obtener página actual
    const currentPage = location.pathname + location.search;
    
    // Obtener referrer
    const referrer = document.referrer || 'direct';

    // Datos del tracking
    const trackingData = {
      page: currentPage,
      referrer: referrer,
      timestamp: new Date().toISOString()
    };

    if (debug) {
      console.log('📊 Page tracking:', trackingData);
    }

    // Enviar tracking (asíncrono, no bloquea la navegación)
    fetch(API_ENDPOINTS.portfolio.trackVisit, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(trackingData),
      // No esperar respuesta para no bloquear
      keepalive: true
    })
    .then(response => {
      if (debug && response.ok) {
        return response.json().then(data => {
          console.log('📊 Tracking sent:', data);
        });
      }
    })
    .catch(error => {
      // Silenciar errores para no afectar la experiencia del usuario
      if (debug) {
        console.warn('📊 Tracking error (non-critical):', error);
      }
    });

  }, [location, enabled, onlyProduction, debug]);
};

export default usePageTracking;
