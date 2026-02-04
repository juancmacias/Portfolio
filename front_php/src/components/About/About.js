import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import Github from "./Github";
import Techstack from "./Techstack";
import Aboutcard from "./AboutCard";
import laptopImg from "../../Assets/about.png";
import Toolstack from "./Toolstack";
import Analytics from "../Analytics";
import MetaData from "../../Services/MetaData";
import { urlApi } from "../../Services/urls";

function About() {
  Analytics("Sobre mí")
  return (
    <Container fluid className="about-section">
      <MetaData
         _title={'Portafolio de Juan Carlos Macías de Madrid | Desarrollo web jcms'}
         _descr={'Hola, soy Juan Carlos Macías y vivo en Madrid, España. Me encuentro en busqueda activa de empleo, reorganizando mi mundo laboral. Dispongo de cursos de formación profesional relacionados con el desarrollo de sofware.'}
         _url={`${urlApi}about`}
         _img={`${urlApi}Assets/Projects/portfolio.png`}
      />
      <Container>
        <Row style={{ justifyContent: "center", padding: "10px" }}>
          <Col
            md={7}
            style={{
              justifyContent: "center",
              paddingTop: "30px",
              paddingBottom: "50px",
            }}
          >
            <h1 style={{ fontSize: "2.1em", paddingBottom: "20px" }}>
              Quien <strong className="purple">soy</strong>
            </h1>
            <Aboutcard />
          </Col>
          <Col
            md={5}
            style={{ paddingTop: "120px", paddingBottom: "50px" }}
            className="about-img"
          >
            <img src={laptopImg} alt="about" className="img-fluid" />
          </Col>
        </Row>
        <h1 className="project-heading">
          Profesional <strong className="purple">skill </strong>
        </h1>

        <Techstack />

        <h1 className="project-heading">
          <strong className="purple">Herramientas </strong> que uso
        </h1>
        <Toolstack />

        <Github />
      </Container>
    </Container>
  );
}

export default About;
