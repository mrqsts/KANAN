<?php

use Controllers\AuthController;
use Controllers\DashboardController;

require_once __DIR__ . '/../autoload.php';

// 3.4 Sesiones: identificadores no predecibles (strict mode rechaza IDs no iniciados por el servidor)
ini_set('session.use_strict_mode', '1');
// 3.4 Cookies seguras: HttpOnly, Secure (solo HTTPS), SameSite
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 3.5 Manejo de errores: no exponer stack trace ni mensajes internos al usuario
set_exception_handler(function (\Throwable $e) {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body><p>Ha ocurrido un error. Intenta de nuevo más tarde.</p></body></html>';
});

// 3.1 Validación de entrada: ruta permitida (whitelist), evita inyección
$route = isset($_GET['route']) && is_string($_GET['route']) ? trim($_GET['route']) : 'login';
$route = preg_replace('/[^a-z0-9_]/', '', $route) ?: 'login';

$allowedRoutes = [
    'login', 'login_mfa', 'logout', 'register', 'dashboard',
    'health_store', 'med_list', 'med_store', 'personal_data_save',
    'save_email', 'change_pin', 'pdf_report', 'generate_qr', 'set_public_url',
];
if (!in_array($route, $allowedRoutes, true)) {
    $route = 'login';
}

switch ($route) {
    case 'login':
        (new AuthController())->login();
        break;
    case 'register':
        (new AuthController())->register();
        break;
    case 'login_mfa':
        (new AuthController())->loginMfa();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'dashboard':
        (new DashboardController())->index();
        break;
    case 'health_store':
        (new DashboardController())->storeHealthLog();
        break;
    case 'med_list':
        (new DashboardController())->listMedications();
        break;
    case 'med_store':
        (new DashboardController())->storeMedication();
        break;
    case 'personal_data_save':
        (new DashboardController())->savePersonalData();
        break;
    case 'save_email':
        (new DashboardController())->saveEmail();
        break;
    case 'change_pin':
        (new DashboardController())->changePin();
        break;
    case 'pdf_report':
        (new DashboardController())->generatePdfReport();
        break;
    case 'generate_qr':
        (new DashboardController())->generateEmergencyQr();
        break;
    case 'set_public_url':
        (new DashboardController())->setPublicUrl();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada.';
}

