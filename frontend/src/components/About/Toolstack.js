import React from "react";
import { Col, Row } from "react-bootstrap";
import {
  SiAndroidstudio,
  SiIntellijidea,
  SiVisualstudiocode,
  SiPostman,
  SiSlack,
  SiVercel,
  SiEclipseide 
} from "react-icons/si";

function Toolstack() {
  return (
    <Row style={{ justifyContent: "center", paddingBottom: "50px" }}>
      <Col xs={4} md={2} className="tech-icons">
        <SiAndroidstudio title="Android Studio"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiVisualstudiocode title="visual Studio Code"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiPostman title="Postman"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiSlack title="Slack"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiVercel title="Vercel"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiIntellijidea title="InetlliJ"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiEclipseide title="Eclipse"/>
      </Col>
    </Row>
  );
}

export default Toolstack;
