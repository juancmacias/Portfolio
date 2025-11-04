import React, { useState, useEffect } from "react";
import { Container, Row, Col, Button } from "react-bootstrap";
import { Link } from "react-router-dom";
import ArticleCard from "../Articles/ArticleCard";
import { API_ENDPOINTS } from "../../Services/urls";

function RecentArticles() {
  const [articles, setArticles] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const fetchRecentArticles = async () => {
      try {
        const response = await fetch(`${API_ENDPOINTS.portfolio.articles}?limit=3`);
        const result = await response.json();
        
        if (result.success && result.data.articles) {
          setArticles(result.data.articles.slice(0, 3));
        }
      } catch (error) {
        console.error('Error fetching recent articles:', error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchRecentArticles();
  }, []);

  if (isLoading || articles.length === 0) {
    return null; // No mostrar nada si está cargando o no hay artículos
  }

  return (
    <Container fluid className="recent-articles-section py-5">
      <Container>
        <Row>
          <Col>
            <h2 className="project-heading">
              Últimos <strong className="purple">artículos</strong>
            </h2>
            <p className="text-muted mb-4">
              Reflexiones y experiencias recientes en desarrollo
            </p>
          </Col>
        </Row>
        
        <Row className="g-4">
          {articles.map((article) => (
            <Col key={article.id} md={6} lg={4}>
              <ArticleCard {...article} />
            </Col>
          ))}
        </Row>
        
        <Row className="mt-4">
          <Col className="text-center">
            <Button 
              as={Link} 
              to="/articles" 
              variant="outline-primary"
              size="lg"
            >
              Ver todos los artículos →
            </Button>
          </Col>
        </Row>
      </Container>
    </Container>
  );
}

export default RecentArticles;