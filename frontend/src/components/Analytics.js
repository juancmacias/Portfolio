import ReactGA from "react-ga4";

const Analytics = (onAction) => {
    console.log("enviar "+ onAction)
    ReactGA.initialize("G-98DNV82Z6L");
    ReactGA.send({ hitType: "pageview", page: "/my-path", title: onAction });
    ReactGA.event({
        category: 'User',
        action: onAction,
        label: onAction,
    });
}
export default Analytics;