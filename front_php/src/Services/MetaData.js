import React from "react";
import MetaTags from 'react-meta-tags';

function MetaData(props) {
    // Formatear fechas para metadatos
    const formatDateForMeta = (dateString) => {
        if (!dateString) return null;
        const date = new Date(dateString);
        return date.toISOString();
    };

    // Generar structured data para artículos
    const generateStructuredData = () => {
        if (props._type === "article") {
            const structuredData = {
                "@context": "https://schema.org",
                "@type": "Article",
                "headline": props._title,
                "description": props._descr,
                "url": props._url,
                "author": {
                    "@type": "Person",
                    "name": props._author || "Juan Carlos Macías"
                },
                "publisher": {
                    "@type": "Person",
                    "name": "Juan Carlos Macías"
                }
            };

            if (props._published) {
                structuredData.datePublished = formatDateForMeta(props._published);
            }
            if (props._modified) {
                structuredData.dateModified = formatDateForMeta(props._modified);
            }
            if (props._img) {
                structuredData.image = props._img;
            }

            return JSON.stringify(structuredData);
        }
        return null;
    };

    const structuredData = generateStructuredData();

    return (
        <MetaTags>
            {/* Metadatos básicos */}
            <title>{props._title}</title>
            <meta name="description" content={props._descr} />
            
            {/* Metadatos de autor */}
            {props._author && <meta name="author" content={props._author} />}
            
            {/* Metadatos de licencia y copyright */}
            <meta name="license" content="Creative Commons BY-SA 4.0" />
            <meta name="copyright" content={`© ${new Date().getFullYear()} Juan Carlos Macías`} />
            
            {/* Open Graph */}
            <meta property="og:title" content={props._title} />
            <meta property="og:description" content={props._descr} />
            <meta property="og:type" content={props._type || "website"} />
            <meta property="og:url" content={props._url} />
            {props._img && <meta property="og:image" content={props._img} />}
            
            {/* Metadatos específicos de artículos */}
            {props._type === "article" && (
                <>
                    {props._published && (
                        <meta property="article:published_time" content={formatDateForMeta(props._published)} />
                    )}
                    {props._modified && (
                        <meta property="article:modified_time" content={formatDateForMeta(props._modified)} />
                    )}
                    {props._author && (
                        <meta property="article:author" content={props._author} />
                    )}
                </>
            )}
            
            {/* Twitter Card */}
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={props._title} />
            <meta name="twitter:description" content={props._descr} />
            {props._img && <meta name="twitter:image" content={props._img} />}
            
            {/* URL canónica */}
            <link rel="canonical" href={props._url} />
            
            {/* Structured Data para artículos */}
            {structuredData && (
                <script type="application/ld+json">
                    {structuredData}
                </script>
            )}
        </MetaTags>
    )
}

export default MetaData;