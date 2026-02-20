<?php

use Utils\Security;

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3 text-center">Verificación en dos pasos</h5>
                <?php if (!empty($mfaViaEmail)): ?>
                    <p class="text-muted small">
                        Hemos enviado un código de 6 dígitos a tu correo. Revísalo e ingrésalo aquí. El código caduca en 10 minutos.
                    </p>
                <?php else: ?>
                    <p class="text-muted small">
                        No tienes correo vinculado. Usa el código por defecto: <strong>123123</strong>. Configura tu correo en el dashboard para recibir códigos por email.
                    </p>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?= Security::e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php?route=login_mfa">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="mb-3">
                        <label for="mfa_code" class="form-label">Código MFA</label>
                        <input type="password" class="form-control" id="mfa_code" name="mfa_code" pattern="\d{6}" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Acceder</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'MFA - Kanan Web';
include __DIR__ . '/../layouts/base.php';

