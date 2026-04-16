import React from "react"
import { Container, Row, Col } from "react-bootstrap"
//import homeLogo from "../../Assets/b1.png"
import Home2 from "./Home2"
import Type from "./Type"
import RecentArticles from "./RecentArticles"
import Analytics from "../Analytics"
import MetaData from "../../Services/MetaData";
import { urlApi } from "../../Services/urls";


function Home() {
  Analytics("Inicio")
  return (

    <section>
      <MetaData
         _title={'Soluciones web, IA Generativa, Automatizaciones | Juan Carlos Macías'}
         _descr={'Desarrollo web full stack (React, PHP, Java) con IA Generativa. Creo automatizaciones inteligentes y aplicaciones escalables. Especialista en integración de modelos LLM y MLOps en Madrid.'}
         _url={urlApi}
         _img={`${urlApi}Assets/Projects/portfolio.png`}
      />
      <Container fluid className="home-section" id="home">

        <Container className="home-content">
          <Row>
            <Col md={7} className="home-header">
              <h2 style={{ paddingBottom: 15 }} className="heading">
                Hola, bienvenid@{" "}
                <span className="wave" role="img" aria-labelledby="wave">
                  👋🏻
                </span>
              </h2>
              <strong className="heading">Soy</strong>
              <h1 className="heading-name">
                <strong className="main-name">Juan Carlos Macías</strong>
              </h1>

              <div style={{ padding: 50, textAlign: "left" }}>
                <h3><Type /></h3>
              </div>
            </Col>

            <Col md={5} style={{ paddingBottom: 20 }}>
              <img
                src="Assets/b1.png"
                alt="JCMS"
                aria-label="Juan Carlos Macías"
                className="img-fluid"
                style={{ maxHeight: "450px", borderRadius: "120px" }}
              />
            </Col>
          </Row>
        </Container>
      </Container>
      <RecentArticles />
      <Home2 />
    </section>
  );
}

export default Home;
