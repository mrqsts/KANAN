<?php

use Utils\Security;

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3 text-center">Crear cuenta</h5>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?= Security::e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php?route=register">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de usuario</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100"
                               value="<?= Security::e($_POST['nombre'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico (opcional)</label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="255"
                               placeholder="Para recibir códigos MFA por correo"
                               value="<?= Security::e($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="pin" class="form-label">PIN (6 dígitos)</label>
                        <input type="password" class="form-control" id="pin" name="pin" pattern="\d{6}" required
                               placeholder="No uses 123456 ni secuencias">
                    </div>
                    <div class="mb-3">
                        <label for="pin_confirm" class="form-label">Confirmar PIN</label>
                        <input type="password" class="form-control" id="pin_confirm" name="pin_confirm" pattern="\d{6}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrarme</button>
                </form>

                <hr class="my-3">
                <p class="text-center text-muted small mb-0">
                    <a href="index.php?route=login">Ya tengo cuenta. Iniciar sesión</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Registro - Kanan Web';
include __DIR__ . '/../layouts/base.php';
