<?php
use Utils\Security;
ob_start();
?>

<div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end;">
    <div>
        <h1 style="margin: 0; color: var(--primary);">Panel de Salud</h1>
        <p style="color: #666; margin: 0;">Bienvenido de nuevo, <strong><?= Security::e($userName); ?></strong></p>
    </div>
    <div style="text-align: right;">
        <span style="font-size: 0.8rem; color: #888;">ID: #<?= $userId ?></span>
    </div>
</div>

<?php if (!empty($_GET['pin_ok'])): ?>
    <div class="alert alert-success">PIN actualizado correctamente.</div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- COLUMNA IZQUIERDA: BITÁCORA -->
    <div class="card">
        <div class="section-header">
            <h2 style="font-size: 1.2rem; margin: 0;">🩺 Bitácora Diaria</h2>
        </div>
        
        <form method="post" action="index.php?route=health_store" style="margin-bottom: 2rem;">
            <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Temperatura (°C)</label>
                    <input type="number" step="0.1" name="temperatura" min="35" max="42" placeholder="36.5" required>
                </div>
                <div class="form-group">
                    <label>Presión (Sist/Diast)</label>
                    <div style="display: flex; gap: 5px; align-items: center;">
                        <input type="number" name="presion_sys" min="70" max="250" placeholder="120" required style="padding: 0.8rem 0.4rem; text-align: center;">
                        <span>/</span>
                        <input type="number" name="presion_dia" min="40" max="150" placeholder="80" required style="padding: 0.8rem 0.4rem; text-align: center;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.1" name="peso" min="20" max="500" placeholder="70.5">
                </div>
                <div class="form-group">
                    <label>Energía (1-10)</label>
                    <input type="number" min="1" max="10" name="nivel_energia" placeholder="8">
                </div>
            </div>

            <div class="form-group">
                <label>Síntomas o malestares</label>
                <textarea name="sintomas" rows="2" placeholder="Describe cómo te sientes hoy..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Registro</button>
        </form>

        <div class="section-header">
            <h3 style="font-size: 1rem; margin: 0;">Historial Reciente</h3>
        </div>
        <div style="max-height: 200px; overflow-y: auto;">
            <?php if (empty($healthLogs)): ?>
                <p style="font-size: 0.9rem; color: #999; text-align: center;">No hay registros previos.</p>
            <?php else: ?>
                <table style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
                    <thead style="border-bottom: 1px solid #eee;">
                        <tr>
                            <th style="text-align: left; padding: 5px;">Fecha</th>
                            <th style="text-align: center; padding: 5px;">Temp</th>
                            <th style="text-align: center; padding: 5px;">Ene</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($healthLogs, 0, 5) as $log): ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 8px 5px;"><?= date('d/m H:i', strtotime($log['fecha'])) ?></td>
                                <td style="padding: 8px 5px; text-align: center;"><?= Security::e((string)$log['temperatura']) ?>°</td>
                                <td style="padding: 8px 5px; text-align: center;"><?= Security::e((string)$log['nivel_energia']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- COLUMNA DERECHA: MEDICAMENTOS Y REPORTES -->
    <div class="card">
        <div class="section-header">
            <h2 style="font-size: 1.2rem; margin: 0;">💊 Medicamentos</h2>
        </div>
        
        <form method="post" action="index.php?route=med_store" style="margin-bottom: 1.5rem;">
            <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
            <div class="form-group">
                <input type="text" name="nombre_medicamento" placeholder="Nombre (Ej: Paracetamol)" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <input type="text" name="dosis" placeholder="Dosis (Ej: 500mg)" required>
                <input type="text" name="horario" placeholder="Horario (8:00)" required>
            </div>
            <button type="submit" class="btn btn-secondary" style="width: 100%; padding: 0.5rem; margin-top: 10px;">Agregar</button>
        </form>

        <div style="margin-bottom: 2rem;">
            <?php foreach ($medications as $med): ?>
                <div style="background: rgba(82, 183, 136, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: var(--primary);"><?= Security::e($med['nombre_medicamento']) ?></strong>
                        <div style="font-size: 0.8rem; color: #666;"><?= Security::e($med['dosis']) ?> • <?= Security::e($med['horario']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section-header">
            <h2 style="font-size: 1.2rem; margin: 0;">📄 Reporte Semanal</h2>
        </div>
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Descarga un resumen cifrado de tu última semana para tu médico.</p>
        
        <form method="post" action="index.php?route=pdf_report" target="_blank">
            <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
            <div class="form-group">
                <label style="font-size: 0.8rem;">Contraseña para el PDF</label>
                <input type="password" name="pdf_password" placeholder="Mínimo 6 caracteres" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; background: var(--secondary);">Generar PDF Seguro</button>
        </form>
    </div>
</div>

<!-- SECCIÓN DE CONFIGURACIÓN (PIE DE PÁGINA) -->
<div class="card" style="margin-top: 2rem; padding: 1.5rem;">
    <div class="section-header">
        <h3 style="font-size: 1rem; margin: 0;">Seguridad de la Cuenta</h3>
    </div>
    <form method="post" action="index.php?route=change_pin" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end;">
        <input type="hidden" name="csrf_token" value="<?= Security::e($csrfToken); ?>">
        <div class="form-group" style="margin: 0;">
            <label style="font-size: 0.75rem;">PIN Actual</label>
            <input type="password" name="current_pin" pattern="\d{6}" maxlength="6" style="padding: 0.4rem;" required>
        </div>
        <div class="form-group" style="margin: 0;">
            <label style="font-size: 0.75rem;">Nuevo PIN</label>
            <input type="password" name="new_pin" pattern="\d{6}" maxlength="6" style="padding: 0.4rem;" required>
        </div>
        <div class="form-group" style="margin: 0;">
            <label style="font-size: 0.75rem;">Confirmar</label>
            <input type="password" name="new_pin_confirm" pattern="\d{6}" maxlength="6" style="padding: 0.4rem;" required>
        </div>
        <button type="submit" class="btn btn-secondary" style="padding: 0.4rem; font-size: 0.8rem;">Actualizar PIN</button>
    </form>
</div>

<?php
$content = ob_get_clean();
$title = 'Panel de Salud - Kanan';
include __DIR__ . '/../layouts/base.php';
?>
