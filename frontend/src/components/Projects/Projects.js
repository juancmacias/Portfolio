import React, { useEffect, useState } from "react";
import { Container, Row, Col } from "react-bootstrap";
import ProjectCard from "./ProjectCards";
//import proyectos from "../../Datos/datos_proyectos.json";
import { urlApi } from "../../Services/urls";
import Analytics from "../Analytics";
import LoadingSpinner from "../Loading/LoadingSpinner"
import MetaData from "../../Services/MetaData";

function Projects() {
  const [proyectos, setProyectos] = useState([])
  const [ isLoading, setIsLoading] = useState(true)
  Analytics("Proyectos")
  
  async function obtenerDatos() {
    const data = await fetch(`${urlApi}api/porfolio/recuperar.php`);
    const users = await data.json();
    const nueva = [...users]
    nueva.reverse()
    setProyectos(nueva)
    setIsLoading(false);
  }
  useEffect(() => {
    obtenerDatos()
  }, [])

  const renderProyectos = (
    <div>
      <MetaData
         _title={'Porfolio de Juan Carlos Macías, proyectos desarrollados | Desarrollo web jcms'}
         _descr={'Soluciones digitales para distintas entidades, usando PHP, SQL, Java, React, JavaScript, Symfony'}
         _url={'http://www.juancarlosmacias.es/project'}
         _img={'http://www.juancarlosmacias.es/Assets/Projects/portfolio.png'}
         
      />
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
    </div>
  )
  return (
    <Container fluid className="project-section">
      {isLoading ? <LoadingSpinner /> : renderProyectos}
    </Container>

  );
}

export default Projects;
