import React, { useState } from "react";
import usePrefersColorScheme from 'use-prefers-color-scheme'
import Navbar from "react-bootstrap/Navbar";
import Nav from "react-bootstrap/Nav";
import Container from "react-bootstrap/Container";
import logo from "../Assets/logo.png";
import { Link } from "react-router-dom";
import { CiLight, CiDark} from "react-icons/ci";
import {
  AiOutlineHome,
  AiOutlineFundProjectionScreen,
  AiOutlineUser,
} from "react-icons/ai";


import { CgFileDocument } from "react-icons/cg";

function NavBar({ onAction }) {
  const prefersColorScheme = usePrefersColorScheme();
  const [ theme, setTheme] = useState(localStorage.getItem('preferencia')=== null? prefersColorScheme:localStorage.getItem('preferencia'));
  localStorage.setItem("preferencia", theme);
  const [expand, updateExpanded] = useState(false);
  const [navColour, updateNavbar] = useState(false);

  function scrollHandler() {
    if (window.scrollY >= 20) {
      updateNavbar(true);
    } else {
      updateNavbar(false);
    }
  }
  const cambiar = (valor) =>{
    onAction(valor)
    setTheme(valor)
  }
  window.addEventListener("scroll", scrollHandler);

  return (
    <Navbar
      expanded={expand}
      fixed="top"
      expand="md"
      className={navColour ? "sticky" : "navbar"}
    >
      <Container>
        <Navbar.Brand href="/" className="d-flex">
          <img src={logo} className="img-fluid logo" alt="brand" />
        </Navbar.Brand>
        <Navbar.Toggle
          aria-controls="responsive-navbar-nav"
          onClick={() => {
            updateExpanded(expand ? false : "expanded");
          }}
        >
          <span></span>
          <span></span>
          <span></span>
        </Navbar.Toggle>
        <Navbar.Collapse id="responsive-navbar-nav">
          <Nav className="ms-auto" defaultActiveKey="#home">
            <Nav.Item>
              <Nav.Link as={Link} to="/" onClick={() => updateExpanded(false)}>
                <AiOutlineHome style={{ marginBottom: "2px" }} /> Inicio
              </Nav.Link>
            </Nav.Item>

            <Nav.Item>
              <Nav.Link
                as={Link}
                to="/about"
                onClick={() => updateExpanded(false)}
              >
                <AiOutlineUser style={{ marginBottom: "2px" }} /> Sobre mí
              </Nav.Link>
            </Nav.Item>

            <Nav.Item>
              <Nav.Link
                as={Link}
                to="/project"
                onClick={() => updateExpanded(false)}
              >
                <AiOutlineFundProjectionScreen style={{ marginBottom: "2px" }} /> Proyectos
              </Nav.Link>
            </Nav.Item>

            <Nav.Item>
              <Nav.Link
                as={Link}
                to="/resume"
                onClick={() => updateExpanded(false)}
              >
                <CgFileDocument style={{ marginBottom: "2px" }} /> Currículo
              </Nav.Link>
            </Nav.Item>

            <Nav.Item>
              <Nav.Link
                as={Link}
                to="/contact"
                onClick={() => updateExpanded(false)}
              >
                 Contactar
              </Nav.Link>
            </Nav.Item>

            <Nav.Item>
              <Nav.Link
                  className={theme === "dark" ? "show" : "hidden"}
                  aria-label="Use Dark Mode" onClick={() => cambiar('light')}
                  title='Activar modo claro'
                  rel="noreferrer"
                >
                
                <CiDark style={{ fontSize: "2em" }}  />
              </Nav.Link>
              <Nav.Link
                  className={theme === "light" ? "show" : "hidden"}
                  aria-label="Use Light Mode" onClick={() => cambiar('dark')}
                  title='Activar modo oscuro'>
                  <CiLight style={{ fontSize: "2em" }}  />
              </Nav.Link>
            </Nav.Item>
          </Nav>
        </Navbar.Collapse>
      </Container>
    </Navbar>
  );
}

export default NavBar;
