<?php
/**
 * Patient Appointments & Queue Status Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;
use PDO;

class PatientAppointmentController
{
    private function getPatientId(): int
    {
        $user = Auth::requireRole(['patient', 'pasien']);
        if (!empty($user['patient_id'])) {
            return (int) $user['patient_id'];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $user['user_id']]);
        $row = $stmt->fetch();
        if (!$row) {
            Response::error(404, 'Profil pasien tidak ditemukan.');
        }

        return (int) $row['patient_id'];
    }

    public function index(Request $req): void
    {
        $patientId = $this->getPatientId();
        $pdo = Database::getConnection();

        // IDOR Protection: Strictly filtered by patient_id
        $stmt = $pdo->prepare("
            SELECT a.appointment_id AS id, 
                   CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                   a.patient_id, a.doctor_id,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   SUBSTR(a.appointment_start, 12, 5) AS start_time,
                   SUBSTR(a.appointment_end, 12, 5) AS end_time,
                   a.status, a.reason, a.rejection_reason, a.cancellation_reason, a.created_at,
                   du.name AS doctor_name,
                   s.specialization_name AS specialization,
                   pu.name AS patient_name,
                   q.queue_number, 
                   COALESCE(q.queue_status, 'waiting') AS queue_status,
                   (
                       SELECT cur_q.queue_number 
                       FROM appointment_queues cur_q 
                       WHERE cur_q.doctor_id = a.doctor_id 
                         AND cur_q.queue_date = SUBSTR(a.appointment_start, 1, 10) 
                         AND cur_q.queue_status = 'serving'
                       LIMIT 1
                   ) AS current_serving_queue
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = a.doctor_id
            JOIN users du ON du.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            LEFT JOIN appointment_queues q ON q.appointment_id = a.appointment_id
            WHERE a.patient_id = :pid
            ORDER BY a.appointment_start DESC
        ");
        $stmt->execute([':pid' => $patientId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['current_serving_queue'] = (int) ($r['current_serving_queue'] ?: 0);
        }

        Response::json(['ok' => true, 'data' => $rows]);
    }

    public function upcoming(Request $req): void
    {
        $patientId = $this->getPatientId();
        $now = date('Y-m-d H:i:s');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT a.appointment_id AS id, 
                   CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                   a.patient_id, a.doctor_id,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   SUBSTR(a.appointment_start, 12, 5) AS start_time,
                   SUBSTR(a.appointment_end, 12, 5) AS end_time,
                   a.status, a.reason, a.created_at,
                   du.name AS doctor_name,
                   s.specialization_name AS specialization,
                   pu.name AS patient_name,
                   q.queue_number, 
                   COALESCE(q.queue_status, 'waiting') AS queue_status
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = a.doctor_id
            JOIN users du ON du.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            LEFT JOIN appointment_queues q ON q.appointment_id = a.appointment_id
            WHERE a.patient_id = :pid AND a.appointment_start >= :now AND a.status IN ('pending', 'confirmed')
            ORDER BY a.appointment_start ASC
        ");
        $stmt->execute([':pid' => $patientId, ':now' => $now]);
        $rows = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $rows]);
    }

    public function show(Request $req): void
    {
        $patientId = $this->getPatientId();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT a.appointment_id AS id, 
                   CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                   a.patient_id, a.doctor_id,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   SUBSTR(a.appointment_start, 12, 5) AS start_time,
                   SUBSTR(a.appointment_end, 12, 5) AS end_time,
                   a.status, a.reason, a.rejection_reason, a.cancellation_reason, a.created_at,
                   du.name AS doctor_name,
                   s.specialization_name AS specialization,
                   pu.name AS patient_name,
                   q.queue_number, 
                   COALESCE(q.queue_status, 'waiting') AS queue_status,
                   (
                       SELECT cur_q.queue_number 
                       FROM appointment_queues cur_q 
                       WHERE cur_q.doctor_id = a.doctor_id 
                         AND cur_q.queue_date = SUBSTR(a.appointment_start, 1, 10) 
                         AND cur_q.queue_status = 'serving'
                       LIMIT 1
                   ) AS current_serving_queue
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = a.doctor_id
            JOIN users du ON du.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            LEFT JOIN appointment_queues q ON q.appointment_id = a.appointment_id
            WHERE a.appointment_id = :id AND a.patient_id = :pid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':pid' => $patientId]);
        $appointment = $stmt->fetch();

        if (!$appointment) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        $appointment['current_serving_queue'] = (int) ($appointment['current_serving_queue'] ?: 0);
        $appointment['history'] = [
            ['at' => $appointment['created_at'], 'action' => 'Dibuat', 'by' => $appointment['patient_name'], 'note' => 'Pengajuan janji temu']
        ];
        if (!empty($appointment['cancellation_reason'])) {
            $appointment['history'][] = ['at' => $appointment['created_at'], 'action' => 'Dibatalkan', 'by' => 'Pasien', 'note' => $appointment['cancellation_reason']];
        }

        Response::json(['ok' => true, 'data' => $appointment]);
    }

    public function store(Request $req): void
    {
        $patientId = $this->getPatientId();
        $user = Auth::user();
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'doctor_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'time'],
            'reason' => ['required', 'min:10']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $doctorId = (int) $body['doctor_id'];
        $date = (string) $body['date'];
        $startTime = substr((string) $body['start_time'], 0, 5);
        $reason = Validator::sanitize((string) $body['reason']);

        if ($date < date('Y-m-d')) {
            Response::error(422, 'Tidak dapat membuat janji temu pada tanggal yang sudah lewat.');
        }

        $parts = explode(':', $startTime);
        $totalMin = ((int) $parts[0]) * 60 + ((int) $parts[1]) + 30;
        $endTime = sprintf('%02d:%02d', floor($totalMin / 60), $totalMin % 60);

        $startFull = "$date $startTime:00";
        $endFull = "$date $endTime:00";

        $pdo = Database::getConnection();

        // Check double booking collision
        $checkStmt = $pdo->prepare("
            SELECT appointment_id FROM appointments 
            WHERE doctor_id = :did AND appointment_start = :start AND status IN ('pending', 'confirmed')
            LIMIT 1
        ");
        $checkStmt->execute([':did' => $doctorId, ':start' => $startFull]);
        if ($checkStmt->fetch()) {
            Response::error(422, 'Slot pada jam tersebut sudah terisi. Silakan pilih slot lain.');
        }

        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $ins = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_start, appointment_end, status, reason, created_at, updated_at)
                VALUES (:pid, :did, :start, :end, 'pending', :reason, :now, :now)
            ");
            $ins->execute([
                ':pid' => $patientId,
                ':did' => $doctorId,
                ':start' => $startFull,
                ':end' => $endFull,
                ':reason' => $reason,
                ':now' => $now
            ]);
            $appointmentId = (int) $pdo->lastInsertId();

            // 🆕 Calculate Queue Number for that day
            $qStmt = $pdo->prepare("
                SELECT COALESCE(MAX(queue_number), 0) + 1 AS next_queue 
                FROM appointment_queues 
                WHERE doctor_id = :did AND queue_date = :date
            ");
            $qStmt->execute([':did' => $doctorId, ':date' => $date]);
            $queueNumber = (int) $qStmt->fetchColumn();

            $qIns = $pdo->prepare("
                INSERT INTO appointment_queues (appointment_id, doctor_id, queue_date, queue_number, queue_status)
                VALUES (:aid, :did, :date, :qno, 'waiting')
            ");
            $qIns->execute([
                ':aid' => $appointmentId,
                ':did' => $doctorId,
                ':date' => $date,
                ':qno' => $queueNumber
            ]);

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'CREATE_APPOINTMENT', 'appointment', $appointmentId, 'success', "Queue #$queueNumber");

            Response::json([
                'ok' => true,
                'id' => $appointmentId,
                'code' => 'APT-' . (1000 + $appointmentId),
                'queue_number' => $queueNumber,
                'message' => 'Permintaan janji temu berhasil dibuat. Menunggu konfirmasi dokter.'
            ], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function cancel(Request $req): void
    {
        $patientId = $this->getPatientId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'reason' => ['required', 'min:5']
        ])) {
            Response::error(422, 'Alasan pembatalan wajib diisi (minimal 5 karakter).', $val->getErrors());
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = :id AND patient_id = :pid LIMIT 1");
        $stmt->execute([':id' => $id, ':pid' => $patientId]);
        $appt = $stmt->fetch();

        if (!$appt) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        if (!in_array($appt['status'], ['pending', 'confirmed'])) {
            Response::error(422, 'Hanya janji temu berstatus menunggu atau diterima yang dapat dibatalkan.');
        }

        $now = date('Y-m-d H:i:s');
        $reason = Validator::sanitize((string) $body['reason']);

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("
                UPDATE appointments 
                SET status = 'cancelled', cancellation_reason = :reason, cancelled_by = :uid, cancelled_at = :now, updated_at = :now 
                WHERE appointment_id = :id
            ");
            $up->execute([':reason' => $reason, ':uid' => $user['user_id'], ':now' => $now, ':id' => $id]);

            $pdo->prepare("UPDATE appointment_queues SET queue_status = 'skipped' WHERE appointment_id = :id")
                ->execute([':id' => $id]);

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'CANCEL_APPOINTMENT', 'appointment', $id, 'success', $reason);

            Response::json(['ok' => true, 'message' => 'Janji temu berhasil dibatalkan.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function reschedule(Request $req): void
    {
        $patientId = $this->getPatientId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'date' => ['required', 'date'],
            'start_time' => ['required', 'time']
        ])) {
            Response::error(422, 'Tanggal dan jam baru wajib dipilih.', $val->getErrors());
        }

        $newDate = (string) $body['date'];
        $newStart = substr((string) $body['start_time'], 0, 5);
        $rescheduleReason = Validator::sanitize((string) ($body['reason'] ?? ''));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = :id AND patient_id = :pid LIMIT 1");
        $stmt->execute([':id' => $id, ':pid' => $patientId]);
        $old = $stmt->fetch();

        if (!$old) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        $parts = explode(':', $newStart);
        $totalMin = ((int) $parts[0]) * 60 + ((int) $parts[1]) + 30;
        $newEnd = sprintf('%02d:%02d', floor($totalMin / 60), $totalMin % 60);

        $newStartFull = "$newDate $newStart:00";
        $newEndFull = "$newDate $newEnd:00";

        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $cNote = "Dijadwalkan ulang ke $newDate $newStart" . ($rescheduleReason ? " — $rescheduleReason" : "");

            // Cancel old
            $upOld = $pdo->prepare("
                UPDATE appointments 
                SET status = 'cancelled', cancellation_reason = :note, cancelled_by = :uid, cancelled_at = :now, updated_at = :now 
                WHERE appointment_id = :id
            ");
            $upOld->execute([':note' => $cNote, ':uid' => $user['user_id'], ':now' => $now, ':id' => $id]);

            $pdo->prepare("UPDATE appointment_queues SET queue_status = 'skipped' WHERE appointment_id = :id")
                ->execute([':id' => $id]);

            // Create new
            $ins = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_start, appointment_end, status, reason, rescheduled_from_id, rescheduled_by, created_at, updated_at)
                VALUES (:pid, :did, :start, :end, 'pending', :reason, :from_id, :by_uid, :now, :now)
            ");
            $ins->execute([
                ':pid' => $patientId,
                ':did' => $old['doctor_id'],
                ':start' => $newStartFull,
                ':end' => $newEndFull,
                ':reason' => $old['reason'],
                ':from_id' => $old['appointment_id'],
                ':by_uid' => $user['user_id'],
                ':now' => $now
            ]);
            $newId = (int) $pdo->lastInsertId();

            // 🆕 New queue
            $qStmt = $pdo->prepare("
                SELECT COALESCE(MAX(queue_number), 0) + 1 FROM appointment_queues WHERE doctor_id = :did AND queue_date = :date
            ");
            $qStmt->execute([':did' => $old['doctor_id'], ':date' => $newDate]);
            $queueNumber = (int) $qStmt->fetchColumn();

            $pdo->prepare("
                INSERT INTO appointment_queues (appointment_id, doctor_id, queue_date, queue_number, queue_status)
                VALUES (:aid, :did, :date, :qno, 'waiting')
            ")->execute([':aid' => $newId, ':did' => $old['doctor_id'], ':date' => $newDate, ':qno' => $queueNumber]);

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'RESCHEDULE_APPOINTMENT', 'appointment', $newId, 'success');

            Response::json([
                'ok' => true,
                'id' => $newId,
                'code' => 'APT-' . (1000 + $newId),
                'queue_number' => $queueNumber,
                'message' => 'Janji temu berhasil dijadwalkan ulang.'
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function queueStatus(Request $req): void
    {
        $patientId = $this->getPatientId();
        $appointmentId = (int) ($req->query('appointment_id') ?: $req->param('id'));

        $pdo = Database::getConnection();

        $query = "
            SELECT a.appointment_id AS id,
                   CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   SUBSTR(a.appointment_start, 12, 5) AS start_time,
                   a.status AS appointment_status,
                   q.queue_number, q.queue_status,
                   d.doctor_id, du.name AS doctor_name, s.specialization_name AS specialization,
                   (
                       SELECT cur_q.queue_number 
                       FROM appointment_queues cur_q 
                       WHERE cur_q.doctor_id = a.doctor_id 
                         AND cur_q.queue_date = SUBSTR(a.appointment_start, 1, 10) 
                         AND cur_q.queue_status = 'serving'
                       LIMIT 1
                   ) AS current_serving_queue
            FROM appointments a
            JOIN doctors d ON d.doctor_id = a.doctor_id
            JOIN users du ON du.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            LEFT JOIN appointment_queues q ON q.appointment_id = a.appointment_id
            WHERE a.patient_id = :pid
        ";

        if ($appointmentId > 0) {
            $query .= " AND a.appointment_id = :aid";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':pid' => $patientId, ':aid' => $appointmentId]);
            $row = $stmt->fetch();
        } else {
            $query .= " AND SUBSTR(a.appointment_start, 1, 10) = :today AND a.status IN ('pending', 'confirmed') ORDER BY a.appointment_start ASC LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':pid' => $patientId, ':today' => date('Y-m-d')]);
            $row = $stmt->fetch();
        }

        if (!$row) {
            Response::json([
                'ok' => true,
                'has_active_queue' => false,
                'message' => 'Tidak ada antrian aktif untuk hari ini.'
            ]);
        }

        $myQueue = (int) ($row['queue_number'] ?? 1);
        $currentServing = (int) ($row['current_serving_queue'] ?: 0);
        $ahead = max(0, $myQueue - $currentServing);

        Response::json([
            'ok' => true,
            'has_active_queue' => true,
            'data' => [
                'appointment_id' => $row['id'],
                'code' => $row['code'],
                'doctor_name' => $row['doctor_name'],
                'specialization' => $row['specialization'],
                'date' => $row['date'],
                'start_time' => $row['start_time'],
                'my_queue_number' => $myQueue,
                'current_serving_queue' => $currentServing,
                'people_ahead' => $ahead,
                'queue_status' => $row['queue_status'] ?? 'waiting',
                'estimated_wait_minutes' => $ahead * 15
            ]
        ]);
    }
}
