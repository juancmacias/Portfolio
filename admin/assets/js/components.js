/**
 * Componentes JavaScript reutilizables
 */

// Gestión del sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }
}

// Gestión de submenús
function toggleSubmenu(element) {
    const navItem = element.closest('.nav-item');
    if (navItem) {
        navItem.classList.toggle('active');
    }
}

// Gestión de notificaciones
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        const isVisible = dropdown.style.display === 'block';
        dropdown.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            loadNotifications();
        }
    }
}

async function loadNotifications() {
    try {
        const response = await AdminUtils.apiRequest(`${AdminConfig.apiUrl}/notifications.php`);
        const notificationList = document.getElementById('notificationList');
        const notificationCount = document.getElementById('notificationCount');
        
        if (response.success && response.notifications) {
            const notifications = response.notifications;
            
            if (notifications.length === 0) {
                notificationList.innerHTML = '<div class="no-notifications">No hay notificaciones</div>';
                notificationCount.style.display = 'none';
            } else {
                notificationList.innerHTML = notifications.map(notification => `
                    <div class="notification-item ${notification.read ? '' : 'unread'}">
                        <div class="notification-content">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${AdminUtils.formatDate(notification.created_at)}</div>
                        </div>
                        ${!notification.read ? '<div class="notification-indicator"></div>' : ''}
                    </div>
                `).join('');
                
                const unreadCount = notifications.filter(n => !n.read).length;
                if (unreadCount > 0) {
                    notificationCount.textContent = unreadCount;
                    notificationCount.style.display = 'block';
                } else {
                    notificationCount.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

async function markAllAsRead() {
    try {
        await AdminUtils.apiRequest(`${AdminConfig.apiUrl}/notifications.php`, {
            method: 'POST',
            body: { action: 'mark_all_read' }
        });
        
        loadNotifications();
        AdminUtils.showNotification('success', 'Notificaciones marcadas como leídas');
    } catch (error) {
        console.error('Error marking notifications as read:', error);
    }
}

// Gestión del menú de usuario
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        const isVisible = dropdown.style.display === 'block';
        dropdown.style.display = isVisible ? 'none' : 'block';
    }
}

// Modal de cambio de contraseña
function showChangePasswordModal() {
    AdminUtils.showModal('changePasswordModal');
    toggleUserMenu(); // Cerrar el menú de usuario
}

async function changePassword(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = AdminUtils.serializeForm(form);
    
    // Validar que las contraseñas coincidan
    if (formData.newPassword !== formData.confirmPassword) {
        AdminUtils.showNotification('error', 'Las contraseñas no coinciden');
        return;
    }
    
    try {
        const response = await AdminUtils.apiRequest(`${AdminConfig.apiUrl}/auth.php`, {
            method: 'POST',
            body: {
                action: 'change_password',
                current_password: formData.currentPassword,
                new_password: formData.newPassword
            }
        });
        
        if (response.success) {
            AdminUtils.closeModal('changePasswordModal');
            form.reset();
            AdminUtils.showNotification('success', 'Contraseña cambiada exitosamente');
        } else {
            AdminUtils.showNotification('error', response.message || 'Error al cambiar la contraseña');
        }
    } catch (error) {
        console.error('Error changing password:', error);
    }
}

// Modales de ayuda y acerca de
function showHelpModal() {
    AdminUtils.showModal('helpModal');
}

function showAboutModal() {
    AdminUtils.showModal('aboutModal');
}

// Búsqueda global
const globalSearchInput = document.getElementById('globalSearch');
if (globalSearchInput) {
    const debouncedSearch = AdminUtils.debounce(performGlobalSearch, 300);
    globalSearchInput.addEventListener('input', debouncedSearch);
}

async function performGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    const query = searchInput?.value.trim();
    
    if (!query || query.length < 2) {
        return;
    }
    
    try {
        const response = await AdminUtils.apiRequest(`${AdminConfig.apiUrl}/search.php?q=${encodeURIComponent(query)}`);
        
        if (response.success && response.results) {
            showSearchResults(response.results);
        }
    } catch (error) {
        console.error('Error performing search:', error);
    }
}

function showSearchResults(results) {
    // Implementar mostrar resultados de búsqueda
    console.log('Search results:', results);
}

// Gestión de tablas con paginación
function initializePagination(containerId, data, renderFunction, itemsPerPage = 10) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let currentPage = 1;
    const totalPages = Math.ceil(data.length / itemsPerPage);
    
    function renderPage() {
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageData = data.slice(start, end);
        
        container.innerHTML = renderFunction(pageData);
        updatePaginationControls();
    }
    
    function updatePaginationControls() {
        const paginationContainer = container.nextElementSibling;
        if (!paginationContainer || !paginationContainer.classList.contains('pagination-container')) {
            return;
        }
        
        paginationContainer.innerHTML = `
            <div class="pagination">
                <button class="pagination-item" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
                    ‹ Anterior
                </button>
                ${Array.from({length: totalPages}, (_, i) => i + 1).map(page => `
                    <button class="pagination-item ${page === currentPage ? 'active' : ''}" onclick="changePage(${page})">
                        ${page}
                    </button>
                `).join('')}
                <button class="pagination-item" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
                    Siguiente ›
                </button>
            </div>
            <div class="pagination-info">
                Página ${currentPage} de ${totalPages} (${data.length} elementos)
            </div>
        `;
    }
    
    window.changePage = function(page) {
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            renderPage();
        }
    };
    
    renderPage();
}

