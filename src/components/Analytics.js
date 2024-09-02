import ReactGA from "react-ga4";

const Analytics = (onAction) => {
    console.log("enviar "+ onAction)
    ReactGA.initialize("G-98DNV82Z6L");
    ReactGA.send({ hitType: "Web", page: "CV - JCMS", title: onAction });
}
export default Analytics;