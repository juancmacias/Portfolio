<?php $breadcrumb = getBreadcrumb(); ?>
<?php if (!empty($breadcrumb)): ?>
    <nav class="breadcrumb" aria-label="Navegación de ruta">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="<?= getRoute('dashboard') ?>" class="breadcrumb-link">
                    <span class="breadcrumb-icon">🏠</span>
                    Inicio
                </a>
            </li>
            <?php foreach ($breadcrumb as $index => $item): ?>
                <li class="breadcrumb-item <?= $index === count($breadcrumb) - 1 ? 'active' : '' ?>">
                    <span class="breadcrumb-separator">›</span>
                    <?php if (isset($item['url']) && $index < count($breadcrumb) - 1): ?>
                        <a href="<?= $item['url'] ?>" class="breadcrumb-link">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    <?php else: ?>
                        <span class="breadcrumb-current">
                            <?= htmlspecialchars($item['title']) ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
<?php endif; ?>