import React from "react";
import MetaTags from 'react-meta-tags';

function MetaData(props) {
    return (
        <MetaTags>
            <title>{props._title}</title>
            <meta name="description" content={props._descr} />
            <meta property="og:title" content={props._title} />
            <meta property="og:description" content={props._descr} />
            <meta property="og:type" content="porfolio" />
            <meta property="og:url" content={props._url} />
            <meta property="og:image" content={props._img} />
            <link rel="canonical" href={props._url} />
        </MetaTags>
    )
}
export default MetaData;