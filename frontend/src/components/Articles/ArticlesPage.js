import React, { useState, useEffect } from "react";
import { Container, Row, Col, Button, ButtonGroup, Form, InputGroup, Spinner, Alert } from "react-bootstrap";
import { FaSearch, FaFilter, FaTh, FaList } from "react-icons/fa";
import ArticleCard from "./ArticleCard";
import LoadingSpinner from "../Loading/LoadingSpinner";
import Analytics from "../Analytics";
import MetaData from "../../Services/MetaData";
import { urlApi, API_ENDPOINTS } from "../../Services/urls";

function ArticlesPage() {
  const [articles, setArticles] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [pagination, setPagination] = useState({});
  const [currentPage, setCurrentPage] = useState(1);
  const [viewMode, setViewMode] = useState('grid'); // 'grid' o 'list'

  Analytics("Artículos");

  // Cargar artículos
  const fetchArticles = async (page = 1) => {
    try {
      setIsLoading(true);
      const apiUrl = `${API_ENDPOINTS.portfolio.articles}?page=${page}&limit=12`;
      
      console.log('🔍 Fetching articles from:', apiUrl);
      console.log('🔍 API_ENDPOINTS:', API_ENDPOINTS);
      
      const response = await fetch(apiUrl);
      
      console.log('🔍 Response status:', response.status);
      console.log('🔍 Response headers:', Object.fromEntries(response.headers.entries()));
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const result = await response.json();
      console.log('🔍 API Response:', result);
      
      if (result.success) {
        setArticles(result.data.articles || []);
        setPagination(result.data.pagination || {});
        setError(null);
      } else {
        setError(result.message || 'Error al cargar artículos');
      }
    } catch (error) {
      console.error('❌ Error fetching articles:', error);
      setError(`Error de conexión: ${error.message}`);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchArticles(currentPage);
  }, [currentPage]);

  // Filtrar artículos por búsqueda
  const filteredArticles = articles.filter(article => {
    const matchesSearch = !searchTerm || 
      article.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (article.excerpt && article.excerpt.toLowerCase().includes(searchTerm.toLowerCase()));
    
    return matchesSearch;
  });

  // Manejar cambio de página
  const handlePageChange = (page) => {
    setCurrentPage(page);
    window.scrollTo(0, 0);
  };

  // Resetear filtros
  const resetFilters = () => {
    setSearchTerm('');
  };

  if (isLoading && articles.length === 0) {
    return <LoadingSpinner />;
  }

  return (
    <Container fluid className="articles-section">
      <MetaData
        _title={'Artículos y Blog | Portfolio JCMS'}
        _descr={'Reflexiones, tutoriales y experiencias en desarrollo web, tecnología y proyectos personales'}
        _url={`${urlApi}articles`}
        _img={`${urlApi}Assets/Projects/portfolio.png`}
        _type="website"
        _author="Juan Carlos Macías"
      />

      <Container>
        {/* Header */}
        <Row >
          <Col md={8}>
            <h1 className="project-heading">
              Artículos & <strong className="purple">Blog</strong>
            </h1>
            <p className="lead text-muted">
              Reflexiones, tutoriales y experiencias en desarrollo
            </p>
          </Col>
          <Col md={4} className="d-flex align-items-center justify-content-end">
            <div className="view-toggle">
              <ButtonGroup size="sm">
                <Button 
                  variant={viewMode === 'grid' ? 'primary' : 'outline-primary'}
                  onClick={() => setViewMode('grid')}
                >
                  <FaTh />
                </Button>
                <Button 
                  variant={viewMode === 'list' ? 'primary' : 'outline-primary'}
                  onClick={() => setViewMode('list')}
                >
                  <FaList />
                </Button>
              </ButtonGroup>
            </div>
          </Col>
        </Row>

        {/* Filtros y Búsqueda */}
        <Row className="filters-section mb-4">
          <Col md={8}>
            <InputGroup>
              <InputGroup.Text>
                <FaSearch />
              </InputGroup.Text>
              <Form.Control
                type="text"
                placeholder="Buscar artículos..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </InputGroup>
          </Col>
          <Col md={4}>
            {searchTerm && (
              <Button 
                variant="outline-secondary" 
                onClick={resetFilters}
                className="w-100"
              >
                <FaFilter /> Limpiar búsqueda
              </Button>
            )}
          </Col>
        </Row>

        {/* Error State */}
        {error && (
          <Row>
            <Col>
              <Alert variant="danger">
                <h5>Error al cargar artículos</h5>
                <p>{error}</p>
                <Button variant="outline-danger" onClick={() => fetchArticles(currentPage)}>
                  Reintentar
                </Button>
              </Alert>
            </Col>
          </Row>
        )}

        {/* Estadísticas */}
        {!error && (
          <Row className="mb-3">
            <Col>
              <small className="text-muted">
                {filteredArticles.length} artículo{filteredArticles.length !== 1 ? 's' : ''} 
                {searchTerm && ` encontrado${filteredArticles.length !== 1 ? 's' : ''} para "${searchTerm}"`}
              </small>
            </Col>
          </Row>
        )}

        {/* Grid de Artículos */}
        {!error && filteredArticles.length > 0 ? (
          <Row className={viewMode === 'grid' ? 'g-4' : 'g-2'}>
            {filteredArticles.map((article) => (
              <Col 
                key={article.id}
                md={viewMode === 'grid' ? 6 : 12} 
                lg={viewMode === 'grid' ? 4 : 12}
                className="article-card-container"
              >
                <ArticleCard {...article} />
              </Col>
            ))}
          </Row>
        ) : !error && (
          <Row>
            <Col className="text-center py-5">
              <h3 className="text-muted">No se encontraron artículos</h3>
              <p className="text-muted">
                {searchTerm 
                  ? 'Intenta modificar el término de búsqueda'
                  : 'Aún no hay artículos publicados'
                }
              </p>
              {searchTerm && (
                <Button variant="primary" onClick={resetFilters}>
                  Ver todos los artículos
                </Button>
              )}
            </Col>
          </Row>
        )}

        {/* Paginación */}
        {pagination.total_pages > 1 && (
          <Row className="mt-5">
            <Col className="d-flex justify-content-center">
              <div className="pagination-controls">
                <Button 
                  variant="outline-primary" 
                  disabled={!pagination.has_prev || isLoading}
                  onClick={() => handlePageChange(currentPage - 1)}
                  className="me-2"
                >
                  Anterior
                </Button>
                
                <span className="mx-3 d-flex align-items-center">
                  Página {pagination.current_page} de {pagination.total_pages}
                </span>
                
                <Button 
                  variant="outline-primary" 
                  disabled={!pagination.has_next || isLoading}
                  onClick={() => handlePageChange(currentPage + 1)}
                  className="ms-2"
                >
                  Siguiente
                </Button>
              </div>
            </Col>
          </Row>
        )}

        {/* Loading overlay */}
        {isLoading && articles.length > 0 && (
          <div className="text-center mt-3">
            <Spinner animation="border" size="sm" /> Cargando...
          </div>
        )}
      </Container>
    </Container>
  );
}

export default ArticlesPage;