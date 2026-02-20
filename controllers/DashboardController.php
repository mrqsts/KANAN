<?php

namespace Controllers;

use Models\HealthLog;
use Models\Medication;
use Models\User;
use Utils\Security;
use Utils\Logger;
use Utils\PdfReport;
use Utils\Validator;
use Config\Database;
use PDO;

class DashboardController
{
    private function requireAuth(): int
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            $this->clearSessionAndRedirectLogin();
        }
        $user = User::findById($userId);
        if (!$user) {
            $this->clearSessionAndRedirectLogin();
        }
        return $userId;
    }

    private function clearSessionAndRedirectLogin(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: index.php?route=login');
        exit;
    }

    public function index(): void
    {
        $userId = $this->requireAuth();

        $healthLogs = HealthLog::getRecent($userId);
        $medications = Medication::allByUser($userId);
        $csrfToken = Security::getCsrfToken();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT edad, sangre, altura, peso, alergias FROM personal_data WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $personal = $stmt->fetch() ?: ['edad' => null, 'sangre' => null, 'altura' => null, 'peso' => null, 'alergias' => null];

        $stmt = $pdo->prepare('SELECT nombre, email FROM users WHERE id = :uid');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $userName = $row['nombre'] ?? '';
        $userEmail = $row['email'] ?? '';

        $emergencyQrUrl = null;
        $publicBaseUrl = $_SESSION['public_base_url'] ?? '';
        if (!empty($_GET['emerg_token'])) {
            if (!empty($publicBaseUrl)) {
                $base = rtrim(preg_replace('#/+$#', '', $publicBaseUrl), '/');
                $emergencyQrUrl = $base . '/ver_emergencia.php?token=' . $_GET['emerg_token'];
            } else {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');
                $base = rtrim(str_replace('\\', '/', $base), '/');
                $emergencyQrUrl = $scheme . '://' . $host . ($base ? $base . '/' : '/') . 'ver_emergencia.php?token=' . $_GET['emerg_token'];
            }
        }

        include __DIR__ . '/../views/dashboard/index.php';
    }

    public function savePersonalData(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Solicitud inválida.';
            return;
        }

        $edad = Security::sanitizeInt($_POST['edad'] ?? null);
        $sangre = Security::sanitizeString($_POST['sangre'] ?? null);
        $altura = Security::sanitizeFloat($_POST['altura'] ?? null);
        $peso = Security::sanitizeFloat($_POST['peso'] ?? null);
        $alergias = Security::sanitizeString($_POST['alergias'] ?? null);

        if ($edad !== null && !Validator::int($edad, 1, 120)) {
            $edad = null;
        }
        if ($sangre !== null && $sangre !== '') {
            $sangre = substr($sangre, 0, 3);
            if (!Validator::bloodType($sangre)) {
                $sangre = null;
            }
        }
        if ($altura !== null && !Validator::float($altura, 0, 300)) {
            $altura = null;
        }
        if ($peso !== null && !Validator::float($peso, 0, 500)) {
            $peso = null;
        }
        if ($alergias !== null && !Validator::string($alergias, 0, 2000)) {
            $alergias = null;
        }
        if ($alergias !== null) {
            $alergias = substr($alergias, 0, 2000);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO personal_data (user_id, edad, sangre, altura, peso, alergias)
             VALUES (:uid, :edad, :sangre, :altura, :peso, :alergias)
             ON DUPLICATE KEY UPDATE
               edad = VALUES(edad),
               sangre = VALUES(sangre),
               altura = VALUES(altura),
               peso = VALUES(peso),
               alergias = VALUES(alergias)'
        );
        $stmt->execute([
            ':uid'     => $userId,
            ':edad'    => $edad,
            ':sangre'  => $sangre ?: null,
            ':altura'  => $altura,
            ':peso'    => $peso,
            ':alergias'=> $alergias ?: null,
        ]);

        header('Location: index.php?route=dashboard');
        exit;
    }

    public function saveEmail(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: index.php?route=dashboard');
            exit;
        }

        $email = trim(Security::sanitizeString($_POST['email'] ?? ''));
        if ($email !== '' && !Validator::email($email)) {
            header('Location: index.php?route=dashboard&email_error=1');
            exit;
        }
        if ($email !== '' && mb_strlen($email) > 255) {
            $email = substr($email, 0, 255);
        }

        if ($email !== '') {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :uid LIMIT 1');
            $stmt->execute([':email' => $email, ':uid' => $userId]);
            if ($stmt->fetch()) {
                header('Location: index.php?route=dashboard&email_error=2');
                exit;
            }
        }

        User::updateEmail($userId, $email !== '' ? $email : null);
        header('Location: index.php?route=dashboard&email_ok=1');
        exit;
    }

    public function changePin(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: index.php?route=dashboard');
            exit;
        }

        $currentPin = $_POST['current_pin'] ?? '';
        $newPin = $_POST['new_pin'] ?? '';
        $newPinConfirm = $_POST['new_pin_confirm'] ?? '';

        if (!\Utils\Validator::pinFormat($currentPin) || !\Utils\Validator::pinFormat($newPin)) {
            header('Location: index.php?route=dashboard&pin_error=1');
            exit;
        }
        if ($newPin !== $newPinConfirm) {
            header('Location: index.php?route=dashboard&pin_error=2');
            exit;
        }
        if (Security::isWeakPin($newPin)) {
            header('Location: index.php?route=dashboard&pin_error=3');
            exit;
        }

        if (!User::updatePin($userId, $currentPin, $newPin)) {
            header('Location: index.php?route=dashboard&pin_error=4');
            exit;
        }

        header('Location: index.php?route=dashboard&pin_ok=1');
        exit;
    }

    public function setPublicUrl(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        $url = trim((string)($_POST['public_base_url'] ?? ''));
        if (Validator::url($url)) {
            $_SESSION['public_base_url'] = rtrim(preg_replace('#/+$#', '', $url), '/');
        } else {
            unset($_SESSION['public_base_url']);
        }
        header('Location: index.php?route=dashboard');
        exit;
    }

    public function storeHealthLog(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Solicitud inválida.';
            return;
        }

        $temperatura = Security::sanitizeFloat($_POST['temperatura'] ?? null);
        $presion = Security::sanitizeString($_POST['presion'] ?? null);
        $peso = Security::sanitizeFloat($_POST['peso'] ?? null);
        $nivel = Security::sanitizeInt($_POST['nivel_energia'] ?? null);
        $sintomas = Security::sanitizeString($_POST['sintomas'] ?? null);

        if ($temperatura !== null && !Validator::float($temperatura, 35, 42)) {
            $temperatura = null;
        }
        if ($presion !== null && !Validator::string($presion, 0, 20)) {
            $presion = null;
        }
        if ($peso !== null && !Validator::float($peso, 0, 500)) {
            $peso = null;
        }
        if ($nivel !== null && !Validator::int($nivel, 1, 10)) {
            $nivel = null;
        }
        if ($sintomas !== null && !Validator::string($sintomas, 0, 2000)) {
            $sintomas = null;
        }
        if ($sintomas !== null) {
            $sintomas = substr($sintomas, 0, 2000);
        }

        try {
            HealthLog::create($userId, $temperatura, $presion, $peso, $nivel, $sintomas);
        } catch (\Throwable $e) {
            error_log('Error creando health_log: ' . $e->getMessage());
        }

        header('Location: index.php?route=dashboard');
        exit;
    }

    public function listMedications(): void
    {
        $this->index();
    }

    public function storeMedication(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Solicitud inválida.';
            return;
        }

        $nombre  = Security::sanitizeString($_POST['nombre_medicamento'] ?? '');
        $dosis   = Security::sanitizeString($_POST['dosis'] ?? '');
        $horario = Security::sanitizeString($_POST['horario'] ?? '');

        if (!Validator::string($nombre, 1, 255)) {
            $nombre = '';
        }
        if (!Validator::string($dosis, 1, 100)) {
            $dosis = '';
        }
        if (!Validator::string($horario, 1, 100)) {
            $horario = '';
        }
        $nombre = $nombre !== null ? substr($nombre, 0, 255) : '';
        $dosis = $dosis !== null ? substr($dosis, 0, 100) : '';
        $horario = $horario !== null ? substr($horario, 0, 100) : '';

        if ($nombre !== '' && $dosis !== '' && $horario !== '') {
            try {
                Medication::create($userId, $nombre, $dosis, $horario);
            } catch (\Throwable $e) {
                error_log('Error creando medicamento: ' . $e->getMessage());
            }
        }

        header('Location: index.php?route=dashboard');
        exit;
    }

    public function generatePdfReport(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Solicitud inválida.';
            return;
        }

        $password = $_POST['pdf_password'] ?? '';
        if (!Validator::string($password, 6, 128)) {
            header('Location: index.php?route=dashboard&pdf_error=1');
            exit;
        }

        Logger::log($userId, 'Generación de reporte PDF semanal');
        PdfReport::generateEncryptedWeeklyReport($userId, $password);
    }

    public function generateEmergencyQr(): void
    {
        $userId = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=dashboard');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Solicitud inválida.';
            return;
        }

        $token = Security::generateToken(32);
        $expiresAt = (new \DateTime('+1 day'))->format('Y-m-d H:i:s');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO emergency_tokens (user_id, token, expires_at)
             VALUES (:uid, :token, :exp)'
        );
        $stmt->execute([
            ':uid'   => $userId,
            ':token' => $token,
            ':exp'   => $expiresAt,
        ]);

        Logger::log($userId, 'Generación de token de emergencia');

        header('Location: index.php?route=dashboard&emerg_token=' . urlencode($token));
        exit;
    }

    public function emergencyView(): void
    {
        $token = $_GET['token'] ?? '';
        $token = preg_replace('/[^a-f0-9]/i', '', $token);

        if (strlen($token) < 32) {
            http_response_code(400);
            echo 'Token inválido o expirado.';
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT et.user_id, pd.sangre, pd.alergias
             FROM emergency_tokens et
             LEFT JOIN personal_data pd ON et.user_id = pd.user_id
             WHERE et.token = :token AND et.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $data = $stmt->fetch();

        if (!$data) {
            http_response_code(404);
            echo 'Token inválido o expirado.';
            return;
        }

        $sangre = $data['sangre'] !== null && $data['sangre'] !== '' ? Security::e($data['sangre']) : 'No especificado';
        $alergias = $data['alergias'] !== null && $data['alergias'] !== '' ? Security::e($data['alergias']) : 'No especificado';

        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Emergencia - Kanan Web</title></head><body>';
        echo '<h1>Información de emergencia</h1>';
        echo '<p><strong>Tipo de sangre:</strong> ' . $sangre . '</p>';
        echo '<p><strong>Alergias:</strong> ' . nl2br($alergias) . '</p>';
        echo '</body></html>';
    }
}

