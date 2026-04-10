<?php
// Este archivo se renderiza dentro de admin/includes/layouts/base.php
?>

<div style="margin-bottom: 12px;">
  <h2 style="margin: 0;">Configuración OAuth</h2>
  <p style="margin: 6px 0 0 0;">Guarda estos valores en <code>system_config</code>.</p>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="POST">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="google_calendar_enabled" name="google_calendar_enabled" <?= !empty($currentEnabled) ? 'checked' : '' ?> />
        <label class="form-check-label" for="google_calendar_enabled">Activar Google Calendar</label>
      </div>

      <div class="mb-3">
        <label class="form-label" for="google_oauth_client_id">Client ID</label>
        <input class="form-control" id="google_oauth_client_id" name="google_oauth_client_id" value="<?= htmlspecialchars($currentClientId ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label class="form-label" for="google_oauth_client_secret">Client Secret</label>
        <input class="form-control" id="google_oauth_client_secret" name="google_oauth_client_secret" value="<?= htmlspecialchars($currentClientSecret ?? '') ?>" />
        <div class="form-text">Recomendación: en una siguiente fase lo ciframos en BD.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="google_oauth_redirect_uri">Redirect URI</label>
        <input class="form-control" id="google_oauth_redirect_uri" name="google_oauth_redirect_uri" value="<?= htmlspecialchars($currentRedirectUri ?? '') ?>" placeholder="https://www.tu-dominio.com/api/portfolio/calendar-auth-callback.php" />
      </div>

      <div class="mb-3">
        <label class="form-label" for="google_calendar_id">Calendar ID</label>
        <input class="form-control" id="google_calendar_id" name="google_calendar_id" value="<?= htmlspecialchars($currentCalendarId ?? 'primary') ?>" placeholder="primary" />
        <div class="form-text">Usa <code>primary</code> o el ID de un calendario compartido.</div>
      </div>

      <button class="btn btn-primary" type="submit">Guardar</button>
    </form>

    <hr />
    <p class="mb-1"><strong>Probar OAuth</strong></p>
    <p class="mb-0">Abre: <code>/api/portfolio/calendar-auth-start.php</code></p>
  </div>
</div>
