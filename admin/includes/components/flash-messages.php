<?php $flashMessages = getFlashMessages(); ?>
<?php if (!empty($flashMessages)): ?>
    <div class="flash-messages">
        <?php foreach ($flashMessages as $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible" role="alert">
                <div class="alert-content">
                    <span class="alert-icon">
                        <?php 
                        switch ($flash['type']) {
                            case 'success':
                                echo '✅';
                                break;
                            case 'error':
                                echo '❌';
                                break;
                            case 'warning':
                                echo '⚠️';
                                break;
                            case 'info':
                                echo 'ℹ️';
                                break;
                            default:
                                echo '📢';
                        }
                        ?>
                    </span>
                    <span class="alert-message"><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Cerrar">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>