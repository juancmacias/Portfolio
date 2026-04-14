import React from "react";
import { Container, Row, Col, Form, Button, Alert } from "react-bootstrap";
import { AiOutlineMail, AiOutlinePhone, AiOutlineEnvironment } from "react-icons/ai";
import useContactForm from "../../hooks/useContactForm";
import "./ContactPage.css";

function ContactPage() {
  const {
    formData,
    errors,
    isSubmitting,
    submitSuccess,
    submitError,
    handleChange,
    handleSubmit,
    resetStatus
  } = useContactForm();

  return (
    <Container fluid className="contact-section">
      <Container>
        <Row className="mb-5">
          <Col>
            <h1 className="project-heading text-center">
              ¡Hablemos! <strong className="purple">Contacto</strong>
            </h1>
            <p className="text-center" style={{ fontSize: "1.1em", color: "rgba(255,255,255,0.7)" }}>
              ¿Tienes un proyecto en mente o simplemente quieres decir hola? No dudes en escribirme.
            </p>
          </Col>
        </Row>

        {/* Mensajes de éxito/error */}
        {submitSuccess && (
          <Alert variant="success" dismissible onClose={resetStatus} className="mb-4">
            <Alert.Heading>✅ ¡Mensaje enviado con éxito!</Alert.Heading>
            <p>
              Gracias por contactar conmigo. Te responderé lo antes posible, normalmente en menos de 24 horas.
            </p>
          </Alert>
        )}

        {submitError && (
          <Alert variant="danger" dismissible onClose={resetStatus} className="mb-4">
            <Alert.Heading>❌ Error al enviar</Alert.Heading>
            <p>{submitError}</p>
          </Alert>
        )}

        <Row className="contact-content">
          {/* Columna izquierda: Información de contacto */}
          <Col md={5} className="contact-info-section">
            <div className="contact-info-card">
              <h2 className="mb-4">📧 Información de Contacto</h2>
              
              <div className="contact-item">
                <AiOutlineMail className="contact-icon" />
                <div>
                  <h5>Email</h5>
                  <a href="mailto:juancmaciassalvador@gmail.com">juancmaciassalvador@gmail.com</a>
                </div>
              </div>

              <div className="contact-item">
                <AiOutlinePhone className="contact-icon" />
                <div>
                  <h5>Teléfono</h5>
                  <a href="tel:+34618309775">+34 618 309 775</a>
                </div>
              </div>

              <div className="contact-item">
                <AiOutlineEnvironment className="contact-icon" />
                <div>
                  <h5>Ubicación</h5>
                  <p>Madrid, España</p>
                </div>
              </div>


            </div>
          </Col>

          {/* Columna derecha: Formulario de contacto */}
          <Col md={7}>
            <div className="contact-form-card">
              <h2 className="mb-4">✉️ Envíame un Mensaje</h2>
              
              <Form onSubmit={handleSubmit}>
                <Row>
                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Nombre <span className="text-danger">*</span></Form.Label>
                      <Form.Control
                        type="text"
                        name="name"
                        value={formData.name}
                        onChange={handleChange}
                        placeholder="Tu nombre completo"
                        isInvalid={!!errors.name}
                        disabled={isSubmitting}
                      />
                      <Form.Control.Feedback type="invalid">
                        {errors.name}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>

                  <Col md={6}>
                    <Form.Group className="mb-3">
                      <Form.Label>Email <span className="text-danger">*</span></Form.Label>
                      <Form.Control
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        placeholder="tu@email.com"
                        isInvalid={!!errors.email}
                        disabled={isSubmitting}
                      />
                      <Form.Control.Feedback type="invalid">
                        {errors.email}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                </Row>

                <Row>
                  <Col md={12}>
                    <Form.Group className="mb-3">
                      <Form.Label>Teléfono <span className="text-danger">*</span></Form.Label>
                      <Form.Control
                        type="tel"
                        name="phone"
                        value={formData.phone}
                        onChange={handleChange}
                        placeholder="+34 600 000 000"
                        isInvalid={!!errors.phone}
                        disabled={isSubmitting}
                      />
                      <Form.Control.Feedback type="invalid">
                        {errors.phone}
                      </Form.Control.Feedback>
                    </Form.Group>
                  </Col>
                </Row>

                <Form.Group className="mb-4">
                  <Form.Label>Mensaje <span className="text-danger">*</span></Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={6}
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    placeholder="Cuéntame sobre tu proyecto o lo que necesites..."
                    isInvalid={!!errors.message}
                    disabled={isSubmitting}
                  />
                  <Form.Control.Feedback type="invalid">
                    {errors.message}
                  </Form.Control.Feedback>
                  <Form.Text className="text-muted">
                    Mínimo 10 caracteres
                  </Form.Text>
                </Form.Group>

                {/* Honeypot anti-bot (campo oculto) */}
                <Form.Control
                  type="text"
                  name="website"
                  value={formData.website}
                  onChange={handleChange}
                  tabIndex="-1"
                  autoComplete="off"
                  className="honeypot-field"
                />

                <div className="d-grid">
                  <Button
                    variant="primary"
                    type="submit"
                    size="lg"
                    disabled={isSubmitting}
                    className="submit-button"
                  >
                    {isSubmitting ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Enviando...
                      </>
                    ) : (
                      <>
                        <AiOutlineMail className="me-2" />
                        Enviar Mensaje
                      </>
                    )}
                  </Button>
                </div>

                <p className="mt-3 text-center text-muted small">
                  <span>🔒</span> Tus datos están protegidos y solo se usarán para responder a tu mensaje.
                </p>
              </Form>
            </div>
          </Col>
        </Row>
      </Container>
    </Container>
  );
}

export default ContactPage;
