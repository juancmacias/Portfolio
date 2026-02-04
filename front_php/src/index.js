import React from "react";
import { createRoot, hydrateRoot } from 'react-dom/client';
import "./index.css";
import App from "./App";
import reportWebVitals from "./reportWebVitals";

// ==========================================
// SSR HYDRATION LOGIC
// ==========================================

const rootElement = document.getElementById("root");

// Obtener state inicial del servidor (si existe)
const initialState = window.__INITIAL_STATE__ || {};

// Detectar si el contenido fue prerenderizado por PHP
const hasServerRenderedContent = 
  rootElement && 
  rootElement.children.length > 0 &&
  initialState.isSSR === true;

if (hasServerRenderedContent) {
  // ✅ HIDRATACIÓN: El HTML ya existe, React "toma control"
  console.log('🚀 Hidratando aplicación con SSR state:', {
    route: initialState.route,
    title: initialState.title,
    isSSR: initialState.isSSR
  });
  
  hydrateRoot(
    rootElement,
    <React.StrictMode>
      <App initialState={initialState} />
    </React.StrictMode>
  );
  
} else {
  // ⚛️ RENDER NORMAL: Sin SSR, renderizar desde cero (CSR)
  console.log('⚛️ Renderizando aplicación desde cero (CSR)');
  
  const root = createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
}

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();
