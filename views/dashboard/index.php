<?php

use Utils\Security;

ob_start();
?>
<div class="row mb-3">
    <div class="col-md-8">
        <h2>Dashboard</h2>
        <?php if (!empty($userName)): ?>
            <p class="text-muted mb-0">Conectado como <strong><?= Security::e($userName); ?></strong></p>
        <?php endif; ?>
    </div>
    <div class="col-md-4 text-end">
        <a href="index.php?route=logout" class="btn btn-outline-secondary btn-sm">Salir</a>
    </div>
</div>

<?php if (!empty($_GET['pin_ok'])): ?>
    <div class="alert alert-success alert-dismissible fade show">PIN actualizado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['pin_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php
        $err = (int)($_GET['pin_error'] ?? 0);
        echo $err === 1 ? 'PIN debe ser de 6 dígitos.' : ($err === 2 ? 'El nuevo PIN y la confirmación no coinciden.' : ($err === 3 ? 'Elige un PIN más seguro (evita 123456, 000000 o secuencias).' : 'PIN actual incorrecto.'));
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['email_ok'])): ?>
    <div class="alert alert-success alert-dismissible fade show">Correo actualizado. Los próximos inicios de sesión enviarán el código MFA a tu correo.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['email_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_GET['email_error'] == '2' ? 'Ese correo ya está en uso por otra cuenta.' : 'El correo no es válido.'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">Mi cuenta</div>
            <div class="card-body">
                <p class="mb-2"><strong>Nombre de cuenta:</strong> <?= Security::e($userName ?? ''); ?></p>
                <form method="post" action="index.php?route=change_pin" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="col-md-2">
                        <label class="form-label small">PIN actual</label>
                        <input type="password" name="current_pin" class="form-control form-control-sm" pattern="\d{6}" maxlength="6" placeholder="6 dígitos" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Nuevo PIN</label>
                        <input type="password" name="new_pin" class="form-control form-control-sm" pattern="\d{6}" maxlength="6" placeholder="6 dígitos" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Confirmar nuevo PIN</label>
                        <input type="password" name="new_pin_confirm" class="form-control form-control-sm" pattern="\d{6}" maxlength="6" placeholder="6 dígitos" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-warning">Cambiar PIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="card-header">Correo para MFA</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Vincula un correo para recibir códigos de verificación en dos pasos por email. Si no lo configuras, se usará el código por defecto (123123).</p>
                <form method="post" action="index.php?route=save_email" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="col">
                        <label class="form-label small">Correo electrónico</label>
                        <input type="email" name="email" class="form-control form-control-sm" maxlength="255"
                               placeholder="tu@correo.com" value="<?= Security::e($userEmail ?? ''); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">Mis datos personales</div>
            <div class="card-body">
                <p class="small text-muted mb-3">Estos datos se muestran en el acceso de emergencia (QR). Completa al menos tipo de sangre y alergias.</p>
                <form method="post" action="index.php?route=personal_data_save" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="col-md-2">
                        <label class="form-label">Tipo de sangre</label>
                        <select name="sangre" class="form-select">
                            <option value="">— Elegir —</option>
                            <option value="A+" <?= ($personal['sangre'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                            <option value="A-" <?= ($personal['sangre'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                            <option value="B+" <?= ($personal['sangre'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                            <option value="B-" <?= ($personal['sangre'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                            <option value="AB+" <?= ($personal['sangre'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                            <option value="AB-" <?= ($personal['sangre'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                            <option value="O+" <?= ($personal['sangre'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                            <option value="O-" <?= ($personal['sangre'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alergias</label>
                        <input type="text" name="alergias" class="form-control" placeholder="Ej: Penicilina, polen"
                               value="<?= Security::e($personal['alergias'] ?? '') ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Edad</label>
                        <input type="number" name="edad" class="form-control" min="1" max="120" placeholder="—"
                               value="<?= Security::e((string)($personal['edad'] ?? '')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Altura (cm)</label>
                        <input type="number" name="altura" class="form-control" step="0.1" min="0" placeholder="—"
                               value="<?= Security::e((string)($personal['altura'] ?? '')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" name="peso" class="form-control" step="0.1" min="0" placeholder="—"
                               value="<?= Security::e((string)($personal['peso'] ?? '')) ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">Bitácora de salud</div>
            <div class="card-body">
                <form method="post" action="index.php?route=health_store">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="mb-2">
                        <label class="form-label">Temperatura (°C)</label>
                        <input type="number" step="0.1" name="temperatura" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Presión arterial</label>
                        <input type="text" name="presion" class="form-control" placeholder="120/80">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" step="0.1" name="peso" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nivel de energía (1-10)</label>
                        <input type="number" min="1" max="10" name="nivel_energia" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Malestares / síntomas</label>
                        <textarea name="sintomas" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Guardar registro</button>
                </form>
            </div>
        </div>

        <div class="card mt-3 shadow-sm">
            <div class="card-header">Historial reciente</div>
            <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($healthLogs)): ?>
                    <p class="text-muted">Sin registros aún.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Temp</th>
                            <th>Presión</th>
                            <th>Energía</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($healthLogs as $log): ?>
                            <tr>
                                <td><?= Security::e($log['fecha']); ?></td>
                                <td><?= Security::e((string)$log['temperatura']); ?></td>
                                <td><?= Security::e($log['presion']); ?></td>
                                <td><?= Security::e((string)$log['nivel_energia']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Gestión de medicamentos</div>
            <div class="card-body">
                <form method="post" action="index.php?route=med_store" class="mb-3">
                    <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                    <div class="mb-2">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_medicamento" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Dosis</label>
                        <input type="text" name="dosis" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Horario</label>
                        <input type="text" name="horario" class="form-control" placeholder="Ej: 08:00 y 20:00" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Agregar</button>
                </form>

                <?php if (empty($medications)): ?>
                    <p class="text-muted">Sin medicamentos registrados.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Medicamento</th>
                            <th>Dosis</th>
                            <th>Horario</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($medications as $med): ?>
                            <tr>
                                <td><?= Security::e($med['nombre_medicamento']); ?></td>
                                <td><?= Security::e($med['dosis']); ?></td>
                                <td><?= Security::e($med['horario']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <h5>Emergencia</h5>
                <p class="text-muted small">Utiliza estos botones solo en caso de emergencia real.</p>
                <a href="tel:911" class="btn btn-danger btn-lg w-100 mb-2">Llamar 911</a>
                <a href="tel:+52XXXXXXXXXX" class="btn btn-warning btn-lg w-100">Llamar Universidad</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Reportes y acceso de emergencia</h5>
                <div class="mb-3 p-2 bg-light rounded">
                    <label class="form-label small fw-bold">QR visible para todos (app sigue en tu PC)</label>
                    <p class="small text-muted mb-1">Usa un túnel (ngrok, Cloudflare Tunnel) y pega aquí la URL pública. El QR apuntará a esa URL y cualquiera podrá abrir la ficha de emergencia.</p>
                    <form method="post" action="index.php?route=set_public_url" class="row g-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                        <div class="col">
                            <input type="url" name="public_base_url" class="form-control form-control-sm" placeholder="https://abc123.ngrok.io"
                                   value="<?= Security::e($publicBaseUrl ?? '') ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Guardar URL</button>
                        </div>
                    </form>
                </div>
                <div class="mb-3">
                    <form method="post" action="index.php?route=pdf_report" target="_blank">
                        <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                        <label class="form-label">Contraseña para PDF</label>
                        <input type="password" name="pdf_password" class="form-control mb-2" minlength="6" required>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            Generar reporte PDF (última semana)
                        </button>
                    </form>
                </div>

                <div class="mb-3">
                    <form method="post" action="index.php?route=generate_qr">
                        <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            Generar QR de emergencia (24h)
                        </button>
                    </form>
                </div>

                <?php if (!empty($emergencyQrUrl)): ?>
                    <?php
                    $emergencyQrUrlSafe = Security::e($emergencyQrUrl);
                    $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($emergencyQrUrl);
                    ?>
                    <div class="mt-3 p-3 bg-white rounded-3 border text-center">
                        <p class="small text-muted mb-2">Escanea este código QR en caso de emergencia (válido 24h):</p>
                        <img src="<?= Security::e($qrImageUrl) ?>" alt="QR emergencia" width="200" height="200" class="d-block mx-auto p-2 bg-white rounded shadow-sm">
                        <p class="small mt-3 mb-0 text-muted">
                            <a href="<?= $emergencyQrUrlSafe ?>" target="_blank" rel="noopener">Abrir enlace de emergencia</a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Dashboard - Kanan Web';
include __DIR__ . '/../layouts/base.php';

