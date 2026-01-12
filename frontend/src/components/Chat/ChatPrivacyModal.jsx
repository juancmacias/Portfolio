import React from 'react';
import './ChatPrivacyModal.css';

/**
 * Modal de Consentimiento de Privacidad para el Chat
 * Aparece la primera vez que el usuario intenta usar el chat
 * Cumple con requisitos GDPR básicos
 * 
 * @author Juan Carlos Macías
 * @version 1.0
 */

const ChatPrivacyModal = ({ isOpen, onAccept, onReject }) => {
  if (!isOpen) return null;

  return (
    <div className="chat-privacy-overlay" onClick={(e) => e.stopPropagation()}>
      <div className="chat-privacy-modal" onClick={(e) => e.stopPropagation()}>
        <div className="privacy-modal-header">
          <h3>🔒 Privacidad y Uso de Datos</h3>
        </div>
        
        <div className="privacy-modal-body">
          <p className="privacy-intro">
            Antes de usar el chat conversacional, es importante que conozcas cómo procesamos tu información:
          </p>
          
          <div className="privacy-section">
            <h4>📝 Información que Procesamos</h4>
            <ul>
              <li><strong>Mensajes del chat:</strong> Los mensajes que envíes serán procesados por servicios de IA (Groq/Llama) para generar respuestas.</li>
              <li><strong>ID de sesión:</strong> Se genera un identificador temporal único para mantener el contexto de tu conversación.</li>
              <li><strong>Timestamp:</strong> Fecha y hora de los mensajes para ordenar la conversación.</li>
            </ul>
          </div>
          
          <div className="privacy-section">
            <h4>🎯 Uso de los Datos</h4>
            <ul>
              <li>Generar respuestas relevantes a tus preguntas</li>
              <li>Mejorar la experiencia conversacional</li>
              <li>Registros técnicos para debugging (sin datos personales)</li>
            </ul>
          </div>
          
          <div className="privacy-section">
            <h4>🛡️ Protección de Datos</h4>
            <ul>
              <li><strong>No se guardan datos personales:</strong> No solicitamos ni almacenamos información identificable.</li>
              <li><strong>Sesión temporal:</strong> Los datos de la sesión se eliminan al cerrar el chat o el navegador.</li>
              <li><strong>Sin compartir con terceros:</strong> Tus mensajes no se comparten con fines comerciales.</li>
              <li><strong>Procesamiento externo:</strong> Los mensajes se envían a Groq API para procesamiento de IA.</li>
            </ul>
          </div>
          
          <div className="privacy-section">
            <h4>✅ Tus Derechos</h4>
            <ul>
              <li>Puedes borrar el historial del chat en cualquier momento usando el botón "🗑️"</li>
              <li>Puedes cerrar el chat sin aceptar estas condiciones</li>
              <li>No hay seguimiento entre sesiones</li>
            </ul>
          </div>
          
          <div className="privacy-important">
            <p>
              ⚠️ <strong>Importante:</strong> No compartas información sensible o personal (contraseñas, datos bancarios, información médica) en el chat.
            </p>
          </div>
          
          <div className="privacy-footer-note">
            <p>
              Al continuar, aceptas que has leído y comprendido cómo procesamos tus mensajes.
              Para más información, consulta nuestra <a href="/politics" target="_top" rel="noopener noreferrer">Política de Privacidad</a>.
            </p>
          </div>
        </div>
        
        <div className="privacy-modal-actions">
          <button 
            className="privacy-btn privacy-btn-reject" 
            onClick={onReject}
          >
            No Aceptar
          </button>
          <button 
            className="privacy-btn privacy-btn-accept" 
            onClick={onAccept}
          >
            Aceptar y Continuar
          </button>
        </div>
      </div>
    </div>
  );
};

export default ChatPrivacyModal;
