<?php
/**
 * Admin Doctor Management Controller (medical_platform)
 * COMP6695 Secure Programming
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;

class AdminDoctorController
{
    public function index(Request $req): void
    {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT d.doctor_id AS id, d.user_id, u.name, u.email,
                   d.license_number AS license, d.years_of_experience AS experience_years, 
                   d.consultation_fee AS fee, d.verification_status AS verification,
                   d.rejection_reason, u.account_status AS user_status,
                   s.specialization_id, s.specialization_name AS specialization
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            ORDER BY d.doctor_id DESC
        ");
        $doctors = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $doctors]);
    }

    public function pending(Request $req): void
    {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT d.doctor_id AS id, d.user_id, u.name, u.email,
                   d.license_number AS license, d.years_of_experience AS experience_years, 
                   d.consultation_fee AS fee, d.verification_status AS verification,
                   s.specialization_id, s.specialization_name AS specialization
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            WHERE d.verification_status = 'pending'
            ORDER BY d.doctor_id ASC
        ");
        $doctors = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $doctors]);
    }

    public function verify(Request $req): void
    {
        $admin = Auth::requireRole('admin');
        $id = (int) $req->param('id');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'decision' => ['required', 'in:verified,rejected']
        ])) {
            Response::error(422, 'Keputusan verifikasi tidak valid.', $val->getErrors());
        }

        $decision = $body['decision'];
        $note = Validator::sanitize($body['note'] ?? '');

        if ($decision === 'rejected' && strlen(trim($note)) < 5) {
            Response::error(422, 'Alasan penolakan wajib diisi (minimal 5 karakter).');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            Response::error(404, 'Dokter tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $up = $pdo->prepare("
            UPDATE doctors 
            SET verification_status = :dec, verified_by = :vby, verified_at = :vat, rejection_reason = :note
            WHERE doctor_id = :id
        ");
        $up->execute([
            ':dec' => $decision,
            ':vby' => $admin['user_id'],
            ':vat' => $now,
            ':note' => ($decision === 'rejected' ? $note : null),
            ':id' => $id
        ]);

        $action = $decision === 'verified' ? 'VERIFY_DOCTOR' : 'REJECT_DOCTOR';
        AuditLogger::log($admin['user_id'], $action, 'doctor', $id, 'success', $note);

        Response::json([
            'ok' => true,
            'message' => $decision === 'verified' ? 'Dokter berhasil diverifikasi.' : 'Pengajuan dokter ditolak.',
            'data' => array_merge($doctor, ['verification' => $decision, 'rejection_reason' => $note])
        ]);
    }

    public function store(Request $req): void
    {
        $admin = Auth::requireRole('admin');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'name' => ['required', 'min:3', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'specialization_id' => ['required', 'integer'],
            'license' => ['required', 'min:5'],
            'fee' => ['required', 'numeric'],
            'experience_years' => ['required', 'integer']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $email = strtolower(trim((string) $body['email']));
        $pdo = Database::getConnection();

        $check = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email LIMIT 1");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            Response::error(422, 'Email dokter ini sudah terdaftar.');
        }

        $pdo->beginTransaction();
        try {
            $hashed = Auth::hashPassword((string) $body['password']);
            $now = date('Y-m-d H:i:s');

            $uStmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, account_status, created_at, updated_at)
                VALUES (:name, :email, :password, 'doctor', 'active', :now, :now)
            ");
            $uStmt->execute([
                ':name' => Validator::sanitize($body['name']),
                ':email' => $email,
                ':password' => $hashed,
                ':now' => $now
            ]);
            $userId = (int) $pdo->lastInsertId();

            $ver = !empty($body['verification']) ? $body['verification'] : 'verified';
            $dStmt = $pdo->prepare("
                INSERT INTO doctors (user_id, license_number, years_of_experience, consultation_fee, verification_status, verified_by, verified_at)
                VALUES (:uid, :lic, :exp, :fee, :ver, :vby, :vat)
            ");
            $dStmt->execute([
                ':uid' => $userId,
                ':lic' => Validator::sanitize($body['license']),
                ':exp' => (int) $body['experience_years'],
                ':fee' => (float) $body['fee'],
                ':ver' => $ver,
                ':vby' => ($ver === 'verified' ? $admin['user_id'] : null),
                ':vat' => ($ver === 'verified' ? $now : null)
            ]);
            $doctorId = (int) $pdo->lastInsertId();

            // Specialization
            $pdo->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (:did, :sid)")
                ->execute([':did' => $doctorId, ':sid' => (int) $body['specialization_id']]);

            $pdo->commit();

            AuditLogger::log($admin['user_id'], 'CREATE_DOCTOR', 'doctor', $doctorId, 'success', 'Created by admin');

            Response::json([
                'ok' => true,
                'id' => $doctorId,
                'message' => 'Akun dokter berhasil dibuat.'
            ], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(Request $req): void
    {
        $admin = Auth::requireRole('admin');
        $id = (int) $req->param('id');
        $body = $req->body();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            Response::error(404, 'Dokter tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $license = isset($body['license']) ? Validator::sanitize($body['license']) : $doctor['license_number'];
        $specId = isset($body['specialization_id']) ? (int) $body['specialization_id'] : null;
        $exp = isset($body['experience_years']) ? (int) $body['experience_years'] : $doctor['years_of_experience'];
        $fee = isset($body['fee']) ? (float) $body['fee'] : $doctor['consultation_fee'];
        $ver = isset($body['verification']) ? $body['verification'] : $doctor['verification_status'];

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("
                UPDATE doctors 
                SET license_number = :lic, years_of_experience = :exp, consultation_fee = :fee, verification_status = :ver
                WHERE doctor_id = :id
            ");
            $up->execute([
                ':lic' => $license,
                ':exp' => $exp,
                ':fee' => $fee,
                ':ver' => $ver,
                ':id' => $id
            ]);

            if (isset($body['name']) && $doctor['user_id']) {
                $name = Validator::sanitize($body['name']);
                $pdo->prepare("UPDATE users SET name = :name, updated_at = :now WHERE user_id = :uid")
                    ->execute([':name' => $name, ':now' => $now, ':uid' => $doctor['user_id']]);
            }

            if ($specId) {
                $pdo->prepare("DELETE FROM doctor_specializations WHERE doctor_id = :did")->execute([':did' => $id]);
                $pdo->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (:did, :sid)")
                    ->execute([':did' => $id, ':sid' => $specId]);
            }

            $pdo->commit();

            AuditLogger::log($admin['user_id'], 'UPDATE_DOCTOR', 'doctor', $id, 'success');

            Response::json(['ok' => true, 'message' => 'Data dokter berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function destroy(Request $req): void
    {
        $admin = Auth::requireRole('admin');
        $id = (int) $req->param('id');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            Response::error(404, 'Dokter tidak ditemukan.');
        }

        $pdo->beginTransaction();
        try {
            $today = date('Y-m-d');
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM appointments 
                WHERE doctor_id = :did AND status IN ('pending', 'confirmed') AND appointment_start >= :today
            ");
            $chk->execute([':did' => $id, ':today' => "$today 00:00:00"]);
            $hasActive = (int) $chk->fetchColumn();

            if ($hasActive > 0) {
                // Soft deactivation
                $pdo->prepare("UPDATE doctors SET verification_status = 'rejected' WHERE doctor_id = :id")
                    ->execute([':id' => $id]);
                if ($doctor['user_id']) {
                    $pdo->prepare("UPDATE users SET account_status = 'inactive' WHERE user_id = :uid")
                        ->execute([':uid' => $doctor['user_id']]);
                }
                $msg = 'Dokter memiliki janji temu aktif, status akun dinonaktifkan (soft delete).';
            } else {
                if ($doctor['user_id']) {
                    $pdo->prepare("DELETE FROM users WHERE user_id = :uid")->execute([':uid' => $doctor['user_id']]);
                }
                $pdo->prepare("DELETE FROM doctors WHERE doctor_id = :id")->execute([':id' => $id]);
                $msg = 'Akun dokter berhasil dihapus.';
            }

            $pdo->commit();

            AuditLogger::log($admin['user_id'], 'DELETE_DOCTOR', 'doctor', $id, 'success');

            Response::json(['ok' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
