<?php
// Obtener la página actual para marcar el menú activo
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-content">
        <!-- Navegación principal -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <?php foreach ($navigationMenu as $item): ?>
                    <?php if (isset($item['children'])): ?>
                        <!-- Menú con submenús -->
                        <li class="nav-item has-children <?= in_array($currentPage, $item['active'] ?? []) ? 'active' : '' ?>">
                            <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                                <?php if (isset($item['icon'])): ?>
                                    <span class="nav-icon"><?= $item['icon'] ?></span>
                                <?php endif; ?>
                                <span class="nav-text"><?= htmlspecialchars($item['title']) ?></span>
                                <span class="nav-arrow">▼</span>
                            </a>
                            <ul class="nav-submenu">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="nav-subitem <?= in_array($currentPage, $child['active'] ?? []) ? 'active' : '' ?>">
                                        <a href="<?= $child['url'] ?>" class="nav-sublink">
                                            <span class="nav-subtext"><?= htmlspecialchars($child['title']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Menú simple -->
                        <li class="nav-item <?= in_array($currentPage, $item['active'] ?? []) ? 'active' : '' ?>">
                            <a href="<?= $item['url'] ?>" class="nav-link">
                                <?php if (isset($item['icon'])): ?>
                                    <span class="nav-icon"><?= $item['icon'] ?></span>
                                <?php endif; ?>
                                <span class="nav-text"><?= htmlspecialchars($item['title']) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- Información del sistema -->
        <div class="sidebar-footer">
            <div class="system-info">
                <div class="system-version">
                    <span class="version-label">Versión</span>
                    <span class="version-number"><?= ADMIN_VERSION ?></span>
                </div>
                <div class="system-status">
                    <span class="status-indicator online"></span>
                    <span class="status-text">Sistema activo</span>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Overlay para mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>