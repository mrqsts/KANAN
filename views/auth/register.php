<?php $title = 'Crear Cuenta'; ob_start(); ?>

<div class="card" style="max-width: 500px; margin: auto;">
    <h2>Crea tu cuenta de salud</h2>
    <p style="color: #666; margin-bottom: 2rem;">Tu información estará protegida con cifrado y Google Authenticator.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= \Utils\Security::e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?route=register">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="form-group">
            <label for="nombre">Nombre de Usuario</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej: mordo_health" required>
        </div>

        <div class="form-group">
            <label for="pin">PIN de Seguridad (6 dígitos)</label>
            <input type="password" id="pin" name="pin" placeholder="••••••" maxlength="6" pattern="\d{6}" inputmode="numeric" required>
        </div>

        <div class="form-group">
            <label for="pin_confirm">Confirma tu PIN</label>
            <input type="password" id="pin_confirm" name="pin_confirm" placeholder="••••••" maxlength="6" pattern="\d{6}" inputmode="numeric" required>
        </div>

        <div class="auth-actions" style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Cuenta</button>
            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                ¿Ya tienes cuenta? <a href="index.php?route=login" style="color: var(--secondary); font-weight: 600;">Inicia sesión</a>
            </p>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
