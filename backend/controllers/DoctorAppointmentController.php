<?php
/**
 * Doctor Appointments, Availability & Exceptions Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;
use PDO;

class DoctorAppointmentController
{
    private static array $dayMap = [
        0 => 'sun',
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat'
    ];

    private static array $revDayMap = [
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6
    ];

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

    public function index(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $pdo = Database::getConnection();

        $query = "
            SELECT a.appointment_id AS id,
                   CONCAT('APT-', (1000 + a.appointment_id)) AS code,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   SUBSTR(a.appointment_start, 12, 5) AS start_time,
                   SUBSTR(a.appointment_end, 12, 5) AS end_time,
                   a.status, a.reason, a.rejection_reason, a.cancellation_reason, a.created_at,
                   pu.name AS patient_name,
                   p.phone AS patient_phone,
                   pu.email AS patient_email,
                   q.queue_number,
                   COALESCE(q.queue_status, 'waiting') AS queue_status
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            LEFT JOIN appointment_queues q ON q.appointment_id = a.appointment_id
            WHERE a.doctor_id = :did
        ";

        $params = [':did' => $doctorId];

        $date = $req->query('date');
        if (!empty($date)) {
            $query .= " AND SUBSTR(a.appointment_start, 1, 10) = :date";
            $params[':date'] = $date;
        }

        $status = $req->query('status');
        if (!empty($status)) {
            $query .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        $query .= " ORDER BY a.appointment_start DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $appointments]);
    }

    public function accept(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = :id AND doctor_id = :did LIMIT 1");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $appt = $stmt->fetch();

        if (!$appt) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE appointments SET status = 'confirmed', updated_at = :now WHERE appointment_id = :id")
            ->execute([':now' => $now, ':id' => $id]);

        AuditLogger::log($user['user_id'], 'CONFIRM_APPOINTMENT', 'appointment', $id, 'success');

        Response::json(['ok' => true, 'message' => 'Janji temu berhasil diterima.']);
    }

    public function reject(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'reason' => ['required', 'min:5']
        ])) {
            Response::error(422, 'Alasan penolakan wajib diisi (minimal 5 karakter).', $val->getErrors());
        }

        $reason = Validator::sanitize((string) $body['reason']);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = :id AND doctor_id = :did LIMIT 1");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $appt = $stmt->fetch();

        if (!$appt) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("
                UPDATE appointments 
                SET status = 'rejected', rejection_reason = :reason, rejected_by = :uid, rejected_at = :now, updated_at = :now 
                WHERE appointment_id = :id
            ");
            $up->execute([':reason' => $reason, ':uid' => $user['user_id'], ':now' => $now, ':id' => $id]);

            $pdo->prepare("UPDATE appointment_queues SET queue_status = 'skipped' WHERE appointment_id = :id")
                ->execute([':id' => $id]);

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'REJECT_APPOINTMENT', 'appointment', $id, 'success', $reason);

            Response::json(['ok' => true, 'message' => 'Janji temu ditolak.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function complete(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = :id AND doctor_id = :did LIMIT 1");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $appt = $stmt->fetch();

        if (!$appt) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE appointments SET status = 'completed', updated_at = :now WHERE appointment_id = :id")
                ->execute([':now' => $now, ':id' => $id]);

            $pdo->prepare("UPDATE appointment_queues SET queue_status = 'completed', completed_at = :now WHERE appointment_id = :id")
                ->execute([':now' => $now, ':id' => $id]);

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'COMPLETE_APPOINTMENT', 'appointment', $id, 'success');

            Response::json(['ok' => true, 'message' => 'Janji temu ditandai selesai.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function schedule(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = :did ORDER BY start_time ASC");
        $stmt->execute([':did' => $doctorId]);
        $avail = $stmt->fetchAll();

        $formatted = array_map(function ($s) {
            return [
                'id' => $s['availability_id'],
                'doctor_id' => $s['doctor_id'],
                'day' => self::$revDayMap[$s['day_of_week']] ?? 1,
                'start' => substr($s['start_time'], 0, 5),
                'end' => substr($s['end_time'], 0, 5),
                'active' => (bool) $s['is_active']
            ];
        }, $avail);

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        Response::json([
            'ok' => true,
            'data' => $formatted,
            'days' => $days
        ]);
    }

    public function updateSchedule(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $body = $req->body();

        $slots = $body['slots'] ?? null;
        if (!is_array($slots)) {
            Response::error(422, 'Format jadwal tidak valid.');
        }

        foreach ($slots as $s) {
            if (!empty($s['active'])) {
                if (empty($s['start']) || empty($s['end']) || $s['end'] <= $s['start']) {
                    $dayName = (int) ($s['day'] ?? 0) + 1;
                    Response::error(422, "Jam selesai harus lebih besar dari jam mulai (hari ke-$dayName).");
                }
            }
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM doctor_availability WHERE doctor_id = :did")->execute([':did' => $doctorId]);

            $ins = $pdo->prepare("
                INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_active)
                VALUES (:did, :dow, :start, :end, :act)
            ");

            foreach ($slots as $s) {
                $dowCode = self::$dayMap[(int) ($s['day'] ?? 1)] ?? 'mon';
                $ins->execute([
                    ':did' => $doctorId,
                    ':dow' => $dowCode,
                    ':start' => substr((string) $s['start'], 0, 5) . ':00',
                    ':end' => substr((string) $s['end'], 0, 5) . ':00',
                    ':act' => !empty($s['active']) ? 1 : 0
                ]);
            }

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'UPDATE_AVAILABILITY', 'doctor', $doctorId, 'success');

            Response::json(['ok' => true, 'message' => 'Jadwal praktik berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function leaves(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT exception_id AS id, doctor_id, exception_date AS date, exception_type AS type,
                   start_time AS start, end_time AS end, reason AS note
            FROM doctor_schedule_exceptions
            WHERE doctor_id = :did
            ORDER BY exception_date DESC
        ");
        $stmt->execute([':did' => $doctorId]);
        $leaves = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $leaves]);
    }

    public function storeLeave(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'date' => ['required', 'date'],
            'type' => ['required', 'in:cuti,jam_khusus,leave,custom_hours']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $type = match ($body['type']) {
            'cuti', 'leave' => 'leave',
            'jam_khusus', 'custom_hours' => 'custom_hours',
            default => 'leave'
        };

        $start = null;
        $end = null;
        if ($type === 'custom_hours') {
            if (empty($body['start']) || empty($body['end']) || $body['end'] <= $body['start']) {
                Response::error(422, 'Jam selesai harus lebih besar dari jam mulai untuk jam khusus.');
            }
            $start = substr((string) $body['start'], 0, 5) . ':00';
            $end = substr((string) $body['end'], 0, 5) . ':00';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO doctor_schedule_exceptions (doctor_id, exception_date, exception_type, start_time, end_time, reason, created_by, created_at, updated_at)
            VALUES (:did, :date, :type, :start, :end, :reason, :uid, :now, :now)
        ");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':did' => $doctorId,
            ':date' => $body['date'],
            ':type' => $type,
            ':start' => $start,
            ':end' => $end,
            ':reason' => Validator::sanitize($body['note'] ?? ''),
            ':uid' => $user['user_id'],
            ':now' => $now
        ]);
        $id = (int) $pdo->lastInsertId();

        AuditLogger::log($user['user_id'], 'CREATE_SCHEDULE_EXCEPTION', 'doctor_schedule_exceptions', $id, 'success');

        Response::json(['ok' => true, 'id' => $id, 'message' => 'Pengajuan cuti/jam khusus berhasil disimpan.'], 201);
    }

    public function deleteLeave(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');

        $pdo = Database::getConnection();
        $pdo->prepare("DELETE FROM doctor_schedule_exceptions WHERE exception_id = :id AND doctor_id = :did")
            ->execute([':id' => $id, ':did' => $doctorId]);

        AuditLogger::log($user['user_id'], 'DELETE_SCHEDULE_EXCEPTION', 'doctor_schedule_exceptions', $id, 'success');

        Response::json(['ok' => true, 'message' => 'Data cuti berhasil dihapus.']);
    }
}
