import React from "react";
import { Card, Badge, Button } from "react-bootstrap";
import { Link } from "react-router-dom";
import { FaClock, FaCalendar, FaTag } from "react-icons/fa";
import Analytics from "../Analytics";

function ArticleCard({ 
  id, 
  title, 
  excerpt, 
  slug, 
  tags = [], 
  featured_image, 
  created_at, 
  published_at 
}) {
  
  // Calcular tiempo de lectura aproximado (250 palabras por minuto)
  const calculateReadTime = (text) => {
    if (!text) return 1;
    const words = text.split(' ').length;
    const readTime = Math.ceil(words / 250);
    return readTime < 1 ? 1 : readTime;
  };

  // Formatear fecha
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  // Obtener color por categoría/tag
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

  const readTime = calculateReadTime(excerpt);
  const displayDate = published_at || created_at;
  const primaryTag = (tags && tags.length > 0) ? tags[0] : 'artículo';

  const handleClick = () => {
    Analytics(`Article Card: ${title}`);
  };

  return (
    <Card className="article-card h-100 shadow-sm">
      {featured_image && (
        <div className="article-image-container">
          <Card.Img 
            variant="top" 
            src={featured_image} 
            alt={title}
            className="article-featured-image"
            style={{ height: '200px', objectFit: 'cover' }}
          />
        </div>
      )}
      
      <Card.Body className="d-flex flex-column">
        {/* Meta información */}
        <div className="article-meta mb-2">
          <Badge 
            variant={getCategoryColor(primaryTag)} 
            className="me-2"
          >
            <FaTag className="me-1" />
            {primaryTag}
          </Badge>
          <small className="text-muted">
            <FaClock className="me-1" />
            {readTime} min lectura
          </small>
        </div>
        
        {/* Título */}
        <Card.Title className="article-title">
          <Link 
            to={`/article/${slug}`} 
            className="text-decoration-none"
            onClick={handleClick}
          >
            {title}
          </Link>
        </Card.Title>
        
        {/* Excerpt */}
        <Card.Text className="article-excerpt flex-grow-1">
          {excerpt || 'Este artículo no tiene descripción disponible.'}
        </Card.Text>
        
        {/* Tags */}
        {tags && tags.length > 1 && (
          <div className="article-tags mb-3">
            {tags.slice(1, 4).map((tag, index) => (
              <Badge 
                key={index} 
                variant="outline-secondary" 
                size="sm" 
                className="me-1 mb-1"
              >
                #{tag}
              </Badge>
            ))}
            {tags.length > 4 && (
              <Badge variant="outline-secondary" size="sm">
                +{tags.length - 4} más
              </Badge>
            )}
          </div>
        )}
        
        {/* Footer */}
        <div className="article-footer d-flex justify-content-between align-items-center">
          <small className="text-muted">
            <FaCalendar className="me-1" />
            {formatDate(displayDate)}
          </small>
          <Button 
            variant="outline-primary" 
            size="sm"
            as={Link}
            to={`/article/${slug}`}
            onClick={handleClick}
          >
            Leer más →
          </Button>
        </div>
      </Card.Body>
    </Card>
  );
}

export default ArticleCard;