<?php

namespace Utils;

use Models\HealthLog;
use Config\Database;
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

        $pdf = new TCPDF();
        $pdf->SetCreator('Kanan Web');
        $pdf->SetAuthor('Kanan Web');
        $pdf->SetTitle('Reporte semanal de salud');

        $pdf->SetProtection(
            ['print', 'copy'],
            $password,
            null
        );

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        $html = '<h1>Reporte semanal de salud</h1>';
        $html .= '<p>Paciente: ' . Security::e($nombre) . '</p>';
        $html .= '<table border="1" cellpadding="4">
                    <tr>
                      <th>Fecha</th>
                      <th>Temp</th>
                      <th>Presión</th>
                      <th>Peso</th>
                      <th>Energía</th>
                      <th>Síntomas</th>
                    </tr>';

        foreach ($logs as $log) {
            $html .= '<tr>';
            $html .= '<td>' . Security::e($log['fecha']) . '</td>';
            $html .= '<td>' . Security::e((string)$log['temperatura']) . '</td>';
            $html .= '<td>' . Security::e($log['presion']) . '</td>';
            $html .= '<td>' . Security::e((string)$log['peso']) . '</td>';
            $html .= '<td>' . Security::e((string)$log['nivel_energia']) . '</td>';
            $html .= '<td>' . Security::e($log['sintomas']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('reporte_semanal.pdf', 'I');
    }
}

