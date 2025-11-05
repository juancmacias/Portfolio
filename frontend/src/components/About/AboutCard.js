import React from "react";
import Card from "react-bootstrap/Card";
import { ImPointRight } from "react-icons/im";

function AboutCard() {
  return (
    <Card className="quote-card-view">
      <Card.Body>
        <blockquote className="blockquote mb-0">
          <p style={{ textAlign: "justify" }}>
            Hola, soy <span className="purple">Juan Carlos Macías </span>
            y vivo en <span className="purple"> Madrid, España.</span>
            <br />
            Me encuentro en busqueda activa de empleo, reorganizando mi mundo laboral.
            <br />
            Dispongo de de cursos de formación profesional relacionados con el desarrollo de sofware.
            <br />
            <br />
            Cuando no estoy codificando, me gusta hacer otras cosas:
          </p>
          <ul>
            <li className="about-activity">
              <ImPointRight /> Estar con mi familia y amigos
            </li>
            <li className="about-activity">
              <ImPointRight /> Senderismo
            </li>
            <li className="about-activity">
              <ImPointRight /> Viajar
            </li>
            <li className="about-activity">
              <ImPointRight /> Leer
            </li>
            <li className="about-activity">
              <ImPointRight /> Deporte: tenis, carrera 10K, bicicleta
            </li>
            <li className="about-activity">
              <ImPointRight /> Electrónica: IoT, Arduino, Raspberry Pi, Lego
            </li>
          </ul>

        </blockquote>
      </Card.Body>
    </Card>
  );
}

export default AboutCard;
