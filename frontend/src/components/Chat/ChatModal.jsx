import React, { useState, useRef, useEffect } from 'react';
import useChatRAG from '../../hooks/useChatRAG';
import ChatPrivacyModal from './ChatPrivacyModal';
import ChatPrivacyDisclaimer from './ChatPrivacyDisclaimer';
import MessageRenderer from './MessageRenderer';
import './ChatModal.css';

/**
 * Modal de Chat Conversacional con RAG
 * Interfaz principal para interactuar con el asistente IA
 * 
 * @author Juan Carlos Macías
 * @version 1.0
 */

// Clave para localStorage
const CHAT_CONSENT_KEY = 'chatPrivacyConsent';

const ChatModal = ({ isOpen, onClose }) => {
  // Hook personalizado para manejo del chat
  const {
    messages,
    isLoading,
    error,
    sessionId,
    isListening,
    isSpeaking,
    voiceEnabled,
    sendMessage,
    clearChat,
    sendSuggestedQuestion,
    startListening,
    stopListening,
    toggleVoice,
    checkSpeechSupport,
    getChatStats
  } = useChatRAG();
  
  // Estados locales
  const [inputText, setInputText] = useState('');
  const [showStats, setShowStats] = useState(false);
  const [speechSupport, setSpeechSupport] = useState({ speechRecognition: false, speechSynthesis: false });
  const [readingMessageId, setReadingMessageId] = useState(null);
  
  // Estados de privacidad
  const [hasConsent, setHasConsent] = useState(false);
  const [showPrivacyModal, setShowPrivacyModal] = useState(false);
  const [consentChecked, setConsentChecked] = useState(false);
  
  // Referencias
  const messagesEndRef = useRef(null);
  const inputRef = useRef(null);
  
  // Verificar consentimiento al abrir el modal
  useEffect(() => {
    if (isOpen && !consentChecked) {
      const savedConsent = localStorage.getItem(CHAT_CONSENT_KEY);
      if (savedConsent === 'true') {
        setHasConsent(true);
      } else {
        setShowPrivacyModal(true);
      }
      setConsentChecked(true);
    }
  }, [isOpen, consentChecked]);
  
  // Verificar soporte de voz al montar
  useEffect(() => {
    setSpeechSupport(checkSpeechSupport());
  }, [checkSpeechSupport]);
  
  // Manejar aceptación de privacidad
  const handleAcceptPrivacy = () => {
    localStorage.setItem(CHAT_CONSENT_KEY, 'true');
    setHasConsent(true);
    setShowPrivacyModal(false);
  };
  
  // Manejar rechazo de privacidad
  const handleRejectPrivacy = () => {
    setShowPrivacyModal(false);
    onClose(); // Cerrar el chat si no acepta
  };
  
  // Auto-scroll al final de mensajes
  useEffect(() => {
    scrollToBottom();
  }, [messages]);
  
  // Enfocar input cuando se abre el modal
  useEffect(() => {
    if (isOpen && inputRef.current) {
      setTimeout(() => inputRef.current.focus(), 100);
    }
  }, [isOpen]);
  
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!inputText.trim() || isLoading) return;
    
    await sendMessage(inputText);
    setInputText('');
  };
  
  const handleSuggestedClick = (question) => {
    sendSuggestedQuestion(question);
  };
  
  const handleVoiceToggle = () => {
    if (isListening) {
      stopListening();
    } else {
      toggleVoice();
      if (!voiceEnabled) {
        startListening();
      }
    }
  };
  
  const formatTimestamp = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  
  const renderMessage = (message) => {
    const isUser = message.sender === 'user';
    const isSystem = message.sender === 'system';
    const isError = message.isError;
    const isBot = !isUser && !isSystem;
    
    // Función para leer un mensaje específico
    const handleReadMessage = (text, messageId) => {
      if (speechSupport.speechSynthesis) {
        // Si ya se está leyendo este mensaje, detener
        if (readingMessageId === messageId) {
          window.speechSynthesis.cancel();
          setReadingMessageId(null);
          return;
        }
        
        // Detener cualquier lectura anterior
        window.speechSynthesis.cancel();
        setReadingMessageId(messageId);
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'es-ES';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        utterance.volume = 0.8;
        
        // Cuando termine de leer, limpiar el estado
        utterance.onend = () => {
          setReadingMessageId(null);
        };
        
        utterance.onerror = () => {
          setReadingMessageId(null);
        };
        
        window.speechSynthesis.speak(utterance);
      } else {
        alert('Tu navegador no soporta síntesis de voz');
      }
    };
    
    return (
      <div
        key={message.id}
        className={`chat-message ${isUser ? 'user-message' : isSystem ? 'system-message' : 'bot-message'} ${isError ? 'error-message' : ''}`}
      >
        <div className="message-content">
          <div className="message-text">
            {isBot && !isError ? (
              <MessageRenderer content={message.text} />
            ) : (
              message.text
            )}
          </div>
          <div className="message-meta">
            <span className="message-time">
              {formatTimestamp(message.timestamp)}
            </span>
            {message.metadata && (
              <span className="message-info">
                {message.metadata.llmProvider} • {message.metadata.tokensUsed} tokens
              </span>
            )}
            {/* Botón de lectura para mensajes del bot */}
            {isBot && !isError && speechSupport.speechSynthesis && (
              <button
                className={`read-message-btn ${readingMessageId === message.id ? 'reading' : ''}`}
                onClick={() => handleReadMessage(message.text, message.id)}
                title={readingMessageId === message.id ? 'Detener lectura' : 'Leer mensaje en voz alta'}
                aria-label={readingMessageId === message.id ? 'Detener lectura' : 'Leer este mensaje'}
              >
                {readingMessageId === message.id ? '⏹️' : '🔊'}
              </button>
            )}
          </div>
        </div>
        
        {/* Preguntas sugeridas */}
        {message.suggestedQuestions && message.suggestedQuestions.length > 0 && (
          <div className="suggested-questions">
            <p className="suggested-label">Preguntas relacionadas:</p>
            {message.suggestedQuestions.map((question, index) => (
              <button
                key={index}
                className="suggested-question"
                onClick={() => handleSuggestedClick(question)}
                disabled={isLoading}
              >
                {question}
              </button>
            ))}
          </div>
        )}
      </div>
    );
  };
  
  const stats = getChatStats();
  
  if (!isOpen) return null;
  
  return (
    <div className="chat-modal-overlay" onClick={onClose}>
      <div className="chat-modal" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="chat-header">
          <div className="chat-title">
            <h3>🤖 Asistente Portfolio</h3>
            {sessionId && (
              <span className="session-info">
                Sesión: {sessionId.slice(-8)}
              </span>
            )}
          </div>
          
          <div className="chat-controls">
            <button
              className={`control-btn ${showStats ? 'active' : ''}`}
              onClick={() => setShowStats(!showStats)}
              title="Ver estadísticas"
            >
              📊
            </button>
            
            {speechSupport.speechRecognition && (
              <button
                className={`control-btn voice-btn ${voiceEnabled ? 'active' : ''} ${isListening ? 'listening' : ''}`}
                onClick={handleVoiceToggle}
                title={voiceEnabled ? 'Desactivar voz' : 'Activar voz'}
                disabled={isLoading}
              >
                {isListening ? '🎙️' : '🎤'}
              </button>
            )}
            
            <button
              className="control-btn clear-btn"
              onClick={clearChat}
              title="Limpiar chat"
              disabled={isLoading}
            >
              🗑️
            </button>
            
            <button
              className="control-btn close-btn"
              onClick={onClose}
              title="Cerrar chat"
            >
              ✖️
            </button>
          </div>
        </div>
        
        {/* Estadísticas */}
        {showStats && (
          <div className="chat-stats">
            <div className="stats-grid">
              <div className="stat-item">
                <span className="stat-label">Mensajes:</span>
                <span className="stat-value">{stats.totalMessages}</span>
              </div>
              <div className="stat-item">
                <span className="stat-label">Tokens:</span>
                <span className="stat-value">{stats.totalTokens}</span>
              </div>
              <div className="stat-item">
                <span className="stat-label">Tiempo promedio:</span>
                <span className="stat-value">{stats.averageProcessingTime.toFixed(2)}s</span>
              </div>
            </div>
          </div>
        )}
        
        {/* Estado de voz */}
        {voiceEnabled && (
          <div className="voice-status">
            {isListening && (
              <div className="voice-indicator listening">
                🎙️ Escuchando... Habla ahora
              </div>
            )}
            {isSpeaking && (
              <div className="voice-indicator speaking">
                🔊 Reproduciendo respuesta...
              </div>
            )}
          </div>
        )}
        
        {/* Error global */}
        {error && (
          <div className="chat-error">
            <span className="error-icon">⚠️</span>
            <span className="error-text">{error}</span>
          </div>
        )}
        
        {/* Mensajes */}
        <div className="chat-messages">
          {messages.length === 0 ? (
            <div className="welcome-message">
              <div className="welcome-content">
                <h4>👋 ¡Hola! Soy tu asistente virtual</h4>
                <p>Puedo ayudarte con información sobre Juan Carlos Macías, sus proyectos, experiencia técnica y más.</p>
                <div className="welcome-suggestions">
                  <button onClick={() => handleSuggestedClick('¿Qué tecnologías dominas?')}>
                    ¿Qué tecnologías dominas?
                  </button>
                  <button onClick={() => handleSuggestedClick('Cuéntame sobre tus proyectos')}>
                    Cuéntame sobre tus proyectos
                  </button>
                  <button onClick={() => handleSuggestedClick('¿Qué experiencia tienes?')}>
                    ¿Qué experiencia tienes?
                  </button>
                </div>
              </div>
            </div>
          ) : (
            messages.map(renderMessage)
          )}
          
          {/* Indicador de carga */}
          {isLoading && (
            <div className="chat-message bot-message">
              <div className="message-content">
                <div className="typing-indicator">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
                <div className="message-meta">
                  <span className="message-time">Escribiendo...</span>
                </div>
              </div>
            </div>
          )}
          
          <div ref={messagesEndRef} />
        </div>
        
        {/* Input de mensaje */}
        <form className="chat-input-form" onSubmit={handleSubmit}>
          <div className="input-container">
            <input
              ref={inputRef}
              type="text"
              value={inputText}
              onChange={(e) => setInputText(e.target.value)}
              placeholder={isListening ? 'Escuchando...' : 'Escribe tu pregunta...'}
              className="chat-input"
              disabled={isLoading || isListening}
            />
            
            {/* Botón de voz en el input */}
            {speechSupport.speechRecognition && (
              <button
                type="button"
                className={`voice-input-btn ${isListening ? 'listening' : ''}`}
                onClick={startListening}
                disabled={isLoading || isListening}
                title="Dictar mensaje"
              >
                🎤
              </button>
            )}
            
            <button
              type="submit"
              className="send-btn"
              disabled={!inputText.trim() || isLoading || isListening || !hasConsent}
              title="Enviar mensaje"
            >
              {isLoading ? '⏳' : '➤'}
            </button>
          </div>
        </form>
        
        {/* Disclaimer de privacidad - siempre visible */}
        {hasConsent && <ChatPrivacyDisclaimer />}
        
        {/* Footer */}
        <div className="chat-footer">
          <span className="footer-info">
            🤖 Powered by Groq • 🧠 RAG • 🎤 Web Speech API
          </span>
          {!speechSupport.speechRecognition && (
            <span className="footer-warning">
              ⚠️ Voz no disponible en este navegador
            </span>
          )}
        </div>
      </div>
      
      {/* Modal de consentimiento de privacidad */}
      <ChatPrivacyModal
        isOpen={showPrivacyModal}
        onAccept={handleAcceptPrivacy}
        onReject={handleRejectPrivacy}
      />
    </div>
  );
};

export default ChatModal;