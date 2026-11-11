<?php
/**
 * Doctor Queue Management Controller (medical_platform)
 * COMP6695 Secure Programming
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use PDO;

class DoctorQueueController
{
    private function getDoctorId(): int
    {
        $user = Auth::requireRole(['doctor', 'dokter']);
        if (!empty($user['doctor_id'])) {
            return (int) $user['doctor_id'];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $user['user_id']]);
        $row = $stmt->fetch();
        if (!$row) {
            Response::error(404, 'Profil dokter tidak ditemukan.');
        }

        return (int) $row['doctor_id'];
    }

    public function today(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $today = date('Y-m-d');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT a.appointment_id AS id,
                   CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   SUBSTR(a.appointment_start, 12, 5) AS start_time,
                   SUBSTR(a.appointment_end, 12, 5) AS end_time,
                   a.status, a.reason,
                   q.queue_number, q.queue_status,
                   p.patient_id, pu.name AS patient_name, p.phone AS patient_phone
            FROM appointment_queues q
            JOIN appointments a ON a.appointment_id = q.appointment_id
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            WHERE q.doctor_id = :did AND q.queue_date = :today
            ORDER BY q.queue_number ASC
        ");
        $stmt->execute([':did' => $doctorId, ':today' => $today]);
        $all = $stmt->fetchAll();

        $serving = null;
        $waiting = [];
        $done = [];
        $currentQueueNumber = 0;

        foreach ($all as $item) {
            if ($item['queue_status'] === 'serving') {
                $serving = $item;
                $currentQueueNumber = (int) $item['queue_number'];
            } elseif ($item['queue_status'] === 'waiting' && in_array($item['status'], ['pending', 'confirmed'])) {
                $waiting[] = $item;
            } else {
                $done[] = $item;
                if ($currentQueueNumber === 0) {
                    $currentQueueNumber = max($currentQueueNumber, (int) $item['queue_number']);
                }
            }
        }

        Response::json([
            'ok' => true,
            'data' => [
                'date' => $today,
                'current_queue_number' => $currentQueueNumber,
                'current_serving' => $serving,
                'waiting_list' => $waiting,
                'done_list' => $done,
                'total_patients' => count($all),
                'waiting_count' => count($waiting),
                'done_count' => count($done)
            ]
        ]);
    }

    /**
     * 🆕 Panggil Antrian Berikutnya (Next Queue)
     * Menandai pasien sebelumnya selesai, antrian maju ke pasien berikutnya.
     */
    public function next(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $pdo = Database::getConnection();

        $pdo->beginTransaction();
        try {
            // 1. If someone is currently serving, mark completed
            $currServingStmt = $pdo->prepare("
                SELECT q.appointment_id, q.queue_number 
                FROM appointment_queues q
                WHERE q.doctor_id = :did AND q.queue_date = :today AND q.queue_status = 'serving'
                LIMIT 1
            ");
            $currServingStmt->execute([':did' => $doctorId, ':today' => $today]);
            $currentServing = $currServingStmt->fetch();

            if ($currentServing) {
                $pdo->prepare("
                    UPDATE appointment_queues 
                    SET queue_status = 'completed', completed_at = :now 
                    WHERE appointment_id = :aid
                ")->execute([':now' => $now, ':aid' => $currentServing['appointment_id']]);

                $pdo->prepare("
                    UPDATE appointments 
                    SET status = 'completed', updated_at = :now 
                    WHERE appointment_id = :aid
                ")->execute([':now' => $now, ':aid' => $currentServing['appointment_id']]);
            }

            // 2. Find next waiting patient (lowest queue_number)
            $nextStmt = $pdo->prepare("
                SELECT a.appointment_id AS id, 
                       CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                       SUBSTR(a.appointment_start, 12, 5) AS start_time,
                       a.reason,
                       q.queue_number,
                       pu.name AS patient_name,
                       p.phone AS patient_phone
                FROM appointment_queues q
                JOIN appointments a ON a.appointment_id = q.appointment_id
                JOIN patients p ON p.patient_id = a.patient_id
                JOIN users pu ON pu.user_id = p.user_id
                WHERE q.doctor_id = :did AND q.queue_date = :today 
                  AND q.queue_status = 'waiting'
                  AND a.status IN ('pending', 'confirmed')
                ORDER BY q.queue_number ASC
                LIMIT 1
            ");
            $nextStmt->execute([':did' => $doctorId, ':today' => $today]);
            $nextPatient = $nextStmt->fetch();

            if (!$nextPatient) {
                $pdo->commit();
                Response::json([
                    'ok' => true,
                    'has_next' => false,
                    'message' => 'Semua antrian hari ini telah selesai dipanggil.'
                ]);
            }

            // 3. Mark next patient as serving
            $queueNo = (int) $nextPatient['queue_number'];
            $pdo->prepare("
                UPDATE appointment_queues 
                SET queue_status = 'serving', called_at = :now 
                WHERE appointment_id = :aid
            ")->execute([':now' => $now, ':aid' => $nextPatient['id']]);

            $pdo->prepare("
                UPDATE appointments 
                SET status = 'confirmed', updated_at = :now 
                WHERE appointment_id = :aid
            ")->execute([':now' => $now, ':aid' => $nextPatient['id']]);

            $pdo->commit();

            AuditLogger::log(
                $user['user_id'],
                'CALL_NEXT_QUEUE',
                'appointment_queues',
                $nextPatient['id'],
                'success',
                "Called queue #$queueNo ({$nextPatient['patient_name']})"
            );

            Response::json([
                'ok' => true,
                'has_next' => true,
                'message' => "Antrian #$queueNo ({$nextPatient['patient_name']}) berhasil dipanggil.",
                'data' => [
                    'appointment_id' => $nextPatient['id'],
                    'code' => $nextPatient['code'],
                    'queue_number' => $queueNo,
                    'patient_name' => $nextPatient['patient_name'],
                    'patient_phone' => $nextPatient['patient_phone'],
                    'reason' => $nextPatient['reason'],
                    'start_time' => $nextPatient['start_time']
                ]
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function call(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $body = $req->body();
        $appointmentId = (int) ($body['appointment_id'] ?? $req->param('id'));

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT q.queue_number, pu.name AS patient_name 
            FROM appointment_queues q
            JOIN appointments a ON a.appointment_id = q.appointment_id
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            WHERE q.appointment_id = :aid AND q.doctor_id = :did AND q.queue_date = :today
            LIMIT 1
        ");
        $stmt->execute([':aid' => $appointmentId, ':did' => $doctorId, ':today' => $today]);
        $row = $stmt->fetch();

        if (!$row) {
            Response::error(404, 'Antrian tidak ditemukan.');
        }

        $pdo->prepare("
            UPDATE appointment_queues SET queue_status = 'serving', called_at = :now WHERE appointment_id = :aid
        ")->execute([':now' => $now, ':aid' => $appointmentId]);

        AuditLogger::log($user['user_id'], 'RECALL_QUEUE', 'appointment_queues', $appointmentId, 'success', "Recalled queue #{$row['queue_number']}");

        Response::json([
            'ok' => true,
            'message' => "Antrian #{$row['queue_number']} ({$row['patient_name']}) dipanggil kembali."
        ]);
    }
}
