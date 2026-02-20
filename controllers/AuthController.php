<?php

namespace Controllers;

use Models\User;
use Utils\Security;
use Utils\Logger;
use Utils\Validator;
use Utils\Mailer;

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
            if (Security::isWeakPin($pin)) {
                $error = 'PIN inválido o demasiado débil.';
                $this->renderLogin($error);
                return;
            }

            $user = User::verifyCredentials($nombre, $pin);

            if (!$user) {
                $error = 'Credenciales inválidas o cuenta bloqueada.';
                $this->renderLogin($error);
                return;
            }

            $_SESSION['pending_mfa_user_id'] = $user->id;

            if (!empty($user->email)) {
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
            if ($email !== '' && mb_strlen($email) > 255) {
                $email = substr($email, 0, 255);
            }
            if ($email !== '') {
                try {
                    $pdo = \Config\Database::getConnection();
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    if ($stmt->fetch()) {
                        $this->renderRegister('Ese correo ya está registrado.');
                        return;
                    }
                } catch (\PDOException $e) {
                    if (str_contains($e->getMessage(), "Unknown column 'email'") || str_contains($e->getMessage(), 'email')) {
                        $this->renderRegister('La base de datos no tiene la columna de correo. Ejecuta la migración: mariadb -u root -p kanan_web < database_migration_mfa.sql');
                        return;
                    }
                    throw $e;
                }
            }

            try {
                User::create($nombre, $pin, $email !== '' ? $email : null);
            } catch (\PDOException $e) {
                error_log('Error en registro: ' . $e->getMessage());
                if (str_contains($e->getMessage(), "Unknown column 'email'") || str_contains($e->getMessage(), "mfa_codes")) {
                    $this->renderRegister('Falta ejecutar la migración de MFA. En la carpeta del proyecto ejecuta: mariadb -u root -p kanan_web < database_migration_mfa.sql');
                    return;
                }
                $this->renderRegister('No se pudo crear la cuenta. Intenta de nuevo.');
                return;
            } catch (\Throwable $e) {
                error_log('Error en registro: ' . $e->getMessage());
                $this->renderRegister('No se pudo crear la cuenta. Intenta de nuevo.');
                return;
            }

            header('Location: index.php?route=login&registered=1');
            exit;
        }

        $this->renderRegister();
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

            if (!Validator::string($code, 6, 6, '/^\d{6}$/')) {
                Logger::log((int)$_SESSION['pending_mfa_user_id'], 'MFA fallido');
                $this->renderMfa('Código de verificación inválido.');
                return;
            }

            $userId = (int)$_SESSION['pending_mfa_user_id'];
            $valid = !empty($_SESSION['mfa_via_email'])
                ? User::consumeMfaCode($userId, $code)
                : ($code === '123123');

            if (!$valid) {
                Logger::log($userId, 'MFA fallido');
                $this->renderMfa('Código incorrecto o expirado. Solicita uno nuevo iniciando sesión otra vez.');
                return;
            }

            $_SESSION['user_id'] = $userId;
            unset($_SESSION['pending_mfa_user_id'], $_SESSION['mfa_via_email']);
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

