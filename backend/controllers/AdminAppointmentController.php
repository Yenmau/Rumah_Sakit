<?php
/**
 * Admin Appointments Controller (medical_platform)
 * COMP6695 Secure Programming
 */

namespace Backend\Controllers;

use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;

class AdminAppointmentController
{
    public function index(Request $req): void
    {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $query = "
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
                   p.phone AS patient_phone,
                   pu.email AS patient_email,
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
            WHERE 1=1
        ";

        $params = [];

        $status = $req->query('status');
        if (!empty($status)) {
            $query .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        $date = $req->query('date');
        if (!empty($date)) {
            $query .= " AND SUBSTR(a.appointment_start, 1, 10) = :date";
            $params[':date'] = $date;
        }

        $doctorId = $req->query('doctor_id');
        if (!empty($doctorId)) {
            $query .= " AND a.doctor_id = :did";
            $params[':did'] = (int) $doctorId;
        }

        $search = $req->query('q');
        if (!empty($search)) {
            $query .= " AND (LOWER(pu.name) LIKE :q OR LOWER(du.name) LIKE :q)";
            $params[':q'] = '%' . strtolower(trim((string) $search)) . '%';
        }

        $query .= " ORDER BY a.appointment_start DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $appointments]);
    }
}
