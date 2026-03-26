<?php
use Utils\Security;
$title = 'Seguridad - MFA';
ob_start();
?>

<div class="card" style="max-width: 400px; margin: 4rem auto; text-align: center;">
    <div style="font-size: 3rem; margin-bottom: 1rem;">🔐</div>
    <h2 style="color: var(--secondary);">Verificación de Seguridad</h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 2rem;">
        Por favor, ingresa el código de 6 dígitos que aparece en tu aplicación <strong>Google Authenticator</strong>.
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="font-size: 0.85rem; padding: 0.8rem;">
            <?= Security::e($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?route=login_mfa">
        <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
        
        <div class="form-group">
            <input type="text" 
                   name="mfa_code" 
                   id="mfa_code" 
                   placeholder="000 000" 
                   pattern="\d{6}" 
                   maxlength="6" 
                   style="text-align: center; font-size: 1.5rem; letter-spacing: 5px; font-weight: 600;"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   required 
                   autofocus>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
            Verificar y Acceder
        </button>
    </form>

    <p style="margin-top: 2rem; font-size: 0.8rem; color: #999;">
        ¿Tienes problemas? Asegúrate de que la hora de tu celular sea la correcta.
    </p>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
