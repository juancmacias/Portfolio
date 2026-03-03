import React, { useState, useEffect } from "react";
import { Container, Row, Col, Badge, Button, Alert } from "react-bootstrap";
import { useParams, Link } from "react-router-dom";
import { FaArrowLeft, FaCalendar, FaClock, FaTag, FaShare } from "react-icons/fa";
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import LoadingSpinner from "../Loading/LoadingSpinner";
import Analytics from "../Analytics";
import MetaData from "../../Services/MetaData";
import { urlApi, API_ENDPOINTS } from "../../Services/urls";

function ArticleView() {
  const { slug } = useParams();
  const [article, setArticle] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Registrar vista de artículo
  const registerArticleView = async (articleId) => {
    try {
      const response = await fetch(API_ENDPOINTS.portfolio.viewArticle, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          article_id: articleId
        })
      });
      
      if (response.ok) {
        const result = await response.json();
        console.log('📊 Vista registrada:', result);
      }
    } catch (error) {
      console.warn('No se pudo registrar la vista:', error);
      // No mostrar error al usuario, es funcionalidad secundaria
    }
  };

  // Cargar artículo por slug
  const fetchArticle = async () => {
    try {
      setIsLoading(true);
      
      // Primero obtener todos los artículos para encontrar el ID por slug
      const listResponse = await fetch(API_ENDPOINTS.portfolio.articles);
      
      if (!listResponse.ok) {
        throw new Error(`Error HTTP: ${listResponse.status}`);
      }

      const listResult = await listResponse.json();
      
      if (!listResult.success) {
        throw new Error(listResult.message || 'Error al obtener lista de artículos');
      }

      // Buscar el artículo por slug
      const articles = listResult.data.articles || [];
      const foundArticle = articles.find(a => a.slug === slug);
      
      if (!foundArticle) {
        setError('Artículo no encontrado');
        return;
      }

      // Obtener el artículo completo por ID
      const response = await fetch(`${API_ENDPOINTS.portfolio.articles}?id=${foundArticle.id}`);
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const result = await response.json();
      
      if (result.success) {
        setArticle(result.data);
        Analytics(`Article View: ${result.data.title}`);
        
        // Registrar vista del artículo
        registerArticleView(result.data.id);
        
        setError(null);
      } else {
        setError(result.message || 'Error al cargar el artículo');
      }
    } catch (error) {
      console.error('Error fetching article:', error);
      setError('Error de conexión. Verifica que el servidor esté funcionando.');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (slug) {
      fetchArticle();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [slug]);

  // Calcular tiempo de lectura
  const calculateReadTime = (content) => {
    if (!content) return 1;
    const words = content.split(/\s+/).length;
    const readTime = Math.ceil(words / 250);
    return readTime < 1 ? 1 : readTime;
  };

  // Formatear fecha
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Obtener color por tag
  const getCategoryColor = (tag) => {
    const colors = {
      'tecnologia': 'primary',
      'tech': 'primary',
      'proyectos': 'success',
      'projects': 'success',
      'tutoriales': 'info',
      'tutorials': 'info',
      'personal': 'warning',
      'desarrollo': 'secondary',
      'react': 'info',
      'php': 'dark',
      'javascript': 'warning'
    };
    return colors[tag.toLowerCase()] || 'outline-secondary';
  };

  // Compartir artículo
  const shareArticle = () => {
    if (navigator.share && article) {
      navigator.share({
        title: article.title,
        text: article.excerpt,
        url: window.location.href,
      });
    } else {
      // Fallback: copiar URL al portapapeles
      navigator.clipboard.writeText(window.location.href);
      alert('URL copiada al portapapeles');
    }
    Analytics(`Article Share: ${article?.title}`);
  };

  if (isLoading) {
    return <LoadingSpinner />;
  }

  if (error) {
    return (
      <Container className="py-5">
        <Row>
          <Col md={8} className="mx-auto">
            <Alert variant="danger" className="text-center">
              <h4>Error al cargar el artículo</h4>
              <p>{error}</p>
              <div>
                <Button as={Link} to="/articles" variant="primary" className="me-2">
                  Ver todos los artículos
                </Button>
                <Button variant="outline-danger" onClick={fetchArticle}>
                  Reintentar
                </Button>
              </div>
            </Alert>
          </Col>
        </Row>
      </Container>
    );
  }

  if (!article) {
    return (
      <Container className="py-5">
        <Row>
          <Col md={8} className="mx-auto text-center">
            <h2>Artículo no encontrado</h2>
            <p>El artículo que buscas no existe o ha sido eliminado.</p>
            <Button as={Link} to="/articles" variant="primary">
              Ver todos los artículos
            </Button>
          </Col>
        </Row>
      </Container>
    );
  }

  const readTime = calculateReadTime(article.content);
  const displayDate = article.published_at || article.created_at;

  return (
    <Container fluid className="article-view-section">
      <MetaData
        _title={`${article.title} | Blog JCMS`}
        _descr={article.meta_description || article.excerpt || `Artículo sobre ${article.title}`}
        _url={`${urlApi}article/${article.slug}`}
        _img={article.featured_image || `${urlApi}Assets/Projects/portfolio.png`}
        _type="article"
        _published={article.published_at}
        _modified={article.updated_at}
        _author="Juan Carlos Macías"
      />

      <Container>
        {/* Navegación */}
        <Row className="mb-4">
          <Col>
            <Button 
              as={Link} 
              to="/articles" 
              variant="outline-secondary" 
              size="sm"
            >
              <FaArrowLeft className="me-2" />
              Volver a artículos
            </Button>
          </Col>
        </Row>

        {/* Header del artículo */}
        <Row>
          <Col lg={8} className="mx-auto">
            {/* Imagen destacada */}
            {article.featured_image && (
              <div className="article-featured-image mb-4">
                <img 
                  src={article.featured_image} 
                  alt={article.title}
                  className="img-fluid rounded shadow"
                  style={{ width: '100%', maxHeight: '400px', objectFit: 'cover' }}
                />
              </div>
            )}

            {/* Meta información */}
            <div className="article-meta mb-3">
              {article.tags && article.tags.length > 0 && (
                <div className="mb-2">
                  {article.tags.map((tag, index) => (
                    <Badge 
                      key={index} 
                      variant={getCategoryColor(tag)} 
                      className="me-2 mb-1"
                    >
                      <FaTag className="me-1" />
                      {tag}
                    </Badge>
                  ))}
                </div>
              )}
              
              <div className="d-flex flex-wrap gap-3 text-muted">
                <small>
                  <FaCalendar className="me-1" />
                  {formatDate(displayDate)}
                </small>
                <small>
                  <FaClock className="me-1" />
                  {readTime} min de lectura
                </small>
                <Button 
                  variant="link" 
                  size="sm" 
                  className="p-0 text-muted"
                  onClick={shareArticle}
                >
                  <FaShare className="me-1" />
                  Compartir
                </Button>
              </div>
            </div>

            {/* Título */}
            <h1 className="article-title mb-4">
              {article.title}
            </h1>

            {/* Excerpt */}
            {article.excerpt && (
              <div className="article-excerpt lead text-muted mb-4">
                {article.excerpt}
              </div>
            )}

            {/* Contenido */}
            <div className="article-content">
              <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                components={{
                  // Personalizar renderizado de elementos
                  h1: ({children, ...props}) => <h2 className="mt-4 mb-3" {...props}>{children}</h2>,
                  h2: ({children, ...props}) => <h3 className="mt-4 mb-3" {...props}>{children}</h3>,
                  h3: ({children, ...props}) => <h4 className="mt-3 mb-2" {...props}>{children}</h4>,
                  p: ({children, ...props}) => <p className="mb-3" {...props}>{children}</p>,
                  a: ({children, ...props}) => <a className="text-primary" target="_blank" rel="noopener noreferrer" {...props}>{children}</a>,
                  blockquote: ({children, ...props}) => (
                    <blockquote className="blockquote border-start border-primary border-3 ps-3 py-2 bg-light" {...props}>{children}</blockquote>
                  ),
                  code: ({children, inline, ...props}) => (
                    inline 
                      ? <code className="bg-light px-1 rounded" {...props}>{children}</code>
                      : <pre className="bg-dark text-light p-3 rounded overflow-auto"><code {...props}>{children}</code></pre>
                  )
                }}
              >
                {article.content}
              </ReactMarkdown>
            </div>

            {/* Footer del artículo */}
            <hr className="my-5" />
            
            <div className="article-footer">
              <Row>
                <Col md={6}>
                  <small className="text-muted">
                    Publicado el {formatDate(displayDate)}
                    {article.updated_at !== article.created_at && (
                      <>
                        <br />
                        Actualizado el {formatDate(article.updated_at)}
                      </>
                    )}
                  </small>
                </Col>
                <Col md={6} className="text-md-end">
                  <Button 
                    as={Link} 
                    to="/articles" 
                    variant="primary"
                  >
                    Ver más artículos
                  </Button>
                </Col>
              </Row>
            </div>
          </Col>
        </Row>
      </Container>
    </Container>
  );
}

export default ArticleView;