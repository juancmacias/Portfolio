import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import ProjectCard from "./ProjectCards";
import proyectos from "../../Datos/datos_proyectos.json";
import Analytics from "../Analytics";
function Projects() {
  Analytics("Proyectos")
  return (
    <Container fluid className="project-section">
        <h2 className="project-heading">
          Últimos <strong className="purple">trabajos</strong>
        </h2>
        <Row style={{ justifyContent: "center", paddingBottom: "10px" }}>
          {proyectos.map((datos) => (
            <Col md={4} key={`A${datos.id}`} id={`A${datos.id}`} className="project-card">
              <ProjectCard 
                imgPath={datos.imgPath}
                isBlog={datos.isBlog}
                title={datos.title}
                description={datos.description}
                ghLink={datos.ghLink}
                demoLink={datos.demoLink}
              />
            </Col>
          ))}

        </Row>
      </Container>

  );
}

export default Projects;
