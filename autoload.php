<?php
/**
 * Autocargador de clases PSR-4 (nativo)
 */
spl_autoload_register(function ($class) {
    $prefixes = [
        'Config\\'      => __DIR__ . '/config/',
        'Controllers\\' => __DIR__ . '/controllers/',
        'Models\\'      => __DIR__ . '/models/',
        'Utils\\'       => __DIR__ . '/utils/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// CARGAR LIBRERÍAS DE VENDOR (TCPDF, PHPMailer)
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}
