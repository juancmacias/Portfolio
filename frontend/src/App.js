import React, { useState, useEffect} from "react";
import usePrefersColorScheme from 'use-prefers-color-scheme'
import Preloader from "../src/components/Pre";
import Navbar from "./components/Navbar";
import Home from "./components/Home/Home";
import About from "./components/About/About";
import Projects from "./components/Projects/Projects";
import Footer from "./components/Footer";
import Resume from "./components/Resume/ResumeNew";
//import Usocookies from "./components/Politics/usocookies";
import Politicas from "./components/Politics/politica";
import Analytics from "./components/Analytics";

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

function App() {
  Analytics("Principal")
  const prefersColorScheme = usePrefersColorScheme();
  const [ theme, setTheme] = useState(localStorage.getItem('preferencia')=== null? prefersColorScheme:localStorage.getItem('preferencia'));

  const [load, upadateLoad] = useState(true);


  useEffect(() => {

    const timer = setTimeout(() => {
      upadateLoad(false);
      
    }, 1200);

    return () =>{
      const metaTag = document.querySelector(`meta`);
        if (metaTag) {
            metaTag.remove();
        }
      clearTimeout(timer); 
    } 
  }, []);
  function handleAction(event) {
    setTheme(event);
    console.log('Child did:', event);

}
  return (
    <Router>
      <Preloader load={load} />
      <div className={`${theme}`} id={load ? "no-scroll" : "scroll"}>
        <Navbar onAction={handleAction}/>
        <ScrollToTop />
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/project" element={<Projects />} />
          <Route path="/about" element={<About />} />
          <Route path="/resume" element={<Resume />} />
          <Route path="/politics" element={<Politicas />} />
          <Route path="*" element={<Navigate to="/"/>} />
        </Routes>
        <Footer />
      </div>
    </Router>
  );
}

export default App;
