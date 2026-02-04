import React, { useEffect, useState, useCallback } from "react";
import { Container, Row, Col, Button, ButtonGroup, Pagination } from "react-bootstrap";
import ProjectCard from "./ProjectCards";
import { urlApi } from "../../Services/urls";
import Analytics from "../Analytics";
import LoadingSpinner from "../Loading/LoadingSpinner"
import MetaData from "../../Services/MetaData";
import { FaGlobe, FaMobile, FaThLarge, FaChevronLeft, FaChevronRight } from "react-icons/fa";

function Projects() {
  const [proyectos, setProyectos] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [filtroActivo, setFiltroActivo] = useState('todos') // 'todos', 'web', 'app'
  
  // Estados para paginación
  const [paginaActual, setPaginaActual] = useState(1)
  const [totalPaginas, setTotalPaginas] = useState(1)
  const [totalProyectos, setTotalProyectos] = useState(0)
  const [proyectosPorPagina] = useState(6) // 6 proyectos por página para mejor diseño
  
  Analytics("Proyectos")
  
  const obtenerDatos = useCallback(async (pagina = 1, filtroTipo = '') => {
    try {
      setIsLoading(true);
      
      // Construir URL con parámetros de paginación y filtros
      const params = new URLSearchParams({
        page: pagina,
        limit: proyectosPorPagina
      });
      
      // Añadir filtro de tipo si no es 'todos'
      if (filtroTipo && filtroTipo !== 'todos') {
        params.append('type', filtroTipo);
      }
      
      const fullUrl = `${urlApi}api/portfolio/projects.php?${params.toString()}`;
      console.log('Fetching from:', fullUrl);
      
      const data = await fetch(fullUrl);
      
      console.log('Response status:', data.status);
      console.log('Response headers:', data.headers);
      
      const response = await data.json();
      console.log('API Response:', response);
      
      if (!data.ok) {
        throw new Error(`HTTP error! status: ${data.status}, message: ${response.message || 'Unknown error'}`);
      }
      
      if (!response.success) {
        throw new Error(`API error: ${response.message || 'Unknown API error'}`);
      }
      
      // Los datos están en response.data.projects para la nueva API
      const projects = response.data?.projects || [];
      const pagination = response.data?.pagination || {};
      
      console.log('Projects received:', projects);
      console.log('Pagination info:', pagination);
      
      if (!Array.isArray(projects)) {
        console.error('Projects is not an array:', projects);
        throw new Error('Invalid data format: projects is not an array');
      }
      
      // Actualizar estados con los datos de paginación
      setProyectos(projects);
      setTotalProyectos(pagination.total || 0);
      setTotalPaginas(pagination.pages || 1);
      setPaginaActual(pagination.page || 1);
      
      setIsLoading(false);
    } catch (error) {
      console.error('Error fetching data:', error);
      setIsLoading(false);
      // Opcional: mostrar un mensaje de error al usuario
    }
  }, [proyectosPorPagina]);
  useEffect(() => {
    obtenerDatos(1, filtroActivo)
  }, [filtroActivo, obtenerDatos])

  // Función para cambiar de página
  const cambiarPagina = (nuevaPagina) => {
    if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas) {
      obtenerDatos(nuevaPagina, filtroActivo);
    }
  }

  // Función para cambiar filtro (reinicia a página 1)
  const cambiarFiltro = (nuevoFiltro) => {
    setFiltroActivo(nuevoFiltro);
    setPaginaActual(1);
  }

  // Función para generar números de página para mostrar
  const generarNumerosPagina = () => {
    const delta = 2; // Número de páginas a mostrar antes y después de la actual
    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, paginaActual - delta); i <= Math.min(totalPaginas - 1, paginaActual + delta); i++) {
      range.push(i);
    }

    if (paginaActual - delta > 2) {
      rangeWithDots.push(1, '...');
    } else {
      rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (paginaActual + delta < totalPaginas - 1) {
      rangeWithDots.push('...', totalPaginas);
    } else {
      rangeWithDots.push(totalPaginas);
    }

    return rangeWithDots;
  };

  // Ya no necesitamos filtrar en el frontend, la API lo hace por nosotros
  const proyectosFiltrados = proyectos;

  const renderProyectos = (
    <div>
      <MetaData
         _title={'Porfolio de Juan Carlos Macías, proyectos desarrollados | Desarrollo web jcms'}
         _descr={'Soluciones digitales para distintas entidades, usando PHP, SQL, Java, React, JavaScript, Symfony'}
         _url={`${urlApi}project`}
         _img={`${urlApi}Assets/Projects/portfolio.png`}
         
      />
      <h2 className="project-heading">
        Últimos <strong className="purple">trabajos</strong>
      </h2>
      
      {/* Filtros con iconos */}
      <div style={{ textAlign: 'center', marginBottom: '30px' }}>
        <ButtonGroup>
          <Button 
            variant={filtroActivo === 'todos' ? 'primary' : 'outline-primary'}
            onClick={() => cambiarFiltro('todos')}
            style={{ margin: '0 5px' }}
          >
            <FaThLarge style={{ marginRight: '8px' }} />
            Todos ({totalProyectos})
          </Button>
          <Button 
            variant={filtroActivo === 'web' ? 'primary' : 'outline-primary'}
            onClick={() => cambiarFiltro('web')}
            style={{ margin: '0 5px' }}
          >
            <FaGlobe style={{ marginRight: '8px' }} />
            Web
          </Button>
          <Button 
            variant={filtroActivo === 'app' ? 'primary' : 'outline-primary'}
            onClick={() => cambiarFiltro('app')}
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

      {/* Controles de paginación */}
      {totalPaginas > 1 && (
        <div className="pagination-container" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', marginTop: '30px', marginBottom: '20px' }}>
          <Pagination className="custom-pagination">
            {/* Botón anterior */}
            <Pagination.Prev 
              onClick={() => cambiarPagina(paginaActual - 1)}
              disabled={paginaActual === 1}
            >
              <FaChevronLeft />
            </Pagination.Prev>

            {/* Números de página */}
            {totalPaginas <= 7 ? (
              // Si hay pocas páginas, mostrar todas
              Array.from({ length: totalPaginas }, (_, i) => i + 1).map(numero => (
                <Pagination.Item
                  key={numero}
                  active={numero === paginaActual}
                  onClick={() => cambiarPagina(numero)}
                >
                  {numero}
                </Pagination.Item>
              ))
            ) : (
              // Si hay muchas páginas, usar la lógica inteligente
              generarNumerosPagina().map((numero, index) => (
                numero === '...' ? (
                  <Pagination.Ellipsis key={`ellipsis-${index}`} disabled />
                ) : (
                  <Pagination.Item
                    key={numero}
                    active={numero === paginaActual}
                    onClick={() => cambiarPagina(numero)}
                  >
                    {numero}
                  </Pagination.Item>
                )
              ))
            )}

            {/* Botón siguiente */}
            <Pagination.Next 
              onClick={() => cambiarPagina(paginaActual + 1)}
              disabled={paginaActual === totalPaginas}
            >
              <FaChevronRight />
            </Pagination.Next>
          </Pagination>
        </div>
      )}

      {/* Información de paginación */}
      {totalProyectos > 0 && (
        <div style={{ textAlign: 'center', marginTop: '10px', color: '#666' }}>
          <small>
            Mostrando {proyectos.length} de {totalProyectos} proyectos
            {filtroActivo !== 'todos' && ` (filtro: ${filtroActivo})`}
          </small>
        </div>
      )}
    </div>
  )
  return (
    <Container fluid className="project-section">
      {isLoading ? <LoadingSpinner /> : renderProyectos}
    </Container>

  );
}

export default Projects;
