<?php
/**
 * Authentication Controller (COMP6695 Secure Programming)
 * Target Schema: medical_platform (MySQL 8 / SQLite)
 */

namespace Backend\Controllers;

use Backend\Core\Auth;
use Backend\Core\AuditLogger;
use Backend\Core\CSRF;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;
use PDO;

class AuthController
{
    public function login(Request $req): void
    {
        $body = $req->body();
        $validator = new Validator();
        if (!$validator->validate($body, [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['required', 'in:pasien,dokter,admin,patient,doctor']
        ])) {
            Response::error(422, $validator->getFirstError(), $validator->getErrors());
        }

        $email = strtolower(trim((string) $body['email']));
        $password = (string) $body['password'];
        $requestedRole = Auth::normalizeRole((string) $body['role']);

        $pdo = Database::getConnection();

        // SQL Injection Prevention: Prepared statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // 1. User existence
        if (!$user) {
            AuditLogger::log(null, 'LOGIN_FAILED', 'auth', null, 'failed', "User not found: $email");
            Response::error(401, 'Email atau password salah.');
        }

        $userId = (int) $user['user_id'];

        // 2. Check Brute-force Lockout
        if (!empty($user['locked_until'])) {
            $lockTime = strtotime($user['locked_until']);
            if ($lockTime > time()) {
                $remainingMins = (int) ceil(($lockTime - time()) / 60);
                Response::error(423, "Akun terkunci sementara karena terlalu banyak percobaan gagal. Coba lagi dalam $remainingMins menit.");
            }
        }

        // 3. Verify Password using BCRYPT
        if (!Auth::verifyPassword($password, $user['password_hash'])) {
            $failedAttempts = (int) $user['failed_login_attempts'] + 1;

            if ($failedAttempts >= 5) {
                // Lock account for 15 minutes
                $lockedUntil = date('Y-m-d H:i:s', time() + 900);
                $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = :locked WHERE user_id = :uid")
                    ->execute([':locked' => $lockedUntil, ':uid' => $userId]);

                AuditLogger::log($userId, 'ACCOUNT_LOCKED', 'auth', $userId, 'failed', '5 failed login attempts');
                Response::error(423, 'Terlalu banyak percobaan gagal. Akun Anda telah dikunci selama 15 menit.');
            } else {
                $pdo->prepare("UPDATE users SET failed_login_attempts = :attempts WHERE user_id = :uid")
                    ->execute([':attempts' => $failedAttempts, ':uid' => $userId]);

                AuditLogger::log($userId, 'LOGIN_FAILED', 'auth', $userId, 'failed', 'Invalid password');
                Response::error(401, 'Email atau password salah.');
            }
        }

        // 4. Role Authorization Check
        if ($user['role'] !== $requestedRole) {
            AuditLogger::log($userId, 'LOGIN_FAILED', 'auth', $userId, 'failed', "Role mismatch: expected {$user['role']} but got $requestedRole");
            Response::error(401, 'Email atau password tidak cocok dengan peran yang dipilih.');
        }

        // 5. Account Status Check
        if ($user['account_status'] === 'suspended') {
            AuditLogger::log($userId, 'LOGIN_BLOCKED', 'auth', $userId, 'failed', 'Account suspended');
            Response::error(403, 'Akun Anda ditangguhkan oleh administrator. Silakan hubungi pengelola rumah sakit.');
        }

        if ($user['account_status'] === 'inactive') {
            Response::error(403, 'Akun Anda berstatus nonaktif.');
        }

        // 6. Reset Lockout and update login timestamp
        $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, updated_at = :now WHERE user_id = :uid")
            ->execute([':now' => date('Y-m-d H:i:s'), ':uid' => $userId]);

        // 7. Fetch role specific entity
        $doctor = null;
        $patient = null;
        if ($user['role'] === 'doctor') {
            $dStmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = :uid LIMIT 1");
            $dStmt->execute([':uid' => $userId]);
            $doctor = $dStmt->fetch();
        } elseif ($user['role'] === 'patient') {
            $pStmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = :uid LIMIT 1");
            $pStmt->execute([':uid' => $userId]);
            $patient = $pStmt->fetch();
        }

        // 8. Establish secure authenticated session (persisted in sessions table with SHA-256)
        $sessionData = Auth::login($user, $doctor ?: null, $patient ?: null);

        AuditLogger::log($userId, 'LOGIN', 'auth', $userId, 'success', 'Successful login');

        Response::json([
            'ok' => true,
            'session' => $sessionData
        ]);
    }

    public function logout(Request $req): void
    {
        $user = Auth::user();
        if ($user) {
            AuditLogger::log($user['user_id'], 'LOGOUT', 'auth', $user['user_id'], 'success');
        }

        Auth::logout();
        Response::json(['ok' => true, 'message' => 'Berhasil keluar.']);
    }

    public function me(Request $req): void
    {
        $user = Auth::user();
        if (!$user) {
            Response::error(401, 'Sesi tidak aktif.');
        }

        $pdo = Database::getConnection();
        $doctor = null;
        $patient = null;
        if ($user['role'] === 'doctor') {
            $dStmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = :uid LIMIT 1");
            $dStmt->execute([':uid' => $user['user_id']]);
            $doctor = $dStmt->fetch();
        } elseif ($user['role'] === 'patient') {
            $pStmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = :uid LIMIT 1");
            $pStmt->execute([':uid' => $user['user_id']]);
            $patient = $pStmt->fetch();
        }

        Response::json([
            'ok' => true,
            'session' => Auth::formatSessionPayload($user, $doctor ?: null, $patient ?: null)
        ]);
    }

