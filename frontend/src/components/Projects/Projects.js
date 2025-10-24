import React, { useEffect, useState } from "react";
import { Container, Row, Col, Button, ButtonGroup } from "react-bootstrap";
import ProjectCard from "./ProjectCards";
//import proyectos from "../../Datos/datos_proyectos.json";
import { urlApi } from "../../Services/urls";
import Analytics from "../Analytics";
import LoadingSpinner from "../Loading/LoadingSpinner"
import MetaData from "../../Services/MetaData";
import { FaGlobe, FaMobile, FaThLarge } from "react-icons/fa";

function Projects() {
  const [proyectos, setProyectos] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [filtroActivo, setFiltroActivo] = useState('todos') // 'todos', 'web', 'app'
  Analytics("Proyectos")
  
  async function obtenerDatos() {
    try {
      const fullUrl = `${urlApi}api/porfolio/recuperar.php`;
      console.log('Fetching from:', fullUrl);
      const data = await fetch(fullUrl);
      
      console.log('Response status:', data.status);
      console.log('Response headers:', data.headers);
      
      const textResponse = await data.text();
      console.log('Raw response (first 200 chars):', textResponse.substring(0, 200));
      console.log('Content-Type:', data.headers.get('content-type'));
      console.log('Response URL:', data.url);
      
      if (!data.ok) {
        throw new Error(`HTTP error! status: ${data.status}, response: ${textResponse.substring(0, 500)}`);
      }
      
      // Verificar si la respuesta parece HTML
      if (textResponse.trim().startsWith('<')) {
        throw new Error(`Received HTML instead of JSON. Response starts with: ${textResponse.substring(0, 100)}`);
      }
      
      const users = JSON.parse(textResponse);
      const nueva = [...users]
      nueva.reverse()
      setProyectos(nueva)
      setIsLoading(false);
    } catch (error) {
      console.error('Error fetching data:', error);
      setIsLoading(false);
      // Opcional: mostrar un mensaje de error al usuario
    }
  }
  useEffect(() => {
    obtenerDatos()
  }, [])

  // Función para filtrar proyectos
  const proyectosFiltrados = proyectos.filter(proyecto => {
    if (filtroActivo === 'todos') return true;
    if (filtroActivo === 'web') return proyecto.tipo === 'web' || !proyecto.tipo; // Si no tiene tipo, asumimos web
    if (filtroActivo === 'app') return proyecto.tipo === 'app';
    return true;
  });

  const renderProyectos = (
    <div>
      <MetaData
         _title={'Porfolio de Juan Carlos Macías, proyectos desarrollados | Desarrollo web jcms'}
         _descr={'Soluciones digitales para distintas entidades, usando PHP, SQL, Java, React, JavaScript, Symfony'}
         _url={'https://www.juancarlosmacias.es/project'}
         _img={'https://www.juancarlosmacias.es/Assets/Projects/portfolio.png'}
         
      />
      <h2 className="project-heading">
        Últimos <strong className="purple">trabajos</strong>
      </h2>
      
      {/* Filtros con iconos */}
      <div style={{ textAlign: 'center', marginBottom: '30px' }}>
        <ButtonGroup>
          <Button 
            variant={filtroActivo === 'todos' ? 'primary' : 'outline-primary'}
            onClick={() => setFiltroActivo('todos')}
            style={{ margin: '0 5px' }}
          >
            <FaThLarge style={{ marginRight: '8px' }} />
            Todos
          </Button>
          <Button 
            variant={filtroActivo === 'web' ? 'primary' : 'outline-primary'}
            onClick={() => setFiltroActivo('web')}
            style={{ margin: '0 5px' }}
          >
            <FaGlobe style={{ marginRight: '8px' }} />
            Web
          </Button>
          <Button 
            variant={filtroActivo === 'app' ? 'primary' : 'outline-primary'}
            onClick={() => setFiltroActivo('app')}
            style={{ margin: '0 5px' }}
          >
            <FaMobile style={{ marginRight: '8px' }} />
            Apps
          </Button>
        </ButtonGroup>
      </div>

      <Row style={{ justifyContent: "center", paddingBottom: "10px" }}>
        {proyectosFiltrados.map((datos) => (
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
