import React from "react";
import Card from "react-bootstrap/Card";
import Button from "react-bootstrap/Button";
import { CgWebsite } from "react-icons/cg";
import { BsGithub } from "react-icons/bs";
import Analytics from "../Analytics";
import LazyImage from "../LazyImage";

function ProjectCards(props) {
  return (
    <Card className="project-card-view">
      <LazyImage 
        src={props.imgPath} 
        alt={props.title}
        width="100%"
        height="200"
        className="card-img-top"
        style={{ objectFit: 'cover' }}
      />
      <Card.Body>
        <Card.Title>{props.title}</Card.Title>
        <Card.Text style={{ textAlign: "justify" }}>
          {props.description}
        </Card.Text>
        <div className="card-dow" >
          <Button variant="primary" href={props.isBlog ? props.demoLink : props.ghLink} title={props.title} target="_blank" 
          onClick={() => {
                  Analytics(props.isBlog ? props.demoLink : props.ghLink)
                }}
                >
            {props.isBlog ? (<CgWebsite />) : (<BsGithub />)}
            {props.isBlog ? (" Demo") : (" GitHub")}
            
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
