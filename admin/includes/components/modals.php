<!-- Modal de confirmación global -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeModal('confirmModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="confirmModalTitle">Confirmar acción</h3>
            <button class="modal-close" onclick="closeModal('confirmModal')" aria-label="Cerrar">×</button>
        </div>
        <div class="modal-body">
            <div class="confirm-icon">⚠️</div>
            <p class="confirm-message" id="confirmModalMessage">¿Estás seguro de que quieres continuar?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancelar</button>
            <button type="button" class="btn btn-danger" id="confirmModalButton" onclick="executeConfirmAction()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal de cambio de contraseña -->
<div id="changePasswordModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeModal('changePasswordModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Cambiar contraseña</h3>
            <button class="modal-close" onclick="closeModal('changePasswordModal')" aria-label="Cerrar">×</button>
        </div>
        <form id="changePasswordForm" onsubmit="changePassword(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label for="currentPassword">Contraseña actual</label>
                    <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">Nueva contraseña</label>
                    <input type="password" id="newPassword" name="newPassword" class="form-control" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirmar nueva contraseña</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('changePasswordModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de ayuda -->
<div id="helpModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeModal('helpModal')"></div>
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 class="modal-title">Centro de ayuda</h3>
            <button class="modal-close" onclick="closeModal('helpModal')" aria-label="Cerrar">×</button>
        </div>
        <div class="modal-body">
            <div class="help-content">
                <div class="help-section">
                    <h4>🚀 Primeros pasos</h4>
                    <ul>
                        <li>Accede al <strong>Dashboard</strong> para ver un resumen del sistema</li>
                        <li>Usa <strong>Artículos</strong> para gestionar tu contenido</li>
                        <li>Configura el sistema desde <strong>Configuración</strong></li>
                    </ul>
                </div>
                <div class="help-section">
                    <h4>📝 Gestión de artículos</h4>
                    <ul>
                        <li>Crea artículos usando el editor con IA integrada</li>
                        <li>Usa imágenes y optimízalas automáticamente</li>
                        <li>Publica o guarda como borrador</li>
                    </ul>
                </div>
                <div class="help-section">
                    <h4>⌨️ Atajos de teclado</h4>
                    <ul>
                        <li><code>Ctrl + S</code> - Guardar formulario actual</li>
                        <li><code>Ctrl + /</code> - Buscar globalmente</li>
                        <li><code>Esc</code> - Cerrar modal activo</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeModal('helpModal')">Entendido</button>
        </div>
    </div>
</div>

<!-- Modal Acerca de -->
<div id="aboutModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeModal('aboutModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Acerca del sistema</h3>
            <button class="modal-close" onclick="closeModal('aboutModal')" aria-label="Cerrar">×</button>
        </div>
        <div class="modal-body">
            <div class="about-content">
                <div class="about-logo">📁</div>
                <h4><?= ADMIN_TITLE ?></h4>
                <p class="about-version">Versión <?= ADMIN_VERSION ?></p>
                <p class="about-description"><?= ADMIN_DESCRIPTION ?></p>
                <div class="about-info">
                    <div class="info-item">
                        <strong>Desarrollado:</strong> <?= date('Y') ?>
                    </div>
                    <div class="info-item">
                        <strong>Tecnologías:</strong> PHP, JavaScript, MySQL
                    </div>
                    <div class="info-item">
                        <strong>IA Integrada:</strong> Groq, OpenAI, HuggingFace
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeModal('aboutModal')">Cerrar</button>
        </div>
    </div>
</div>