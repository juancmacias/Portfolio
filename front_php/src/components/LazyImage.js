import React from 'react';
import PropTypes from 'prop-types';

/**
 * Componente LazyImage - Imagen con carga diferida (lazy loading)
 * 
 * Características:
 * - loading="lazy" para carga bajo demanda
 * - decoding="async" para no bloquear el rendering
 * - Dimensiones explícitas para evitar CLS (Cumulative Layout Shift)
 * - Mejora Core Web Vitals (LCP, CLS)
 * 
 * Uso:
 * <LazyImage 
 *   src="/assets/image.jpg"
 *   alt="Descripción"
 *   width="300"
 *   height="200"
 *   className="img-fluid"
 * />
 */
function LazyImage({ src, alt, width, height, className = '', style = {} }) {
    return (
        <img 
            src={src}
            alt={alt}
            loading="lazy"
            decoding="async"
            width={width}
            height={height}
            className={className}
            style={style}
        />
    );
}

LazyImage.propTypes = {
    src: PropTypes.string.isRequired,
    alt: PropTypes.string.isRequired,
    width: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    height: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    className: PropTypes.string,
    style: PropTypes.object
};

export default LazyImage;