    public function csrfToken(Request $req): void
    {
        Response::json([
            'ok' => true,
            'csrf_token' => CSRF::getToken()
        ]);
    }

    public function registerPasien(Request $req): void
    {
        $body = $req->body();
        $validator = new Validator();
        if (!$validator->validate($body, [
            'name' => ['required', 'min:3', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'phone' => ['required', 'min:8', 'max:20'],
            'gender' => ['required', 'in:L,P,male,female'],
            'birth_date' => ['required', 'date'],
            'address' => ['required', 'min:5']
        ])) {
            Response::error(422, $validator->getFirstError(), $validator->getErrors());
        }

        $email = strtolower(trim((string) $body['email']));
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            Response::error(422, 'Email ini sudah terdaftar dalam sistem.');
        }

        $gender = match ($body['gender']) {
            'L', 'male' => 'male',
            'P', 'female' => 'female',
            default => 'male'
        };

        $pdo->beginTransaction();
        try {
            $hashed = Auth::hashPassword((string) $body['password']);
            $now = date('Y-m-d H:i:s');

            $uStmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, account_status, created_at, updated_at)
                VALUES (:name, :email, :password, 'patient', 'active', :now, :now)
            ");
            $uStmt->execute([
                ':name' => Validator::sanitize($body['name']),
                ':email' => $email,
                ':password' => $hashed,
                ':now' => $now
            ]);
            $userId = (int) $pdo->lastInsertId();

            $pStmt = $pdo->prepare("
                INSERT INTO patients (user_id, date_of_birth, gender, phone, address)
                VALUES (:uid, :dob, :gender, :phone, :addr)
            ");
            $pStmt->execute([
                ':uid' => $userId,
                ':dob' => $body['birth_date'],
                ':gender' => $gender,
                ':phone' => Validator::sanitize($body['phone']),
                ':addr' => Validator::sanitize($body['address'])
            ]);
            $patientId = (int) $pdo->lastInsertId();

            $pdo->commit();

            AuditLogger::log($userId, 'REGISTER_PATIENT', 'patient', $patientId, 'success');

            Response::json([
                'ok' => true,
                'id' => $patientId,
                'message' => 'Pendaftaran pasien berhasil. Silakan masuk.'
            ], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function registerDokter(Request $req): void
    {
        $body = $req->body();
        $validator = new Validator();
        if (!$validator->validate($body, [
            'name' => ['required', 'min:3', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'specialization_id' => ['required', 'integer'],
            'license' => ['required', 'min:5'],
            'experience_years' => ['required', 'integer'],
            'fee' => ['required', 'numeric'],
        ])) {
            Response::error(422, $validator->getFirstError(), $validator->getErrors());
        }

        $email = strtolower(trim((string) $body['email']));
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            Response::error(422, 'Email ini sudah terdaftar.');
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

            $dStmt = $pdo->prepare("
                INSERT INTO doctors (user_id, license_number, years_of_experience, consultation_fee, verification_status)
                VALUES (:uid, :lic, :exp, :fee, 'pending')
            ");
            $dStmt->execute([
                ':uid' => $userId,
                ':lic' => Validator::sanitize($body['license']),
                ':exp' => (int) $body['experience_years'],
                ':fee' => (float) $body['fee']
            ]);
            $doctorId = (int) $pdo->lastInsertId();

            // Link specialization (relasi many-to-many doctor_specializations)
            $pdo->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (:did, :sid)")
                ->execute([':did' => $doctorId, ':sid' => (int) $body['specialization_id']]);

            $pdo->commit();

            AuditLogger::log($userId, 'REGISTER_DOCTOR', 'doctor', $doctorId, 'success', 'Doctor registered, pending verification');

            Response::json([
                'ok' => true,
                'id' => $doctorId,
                'verification' => 'pending',
                'message' => 'Pendaftaran dokter berhasil diajukan. Menunggu verifikasi admin.'
            ], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function changePassword(Request $req): void
    {
        $user = Auth::requireAuth();
        $body = $req->body();

        $validator = new Validator();
        if (!$validator->validate($body, [
            'old_password' => ['required'],
            'new_password' => ['required', 'min:6'],
        ])) {
            Response::error(422, $validator->getFirstError(), $validator->getErrors());
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $user['user_id']]);
        $row = $stmt->fetch();

        if (!$row || !Auth::verifyPassword((string) $body['old_password'], $row['password_hash'])) {
            AuditLogger::log($user['user_id'], 'CHANGE_PASSWORD_FAILED', 'auth', $user['user_id'], 'failed', 'Wrong old password');
            Response::error(422, 'Password lama tidak sesuai.');
        }

        $newHash = Auth::hashPassword((string) $body['new_password']);
        $upStmt = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = :now WHERE user_id = :id");
        $upStmt->execute([':hash' => $newHash, ':now' => date('Y-m-d H:i:s'), ':id' => $user['user_id']]);

        AuditLogger::log($user['user_id'], 'CHANGE_PASSWORD', 'auth', $user['user_id'], 'success');

        Response::json(['ok' => true, 'message' => 'Password berhasil diperbarui.']);
    }
}
