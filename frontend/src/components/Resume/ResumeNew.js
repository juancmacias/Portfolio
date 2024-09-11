import React, { useState, useEffect } from "react";
import { Container, Row } from "react-bootstrap";
import Button from "react-bootstrap/Button";
//import pdf from "../../Assets/cv.pdf";
import Analytics from "../Analytics";
import LoadingSpinner from "../Loading/LoadingSpinner";
import { AiOutlineDownload } from "react-icons/ai";
//import { Document, Page, pdfjs } from "react-pdf";
//import "react-pdf/dist/esm/Page/AnnotationLayer.css";
//pdfjs.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;
//pdfjs.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjs.version}/pdf.worker.min.js`;
//pdfjs.GlobalWorkerOptions.workerSrc = `//unpkg.com/pdfjs-dist@${pdfjs.version}/legacy/build/pdf.worker.min.mjs`;
/*
pdfjs.GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url,
).toString();
*/
import MetaData from "../../Services/MetaData";

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
         _title={'Porfolio de Juan Carlos Macías, mi CV.'}
         _descr={'Aquí tienes mi CV, puedes descargalo para valorarlo más detenidamente.'}
         _url={'http://www.juancarlosmacias.es/resume'}
         _img={'https://www.juancarlosmacias.es/Assets/Projects/portfolio.png'}
      />
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
