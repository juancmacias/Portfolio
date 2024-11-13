import React from "react"
import { Container, Row, Col } from "react-bootstrap"
import homeLogo from "../../Assets/home-main.svg"
import Home2 from "./Home2"
import Type from "./Type"
import Analytics from "../Analytics"
import MetaData from "../../Services/MetaData";


function Home() {
  Analytics("Inicio")
  return (

    <section>
      <MetaData
         _title={'Porfolio de Juan Carlos Macías, creador de soluciones únicas | Desarrollo web jcms'}
         _descr={'Página de Juan Carlos Macias, Tu aplicación única en web o móvil. Desarrollador Web y creador de contenido. Especializado en crear aplicaciones.'}
         _url={'http://www.juancarlosmacias.es'}
         _img={'http://www.juancarlosmacias.es/Assets/Projects/portfolio.png'}
      />
      <Container fluid className="home-section" id="home">

        <Container className="home-content">
          <Row>
            <Col md={7} className="home-header">
              <h1 style={{ paddingBottom: 15 }} className="heading">
                Hola, bienvenid@{" "}
                <span className="wave" role="img" aria-labelledby="wave">
                  👋🏻
                </span>
              </h1>

              <h1 className="heading-name">
                Soy
                <strong className="main-name"> Juan Carlos Macías</strong>
              </h1>

              <div style={{ padding: 50, textAlign: "left" }}>
                <Type />
              </div>
            </Col>

            <Col md={5} style={{ paddingBottom: 20 }}>
              <img
                src={homeLogo}
                alt="home pic"
                className="img-fluid"
                style={{ maxHeight: "450px" }}
              />
            </Col>
          </Row>
        </Container>
      </Container>
      <Home2 />
    </section>
  );
}

export default Home;
