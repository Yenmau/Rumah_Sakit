<?php
/**
 * Doctor Profile Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;

class DoctorProfileController
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

    public function show(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT d.doctor_id AS id, d.user_id, u.name, u.email,
                   d.license_number AS license, d.years_of_experience AS experience_years, 
                   d.consultation_fee AS fee, d.verification_status AS verification,
                   d.rejection_reason,
                   s.specialization_id, s.specialization_name AS specialization
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            WHERE d.doctor_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $doctorId]);
        $doctor = $stmt->fetch();

        $verificationNote = match ($doctor['verification']) {
            'verified' => 'Terverifikasi oleh administrator.',
            'pending' => 'Menunggu peninjauan berkas izin praktik (SIP).',
            'rejected' => $doctor['rejection_reason'] ?: 'Pengajuan ditolak oleh admin.',
            default => '-'
        };

        Response::json([
            'ok' => true,
            'data' => $doctor,
            'verification' => [
                'status' => $doctor['verification'],
                'note' => $verificationNote
            ]
        ]);
    }

    public function update(Request $req): void
    {
        $doctorId = $this->getDoctorId();
        $user = Auth::user();
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'specialization_id' => ['integer'],
            'fee' => ['numeric'],
            'experience_years' => ['integer']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = :id LIMIT 1");
        $stmt->execute([':id' => $doctorId]);
        $current = $stmt->fetch();

        $fee = isset($body['fee']) ? (float) $body['fee'] : (float) $current['consultation_fee'];
        $exp = isset($body['experience_years']) ? (int) $body['experience_years'] : (int) $current['years_of_experience'];

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("
                UPDATE doctors 
                SET consultation_fee = :fee, years_of_experience = :exp
                WHERE doctor_id = :id
            ");
            $up->execute([':fee' => $fee, ':exp' => $exp, ':id' => $doctorId]);

            if (!empty($body['specialization_id'])) {
                $pdo->prepare("DELETE FROM doctor_specializations WHERE doctor_id = :did")->execute([':did' => $doctorId]);
                $pdo->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (:did, :sid)")
                    ->execute([':did' => $doctorId, ':sid' => (int) $body['specialization_id']]);
            }

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'UPDATE_PROFILE', 'doctor', $doctorId, 'success');

            Response::json(['ok' => true, 'message' => 'Profil dokter berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
