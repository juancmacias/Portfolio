import React, { useEffect, useState } from "react";
import { Container, Row, Col, Card } from "react-bootstrap";
import { FaEnvelope, FaLinkedin, FaGithub, FaMapMarkerAlt, FaClock } from "react-icons/fa";
import Analytics from "../Analytics";
import MetaData from "../../Services/MetaData";
import { urlApi } from "../../Services/urls";
import { fetchBusinessSeoConfig, getProfessionalServiceStructuredData } from "../../config/seoBusiness";

function Contact() {
    Analytics("Contacto");
    const [localBusinessStructuredData, setLocalBusinessStructuredData] = useState(() => getProfessionalServiceStructuredData());

    useEffect(() => {
        let isMounted = true;

        const loadBusinessSeoConfig = async () => {
            const seoBusinessConfig = await fetchBusinessSeoConfig();
            if (isMounted) {
                setLocalBusinessStructuredData(getProfessionalServiceStructuredData(seoBusinessConfig));
            }
        };

        loadBusinessSeoConfig();

        return () => {
            isMounted = false;
        };
    }, []);
    
    return (
        <section>
            <MetaData
                _title={'Contacto | Juan Carlos Macías - Desarrollador Full Stack'}
                _descr={'Contacta conmigo para consultas profesionales, colaboraciones o proyectos de desarrollo web y soluciones de IA.'}
                _url={`${urlApi}contacto`}
                _img={`${urlApi}Assets/Projects/portfolio.png`}
                _structuredData={localBusinessStructuredData}
            />
            <Container fluid className="about-section container-fluid">
                <Container className="home-content">
                    <Row>
                        <Col md={12} className="mb-4">
                            <h1 className="project-heading">
                                Contacto <strong className="purple">Profesional</strong>
                            </h1>
                            <p className="lead">
                                ¿Tienes un proyecto en mente o quieres colaborar? Estoy disponible para consultas profesionales, 
                                desarrollo de software y soluciones tecnológicas personalizadas.
                            </p>
                        </Col>
                    </Row>

                    <Row className="mt-4">
                        {/* Información de Contacto Principal */}
                        <Col md={6} className="mb-4">
                            <Card className="h-100 shadow-sm">
                                <Card.Body>
                                    <h3 className="purple mb-4">Datos de Contacto</h3>
                                    
                                    <div className="contact-item mb-4">
                                        <h5>
                                            <FaEnvelope className="me-2 purple" />
                                            Email
                                        </h5>
                                        <p>
                                            <a href="mailto:juancmaciassalvador@gmail.com" className="text-decoration-none">
                                                juancmaciassalvador@gmail.com
                                            </a>
                                        </p>
                                        <p className="text-muted small">
                                            Respuesta típica: 24-48 horas laborables
                                        </p>
                                    </div>

                                    <div className="contact-item mb-4">
                                        <h5>
                                            <FaMapMarkerAlt className="me-2 purple" />
                                            Ubicación
                                        </h5>
                                        <p>
                                            Madrid, España<br />
                                            <span className="text-muted small">
                                                Disponible para proyectos remotos y presenciales en la Comunidad de Madrid
                                            </span>
                                        </p>
                                    </div>

                                    <div className="contact-item">
                                        <h5>
                                            <FaClock className="me-2 purple" />
                                            Horario de Respuesta
                                        </h5>
                                        <p className="text-muted">
                                            Lunes a Viernes: 9:00 - 18:00 CET<br />
                                            Fines de semana: Respuesta limitada
                                        </p>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>

                        {/* Redes Sociales y Profesionales */}
                        <Col md={6} className="mb-4">
                            <Card className="h-100 shadow-sm">
                                <Card.Body>
                                    <h3 className="purple mb-4">Encuéntrame en</h3>
                                    
                                    <div className="contact-item mb-4">
                                        <h5>
                                            <FaLinkedin className="me-2" style={{ color: '#0077B5' }} />
                                            LinkedIn
                                        </h5>
                                        <p>
                                            <a 
                                                href="https://www.linkedin.com/in/juancarlosmacias/" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                className="text-decoration-none"
                                            >
                                                linkedin.com/in/juancarlosmacias
                                            </a>
                                        </p>
                                        <p className="text-muted small">
                                            Ideal para oportunidades profesionales y networking
                                        </p>
                                    </div>

                                    <div className="contact-item mb-4">
                                        <h5>
                                            <FaGithub className="me-2" style={{ color: '#333' }} />
                                            GitHub
                                        </h5>
                                        <p>
                                            <a 
                                                href="https://github.com/juancmacias" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                className="text-decoration-none"
                                            >
                                                github.com/juancmacias
                                            </a>
                                        </p>
                                        <p className="text-muted small">
                                            Revisa mis proyectos open source y contribuciones
                                        </p>
                                    </div>

                                    <div className="contact-item">
                                        <h5>Portfolio Web</h5>
                                        <p>
                                            <a 
                                                href="https://www.juancarlosmacias.es" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                className="text-decoration-none"
                                            >
                                                www.juancarlosmacias.es
                                            </a>
                                        </p>
                                        <p className="text-muted small">
                                            Explora mis proyectos y artículos técnicos
                                        </p>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>

                    {/* Servicios Ofrecidos */}
                    <Row className="mt-4">
                        <Col md={12}>
                            <Card className="shadow-sm">
                                <Card.Body>
                                    <h3 className="purple mb-4">¿En qué puedo ayudarte?</h3>
                                    
                                    <Row>
                                        <Col md={6} className="mb-3">
                                            <h5>💻 Desarrollo de Software</h5>
                                            <ul className="text-muted">
                                                <li>Aplicaciones web full stack (React, PHP, Java)</li>
                                                <li>APIs REST y microservicios</li>
                                                <li>Integración de sistemas</li>
                                                <li>Optimización de rendimiento</li>
                                            </ul>
                                        </Col>
                                        <Col md={6} className="mb-3">
                                            <h5>🤖 Inteligencia Artificial</h5>
                                            <ul className="text-muted">
                                                <li>Implementación de modelos LLM (Groq, OpenAI)</li>
                                                <li>Sistemas RAG y chatbots inteligentes</li>
                                                <li>Automatización con IA</li>
                                                <li>Análisis y procesamiento de datos</li>
                                            </ul>
                                        </Col>
                                        <Col md={6} className="mb-3">
                                            <h5>📱 Desarrollo Móvil</h5>
                                            <ul className="text-muted">
                                                <li>Apps nativas Android (Java/Kotlin)</li>
                                                <li>Apps híbridas (React Native)</li>
                                                <li>Integración con backends</li>
                                            </ul>
                                        </Col>
                                        <Col md={6} className="mb-3">
                                            <h5>🎓 Consultoría y Formación</h5>
                                            <ul className="text-muted">
                                                <li>Arquitectura de software</li>
                                                <li>Auditorías de código</li>
                                                <li>Formación técnica para equipos</li>
                                                <li>Migración de sistemas legacy</li>
                                            </ul>
                                        </Col>
                                    </Row>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>

                    {/* Proceso de Contacto */}
                    <Row className="mt-5">
                        <Col md={12}>
                            <Card className="shadow-sm">
                                <Card.Body>
                                    <h3 className="purple mb-4">Proceso de Contacto</h3>
                                    
                                    <Row>
                                        <Col md={3} className="text-center mb-4">
                                            <div className="step-number mb-3">
                                                <span className="badge bg-primary rounded-circle p-3" style={{ fontSize: '1.5em' }}>
                                                    1
                                                </span>
                                            </div>
                                            <h5>Envía tu Consulta</h5>
                                            <p className="text-muted small">
                                                Email con detalles del proyecto o colaboración
                                            </p>
                                        </Col>
                                        <Col md={3} className="text-center mb-4">
                                            <div className="step-number mb-3">
                                                <span className="badge bg-primary rounded-circle p-3" style={{ fontSize: '1.5em' }}>
                                                    2
                                                </span>
                                            </div>
                                            <h5>Análisis Inicial</h5>
                                            <p className="text-muted small">
                                                Revisión de requisitos y viabilidad (24-48h)
                                            </p>
                                        </Col>
                                        <Col md={3} className="text-center mb-4">
                                            <div className="step-number mb-3">
                                                <span className="badge bg-primary rounded-circle p-3" style={{ fontSize: '1.5em' }}>
                                                    3
                                                </span>
                                            </div>
                                            <h5>Reunión Virtual</h5>
                                            <p className="text-muted small">
                                                Video llamada para aclarar detalles
                                            </p>
                                        </Col>
                                        <Col md={3} className="text-center mb-4">
                                            <div className="step-number mb-3">
                                                <span className="badge bg-primary rounded-circle p-3" style={{ fontSize: '1.5em' }}>
                                                    4
                                                </span>
                                            </div>
                                            <h5>Propuesta</h5>
                                            <p className="text-muted small">
                                                Presupuesto, cronograma y plan de trabajo
                                            </p>
                                        </Col>
                                    </Row>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>

                    {/* Información Legal */}
                    <Row className="mt-5">
                        <Col md={12}>
                            <Card className="shadow-sm bg-light">
                                <Card.Body>
                                    <h5 className="mb-3">Información del Responsable</h5>
                                    <p className="mb-2">
                                        <strong>Nombre:</strong> Juan Carlos Macías Salvador<br />
                                        <strong>NIF:</strong> 50170588C<br />
                                        <strong>Dirección:</strong> Calle de Padre Oltra, Madrid, España<br />
                                        <strong>Email:</strong> juancmaciassalvador@gmail.com
                                    </p>
                                    <p className="text-muted small mb-0">
                                        Al contactar, aceptas nuestra{" "}
                                        <a href="/politics">Política de Privacidad</a> y{" "}
                                        <a href="/terminos">Términos y Condiciones</a>.
                                    </p>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                </Container>
            </Container>
        </section>
    );
}

export default Contact;
