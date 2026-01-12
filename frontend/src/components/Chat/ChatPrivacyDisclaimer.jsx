import React from 'react';
import './ChatPrivacyDisclaimer.css';

/**
 * Disclaimer permanente de privacidad en el chat
 * Siempre visible en la parte inferior del chat
 * 
 * @author Juan Carlos Macías
 * @version 1.0
 */

const ChatPrivacyDisclaimer = () => {
  return (
    <div className="chat-privacy-disclaimer">
      <span className="disclaimer-icon">ℹ️</span>
      <span className="disclaimer-text">
        Chat procesado por IA. No compartas información sensible.
        {' '}
        <a href="/politics" target="_top" rel="noopener noreferrer">
          Política de privacidad
        </a>
      </span>
    </div>
  );
};

export default ChatPrivacyDisclaimer;
