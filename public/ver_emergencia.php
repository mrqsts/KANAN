<?php

use Controllers\DashboardController;

require_once __DIR__ . '/../autoload.php';

set_exception_handler(function (\Throwable $e) {
    error_log('ver_emergencia: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body><p>No se pudo cargar la información. Intenta más tarde.</p></body></html>';
});

(new DashboardController())->emergencyView();

