<?php

// Autoloader sencillo para namespaces locales
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Config\\'      => __DIR__ . '/config/',
        'Controllers\\' => __DIR__ . '/controllers/',
        'Models\\'      => __DIR__ . '/models/',
        'Utils\\'       => __DIR__ . '/utils/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});

// Autoload de Composer (para TCPDF y otras libs)
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
}

