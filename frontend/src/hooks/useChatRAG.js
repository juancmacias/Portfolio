import { useState, useCallback, useRef } from 'react';
import { API_ENDPOINTS } from '../Services/urls';

/**
 * Hook personalizado para gestionar el sistema de chat RAG
 * Maneja estado, API calls, historial y funcionalidades de voz
 * 
 * @author Juan Carlos Macías
 * @version 1.0
 */

const useChatRAG = () => {
  // Estados principales
  const [messages, setMessages] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [sessionId, setSessionId] = useState(null);
  
  // Estados de voz
  const [isListening, setIsListening] = useState(false);
  const [isSpeaking, setIsSpeaking] = useState(false);
  const [voiceEnabled, setVoiceEnabled] = useState(false);
  
  // Referencias para Web Speech API
  const recognitionRef = useRef(null);
  const synthesisRef = useRef(null);
  
  // Configuración de la API
  const API_ENDPOINT = API_ENDPOINTS.portfolio.chatRag;
  
  /**
   * Enviar mensaje al chat RAG
   */
  const sendMessage = useCallback(async (userMessage, options = {}) => {
    if (!userMessage.trim() || isLoading) return;
    
    setIsLoading(true);
    setError(null);
    
    // Agregar mensaje del usuario inmediatamente
    const userMsg = {
      id: Date.now(),
      text: userMessage,
      sender: 'user',
      timestamp: new Date().toISOString()
    };
    
    setMessages(prev => [...prev, userMsg]);
    
    try {
      // Preparar payload
      const payload = {
        message: userMessage,
        session_id: sessionId || undefined,
        include_voice: voiceEnabled,
        ...options
      };
      
      // Hacer petición a la API
      console.log('🌐 Enviando petición a:', API_ENDPOINT);
      console.log('📦 Payload:', payload);
      
      const response = await fetch(API_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
      });
      
      console.log('📡 Respuesta del servidor:', response.status, response.statusText);
      
      // Verificar si la respuesta es JSON antes de parsearla
      const contentType = response.headers.get('content-type');
      console.log('📄 Content-Type:', contentType);
      
      if (!contentType || !contentType.includes('application/json')) {
        const textResponse = await response.text();
        console.error('❌ Respuesta no es JSON:', textResponse.substring(0, 200));
        throw new Error(`El servidor devolvió ${contentType || 'contenido no válido'} en lugar de JSON. Verificar endpoint: ${API_ENDPOINT}`);
      }
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.error?.message || 'Error en la respuesta del servidor');
      }
      
      if (!data.success) {
        throw new Error(data.error?.message || 'Error procesando la solicitud');
      }
      
      // Actualizar session ID si es nuevo
      if (data.data.session_id && !sessionId) {
        setSessionId(data.data.session_id);
      }
      
      // Agregar respuesta del bot
      const botMsg = {
        id: Date.now() + 1,
        text: data.data.response,
        sender: 'bot',
        timestamp: data.data.timestamp,
        metadata: {
          ragContext: data.data.rag_context,
          llmProvider: data.data.metadata.llm_provider,
          tokensUsed: data.data.metadata.tokens_used,
          processingTime: data.data.metadata.processing_time
        },
        suggestedQuestions: data.data.suggested_questions || []
      };
      
      setMessages(prev => [...prev, botMsg]);
      
      // Reproducir audio si está habilitado
      if (voiceEnabled && data.data.voice_text) {
        await speakText(data.data.voice_text);
      }
      
      return botMsg;
      
    } catch (err) {
      console.error('Error enviando mensaje:', err);
      
      // Determinar tipo de error
      let errorMessage = 'Error de conexión';
      if (err.name === 'TypeError' && err.message.includes('fetch')) {
        errorMessage = `No se puede conectar al servidor. Verificar: ${API_ENDPOINT}`;
      } else if (err.message.includes('Failed to fetch')) {
        errorMessage = `Servidor no disponible. Endpoint: ${API_ENDPOINT}`;
      } else {
        errorMessage = err.message;
      }
      
      setError(errorMessage);
      
      // Agregar mensaje de error
      const errorMsg = {
        id: Date.now() + 1,
        text: `❌ ${errorMessage}`,
        sender: 'system',
        timestamp: new Date().toISOString(),
        isError: true
      };
      
      setMessages(prev => [...prev, errorMsg]);
      
    } finally {
      setIsLoading(false);
    }
  }, [isLoading, sessionId, voiceEnabled]);
  
  /**
   * Limpiar historial de chat
   */
  const clearChat = useCallback(() => {
    setMessages([]);
    setSessionId(null);
    setError(null);
    stopListening();
    stopSpeaking();
  }, []);
  
  /**
   * Enviar pregunta sugerida
   */
  const sendSuggestedQuestion = useCallback((question) => {
    return sendMessage(question);
  }, [sendMessage]);
  
  /**
   * Inicializar Web Speech API para STT (Speech-to-Text)
   */
  const initializeSpeechRecognition = useCallback(() => {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
      console.warn('Speech Recognition no soportado en este navegador');
      return false;
    }
    
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognitionRef.current = new SpeechRecognition();
    
    recognitionRef.current.continuous = false;
    recognitionRef.current.interimResults = false;
    recognitionRef.current.lang = 'es-ES';
    
    recognitionRef.current.onstart = () => {
      setIsListening(true);
      setError(null);
    };
    
    recognitionRef.current.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      if (transcript.trim()) {
        sendMessage(transcript);
      }
    };
    
    recognitionRef.current.onerror = (event) => {
      console.error('Error de reconocimiento de voz:', event.error);
      setError(`Error de reconocimiento: ${event.error}`);
      setIsListening(false);
    };
    
    recognitionRef.current.onend = () => {
      setIsListening(false);
    };
    
    return true;
  }, [sendMessage]);
  
  /**
   * Iniciar escucha de voz
   */
  const startListening = useCallback(() => {
    if (!recognitionRef.current) {
      if (!initializeSpeechRecognition()) {
        setError('Reconocimiento de voz no disponible');
        return;
      }
    }
    
    if (!isListening && !isLoading) {
      try {
        recognitionRef.current.start();
      } catch (err) {
        console.error('Error iniciando reconocimiento:', err);
        setError('Error iniciando reconocimiento de voz');
      }
    }
  }, [isListening, isLoading, initializeSpeechRecognition]);
  
  /**
   * Detener escucha de voz
   */
  const stopListening = useCallback(() => {
    if (recognitionRef.current && isListening) {
      recognitionRef.current.stop();
    }
  }, [isListening]);
  
  /**
   * Reproducir texto como audio (TTS)
   */
  const speakText = useCallback(async (text) => {
    if (!('speechSynthesis' in window)) {
      console.warn('Speech Synthesis no soportado');
      return;
    }
    
    // Detener síntesis anterior
    window.speechSynthesis.cancel();
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'es-ES';
    utterance.rate = 0.9;
    utterance.pitch = 1;
    utterance.volume = 0.8;
    
    utterance.onstart = () => setIsSpeaking(true);
    utterance.onend = () => setIsSpeaking(false);
    utterance.onerror = () => setIsSpeaking(false);
    
    synthesisRef.current = utterance;
    window.speechSynthesis.speak(utterance);
  }, []);
  
  /**
   * Detener síntesis de voz
   */
  const stopSpeaking = useCallback(() => {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
      setIsSpeaking(false);
    }
  }, []);
  
  /**
   * Toggle funcionalidad de voz
   */
  const toggleVoice = useCallback(() => {
    const newVoiceState = !voiceEnabled;
    setVoiceEnabled(newVoiceState);
    
    if (!newVoiceState) {
      stopListening();
      stopSpeaking();
    }
  }, [voiceEnabled, stopListening, stopSpeaking]);
  
  /**
   * Verificar soporte de Web Speech API
   */
  const checkSpeechSupport = useCallback(() => {
    return {
      speechRecognition: ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window),
      speechSynthesis: 'speechSynthesis' in window
    };
  }, []);
  
  /**
   * Obtener estadísticas del chat
   */
  const getChatStats = useCallback(() => {
    const userMessages = messages.filter(m => m.sender === 'user');
    const botMessages = messages.filter(m => m.sender === 'bot');
    const totalTokens = botMessages.reduce((sum, msg) => 
      sum + (msg.metadata?.tokensUsed || 0), 0
    );
    
    return {
      totalMessages: messages.length,
      userMessages: userMessages.length,
      botMessages: botMessages.length,
      totalTokens,
      sessionId,
      averageProcessingTime: botMessages.length > 0 
        ? botMessages.reduce((sum, msg) => sum + (msg.metadata?.processingTime || 0), 0) / botMessages.length
        : 0
    };
  }, [messages, sessionId]);
  
  return {
    // Estado del chat
    messages,
    isLoading,
    error,
    sessionId,
    
    // Estado de voz
    isListening,
    isSpeaking,
    voiceEnabled,
    
    // Funciones principales
    sendMessage,
    clearChat,
    sendSuggestedQuestion,
    
    // Funciones de voz
    startListening,
    stopListening,
    speakText,
    stopSpeaking,
    toggleVoice,
    checkSpeechSupport,
    
    // Utilidades
    getChatStats
  };
};

export default useChatRAG;