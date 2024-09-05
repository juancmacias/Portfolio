import React, { useState, useEffect } from "react";
import { Container, Row } from "react-bootstrap";
import Button from "react-bootstrap/Button";
import pdf from "../../Assets/pdf/cv.pdf";
import Analytics from "../Analytics";
import LoadingSpinner from "../Loading/LoadingSpinner";
import { AiOutlineDownload } from "react-icons/ai";
import { Document, Page, pdfjs } from "react-pdf";
import "react-pdf/dist/esm/Page/AnnotationLayer.css";
pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;

function ResumeNew() {
  Analytics("CV")
  const [width, setWidth] = useState(1200);
  const [ isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    setWidth(window.innerWidth);
    setIsLoading(false)
  }, []);
  const renderCV = (
    <div>
      <Row style={{ justifyContent: "center", position: "relative" }}>
          <Button
            variant="primary"
            href={pdf}
            target="_blank"
            style={{ maxWidth: "250px" }}
          >
            <AiOutlineDownload />
            &nbsp;Descargar CV
          </Button>
        </Row>

        <Row className="resume">
          <Document file={pdf} className="d-flex justify-content-center">
            <Page pageNumber={1} scale={width > 786 ? 1.7 : 0.6} />
          </Document>
        </Row>

        <Row style={{ justifyContent: "center", position: "relative" }}>
          <Button
            variant="primary"
            href={pdf}
            target="_blank"
            style={{ maxWidth: "250px" }}
          >
            <AiOutlineDownload />
            &nbsp;Descargar CV
          </Button>
        </Row>
    </div>
  )
  return (

      <Container fluid className="resume-section">
        {isLoading ? <LoadingSpinner /> : renderCV}
      </Container>

  );
}

export default ResumeNew;
