<?php
/**
 * Public Doctor Catalog & Booking Slots Controller (medical_platform)
 */

namespace Backend\Controllers;

use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use PDO;

class DoctorPublicController
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

    public function index(Request $req): void
    {
        $pdo = Database::getConnection();
        $query = "
            SELECT d.doctor_id AS id, d.user_id, u.name, u.email,
                   d.license_number AS license, d.years_of_experience AS experience_years, 
                   d.consultation_fee AS fee, d.verification_status AS verification,
                   s.specialization_id, s.specialization_name AS specialization
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            WHERE d.verification_status = 'verified' AND u.account_status = 'active'
        ";

        $params = [];
        $spec = $req->query('specialization');
        if (!empty($spec)) {
            $query .= " AND s.specialization_id = :spec";
            $params[':spec'] = (int) $spec;
        }

        $search = $req->query('q');
        if (!empty($search)) {
            $query .= " AND (LOWER(u.name) LIKE :q OR LOWER(s.specialization_name) LIKE :q)";
            $params[':q'] = '%' . strtolower(trim((string) $search)) . '%';
        }

        $query .= " ORDER BY u.name ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $doctors = $stmt->fetchAll();

        Response::json(['ok' => true, 'data' => $doctors]);
    }

    public function show(Request $req): void
    {
        $id = (int) $req->param('id');
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT d.doctor_id AS id, d.user_id, u.name, u.email,
                   d.license_number AS license, d.years_of_experience AS experience_years, 
                   d.consultation_fee AS fee, d.verification_status AS verification,
                   s.specialization_id, s.specialization_name AS specialization
            FROM doctors d
            JOIN users u ON u.user_id = d.user_id
            LEFT JOIN doctor_specializations ds ON ds.doctor_id = d.doctor_id
            LEFT JOIN specializations s ON s.specialization_id = ds.specialization_id
            WHERE d.doctor_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            Response::error(404, 'Dokter tidak ditemukan.');
        }

        // Fetch schedules
        $sStmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = :did ORDER BY start_time ASC");
        $sStmt->execute([':did' => $id]);
        $schedules = $sStmt->fetchAll();
        $formattedSchedules = array_map(function ($s) {
            return [
                'id' => $s['availability_id'],
                'doctor_id' => $s['doctor_id'],
                'day' => self::$revDayMap[$s['day_of_week']] ?? 1,
                'start' => substr($s['start_time'], 0, 5),
                'end' => substr($s['end_time'], 0, 5),
                'active' => (bool) $s['is_active']
            ];
        }, $schedules);

        // Fetch exceptions (leaves)
        $lStmt = $pdo->prepare("
            SELECT exception_id AS id, doctor_id, exception_date AS date, exception_type AS type, 
                   start_time AS start, end_time AS end, reason AS note
            FROM doctor_schedule_exceptions 
            WHERE doctor_id = :did AND exception_date >= :today 
            ORDER BY exception_date ASC
        ");
        $lStmt->execute([':did' => $id, ':today' => date('Y-m-d')]);
        $leaves = $lStmt->fetchAll();

        Response::json([
            'ok' => true,
            'data' => $doctor,
            'schedules' => $formattedSchedules,
            'leaves' => $leaves
        ]);
    }

    public function slots(Request $req): void
    {
        $id = (int) $req->param('id');
        $days = (int) ($req->query('days') ?: 7);
        if ($days < 1 || $days > 30) {
            $days = 7;
        }

        $pdo = Database::getConnection();

        $dStmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = :id LIMIT 1");
        $dStmt->execute([':id' => $id]);
        if (!$dStmt->fetch()) {
            Response::error(404, 'Dokter tidak ditemukan.');
        }

        // Fetch active schedules
        $sStmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = :did AND is_active = 1");
        $sStmt->execute([':did' => $id]);
        $schedules = $sStmt->fetchAll();

        // Fetch exceptions
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+$days days"));
        $lStmt = $pdo->prepare("
            SELECT * FROM doctor_schedule_exceptions 
            WHERE doctor_id = :did AND exception_date BETWEEN :start AND :end
        ");
        $lStmt->execute([':did' => $id, ':start' => $startDate, ':end' => $endDate]);
        $leaves = $lStmt->fetchAll();

        // Fetch existing booked appointments
        $aStmt = $pdo->prepare("
            SELECT appointment_start, appointment_end, status 
            FROM appointments 
            WHERE doctor_id = :did 
              AND appointment_start >= :start AND appointment_start <= :end 
              AND status IN ('pending', 'confirmed')
        ");
        $aStmt->execute([':did' => $id, ':start' => "$startDate 00:00:00", ':end' => "$endDate 23:59:59"]);
        $booked = $aStmt->fetchAll();

        $daysNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $out = [];

        for ($i = 0; $i < $days; $i++) {
            $currTime = strtotime("+$i days");
            $dateStr = date('Y-m-d', $currTime);
            $dow = (int) date('w', $currTime);
            $dayCode = self::$dayMap[$dow] ?? 'mon';

            // Check full day leave
            $dayLeaves = array_filter($leaves, fn($l) => $l['exception_date'] === $dateStr);
            $hasFullLeave = false;
            foreach ($dayLeaves as $l) {
                if ($l['exception_type'] === 'leave') {
                    $hasFullLeave = true;
                    break;
                }
            }

            $slots = [];
            if (!$hasFullLeave) {
                foreach ($schedules as $s) {
                    if ($s['day_of_week'] === $dayCode) {
                        $startMin = $this->timeToMin($s['start_time']);
                        $endMin = $this->timeToMin($s['end_time']);

                        for ($m = $startMin; $m + 30 <= $endMin; $m += 30) {
                            $slotStart = $this->minToTime($m);
                            $slotEnd = $this->minToTime($m + 30);

                            // Check special custom_hours leave
                            $blockedByLeave = false;
                            foreach ($dayLeaves as $l) {
                                if ($l['exception_type'] === 'custom_hours' && !empty($l['start_time']) && !empty($l['end_time'])) {
                                    $lStart = $this->timeToMin($l['start_time']);
                                    $lEnd = $this->timeToMin($l['end_time']);
                                    if ($m >= $lStart && $m < $lEnd) {
                                        $blockedByLeave = true;
                                        break;
                                    }
                                }
                            }

                            // Check collision with booked appointments
                            $isTaken = false;
                            $slotFullStart = "$dateStr $slotStart:00";
                            foreach ($booked as $b) {
                                if (substr($b['appointment_start'], 0, 16) === substr($slotFullStart, 0, 16)) {
                                    $isTaken = true;
                                    break;
                                }
                            }

                            $slots[] = [
                                'start' => $slotStart,
                                'end' => $slotEnd,
                                'available' => (!$blockedByLeave && !$isTaken)
                            ];
                        }
                    }
                }
            }

            $out[] = [
                'date' => $dateStr,
                'day' => $daysNames[$dow],
                'slots' => $slots
            ];
        }

        Response::json(['ok' => true, 'data' => $out]);
    }

    private function timeToMin(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) $parts[0]) * 60 + ((int) ($parts[1] ?? 0));
    }

    private function minToTime(int $min): string
    {
        $h = floor($min / 60);
        $m = $min % 60;
        return sprintf('%02d:%02d', $h, $m);
    }
}
