import { useState } from 'react';

/**
 * Hook personalizado para gestionar el formulario de contacto
 * Maneja validación, estado del formulario y envío al API
 */
export const useContactForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    message: '',
    website: '', // Honeypot field
    formStartTime: Date.now() // Timestamp para detectar envíos muy rápidos
  });

  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [submitError, setSubmitError] = useState(null);

  /**
   * Maneja cambios en los campos del formulario
   */
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Limpiar error del campo cuando el usuario empiece a escribir
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  /**
   * Valida los datos del formulario
   */
  const validate = () => {
    const newErrors = {};

    // Nombre: mínimo 3 caracteres
    if (!formData.name || formData.name.trim().length < 3) {
      newErrors.name = 'El nombre debe tener al menos 3 caracteres';
    }

    // Email: formato válido
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!formData.email || !emailRegex.test(formData.email)) {
      newErrors.email = 'Introduce un email válido';
    }

    // Teléfono: obligatorio, mínimo 9 caracteres
    if (!formData.phone || formData.phone.trim().length < 9) {
      newErrors.phone = 'El teléfono debe tener al menos 9 caracteres';
    }

    // Mensaje: mínimo 10 caracteres
    if (!formData.message || formData.message.trim().length < 10) {
      newErrors.message = 'El mensaje debe tener al menos 10 caracteres';
    }

    return newErrors;
  };

  /**
   * Maneja el envío del formulario
   */
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Anti-bot: verificar honeypot (campo website debe estar vacío)
    if (formData.website) {
      console.log('Bot detectado: honeypot field rellenado');
      return; // No mostrar error, simplemente ignorar
    }

    // Anti-bot: verificar tiempo mínimo (al menos 3 segundos desde que se cargó el formulario)
    const timeTaken = (Date.now() - formData.formStartTime) / 1000;
    if (timeTaken < 3) {
      setSubmitError('Por favor, tómate tu tiempo para rellenar el formulario');
      return;
    }

    // Validar antes de enviar
    const validationErrors = validate();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    setIsSubmitting(true);
    setSubmitError(null);
    setSubmitSuccess(false);

    try {
      // Detectar entorno y construir URL
      const hostname = window.location.hostname;
      const isDevelopment = hostname === 'localhost' || 
                           hostname === '127.0.0.1' || 
                           hostname.includes('perfil.in') ||
                           hostname.includes('localhost');
      
      // En desarrollo usar URL relativa desde el servidor local
      const apiUrl = isDevelopment 
        ? 'http://www.perfil.in/api/portfolio/contact-submit.php'
        : 'https://www.juancarlosmacias.es/api/portfolio/contact-submit.php';
      
      console.log('Enviando a:', apiUrl);

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        setSubmitSuccess(true);
        // Resetear formulario
        setFormData({
          name: '',
          email: '',
          phone: '',
          message: '',
          website: '',
          formStartTime: Date.now()
        });
        
        // Scroll al top para mostrar mensaje de éxito
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        setSubmitError(data.error || 'Error al enviar el mensaje');
      }
    } catch (error) {
      console.error('Error al enviar formulario:', error);
      setSubmitError('Error de conexión. Por favor, inténtalo de nuevo.');
    } finally {
      setIsSubmitting(false);
    }
  };

  /**
   * Resetea el estado de éxito/error
   */
  const resetStatus = () => {
    setSubmitSuccess(false);
    setSubmitError(null);
  };

  return {
    formData,
    errors,
    isSubmitting,
    submitSuccess,
    submitError,
    handleChange,
    handleSubmit,
    resetStatus
  };
};

export default useContactForm;
