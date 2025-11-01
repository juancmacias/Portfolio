<header class="admin-header">
    <div class="header-container">
        <!-- Logo / Brand -->
        <div class="header-brand">
            <a href="<?= getRoute('dashboard') ?>" class="brand-link">
                <span class="brand-icon">📁</span>
                <span class="brand-text"><?= ADMIN_TITLE ?></span>
            </a>
        </div>
        
        <!-- Navigation Toggle (Mobile) -->
        <button class="nav-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- Header Actions -->
        <div class="header-actions">
            <!-- Search -->
            <div class="header-search">
                <input type="text" placeholder="Buscar..." class="search-input" id="globalSearch">
                <button class="search-btn" onclick="performGlobalSearch()">🔍</button>
            </div>
            
            <!-- Notifications -->
            <div class="header-notifications">
                <button class="notification-btn" onclick="toggleNotifications()" aria-label="Notificaciones">
                    <span class="notification-icon">🔔</span>
                    <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                    <div class="notification-header">
                        <h4>Notificaciones</h4>
                        <button onclick="markAllAsRead()" class="mark-read-btn">Marcar como leídas</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="no-notifications">No hay notificaciones</div>
                    </div>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="header-user">
                <button class="user-btn" onclick="toggleUserMenu()" aria-label="Menú de usuario">
                    <span class="user-avatar">👤</span>
                    <span class="user-name"><?= isset($_SESSION['admin_user']['name']) ? htmlspecialchars($_SESSION['admin_user']['name']) : 'Admin' ?></span>
                    <span class="user-dropdown-arrow">▼</span>
                </button>
                <div class="user-dropdown" id="userDropdown" style="display: none;">
                    <div class="user-info">
                        <div class="user-avatar-large">👤</div>
                        <div class="user-details">
                            <div class="user-name-large"><?= isset($_SESSION['admin_user']['name']) ? htmlspecialchars($_SESSION['admin_user']['name']) : 'Administrador' ?></div>
                            <div class="user-email"><?= isset($_SESSION['admin_user']['email']) ? htmlspecialchars($_SESSION['admin_user']['email']) : 'admin@example.com' ?></div>
                        </div>
                    </div>
                    <div class="user-menu">
                        <a href="<?= getRoute('settings') ?>" class="user-menu-item">
                            <span class="menu-icon">⚙️</span>
                            Configuración
                        </a>
                        <a href="#" onclick="showChangePasswordModal()" class="user-menu-item">
                            <span class="menu-icon">🔒</span>
                            Cambiar contraseña
                        </a>
                        <hr class="user-menu-divider">
                        <a href="<?= getRoute('logout') ?>" class="user-menu-item logout" onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?')">
                            <span class="menu-icon">🚪</span>
                            Cerrar sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>