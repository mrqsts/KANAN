<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use Controllers\AuthController;
use Controllers\DashboardController;

require_once __DIR__ . '/kanan_app/autoload.php';

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

// 3.5 Manejo de errores: MOSTRAR ERRORES PARA DEBUG
set_exception_handler(function (\Throwable $e) {
    echo "<h1>Error detectado:</h1>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . " en la línea " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
});

// 3.1 Validación de entrada: ruta permitida (whitelist), evita inyección
$route = isset($_GET['route']) && is_string($_GET['route']) ? trim($_GET['route']) : 'login';
$route = preg_replace('/[^a-z0-9_]/', '', $route) ?: 'login';

$allowedRoutes = [
    'login', 'login_mfa', 'logout', 'register', 'register_success', 'dashboard',
    'health_store', 'med_list', 'med_store', 'personal_data_save',
    'save_email', 'change_pin', 'pdf_report', 'generate_qr', 'set_public_url',
];
if (!in_array($route, $allowedRoutes, true)) {
    $route = 'login';
}

$authController = new AuthController();
$dashboardController = new DashboardController();

switch ($route) {
    case 'login':
        $authController->login();
        break;
    case 'register':
        $authController->register();
        break;
    case 'register_success':
        $authController->registerSuccess();
        break;
    case 'login_mfa':
        $authController->loginMfa();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'dashboard':
        $dashboardController->index();
        break;
    case 'health_store':
        $dashboardController->storeHealthLog();
        break;
    case 'med_list':
        $dashboardController->listMedications();
        break;
    case 'med_store':
        $dashboardController->storeMedication();
        break;
    case 'personal_data_save':
        $dashboardController->savePersonalData();
        break;
    case 'save_email':
        $dashboardController->saveEmail();
        break;
    case 'change_pin':
        $dashboardController->changePin();
        break;
    case 'pdf_report':
        $dashboardController->generatePdfReport();
        break;
    case 'generate_qr':
        $dashboardController->generateEmergencyQr();
        break;
    case 'set_public_url':
        $dashboardController->setPublicUrl();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada.';
}

