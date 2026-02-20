<?php

use Utils\Security;

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3 text-center">Inicio de sesión</h5>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <?= Security::e($successMessage); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?= Security::e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php?route=login">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de usuario</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="pin" class="form-label">PIN (6 dígitos)</label>
                        <input type="password" class="form-control" id="pin" name="pin" pattern="\d{6}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Continuar</button>
                </form>

                <hr class="my-3">
                <p class="text-center text-muted small mb-0">
                    <a href="index.php?route=register">¿No tienes cuenta? Regístrate</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Login - Kanan Web';
include __DIR__ . '/../layouts/base.php';

