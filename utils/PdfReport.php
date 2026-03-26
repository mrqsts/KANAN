<?php

namespace Utils;

use Models\HealthLog;
use Config\Database;

// SOLUCIÓN DEFINITIVA PARA RUTAS EN INFINITYFREE
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/kanan_app/vendor/tecnickcom/tcpdf/';

if (!defined('K_PATH_MAIN')) {
    define('K_PATH_MAIN', $basePath);
}
if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', $basePath . 'fonts/');
}

$tcpdfPath = $basePath . 'tcpdf.php';
if (file_exists($tcpdfPath)) {
    require_once $tcpdfPath;
}

use TCPDF;

class PdfReport
{
    public static function generateEncryptedWeeklyReport(int $userId, string $password): void
    {
        $logs = HealthLog::getLastWeek($userId);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT nombre FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        $nombre = $user['nombre'] ?? 'Usuario';

        // Configuración de TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // FORZAR RUTAS INTERNAS DE TCPDF
        $pdf->SetCreator('Kanan Web');
        $pdf->SetAuthor('Kanan Web');
        $pdf->SetTitle('Reporte Semanal de Salud - Kanan');

        // Quitar cabecera y pie por defecto de TCPDF
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Establecer protección con contraseña
        $pdf->SetProtection(['print', 'copy'], $password, null);

        $pdf->AddPage();
        
        // Estilo de Colores
        $colorAzul = '#1a759f';
        $colorVerde = '#2d6a4f';
        $colorGris = '#f9f9f9';

        // Título y Cabecera
        $html = '
        <style>
            h1 { color: ' . $colorAzul . '; font-family: helvetica; font-size: 26pt; margin: 0; }
            .subtitle { color: ' . $colorVerde . '; font-size: 14pt; font-weight: bold; border-bottom: 2px solid ' . $colorVerde . '; padding-bottom: 5px; }
            .patient-info { font-size: 10pt; color: #444; }
            table { border-collapse: collapse; width: 100%; margin-top: 30px; }
            th { background-color: ' . $colorAzul . '; color: white; font-weight: bold; padding: 10px; text-align: center; border: 0.5px solid #fff; }
            td { padding: 8px; border-bottom: 0.5px solid #eee; font-size: 10pt; color: #333; }
            .even { background-color: ' . $colorGris . '; }
            .footer { font-size: 8pt; color: #888; text-align: center; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px; }
            .status-badge { font-weight: bold; color: ' . $colorAzul . '; }
        </style>

        <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td width="60%" style="border:none;">
                    <h1>KANAN</h1>
                    <div class="subtitle">Reporte de Salud Semanal</div>
                </td>
                <td width="40%" align="right" style="border:none; padding-top: 10px;">
                    <span class="patient-info"><strong>PACIENTE:</strong> ' . strtoupper(Security::e($nombre)) . '</span><br>
                    <span class="patient-info"><strong>EMISIÓN:</strong> ' . date('d/m/Y H:i') . '</span>
                </td>
            </tr>
        </table>
        
        <p style="color: #666; font-size: 9pt; margin-top: 20px;">Este documento es un resumen confidencial de las métricas de salud registradas por el usuario durante los últimos 7 días.</p>

        <table cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th width="15%">FECHA</th>
                    <th width="10%">TEMP</th>
                    <th width="15%">PRESIÓN</th>
                    <th width="10%">PESO</th>
                    <th width="10%">ENE</th>
                    <th width="40%">SÍNTOMAS / OBSERVACIONES</th>
                </tr>
            </thead>
            <tbody>';

        $i = 0;
        foreach ($logs as $log) {
            $class = ($i++ % 2 == 0) ? 'even' : '';
            $html .= '<tr class="' . $class . '">
                        <td width="15%" align="center">' . date('d/m/y', strtotime($log['fecha'])) . '</td>
                        <td width="10%" align="center"><span class="status-badge">' . Security::e((string)$log['temperatura']) . '°</span></td>
                        <td width="15%" align="center">' . Security::e($log['presion']) . '</td>
                        <td width="10%" align="center">' . Security::e((string)$log['peso']) . 'kg</td>
                        <td width="10%" align="center">' . Security::e((string)$log['nivel_energia']) . '</td>
                        <td width="40%">' . (empty($log['sintomas']) ? '<i style="color:#ccc;">Sin observaciones</i>' : Security::e($log['sintomas'])) . '</td>
                    </tr>';
        }

        if (empty($logs)) {
            $html .= '<tr><td colspan="6" align="center" style="padding: 20px; color: #999;">No hay registros para este periodo.</td></tr>';
        }

        $html .= '
            </tbody>
        </table>

        <div class="footer">
            <br><br>
            <p>_____________________________________</p>
            <p>Firma del Paciente / Médico</p>
            <br>
            <p>Generado automáticamente por KANAN Web - Tu Bitácora de Salud Segura.</p>
        </div>';

        $pdf->writeHTML($html, true, false, true, false, '');
        
        $fileName = 'Reporte_Kanan_' . $nombre . '_' . date('Ymd') . '.pdf';
        $pdf->Output($fileName, 'I');
    }
}
