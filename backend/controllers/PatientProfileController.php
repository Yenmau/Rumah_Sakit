<?php
/**
 * Patient Profile Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;

class PatientProfileController
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

    public function show(Request $req): void
    {
        $patientId = $this->getPatientId();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT p.patient_id AS id, p.user_id, u.name, u.email, 
                   p.phone, p.address, p.date_of_birth AS birth_date,
                   CASE WHEN p.gender = 'male' THEN 'L' ELSE 'P' END AS gender
            FROM patients p
            JOIN users u ON u.user_id = p.user_id
            WHERE p.patient_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $patientId]);
        $patient = $stmt->fetch();

        Response::json(['ok' => true, 'data' => $patient]);
    }

    public function update(Request $req): void
    {
        $patientId = $this->getPatientId();
        $user = Auth::user();
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'name' => ['min:3', 'max:100'],
            'phone' => ['min:8', 'max:20'],
            'gender' => ['in:L,P,male,female'],
            'birth_date' => ['date'],
            'address' => ['min:3', 'max:500']
        ])) {
            Response::error(422, $val->getFirstError(), $val->getErrors());
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, u.name 
            FROM patients p 
            JOIN users u ON u.user_id = p.user_id 
            WHERE p.patient_id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $patientId]);
        $current = $stmt->fetch();

        $name = isset($body['name']) ? Validator::sanitize($body['name']) : $current['name'];
        $phone = isset($body['phone']) ? Validator::sanitize($body['phone']) : $current['phone'];
        $birthDate = $body['birth_date'] ?? $current['date_of_birth'];
        $address = isset($body['address']) ? Validator::sanitize($body['address']) : $current['address'];

        $gender = $current['gender'];
        if (!empty($body['gender'])) {
            $gender = in_array($body['gender'], ['male', 'L']) ? 'male' : 'female';
        }

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("
                UPDATE patients 
                SET phone = :phone, gender = :gender, date_of_birth = :dob, address = :addr
                WHERE patient_id = :id
            ");
            $up->execute([
                ':phone' => $phone,
                ':gender' => $gender,
                ':dob' => $birthDate,
                ':addr' => $address,
                ':id' => $patientId
            ]);

            $pdo->prepare("UPDATE users SET name = :name, updated_at = :now WHERE user_id = :uid")
                ->execute([':name' => $name, ':now' => date('Y-m-d H:i:s'), ':uid' => $user['user_id']]);

            $pdo->commit();

            AuditLogger::log($user['user_id'], 'UPDATE_PROFILE', 'patient', $patientId, 'success');

            Response::json(['ok' => true, 'message' => 'Profil berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
