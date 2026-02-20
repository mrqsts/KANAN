<?php

/**
 * Configuración de correo para MFA (SMTP).
 * Copia este archivo a mail.php y rellena con tus datos.
 * No subas mail.php a un repositorio (añade config/mail.php al .gitignore).
 *
 * Gmail: usa "Contraseña de aplicación" (no tu contraseña normal).
 *   - Activa verificación en 2 pasos en tu cuenta Google.
 *   - Ve a Seguridad > Contraseñas de aplicación y genera una para "Correo".
 *
 * Outlook/Hotmail: smtp.office365.com, puerto 587, TLS.
 */

return [
    'enabled'   => true,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',  // 'tls' o 'ssl'
    'smtp_user' => 'tu_correo@gmail.com',
    'smtp_pass' => 'tu_contraseña_de_aplicacion',
    'from_email' => 'noreply@tudominio.com',  // opcional; si no se pone, se usa smtp_user
    'from_name'  => 'Kanan Web',
];
