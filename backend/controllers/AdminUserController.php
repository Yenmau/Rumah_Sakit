<?php
/**
 * Admin User Management Controller (medical_platform)
 * COMP6695 Secure Programming
 */

namespace Backend\Controllers;

use Backend\Core\AuditLogger;
use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Validator;

class AdminUserController
{
    public function index(Request $req): void
    {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $stmt = $pdo->query("
            SELECT user_id AS id, name, email, 
                   CASE 
                       WHEN role = 'patient' THEN 'pasien'
                       WHEN role = 'doctor' THEN 'dokter'
                       ELSE role 
                   END AS role,
                   account_status AS status, 
                   locked_until, created_at, updated_at AS last_login
            FROM users
            ORDER BY user_id ASC
        ");
        $users = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $users]);
    }

    public function updateStatus(Request $req): void
    {
        $admin = Auth::requireRole('admin');
        $id = (int) $req->param('id');
        $body = $req->body();

        $val = new Validator();
        if (!$val->validate($body, [
            'status' => ['required', 'in:active,inactive,suspended']
        ])) {
            Response::error(422, 'Status tidak valid.', $val->getErrors());
        }

        $newStatus = $body['status'];
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error(404, 'User tidak ditemukan.');
        }

        if ($user['role'] === 'admin' && $newStatus !== 'active') {
            Response::error(403, 'Akun administrator tidak dapat dinonaktifkan.');
        }

        $up = $pdo->prepare("UPDATE users SET account_status = :st, updated_at = :now WHERE user_id = :id");
        $up->execute([':st' => $newStatus, ':now' => date('Y-m-d H:i:s'), ':id' => $id]);

        AuditLogger::log(
            $admin['user_id'],
            'UPDATE_USER_STATUS',
            'user',
            $id,
            'success',
            "Status changed from {$user['account_status']} to $newStatus"
        );

        Response::json([
            'ok' => true,
            'before' => $user['account_status'],
            'data' => array_merge($user, ['account_status' => $newStatus, 'status' => $newStatus]),
            'message' => 'Status user berhasil diperbarui.'
        ]);
    }

    public function unlock(Request $req): void
    {
        $admin = Auth::requireRole('admin');
        $id = (int) $req->param('id');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error(404, 'User tidak ditemukan.');
        }

        $up = $pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL, updated_at = :now 
            WHERE user_id = :id
        ");
        $up->execute([':now' => date('Y-m-d H:i:s'), ':id' => $id]);

        AuditLogger::log($admin['user_id'], 'UNLOCK_USER', 'user', $id, 'success', 'Unlocked by admin');

        Response::json([
            'ok' => true,
            'data' => array_merge($user, ['locked_until' => null, 'failed_login_attempts' => 0]),
            'message' => 'Akun berhasil dibuka kuncinya.'
        ]);
    }
}
