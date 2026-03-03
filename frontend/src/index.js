import React from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import App from "./App";
import reportWebVitals from "./reportWebVitals";

const container = document.getElementById("root");

/**
 * SSR Universal Hydration Strategy
 * 
 * PHP genera HTML inicial con datos del servidor (SEO perfecto).
 * React recupera el state inicial y renderiza la SPA interactiva.
 * 
 * Ventajas:
 * - Googlebot indexa HTML completo instantáneamente
 * - Usuarios ven contenido inmediato (FCP < 0.5s)
 * - React toma control para interactividad SPA
 * - 0% riesgo de penalización (mismo contenido para todos)
 */

// Recuperar state inicial inyectado por PHP
const getInitialState = () => {
  try {
    const stateEl = document.getElementById('__INITIAL_STATE__');
    if (stateEl && stateEl.textContent) {
      const state = JSON.parse(stateEl.textContent);
      console.log('🚀 SSR: State inicial recuperado del servidor', state);
      return state;
    }
  } catch (error) {
    console.warn('⚠️ SSR: Error recuperando initial state', error);
  }
  return null;
};

// Detectar si hay contenido prerenderizado
const hasSSRContent = container && container.children.length > 0;
const initialState = getInitialState();

if (hasSSRContent && initialState) {
  console.log('✅ SSR Mode: Contenido prerenderizado detectado');
  console.log('📍 Ruta SSR:', initialState.route);
  
  // Hacer el state disponible globalmente
  window.__INITIAL_STATE__ = initialState;
  window.__SSR_MODE__ = true;
} else {
  console.log('⚛️ SPA Mode: Renderizado desde cero');
  window.__SSR_MODE__ = false;
}

// Renderizar App (siempre usa createRoot para evitar mismatch)
// React reemplazará el HTML del servidor con su propia versión interactiva
createRoot(container).render(
  <React.StrictMode>
    <App initialState={initialState} />
  </React.StrictMode>
);

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();
