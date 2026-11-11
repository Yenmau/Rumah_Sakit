<?php
/**
 * Admin Audit Log & Summary Controller (medical_platform)
 * COMP6695 Secure Programming
 */

namespace Backend\Controllers;

use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;

class AdminAuditLogController
{
    public function index(Request $req): void
    {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $query = "
            SELECT l.log_id AS id, l.created_at AS at, 
                   COALESCE(u.name, 'Pre-Auth User') AS user,
                   COALESCE(u.role, '-') AS role,
                   l.action, 
                   CONCAT(l.entity_type, '#', COALESCE(l.entity_id, 0)) AS resource,
                   l.result, l.ip_address AS ip, l.description AS details
            FROM audit_logs l
            LEFT JOIN users u ON u.user_id = l.user_id
            WHERE 1=1
        ";
        $params = [];

        $user = $req->query('user');
        if (!empty($user)) {
            $query .= " AND (LOWER(u.name) LIKE :user OR LOWER(u.email) LIKE :user)";
            $params[':user'] = '%' . strtolower(trim((string) $user)) . '%';
        }

        $action = $req->query('action');
        if (!empty($action)) {
            $query .= " AND l.action = :action";
            $params[':action'] = $action;
        }

        $result = $req->query('result');
        if (!empty($result)) {
            $query .= " AND l.result = :result";
            $params[':result'] = $result;
        }

        $date = $req->query('date');
        if (!empty($date)) {
            $query .= " AND SUBSTR(l.created_at, 1, 10) = :date";
            $params[':date'] = $date;
        }

        $query .= " ORDER BY l.created_at DESC, l.log_id DESC LIMIT 200";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $logs]);
    }

    public function summary(Request $req): void
    {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $pendingDoctors = (int) $pdo->query("SELECT COUNT(*) FROM doctors WHERE verification_status = 'pending'")->fetchColumn();
        $totalDoctors = (int) $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
        $activeUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'active'")->fetchColumn();

        $now = date('Y-m-d H:i:s');
        $lockedStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE locked_until IS NOT NULL AND locked_until > :now");
        $lockedStmt->execute([':now' => $now]);
        $lockedUsers = (int) $lockedStmt->fetchColumn();

        $today = date('Y-m-d');
        $apptsTodayStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE SUBSTR(appointment_start, 1, 10) = :today");
        $apptsTodayStmt->execute([':today' => $today]);
        $apptsToday = (int) $apptsTodayStmt->fetchColumn();

        $totalPatients = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

        $yesterday = date('Y-m-d H:i:s', time() - 86400);
        $failedLoginsStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'LOGIN_FAILED' AND created_at >= :yesterday");
        $failedLoginsStmt->execute([':yesterday' => $yesterday]);
        $failedLogins = (int) $failedLoginsStmt->fetchColumn();

        Response::json([
            'ok' => true,
            'data' => [
                'pending_doctors' => $pendingDoctors,
                'active_users' => $activeUsers,
                'locked_users' => $lockedUsers,
                'appointments_today' => $apptsToday,
                'total_doctors' => $totalDoctors,
                'total_patients' => $totalPatients,
                'failed_logins_24h' => $failedLogins
            ]
        ]);
    }
}
