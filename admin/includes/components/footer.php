<footer class="admin-footer">
    <div class="footer-content">
        <div class="footer-left">
            <p class="copyright">
                © <?= date('Y') ?> <?= ADMIN_TITLE ?>. 
                <span class="version">v<?= ADMIN_VERSION ?></span>
            </p>
        </div>
        <div class="footer-center">
            <div class="footer-stats">
                <span class="stat-item">
                    <span class="stat-icon">📊</span>
                    <span class="stat-text" id="dashboardStats">Cargando...</span>
                </span>
            </div>
        </div>
        <div class="footer-right">
            <div class="footer-links">
                <a href="#" onclick="showHelpModal()" class="footer-link">
                    <span class="link-icon">❓</span>
                    Ayuda
                </a>
                <a href="#" onclick="showAboutModal()" class="footer-link">
                    <span class="link-icon">ℹ️</span>
                    Acerca de
                </a>
            </div>
        </div>
    </div>
</footer>