import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import {
  AiFillGithub,
  AiFillInstagram
} from "react-icons/ai";
import {
  FaLinkedinIn,
  FaGooglePlay
} from "react-icons/fa";

function Footer() {
  let date = new Date();
  let year = date.getFullYear();
  return (
    <Container fluid className="footer">
      <Row>
        <Col md="4" className="footer-copywright">
          <h3>Desarrollado por Juan Carlos Macías</h3>
        </Col>
        <Col md="4" className="footer-copywright">
          <h3>Copyright © {year} JCMS</h3>
        </Col>
        <Col md="4" className="footer-body">
          <ul className="footer-icons">
          <li className="social-icons">
                <a
                  href="https://play.google.com/store/apps/dev?id=7098282899285176966"
                  target="_blank"
                  rel="noreferrer"
                >
                  <FaGooglePlay />
                </a>
              </li>
              <li className="social-icons">
                <a
                  href="https://github.com/juancmacias"
                  target="_blank"
                  rel="noreferrer"
                >
                  <AiFillGithub />
                </a>
              </li>
              
              <li className="social-icons">
                <a
                  href="https://www.linkedin.com/in/juancarlosmacias/"
                  target="_blank"
                  rel="noreferrer"
                >
                  <FaLinkedinIn />
                </a>
              </li>
              <li className="social-icons">
                <a
                  href="https://www.instagram.com/jcms_madrid/"
                  target="_blank"
                  rel="noreferrer"
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
