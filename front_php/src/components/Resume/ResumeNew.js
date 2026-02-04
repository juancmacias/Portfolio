import React, { useState, useEffect } from "react";
import { Container, Row } from "react-bootstrap";
import Button from "react-bootstrap/Button";
import Analytics from "../Analytics";
import LoadingSpinner from "../Loading/LoadingSpinner";
import { AiOutlineDownload } from "react-icons/ai";
import MetaData from "../../Services/MetaData";
import { urlApi } from "../../Services/urls";

function ResumeNew() {
  Analytics("CV")
  const [width, setWidth] = useState(1200);
  const [ isLoading, setIsLoading] = useState(true)
  const pdf = "../../Assets/pdf/cv.pdf"
  useEffect(() => {
    setWidth(window.innerWidth);
    setIsLoading(false)
  }, []);
  const renderCV = (
    <div>
      <MetaData
         _title={'Porfolio de Juan Carlos Macías, mi CV | Desarrollo web jcms'}
         _descr={'Aquí tienes mi CV, puedes descargalo para valorarlo más detenidamente.'}
         _url={`${urlApi}resume`}
         _img={`${urlApi}Assets/Projects/portfolio.png`}
      />
      <Row style={{ justifyContent: "center", position: "relative" }}>
          <Button
            variant="primary"
            style={{ maxWidth: "250px" }}
            onClick={() => {
              const link = document.createElement('a');
              link.href = require("../../Assets/pdf/cv.pdf");
              link.download = "cv.pdf";
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }}
          >
            <AiOutlineDownload />
            &nbsp;Descargar CV
          </Button>
        </Row>
        <Row className="resume">
            <embed src={pdf} scale={width > 786 ? 1.7 : 0.6} className="pdfcss"  type="application/pdf" />
          {/*
          <Document file={pdf} className="d-flex justify-content-center">
            <Page pageNumber={1} scale={width > 786 ? 1.7 : 0.6} />
          </Document>
          */}
        </Row>

        <Row style={{ justifyContent: "center", position: "relative" }}>
          <Button
            variant="primary"
            style={{ maxWidth: "250px" }}
            onClick={() => {
              const link = document.createElement('a');
              link.href = require("../../Assets/pdf/cv.pdf");
              link.download = "cv.pdf";
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }}
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
