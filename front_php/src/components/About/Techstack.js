import React from "react";
import { Col, Row } from "react-bootstrap";
import {
  DiJavascript1,
  DiReact,
  DiNodejs,
  DiMongodb,
  DiJava,
  DiPython,
  DiDocker,
  DiSqllite,
  DiPostgresql,
  DiMysql,
  DiPhp
} from "react-icons/di";

import {
  SiFirebase,
  SiNextdotjs,
} from "react-icons/si";


function Techstack() {
  return (
    <Row style={{ justifyContent: "center", paddingBottom: "50px" }}>
      <Col xs={4} md={2} className="tech-icons">
        <DiJavascript1 title="JavaScript" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiPhp title="PHP" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiPython title="Python"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiDocker title="Docker"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiNodejs title="Node.js" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiReact title="React.js" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiMysql title="MySQL" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiPostgresql title="PostgreSQL" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <DiSqllite title="SQLite" />
      </Col>

      <Col xs={4} md={2} className="tech-icons">
        <DiMongodb title="MongoDB"/>
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiNextdotjs title="Next.js" />
      </Col>
      <Col xs={4} md={2} className="tech-icons">
        <SiFirebase title="Firebase"/>
      </Col>

      <Col xs={4} md={2} className="tech-icons">
        <DiJava title="Java"/>
      </Col>
    </Row>
  );
}

export default Techstack;
