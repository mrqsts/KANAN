<?php

namespace Controllers;

use Models\User;
use Utils\Security;
use Utils\Logger;
use Utils\Validator;
use Utils\Mailer;

use Utils\GoogleAuthenticator;

class AuthController
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = Security::sanitizeString($_POST['nombre'] ?? '');
            $pin = $_POST['pin'] ?? '';
            $csrf = $_POST['csrf_token'] ?? '';

            if (!Security::validateCsrfToken($csrf)) {
                http_response_code(400);
                echo 'Solicitud inválida.';
                return;
            }

            if (!Validator::pinFormat($pin)) {
                $this->renderLogin('PIN debe ser exactamente 6 dígitos.');
                return;
            }
            if (!Validator::string($nombre, 1, 100)) {
                $this->renderLogin('Nombre de usuario no válido.');
                return;
            }

            $user = User::verifyCredentials($nombre, $pin);

            if (!$user) {
                $error = 'Credenciales inválidas o cuenta bloqueada.';
                $this->renderLogin($error);
                return;
            }

            $_SESSION['pending_mfa_user_id'] = $user->id;
            // Guardamos si el usuario tiene secreto de Google Authenticator
            $_SESSION['mfa_type'] = !empty($user->mfa_secret) ? 'totp' : 'email';

            if ($_SESSION['mfa_type'] === 'email' && !empty($user->email)) {
                $code = User::createMfaCode($user->id);
                Mailer::sendMfaCode($user->email, $code);
                $_SESSION['mfa_via_email'] = true;
            } else {
                $_SESSION['mfa_via_email'] = false;
            }

            header('Location: index.php?route=login_mfa');
            exit;
        }

        $this->renderLogin();
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = Security::sanitizeString($_POST['nombre'] ?? '');
            $pin = $_POST['pin'] ?? '';
            $pinConfirm = $_POST['pin_confirm'] ?? '';
            $csrf = $_POST['csrf_token'] ?? '';

            if (!Security::validateCsrfToken($csrf)) {
                http_response_code(400);
                echo 'Solicitud inválida.';
                return;
            }

            if (!Validator::string($nombre, 1, 100)) {
                $this->renderRegister('El nombre debe tener entre 1 y 100 caracteres.');
                return;
            }
            if (!Validator::pinFormat($pin)) {
                $this->renderRegister('El PIN debe ser exactamente 6 dígitos.');
                return;
            }
            if ($pin !== $pinConfirm) {
                $this->renderRegister('El PIN y la confirmación no coinciden.');
                return;
            }
            if (Security::isWeakPin($pin)) {
                $this->renderRegister('Elige un PIN más seguro (evita 123456, 000000 o secuencias).');
                return;
            }
            if (User::findByName($nombre) !== null) {
                $this->renderRegister('Ese nombre de usuario ya está en uso.');
                return;
            }

            $email = Security::sanitizeString($_POST['email'] ?? '');
            if ($email !== '' && !Validator::email($email)) {
                $this->renderRegister('El correo no es válido.');
                return;
            }

            // Generar secreto de Google Authenticator para la nueva cuenta
            $mfaSecret = GoogleAuthenticator::createSecret();

            try {
                User::create($nombre, $pin, $email !== '' ? $email : null, $mfaSecret);
                
                // Guardamos datos temporalmente para mostrar el QR en la siguiente pantalla
                $_SESSION['show_qr_nombre'] = $nombre;
                $_SESSION['show_qr_secret'] = $mfaSecret;
                
                header('Location: index.php?route=register_success');
                exit;
            } catch (\PDOException $e) {
                error_log('Error en registro: ' . $e->getMessage());
                $this->renderRegister('No se pudo crear la cuenta. Intenta de nuevo.');
                return;
            }
        }

        $this->renderRegister();
    }

    public function registerSuccess(): void
    {
        if (!isset($_SESSION['show_qr_secret'])) {
            header('Location: index.php?route=login');
            exit;
        }

        $nombre = $_SESSION['show_qr_nombre'];
        $secret = $_SESSION['show_qr_secret'];
        $qrCodeUrl = GoogleAuthenticator::getQrCodeUrl($nombre, $secret, 'KananWeb');
        
        // Limpiamos la sesión después de obtener los datos para la vista
        unset($_SESSION['show_qr_nombre'], $_SESSION['show_qr_secret']);

        include __DIR__ . '/../views/auth/register_success.php';
    }

    public function loginMfa(): void
    {
        if (!isset($_SESSION['pending_mfa_user_id'])) {
            header('Location: index.php?route=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = Security::sanitizeString($_POST['mfa_code'] ?? '');
            $csrf = $_POST['csrf_token'] ?? '';

            if (!Security::validateCsrfToken($csrf)) {
                http_response_code(400);
                echo 'Solicitud inválida.';
                return;
            }

            $userId = (int)$_SESSION['pending_mfa_user_id'];
            $user = User::findById($userId);

            if (!$user) {
                header('Location: index.php?route=login');
                exit;
            }

            $valid = false;
            if (!empty($user->mfa_secret)) {
                // Validación con Google Authenticator
                $valid = GoogleAuthenticator::verifyCode($user->mfa_secret, $code);
            } elseif (!empty($_SESSION['mfa_via_email'])) {
                // Fallback a Email si no tiene secreto (cuentas antiguas)
                $valid = User::consumeMfaCode($userId, $code);
            } else {
                // Fallback MVP
                $valid = ($code === '123123');
            }

            if (!$valid) {
                Logger::log($userId, 'MFA fallido');
                $this->renderMfa('Código incorrecto o expirado.');
                return;
            }

            $_SESSION['user_id'] = $userId;
            unset($_SESSION['pending_mfa_user_id'], $_SESSION['mfa_via_email'], $_SESSION['mfa_type']);
            session_regenerate_id(true);

            Logger::log((int)$_SESSION['user_id'], 'MFA exitoso');

            header('Location: index.php?route=dashboard');
            exit;
        }

        $this->renderMfa();
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            Logger::log((int)$_SESSION['user_id'], 'Logout');
        }
        session_destroy();
        header('Location: index.php?route=login');
        exit;
    }

    private function renderLogin(?string $error = null): void
    {
        $csrfToken = Security::getCsrfToken();
        $successMessage = isset($_GET['registered']) ? 'Cuenta creada. Ya puedes iniciar sesión.' : null;
        include __DIR__ . '/../views/auth/login.php';
    }

    private function renderRegister(?string $error = null): void
    {
        $csrfToken = Security::getCsrfToken();
        include __DIR__ . '/../views/auth/register.php';
    }

    private function renderMfa(?string $error = null): void
    {
        $csrfToken = Security::getCsrfToken();
        $mfaViaEmail = !empty($_SESSION['mfa_via_email']);
        include __DIR__ . '/../views/auth/mfa.php';
    }
}

