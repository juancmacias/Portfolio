import React from 'react';
import './ChatButton.css';

/**
 * Botón flotante para abrir el chat conversacional
 * @param {Function} onClick - Función para abrir el modal de chat
 */
const ChatButton = ({ onClick }) => {
  return (
    <button 
      className="chat-floating-button" 
      onClick={onClick}
      title="Abrir chat conversacional"
      aria-label="Abrir asistente virtual"
    >
      <div className="chat-button-icon">
        🤖
      </div>
      <div className="chat-button-pulse"></div>
    </button>
  );
};

export default ChatButton;