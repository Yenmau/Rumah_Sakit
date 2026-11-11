<?php
/**
 * Doctor Medical Records & Prescriptions Controller (medical_platform)
 * COMP6695 Secure Programming
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;
use PDO;

class DoctorRecordController
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

    public function index(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT r.record_id AS id, r.appointment_id, r.diagnosis, r.notes, 
                   r.record_status AS status, r.finalized_at, r.created_at,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   CONCAT('APT-', (1000 + a.appointment_id)) AS appointment_code,
                   pu.name AS patient_name,
                   du.name AS doctor_name
            FROM medical_records r
            JOIN appointments a ON a.appointment_id = r.appointment_id
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = r.created_by
            JOIN users du ON du.user_id = d.user_id
            WHERE r.created_by = :did
            ORDER BY a.appointment_start DESC
        ");
        $stmt->execute([':did' => $doctorId]);
        $records = $stmt->fetchAll();

        foreach ($records as &$rec) {
            $pStmt = $pdo->prepare("
                SELECT pi.medicine_name AS name, pi.dosage AS dose, pi.frequency, 
                       pi.duration, pi.quantity AS qty, pi.instructions
                FROM prescriptions p
                JOIN prescription_items pi ON pi.prescription_id = p.prescription_id
                WHERE p.record_id = :rid
            ");
            $pStmt->execute([':rid' => $rec['id']]);
            $rec['prescriptions'] = $pStmt->fetchAll();
        }

        AuditLogger::log($user['user_id'], 'VIEW_DOCTOR_RECORDS', 'medical_record', null, 'success');

        Response::json(['ok' => true, 'data' => $records]);
    }

    public function show(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT r.record_id AS id, r.appointment_id, r.diagnosis, r.notes, 
                   r.record_status AS status, r.finalized_at, r.created_at,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   CONCAT('APT-', (1000 + a.appointment_id)) AS appointment_code,
                   pu.name AS patient_name,
                   du.name AS doctor_name
            FROM medical_records r
            JOIN appointments a ON a.appointment_id = r.appointment_id
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = r.created_by
            JOIN users du ON du.user_id = d.user_id
            WHERE r.record_id = :id AND r.created_by = :did
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $record = $stmt->fetch();

        if (!$record) {
            Response::error(404, 'Rekam medis tidak ditemukan.');
        }

        // Fetch prescriptions
        $pStmt = $pdo->prepare("
            SELECT pi.medicine_name AS name, pi.dosage AS dose, pi.frequency, 
                   pi.duration, pi.quantity AS qty, pi.instructions
            FROM prescriptions p
            JOIN prescription_items pi ON pi.prescription_id = p.prescription_id
            WHERE p.record_id = :rid
        ");
        $pStmt->execute([':rid' => $id]);
        $record['prescriptions'] = $pStmt->fetchAll();

        // Fetch revisions
        $rStmt = $pdo->prepare("
            SELECT rev.revision_id, rev.revised_at AS at, du.name AS by, 
                   rev.revision_reason AS reason, rev.previous_diagnosis AS before,
                   r.diagnosis AS after
            FROM medical_record_revisions rev
            JOIN doctors d ON d.doctor_id = rev.revised_by
            JOIN users du ON du.user_id = d.user_id
            JOIN medical_records r ON r.record_id = rev.record_id
            WHERE rev.record_id = :rid
            ORDER BY rev.revised_at ASC
        ");
        $rStmt->execute([':rid' => $id]);
        $record['revisions'] = $rStmt->fetchAll();

        AuditLogger::log($user['user_id'], 'VIEW_RECORD', 'medical_record', $id, 'success');

        Response::json(['ok' => true, 'data' => $record]);
    }

    public function store(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'diagnosis' => ['required', 'min:3']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $appointmentId = !empty($body['appointment_id']) ? (int) $body['appointment_id'] : null;
        $pdo = Database::getConnection();

        // Verify appointment exists
        if (!$appointmentId) {
            Response::error(422, 'Appointment ID wajib dipilih.');
        }

        $aStmt = $pdo->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = :aid AND doctor_id = :did LIMIT 1");
        $aStmt->execute([':aid' => $appointmentId, ':did' => $doctorId]);
        if (!$aStmt->fetch()) {
            Response::error(404, 'Janji temu tidak ditemukan.');
        }

        // Check if record already exists for this appointment
        $check = $pdo->prepare("SELECT record_id FROM medical_records WHERE appointment_id = :aid LIMIT 1");
        $check->execute([':aid' => $appointmentId]);
        if ($check->fetch()) {
            Response::error(409, 'Rekam medis untuk janji temu ini sudah pernah dibuat.');
        }

        $diagnosis = Validator::sanitize($body['diagnosis']);
        $notes = Validator::sanitize($body['notes'] ?? '');
        $finalize = !empty($body['finalize']);
        $recordStatus = $finalize ? 'finalized' : 'draft';
        $finalizedAt = $finalize ? date('Y-m-d H:i:s') : null;
        $now = date('Y-m-d H:i:s');

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("
                INSERT INTO medical_records (appointment_id, diagnosis, notes, record_status, finalized_at, created_by, updated_by, created_at, updated_at)
                VALUES (:aid, :diag, :notes, :st, :fin, :cb, :ub, :now, :now)
            ");
            $ins->execute([
                ':aid' => $appointmentId,
                ':diag' => $diagnosis,
                ':notes' => $notes,
                ':st' => $recordStatus,
                ':fin' => $finalizedAt,
                ':cb' => $doctorId,
                ':ub' => $doctorId,
                ':now' => $now
            ]);
            $recordId = (int) $pdo->lastInsertId();

            // Insert prescription if items provided
            $prescriptions = $body['prescriptions'] ?? [];
            if (is_array($prescriptions) && !empty($prescriptions)) {
                $prIns = $pdo->prepare("INSERT INTO prescriptions (record_id, prescription_date) VALUES (:rid, :date)");
                $prIns->execute([':rid' => $recordId, ':date' => date('Y-m-d')]);
                $prescriptionId = (int) $pdo->lastInsertId();

                $piIns = $pdo->prepare("
                    INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, quantity, instructions)
                    VALUES (:pid, :name, :dose, :freq, :dur, :qty, :inst)
                ");
                foreach ($prescriptions as $p) {
                    if (!empty($p['name'])) {
                        $piIns->execute([
                            ':pid' => $prescriptionId,
                            ':name' => Validator::sanitize($p['name']),
                            ':dose' => Validator::sanitize($p['dose'] ?? '-'),
                            ':freq' => Validator::sanitize($p['frequency'] ?? '-'),
                            ':dur' => Validator::sanitize($p['duration'] ?? '-'),
                            ':qty' => (int) ($p['qty'] ?? 1),
                            ':inst' => Validator::sanitize($p['instructions'] ?? '')
                        ]);
                    }
                }
            }

            // If finalized, mark appointment and queue completed
            if ($finalize) {
                $pdo->prepare("UPDATE appointments SET status = 'completed', updated_at = :now WHERE appointment_id = :aid")
                    ->execute([':now' => $now, ':aid' => $appointmentId]);
                $pdo->prepare("UPDATE appointment_queues SET queue_status = 'completed', completed_at = :now WHERE appointment_id = :aid")
                    ->execute([':now' => $now, ':aid' => $appointmentId]);
            }

            $pdo->commit();

            AuditLogger::log(
                $user['user_id'],
                $finalize ? 'FINALIZE_RECORD' : 'DRAFT_RECORD',
                'medical_record',
                $recordId,
                'success'
            );

            Response::json([
                'ok' => true,
                'id' => $recordId,
                'status' => $recordStatus,
                'message' => $finalize ? 'Rekam medis berhasil difinalisasi.' : 'Draf rekam medis disimpan.'
            ], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $body = $req->body();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE record_id = :id AND created_by = :did LIMIT 1");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $record = $stmt->fetch();

        if (!$record) {
            Response::error(404, 'Rekam medis tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $isFinalized = ($record['record_status'] === 'finalized');

        // Business rule from ERD:
        // "Record yang sudah finalized tidak boleh di-UPDATE begitu saja. Simpan state lama ke medical_record_revisions dulu dalam satu transaksi."
        if ($isFinalized) {
            if (empty($body['reason']) || strlen(trim($body['reason'])) < 5) {
                Response::error(422, 'Alasan revisi wajib diisi untuk rekam medis yang sudah final (minimal 5 karakter).');
            }
        }

        $diagnosis = isset($body['diagnosis']) ? Validator::sanitize($body['diagnosis']) : $record['diagnosis'];
        $notes = isset($body['notes']) ? Validator::sanitize($body['notes']) : $record['notes'];

        $pdo->beginTransaction();
        try {
            if ($isFinalized) {
                // Save previous state to medical_record_revisions
                $revStmt = $pdo->prepare("
                    INSERT INTO medical_record_revisions (record_id, revised_by, previous_diagnosis, previous_notes, previous_record_status, revision_reason, revised_at)
                    VALUES (:rid, :did, :pdiag, :pnotes, :pst, :reason, :now)
                ");
                $revStmt->execute([
                    ':rid' => $id,
                    ':did' => $doctorId,
                    ':pdiag' => $record['diagnosis'],
                    ':pnotes' => $record['notes'],
                    ':pst' => $record['record_status'],
                    ':reason' => Validator::sanitize($body['reason']),
                    ':now' => $now
                ]);
            }

            $recordStatus = !empty($body['finalize']) ? 'finalized' : $record['record_status'];
            $finalizedAt = ($recordStatus === 'finalized' && empty($record['finalized_at'])) ? $now : $record['finalized_at'];

            $up = $pdo->prepare("
                UPDATE medical_records 
                SET diagnosis = :diag, notes = :notes, record_status = :st, finalized_at = :fin, updated_by = :ub, updated_at = :now
                WHERE record_id = :id
            ");
            $up->execute([
                ':diag' => $diagnosis,
                ':notes' => $notes,
                ':st' => $recordStatus,
                ':fin' => $finalizedAt,
                ':ub' => $doctorId,
                ':now' => $now,
                ':id' => $id
            ]);

            // Update prescription items if provided
            if (isset($body['prescriptions']) && is_array($body['prescriptions'])) {
                // Delete existing prescriptions & items
                $prescIds = $pdo->prepare("SELECT prescription_id FROM prescriptions WHERE record_id = :rid");
                $prescIds->execute([':rid' => $id]);
                foreach ($prescIds->fetchAll() as $row) {
                    $pdo->prepare("DELETE FROM prescription_items WHERE prescription_id = :pid")->execute([':pid' => $row['prescription_id']]);
                }
                $pdo->prepare("DELETE FROM prescriptions WHERE record_id = :rid")->execute([':rid' => $id]);

                // Insert renewed prescription
                $pdo->prepare("INSERT INTO prescriptions (record_id, prescription_date) VALUES (:rid, :date)")
                    ->execute([':rid' => $id, ':date' => date('Y-m-d')]);
                $newPrescId = (int) $pdo->lastInsertId();

                $piIns = $pdo->prepare("
                    INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, quantity, instructions)
                    VALUES (:pid, :name, :dose, :freq, :dur, :qty, :inst)
                ");
                foreach ($body['prescriptions'] as $p) {
                    if (!empty($p['name'])) {
                        $piIns->execute([
                            ':pid' => $newPrescId,
                            ':name' => Validator::sanitize($p['name']),
                            ':dose' => Validator::sanitize($p['dose'] ?? '-'),
                            ':freq' => Validator::sanitize($p['frequency'] ?? '-'),
                            ':dur' => Validator::sanitize($p['duration'] ?? '-'),
                            ':qty' => (int) ($p['qty'] ?? 1),
                            ':inst' => Validator::sanitize($p['instructions'] ?? '')
                        ]);
                    }
                }
            }

            $pdo->commit();

            AuditLogger::log(
                $user['user_id'],
                $isFinalized ? 'REVISE_RECORD' : 'UPDATE_RECORD',
                'medical_record',
                $id,
                'success'
            );

            Response::json(['ok' => true, 'message' => 'Rekam medis berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function finalize(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE record_id = :id AND created_by = :did LIMIT 1");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $record = $stmt->fetch();

        if (!$record) {
            Response::error(404, 'Rekam medis tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $pdo->prepare("
            UPDATE medical_records 
            SET record_status = 'finalized', finalized_at = :now, updated_by = :ub, updated_at = :now 
            WHERE record_id = :id
        ")->execute([':now' => $now, ':ub' => $doctorId, ':id' => $id]);

        // Complete appointment & queue
        $pdo->prepare("UPDATE appointments SET status = 'completed', updated_at = :now WHERE appointment_id = :aid")
            ->execute([':now' => $now, ':aid' => $record['appointment_id']]);
        $pdo->prepare("UPDATE appointment_queues SET queue_status = 'completed', completed_at = :now WHERE appointment_id = :aid")
            ->execute([':now' => $now, ':aid' => $record['appointment_id']]);

        AuditLogger::log($user['user_id'], 'FINALIZE_RECORD', 'medical_record', $id, 'success');

        Response::json(['ok' => true, 'message' => 'Rekam medis berhasil difinalisasi.']);
    }

    public function revisions(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE record_id = :id AND created_by = :did LIMIT 1");
        $stmt->execute([':id' => $id, ':did' => $doctorId]);
        $record = $stmt->fetch();

        $rStmt = $pdo->prepare("
            SELECT rev.revised_at AS at, du.name AS by, rev.revision_reason AS reason,
                   rev.previous_diagnosis AS before, r.diagnosis AS after
            FROM medical_record_revisions rev
            JOIN doctors d ON d.doctor_id = rev.revised_by
            JOIN users du ON du.user_id = d.user_id
            JOIN medical_records r ON r.record_id = rev.record_id
            WHERE rev.record_id = :rid
            ORDER BY rev.revised_at DESC
        ");
        $rStmt->execute([':rid' => $id]);
        $revisions = $rStmt->fetchAll();

        Response::json([
            'ok' => true,
            'data' => $revisions,
            'record' => $record ?: null
        ]);
    }
}
