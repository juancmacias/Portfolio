/**
 * Utilidades JavaScript para el sistema administrativo
 */

// Configuración global
window.AdminUtils = {
    // Mostrar/ocultar spinner de carga
    showLoading: function() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'flex';
        }
    },

    hideLoading: function() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    },

    // Funciones de notificación
    showNotification: function(type, message, duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible notification-toast`;
        notification.innerHTML = `
            <div class="alert-content">
                <span class="alert-icon">${this.getAlertIcon(type)}</span>
                <span class="alert-message">${message}</span>
            </div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <span>×</span>
            </button>
        `;

        // Estilos para toast
        notification.style.cssText = `
            position: fixed;
            top: ${document.querySelector('.admin-header').offsetHeight + 20}px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOutRight 0.3s ease-in';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, duration);
        }

        return notification;
    },

    getAlertIcon: function(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️',
            danger: '❌'
        };
        return icons[type] || '📢';
    },

    // Funciones de modal
    showModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus en el primer input
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    },

    closeModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    // Función de confirmación
    confirm: function(message, title = 'Confirmar acción', confirmText = 'Confirmar', cancelText = 'Cancelar') {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmModal');
            const titleEl = document.getElementById('confirmModalTitle');
            const messageEl = document.getElementById('confirmModalMessage');
            const confirmBtn = document.getElementById('confirmModalButton');

            if (titleEl) titleEl.textContent = title;
            if (messageEl) messageEl.textContent = message;
            if (confirmBtn) confirmBtn.textContent = confirmText;

            this.showModal('confirmModal');

            // Función de confirmación
            window.executeConfirmAction = function() {
                AdminUtils.closeModal('confirmModal');
                resolve(true);
            };

            // Función de cancelación
            const cancelBtn = modal.querySelector('.btn-secondary');
            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    AdminUtils.closeModal('confirmModal');
                    resolve(false);
                };
            }
        });
    },

    // Funciones de API
    apiRequest: async function(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaultOptions, ...options };
        if (config.headers['Content-Type'] === 'application/json' && config.body && typeof config.body !== 'string') {
            config.body = JSON.stringify(config.body);
        }

        try {
            this.showLoading();
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            this.showNotification('error', `Error de conexión: ${error.message}`);
            throw error;
        } finally {
            this.hideLoading();
        }
    },

    // Funciones de formulario
    serializeForm: function(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                // Convertir a array si ya existe
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }
        
        return data;
    },

    validateForm: function(form) {
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            const errorEl = input.parentNode.querySelector('.form-error');
            
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
                
                if (!errorEl) {
                    const error = document.createElement('div');
                    error.className = 'form-error';
                    error.textContent = 'Este campo es obligatorio';
                    input.parentNode.appendChild(error);
                }
            } else {
                input.classList.remove('is-invalid');
                if (errorEl) {
                    errorEl.remove();
                }
            }
        });

        return isValid;
    },

    // Funciones de almacenamiento
    storage: {
        set: function(key, value) {
            try {
                localStorage.setItem(`admin_${key}`, JSON.stringify(value));
            } catch (e) {
                console.warn('LocalStorage not available:', e);
            }
        },

        get: function(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(`admin_${key}`);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.warn('LocalStorage not available:', e);
                return defaultValue;
            }
        },

        remove: function(key) {
            try {
                localStorage.removeItem(`admin_${key}`);
            } catch (e) {
                console.warn('LocalStorage not available:', e);
            }
        }
    },

    // Funciones de formateo
    formatDate: function(date, format = 'dd/mm/yyyy HH:MM') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');

        return format
            .replace('dd', day)
            .replace('mm', month)
            .replace('yyyy', year)
            .replace('HH', hours)
            .replace('MM', minutes);
    },

    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    // Funciones de utilidad
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Copiar al portapapeles
    copyToClipboard: function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(() => {
                this.showNotification('success', 'Copiado al portapapeles');
            }).catch(err => {
                console.error('Error copying to clipboard:', err);
                this.fallbackCopyToClipboard(text);
            });
        } else {
            this.fallbackCopyToClipboard(text);
        }
    },

    fallbackCopyToClipboard: function(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            this.showNotification('success', 'Copiado al portapapeles');
        } catch (err) {
            console.error('Fallback copy failed:', err);
            this.showNotification('error', 'No se pudo copiar al portapapeles');
        }
        
        document.body.removeChild(textArea);
    }
};

// Estilos para animaciones de toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .notification-toast {
        animation: slideInRight 0.3s ease-out;
    }

    .form-control.is-invalid {
        border-color: var(--danger-color);
        box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
    }
`;
document.head.appendChild(style);

// Alias globales para conveniencia
window.showNotification = AdminUtils.showNotification.bind(AdminUtils);
window.showModal = AdminUtils.showModal.bind(AdminUtils);
window.closeModal = AdminUtils.closeModal.bind(AdminUtils);
window.copyToClipboard = AdminUtils.copyToClipboard.bind(AdminUtils);