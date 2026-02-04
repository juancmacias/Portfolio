import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import { AiFillGithub, AiFillInstagram } from "react-icons/ai";
import { FaLinkedinIn, FaGooglePlay, FaMapMarkedAlt  } from "react-icons/fa";
import { useEffect, useState } from "react";
import packageJson from '../../package.json';
export const GITHUB_API_BASE_URL = packageJson.config.githubApiBaseUrl;

function Footer() {
  let date = new Date();
  let year = date.getFullYear();
const [version, setVersion] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchLatestTag = async () => {
      try {
        const response = await fetch(`${GITHUB_API_BASE_URL}/tags`);
        if (!response.ok) throw new Error("Error al obtener los tags");

        const tags = await response.json();
        if (tags.length > 0) {
          setVersion(tags[0].name); // El primer tag es el más reciente (por orden del API)
        } else {
          setVersion("Sin versiones");
        }
      } catch (err) {
        setError(err.message);
      }
    };

    fetchLatestTag();
  }, []);

  if (error) return <p>Error: {error}</p>;
  if (!version) return <p>Cargando versión...</p>;
  return (
    <Container fluid className="footer">
      <Row>
        <Col md="4" className="footer-copywright">
          <h3>Desarrollado por Juan Carlos Macías</h3>
        </Col>
        <Col md="4" className="footer-copywright">
          <h3>Copyright © {year} <abbr title="Juan Carlos Macías Salvador">jcms</abbr> - <strong>{version}</strong></h3>
        </Col>
        <Col md="4" className="footer-body">
          <ul className="footer-icons">
          <li className="social-icons">
              <a
                href="https://maps.app.goo.gl/eb43KR6oPFGrNgAn9"
                target="_blank"
                rel="noreferrer"
                aria-label="Mira donde me encuentro"
              >
                <FaMapMarkedAlt />
              </a>
            </li>
            <li className="social-icons">
              <a
                href="https://play.google.com/store/apps/dev?id=7098282899285176966"
                target="_blank"
                rel="noreferrer"
                aria-label="Mira mi perfil de Google Play"
              >
                <FaGooglePlay />
              </a>
            </li>
            <li className="social-icons">
              <a
                href="https://github.com/juancmacias"
                target="_blank"
                rel="noreferrer"
                aria-label="Visita mi repositorio en GitHub"
              >
                <AiFillGithub />
              </a>
            </li>

            <li className="social-icons">
              <a
                href="https://www.linkedin.com/in/juancarlosmacias/"
                target="_blank"
                rel="noreferrer"
                aria-label="Sigueme por LinkedIn"
              >
                <FaLinkedinIn />
              </a>
            </li>
            <li className="social-icons">
              <a
                href="https://www.instagram.com/jcms_madrid/"
                target="_blank"
                rel="noreferrer"
                aria-label="Es mi instagram"
              >
                <AiFillInstagram />
              </a>
            </li>
          </ul>
        </Col>
      </Row>
    </Container>
  );
}

export default Footer;
