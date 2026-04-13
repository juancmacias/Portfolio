/**
 * OptimizedImage Component
 * 
 * Componente React para lazy loading y optimización de imágenes
 * Reduce el tamaño de transferencia y mejora LCP (Largest Contentful Paint)
 * 
 * Uso:
 * import OptimizedImage from './components/OptimizedImage';
 * 
 * <OptimizedImage 
 *   src="/Assets/image.jpg"
 *   alt="Descripción"
 *   width={800}
 *   height={600}
 *   priority={false}
 * />
 */

import React from 'react';
import './OptimizedImage.css';

const OptimizedImage = ({ 
  src, 
  alt, 
  width, 
  height, 
  className = '', 
  priority = false,
  sizes = '100vw'
}) => {
  // Generar srcset para imágenes responsive
  const generateSrcSet = (originalSrc) => {
    const ext = originalSrc.split('.').pop();
    const basePath = originalSrc.replace(`.${ext}`, '');
    
    // Si usas imágenes procesadas, puedes generar múltiples versiones
    return `
      ${basePath}-320w.${ext} 320w,
      ${basePath}-640w.${ext} 640w,
      ${basePath}-1024w.${ext} 1024w,
      ${originalSrc} ${width}w
    `.trim();
  };

  return (
    <img
      src={src}
      alt={alt}
      width={width}
      height={height}
      className={`optimized-image ${className}`}
      loading={priority ? 'eager' : 'lazy'}
      decoding="async"
      // srcSet={generateSrcSet(src)} // Descomentar cuando tengas imágenes múltiples
      sizes={sizes}
      style={{
        width: '100%',
        height: 'auto',
        aspectRatio: width && height ? `${width} / ${height}` : 'auto'
      }}
    />
  );
};

export default OptimizedImage;