// Gestión de formularios con autoguardado
function initializeAutoSave(formId, saveUrl, interval = 30000) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    let lastSavedData = AdminUtils.serializeForm(form);
    let autoSaveTimeout;
    
    function autoSave() {
        const currentData = AdminUtils.serializeForm(form);
        
        // Solo guardar si hay cambios
        if (JSON.stringify(currentData) !== JSON.stringify(lastSavedData)) {
            saveDraft(currentData);
            lastSavedData = currentData;
        }
        
        autoSaveTimeout = setTimeout(autoSave, interval);
    }
    
    async function saveDraft(data) {
        try {
            await AdminUtils.apiRequest(saveUrl, {
                method: 'POST',
                body: { ...data, action: 'auto_save' }
            });
            
            // Mostrar indicador de guardado
            showSaveIndicator();
        } catch (error) {
            console.error('Error in auto-save:', error);
        }
    }
    
    function showSaveIndicator() {
        const indicator = document.getElementById('autoSaveIndicator');
        if (indicator) {
            indicator.textContent = 'Guardado automáticamente';
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        }
    }
    
    // Iniciar autoguardado
    autoSaveTimeout = setTimeout(autoSave, interval);
    
    // Limpiar al salir de la página
    window.addEventListener('beforeunload', () => {
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
    });
}

// Gestión de arrastrar y soltar archivos
function initializeDropZone(elementId, uploadUrl, options = {}) {
    const dropZone = document.getElementById(elementId);
    if (!dropZone) return;
    
    const defaultOptions = {
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        maxSize: 10 * 1024 * 1024, // 10MB
        multiple: false,
        onUploadStart: () => {},
        onUploadProgress: () => {},
        onUploadComplete: () => {},
        onUploadError: () => {}
    };
    
    const config = { ...defaultOptions, ...options };
    
    // Prevenir comportamiento por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Resaltar zona de drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    dropZone.addEventListener('drop', handleDrop, false);
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight() {
        dropZone.classList.add('drag-over');
    }
    
    function unhighlight() {
        dropZone.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (!config.multiple && files.length > 1) {
            AdminUtils.showNotification('warning', 'Solo se permite un archivo');
            return;
        }
        
        Array.from(files).forEach(file => {
            if (validateFile(file)) {
                uploadFile(file);
            }
        });
    }
    
    function validateFile(file) {
        if (!config.allowedTypes.includes(file.type)) {
            AdminUtils.showNotification('error', `Tipo de archivo no permitido: ${file.type}`);
            return false;
        }
        
        if (file.size > config.maxSize) {
            AdminUtils.showNotification('error', `Archivo demasiado grande. Máximo: ${AdminUtils.formatFileSize(config.maxSize)}`);
            return false;
        }
        
        return true;
    }
    
    async function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        try {
            config.onUploadStart(file);
            
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                config.onUploadComplete(data, file);
                AdminUtils.showNotification('success', 'Archivo subido exitosamente');
            } else {
                config.onUploadError(data.message || 'Error al subir archivo', file);
                AdminUtils.showNotification('error', data.message || 'Error al subir archivo');
            }
        } catch (error) {
            config.onUploadError(error.message, file);
            AdminUtils.showNotification('error', `Error de conexión: ${error.message}`);
        }
    }
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(event) {
    const dropdowns = ['notificationDropdown', 'userDropdown'];
    
    dropdowns.forEach(dropdownId => {
        const dropdown = document.getElementById(dropdownId);
        const button = dropdown?.previousElementSibling;
        
        if (dropdown && dropdown.style.display === 'block') {
            if (!dropdown.contains(event.target) && !button?.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        }
    });
});

// Atajos de teclado globales
document.addEventListener('keydown', function(event) {
    // Esc - Cerrar modal activo
    if (event.key === 'Escape') {
        const visibleModals = document.querySelectorAll('.modal[style*="display: flex"]');
        if (visibleModals.length > 0) {
            const lastModal = visibleModals[visibleModals.length - 1];
            lastModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    
    // Ctrl+S - Guardar formulario
    if (event.ctrlKey && event.key === 's') {
        event.preventDefault();
        const activeForm = document.querySelector('form:focus-within, form.active');
        if (activeForm) {
            const saveBtn = activeForm.querySelector('[type="submit"], .btn-primary');
            if (saveBtn) {
                saveBtn.click();
            }
        }
    }
    
    // Ctrl+/ - Enfocar búsqueda
    if (event.ctrlKey && event.key === '/') {
        event.preventDefault();
        const searchInput = document.getElementById('globalSearch');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Cargar estadísticas del pie de página
async function loadFooterStats() {
    try {
        const response = await AdminUtils.apiRequest(`${AdminConfig.apiUrl}/stats.php`);
        const statsElement = document.getElementById('dashboardStats');
        
        if (response.success && statsElement) {
            statsElement.textContent = `${response.stats.articles || 0} artículos • ${response.stats.views || 0} vistas`;
        }
    } catch (error) {
        console.error('Error loading footer stats:', error);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Cargar notificaciones y estadísticas
    loadNotifications();
    loadFooterStats();
    
    // Actualizar periódicamente
    setInterval(loadNotifications, 60000); // Cada minuto
    setInterval(loadFooterStats, 300000); // Cada 5 minutos
});