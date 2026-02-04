import React from 'react';
import ReactMarkdown from 'react-markdown';
import './MessageRenderer.css';

/**
 * Renderizador de mensajes con soporte para Markdown
 * Convierte texto con formato Markdown a HTML con estilos
 * Detecta y formatea URLs automáticamente
 * 
 * @param {string} content - Contenido del mensaje en texto plano o Markdown
 * @author Juan Carlos Macías
 * @version 1.0
 */

const MessageRenderer = ({ content }) => {
  // Función para detectar y convertir URLs en enlaces
  const convertUrlsToLinks = (text) => {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return text.replace(urlRegex, '[$1]($1)');
  };

  // Función para mejorar el formato del contenido
  const preprocessContent = (text) => {
    if (!text) return '';
    
    // Convertir URLs sueltas a enlaces Markdown
    let processed = convertUrlsToLinks(text);
    
    // Convertir viñetas con "+" a viñetas Markdown si no lo están ya
    processed = processed.replace(/^\s*\+\s+/gm, '- ');
    
    // Convertir listas con " - " a formato Markdown apropiado
    processed = processed.replace(/^\s*-\s+/gm, '- ');
    
    return processed;
  };

  // Configuración de componentes personalizados para ReactMarkdown
  const components = {
    // Enlaces con target blank y rel noopener
    a: ({ node, ...props }) => (
      <a 
        {...props} 
        target="_blank" 
        rel="noopener noreferrer"
        className="message-link"
      />
    ),
    // Párrafos con mejor espaciado
    p: ({ node, ...props }) => (
      <p className="message-paragraph" {...props} />
    ),
    // Listas con mejor espaciado
    ul: ({ node, ...props }) => (
      <ul className="message-list" {...props} />
    ),
    li: ({ node, ...props }) => (
      <li className="message-list-item" {...props} />
    ),
    // Código inline
    code: ({ node, inline, ...props }) => (
      inline 
        ? <code className="message-code-inline" {...props} />
        : <code className="message-code-block" {...props} />
    ),
    // Encabezados
    h1: ({ node, ...props }) => <h1 className="message-h1" {...props} />,
    h2: ({ node, ...props }) => <h2 className="message-h2" {...props} />,
    h3: ({ node, ...props }) => <h3 className="message-h3" {...props} />,
    h4: ({ node, ...props }) => <h4 className="message-h4" {...props} />,
  };

  return (
    <div className="message-renderer">
      <ReactMarkdown components={components}>
        {preprocessContent(content)}
      </ReactMarkdown>
    </div>
  );
};

export default MessageRenderer;
