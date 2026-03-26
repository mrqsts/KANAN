<?php
$title = 'Configurar Authenticator';
ob_start();
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>¡Registro casi completo!</h2>
        <p>Para proteger tu cuenta de salud, KANAN utiliza autenticación de dos pasos.</p>
        
        <div class="qr-setup">
            <p>1. Descarga <strong>Google Authenticator</strong> o <strong>Authy</strong> en tu celular.</p>
            <p>2. Escanea este código QR desde la aplicación:</p>
            
            <div class="qr-code-wrapper">
                <img src="<?= $qrCodeUrl ?>" alt="Código QR MFA" style="border: 10px solid #fff; border-radius: 8px;">
            </div>
            
            <p>O ingresa este código manualmente:</p>
            <code class="manual-secret"><?= $secret ?></code>
        </div>

        <div class="auth-info">
            <p>Una vez configurado, presiona el botón para continuar e iniciar sesión.</p>
        </div>

        <div class="auth-actions">
            <a href="index.php?route=login" class="btn btn-primary">Ir al Login</a>
        </div>
    </div>
</div>

<style>
.qr-setup {
    text-align: center;
    margin: 20px 0;
}
.qr-code-wrapper {
    margin: 20px 0;
}
.manual-secret {
    display: block;
    background: #f4f4f4;
    padding: 10px;
    font-size: 1.2rem;
    letter-spacing: 2px;
    margin: 10px 0;
    border-radius: 4px;
    color: #333;
}
.auth-info {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 20px;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
