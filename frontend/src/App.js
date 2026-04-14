import React, { useState, useEffect} from "react";
import usePrefersColorScheme from 'use-prefers-color-scheme'
import Preloader from "../src/components/Pre";
import Navbar from "./components/Navbar";
import Home from "./components/Home/Home";
import About from "./components/About/About";
import Projects from "./components/Projects/Projects";
import Footer from "./components/Footer";
import Resume from "./components/Resume/ResumeNew";
import ArticlesPage from "./components/Articles/ArticlesPage";
import ArticleView from "./components/Articles/ArticleView";
//import Usocookies from "./components/Politics/usocookies";
import Politicas from "./components/Politics/politica";
import Terminos from "./components/Politics/terminos";
import ContactPage from "./components/Contact/ContactPage";
import ScheduleMeeting from "./components/Scheduling/ScheduleMeeting";
import Analytics from "./components/Analytics";
import ChatModal from "./components/Chat/ChatModal";
import ChatButton from "./components/Chat/ChatButton";
import usePageTracking from "./hooks/usePageTracking";

import {
  BrowserRouter as Router,
  Route,
  Routes,
  Navigate
} from "react-router-dom";
import ScrollToTop from "./components/ScrollToTop";
import "./style.css";
import "./App.css";
import "bootstrap/dist/css/bootstrap.min.css";

// Componente interno que usa el hook de tracking (debe estar dentro del Router)
function AppContent({ theme, handleAction, load, chatModalOpen, setChatModalOpen }) {
  // Hook de tracking de visitas (envía notificación a Telegram cada 10 min)
  usePageTracking({
    enabled: true,          // Habilitar tracking
    onlyProduction: true,   // Solo en producción (excluye localhost, perfil.in, frontend.pru)
    debug: false            // Debug mode (console.log)
  });

  return (
    <>
      <Preloader load={load} />
      <div className={`${theme}`} id={load ? "no-scroll" : "scroll"}>
        <Navbar onAction={handleAction}/>
        <ScrollToTop />
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/project" element={<Projects />} />
          <Route path="/about" element={<About />} />
          <Route path="/resume" element={<Resume />} />
          <Route path="/articles" element={<ArticlesPage />} />
          <Route path="/article/:slug" element={<ArticleView />} />
          <Route path="/politics" element={<Politicas />} />
          <Route path="/terminos" element={<Terminos />} />
          <Route path="/contacto" element={<ContactPage />} />
          <Route path="/agendar" element={<ScheduleMeeting />} />
          <Route path="*" element={<Navigate to="/"/>} />
        </Routes>
        <Footer />
        
        {/* Botón flotante de chat */}
        <ChatButton onClick={() => setChatModalOpen(true)} />
        
        {/* Chat Modal */}
        <ChatModal 
          isOpen={chatModalOpen} 
          onClose={() => setChatModalOpen(false)} 
        />
      </div>
    </>
  );
}

function App({ initialState = null }) {
  Analytics("Principal")
  const prefersColorScheme = usePrefersColorScheme();
  const [ theme, setTheme] = useState(localStorage.getItem('preferencia')=== null? prefersColorScheme:localStorage.getItem('preferencia'));

  // Reducir tiempo de preloader si viene de SSR (ya hay contenido)
  const isSSR = initialState !== null;
  const [load, upadateLoad] = useState(!isSSR); // Si es SSR, skip preloader
  const [chatModalOpen, setChatModalOpen] = useState(false);


  useEffect(() => {
    // Log SSR info
    if (isSSR) {
      console.log('🎯 App montada con SSR state:', initialState);
    }

    const timer = setTimeout(() => {
      upadateLoad(false);
      
    }, isSSR ? 300 : 1200); // Preloader más rápido si es SSR

    return () =>{
      const metaTag = document.querySelector(`meta`);
        if (metaTag) {
            metaTag.remove();
        }
      clearTimeout(timer); 
    } 
  }, [isSSR, initialState]);
  function handleAction(event) {
    setTheme(event);
    console.log('Child did:', event);

}
  return (
    <Router>
      <AppContent 
        theme={theme}
        handleAction={handleAction}
        load={load}
        chatModalOpen={chatModalOpen}
        setChatModalOpen={setChatModalOpen}
      />
    </Router>
  );
}

export default App;
