import React from "react";
import Card from "react-bootstrap/Card";
import Button from "react-bootstrap/Button";
import { CgWebsite } from "react-icons/cg";
import { BsGithub } from "react-icons/bs";
import Analytics from "../Analytics";

function ProjectCards(props) {
  return (
    <Card className="project-card-view">
      <Card.Img variant="top" src={props.imgPath} alt={props.title} />
      <Card.Body>
        <Card.Title>{props.title}</Card.Title>
        <Card.Text style={{ textAlign: "justify" }}>
          {props.description}
        </Card.Text>
        <div className="card-dow" >
          <Button variant="primary" href={props.isBlog ? props.demoLink : props.ghLink} title={props.title} target="_blank">
            {props.isBlog ? (<CgWebsite />) : (<BsGithub />)}
            {props.isBlog ? (" Demo") : (" GitHub")}
            onClick={() => {
                  Analytics(props.isBlog ? props.demoLink : props.ghLink)
                }}
          </Button>
        
        {"\n"}
        {"\n"}

        {/* If the component contains Demo link and if it's not a Blog then, it will render the below component  */}

        {!props.isBlog && props.demoLink && (
          <Button
            variant="primary"
            href={props.demoLink}
            target="_blank"
            style={{ marginLeft: "10px" }}
            onClick={() => {
              Analytics(props.demoLink)
            }}
          >
            <CgWebsite /> &nbsp;
            {"Demo"}
          </Button>
        )}
        </div>
      </Card.Body>
    </Card>
  );
}
export default ProjectCards;
