<?php
/**
 * Patient Medical Records & Prescriptions Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use PDO;

class PatientRecordController
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
        $user = Auth::user();
        $pdo = Database::getConnection();

        // IDOR Protection: Patient can only view their own finalized records
        $stmt = $pdo->prepare("
            SELECT r.record_id AS id, r.appointment_id, r.diagnosis, r.notes, 
                   r.record_status AS status, r.finalized_at, r.created_at,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   du.name AS doctor_name,
                   s.specialization_name AS specialization,
                   pu.name AS patient_name
            FROM medical_records r
            JOIN appointments a ON a.appointment_id = r.appointment_id
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = r.created_by
            JOIN users du ON du.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            WHERE a.patient_id = :pid AND r.record_status = 'finalized'
            ORDER BY a.appointment_start DESC
        ");
        $stmt->execute([':pid' => $patientId]);
        $records = $stmt->fetchAll();

        // Attach prescription items
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

        AuditLogger::log($user['user_id'], 'VIEW_RECORDS_LIST', 'medical_record', null, 'success');

        Response::json(['ok' => true, 'data' => $records]);
    }

    public function show(Request $req): void
    {
        $patientId = $this->getPatientId();
        $user = Auth::user();
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT r.record_id AS id, r.appointment_id, r.diagnosis, r.notes, 
                   r.record_status AS status, r.finalized_at, r.created_at,
                   SUBSTR(a.appointment_start, 1, 10) AS date,
                   du.name AS doctor_name,
                   s.specialization_name AS specialization,
                   pu.name AS patient_name
            FROM medical_records r
            JOIN appointments a ON a.appointment_id = r.appointment_id
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN users pu ON pu.user_id = p.user_id
            JOIN doctors d ON d.doctor_id = r.created_by
            JOIN users du ON du.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            WHERE r.record_id = :id AND a.patient_id = :pid AND r.record_status = 'finalized'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':pid' => $patientId]);
        $record = $stmt->fetch();

        if (!$record) {
            Response::error(404, 'Rekam medis tidak ditemukan atau belum difinalisasi oleh dokter.');
        }

        $pStmt = $pdo->prepare("
            SELECT pi.medicine_name AS name, pi.dosage AS dose, pi.frequency, 
                   pi.duration, pi.quantity AS qty, pi.instructions
            FROM prescriptions p
            JOIN prescription_items pi ON pi.prescription_id = p.prescription_id
            WHERE p.record_id = :rid
        ");
        $pStmt->execute([':rid' => $id]);
        $record['prescriptions'] = $pStmt->fetchAll();

        // Audit log access to medical record
        AuditLogger::log($user['user_id'], 'VIEW_RECORD', 'medical_record', $id, 'success');

        Response::json(['ok' => true, 'data' => $record]);
    }
}
