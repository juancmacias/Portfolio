import { Container, Row, Col } from "react-bootstrap";
//import myImg from "../../Assets/home.png";
import Tilt from "react-parallax-tilt";
import {
  AiFillGithub,
  AiFillInstagram
} from "react-icons/ai";
import {
  FaLinkedinIn,
  FaGooglePlay
} from "react-icons/fa";

function Home2() {
  return (
    <Container fluid className="home-about-section" id="about">
      <Container>
        <Row>
          <Col md={8} className="home-about-description">
            <h3 style={{ fontSize: "2.6em" }}>
              Permíteme <span className="purple"> presentarme </span> 
            </h3>
<p className="home-about-body">
  Desde que comencé a programar he recorrido un camino lleno de aprendizaje, curiosidad y crecimiento constante.
  <br />
  <br />
  Soy <i><b className="purple">desarrollador full stack especializado en inteligencia artificial</b></i>, 
  y recientemente he completado un <i><b className="purple">bootcamp intensivo</b></i> 
  que me ha permitido consolidar mis conocimientos en el desarrollo de aplicaciones web completas e integradas con IA.
  <br />
  <br />
  Tengo una gran pasión por la programación, especialmente en 
  <i>
    <b className="purple"> Java para Android, JavaScript y Python.</b>
  </i>
  <br />
  <br />
  Me interesa particularmente el uso de la tecnología en sectores como 
  <i>
    <b className="purple"> la sanidad, la educación y la electrónica,</b>
  </i> 
  donde la innovación puede marcar una verdadera diferencia.
  <br />
  <br />
  Siempre que tengo la oportunidad, aplico mis conocimientos en la creación de entornos 
  <b className="purple"> 3D </b>
  <i>
    <b className="purple">
      utilizando librerías y frameworks modernos de JavaScript,
    </b>
  </i>
  &nbsp;como 
  <i>
    <b className="purple"> React.js</b>
  </i>
  &nbsp;y&nbsp;
  <i>
    <b className="purple"> Next.js.</b>
  </i>
  <br />
  <br />
  En los últimos meses he desarrollado soluciones 
  <i>
    <b className="purple"> dinámicas y descentralizadas con Python</b>
  </i>
  &nbsp;enfocadas en la creación de 
  <i>
    <b className="purple"> endpoints automatizados e inteligentes.</b>
  </i>
  <br />
  <br />
  Me encanta aprender y seguir mejorando mis habilidades, siempre buscando nuevos retos y oportunidades para crecer como desarrollador.
  <br />
  <br />
</p>
          </Col>
          <Col md={4} className="myAvtar">
            <Tilt>
              <img src="../../Assets/home.png" className="img-fluid" alt="avatar" />
            </Tilt>
          </Col>
        </Row>
        {"\n"}
        {"\n"}
        <Row style={{ paddingTop: 40 }}>
          <Col md={12} className="home-about-social">

            <h3>Puedes encontrarme en:</h3>
            <ul className="home-about-social-links">
            <li className="social-icons">
                <a
                  href="https://play.google.com/store/apps/dev?id=7098282899285176966"
                  target="_blank"
                  rel="noreferrer"
                  className="icon-colour home-social-icons"
                  aria-label="Mira mis apliaciones en Google Play"
                >
                  <FaGooglePlay />
                </a>
              </li>
              <li className="social-icons">
                <a
                  href="https://github.com/juancmacias"
                  target="_blank"
                  rel="noreferrer"
                  className="icon-colour  home-social-icons"
                  aria-label="Colabora en mi repositorio de GitHub"
                >
                  <AiFillGithub />
                </a>
              </li>
              
              <li className="social-icons">
                <a
                  href="https://www.linkedin.com/in/juancarlosmacias/"
                  target="_blank"
                  rel="noreferrer"
                  className="icon-colour  home-social-icons"
                  aria-label="Sigueme por LinkedIn"
                >
                  <FaLinkedinIn />
                </a>
              </li>
              <li className="social-icons">
                <a
                  href="https://www.instagram.com/jcms_madrid/"
                  target="_blank"
                  rel="noreferrer"
                  className="icon-colour home-social-icons"
                  aria-label="Es mi Instagram"
                >
                  <AiFillInstagram />
                </a>
              </li>
            </ul>
          </Col>
        </Row>
      </Container>
    </Container>
  );
}
export default Home2;
