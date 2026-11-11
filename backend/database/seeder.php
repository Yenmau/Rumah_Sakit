<?php
/**
 * Database Seeder for medical_platform
 */

namespace Backend\Database;

use Backend\Core\Auth;
use Backend\Core\Database;

class Seeder
{
    public static function run(): void
    {
        $pdo = Database::getConnection();

        // Check if already seeded
        $uCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($uCount > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $defaultPassword = Auth::hashPassword('Password123!');

        // 1. Specializations
        $specs = [
            [1, 'Dokter Umum', 'Pemeriksaan dan konsultasi kesehatan umum'],
            [2, 'Penyakit Dalam', 'Diagnosis dan terapi penyakit organ dalam'],
            [3, 'Anak', 'Kesehatan bayi, anak, dan remaja'],
            [4, 'Jantung', 'Kardiologi dan pembuluh darah'],
            [5, 'Kulit & Kelamin', 'Dermatologi dan venereologi'],
            [6, 'Saraf', 'Neurologi']
        ];
        $sStmt = $pdo->prepare("INSERT INTO specializations (specialization_id, specialization_name, description) VALUES (?, ?, ?)");
        foreach ($specs as $sp) {
            $sStmt->execute($sp);
        }

        // 2. Users (roles: 'patient', 'doctor', 'admin')
        $users = [
            [1, 'Admin Rumah Sakit', 'admin@rs.demo', $defaultPassword, 'admin', 'active'],
            [2, 'dr. Sari Wijaya', 'sari@rs.demo', $defaultPassword, 'doctor', 'active'],
            [3, 'dr. Bagus Prakoso', 'bagus@rs.demo', $defaultPassword, 'doctor', 'active'],
            [4, 'dr. Nadia Kusuma', 'nadia@rs.demo', $defaultPassword, 'doctor', 'suspended'],
            [5, 'Vincent Tiono', 'vincent@mail.demo', $defaultPassword, 'patient', 'active'],
            [6, 'Ayu Lestari', 'ayu@mail.demo', $defaultPassword, 'patient', 'active'],
            [7, 'Rudi Hartono', 'rudi@mail.demo', $defaultPassword, 'patient', 'inactive']
        ];
        $uStmt = $pdo->prepare("
            INSERT INTO users (user_id, name, email, password_hash, role, account_status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($users as $u) {
            $uStmt->execute([$u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $now, $now]);
        }

        // 3. Doctors
        $doctors = [
            [1, 2, 'SIP-2021-00123', 9, 250000.00, 'verified', 1, $now, null],
            [2, 3, 'SIP-2020-00871', 12, 400000.00, 'verified', 1, $now, null],
            [3, 4, 'SIP-2022-00455', 6, 300000.00, 'verified', 1, $now, null],
        ];
        $dStmt = $pdo->prepare("
            INSERT INTO doctors (doctor_id, user_id, license_number, years_of_experience, consultation_fee, verification_status, verified_by, verified_at, rejection_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($doctors as $d) {
            $dStmt->execute($d);
        }

        // 4. Doctor Specializations (relasi many-to-many)
        $docSpecs = [
            [1, 2], // dr. Sari - Penyakit Dalam
            [2, 4], // dr. Bagus - Jantung
            [3, 3], // dr. Nadia - Anak
        ];
        $dsStmt = $pdo->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (?, ?)");
        foreach ($docSpecs as $ds) {
            $dsStmt->execute($ds);
        }

        // 5. Patients
        $patients = [
            [1, 5, '2004-05-11', 'male', '0812000001', 'Jl. Kemuning No. 12, Banjarbaru'],
            [2, 6, '1999-02-20', 'female', '0812000002', 'Jl. Ahmad Yani No. 88'],
            [3, 7, '1988-11-02', 'male', '0812000003', 'Jl. Guntung Manggis No. 5']
        ];
        $pStmt = $pdo->prepare("
            INSERT INTO patients (patient_id, user_id, date_of_birth, gender, phone, address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($patients as $p) {
            $pStmt->execute($p);
        }

        // 6. Doctor Availability (Jadwal mingguan: 'mon','tue','wed','thu','fri','sat','sun')
        $availability = [
            [1, 'mon', '08:00:00', '12:00:00', 1],
            [1, 'wed', '08:00:00', '12:00:00', 1],
            [1, 'fri', '13:00:00', '16:00:00', 1],
            [2, 'tue', '09:00:00', '13:00:00', 1],
            [2, 'thu', '09:00:00', '12:00:00', 0],
            [2, 'sat', '09:00:00', '12:00:00', 1],
        ];
        $avStmt = $pdo->prepare("
            INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_active)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($availability as $av) {
            $avStmt->execute($av);
        }

        // 7. Schedule Exceptions (cuti / jam khusus)
        $dCuti = date('Y-m-d', strtotime('+2 days'));
        $dKhusus = date('Y-m-d', strtotime('+1 day'));
        $exc = [
            [1, $dCuti, 'leave', null, null, 'Cuti tahunan', 2],
            [2, $dKhusus, 'custom_hours', '13:00:00', '15:00:00', 'Operasi terjadwal', 3]
        ];
        $excStmt = $pdo->prepare("
            INSERT INTO doctor_schedule_exceptions (doctor_id, exception_date, exception_type, start_time, end_time, reason, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($exc as $e) {
            $excStmt->execute([$e[0], $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $now, $now]);
        }

        // 8. Appointments
        $today = date('Y-m-d');
        $d1 = date('Y-m-d', strtotime('+1 days'));
        $d3 = date('Y-m-d', strtotime('+3 days'));
        $dPast = date('Y-m-d', strtotime('-2 days'));

        $appointments = [
            [1, 1, 1, "$d1 09:00:00", "$d1 09:30:00", 'pending', 'Kontrol gula darah, sering lemas setelah makan.'],
            [2, 1, 2, "$d3 10:30:00", "$d3 11:00:00", 'confirmed', 'Nyeri dada saat olahraga.'],
            [5, 2, 1, "$today 09:00:00", "$today 09:30:00", 'confirmed', 'Hasil lab darah perlu ditinjau.'],
            [6, 2, 1, "$today 10:00:00", "$today 10:30:00", 'pending', 'Pusing berulang sejak seminggu.'],
            [7, 3, 2, "$today 11:00:00", "$today 11:30:00", 'confirmed', 'Rekam EKG lanjutan.'],
            [8, 3, 1, "$dPast 14:00:00", "$dPast 14:30:00", 'completed', 'Kontrol kolesterol.']
        ];
        $aStmt = $pdo->prepare("
            INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_start, appointment_end, status, reason, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($appointments as $a) {
            $aStmt->execute([$a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $now, $now]);
        }

        // 9. Appointment Queues
        $queues = [
            [1, 1, $d1, 1, 'waiting'],
            [2, 2, $d3, 1, 'waiting'],
            [5, 1, $today, 1, 'serving'],
            [6, 1, $today, 2, 'waiting'],
            [7, 2, $today, 1, 'waiting'],
            [8, 1, $dPast, 1, 'completed']
        ];
        $qStmt = $pdo->prepare("
            INSERT INTO appointment_queues (appointment_id, doctor_id, queue_date, queue_number, queue_status)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($queues as $q) {
            $qStmt->execute($q);
        }

        // 10. Medical Records
        $mStmt = $pdo->prepare("
            INSERT INTO medical_records (record_id, appointment_id, diagnosis, notes, record_status, finalized_at, created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $mStmt->execute([
            1, 8,
            'Hiperkolesterolemia ringan (E78.0)',
            'LDL 168 mg/dL. Disarankan diet rendah lemak dan aktivitas aerobik 30 menit, 5x/minggu. Kontrol 1 bulan.',
            'finalized', $now, 1, 1, $now, $now
        ]);

        // 11. Prescriptions & Items
        $prStmt = $pdo->prepare("INSERT INTO prescriptions (prescription_id, record_id, prescription_date) VALUES (?, ?, ?)");
        $prStmt->execute([1, 1, $dPast]);

        $piStmt = $pdo->prepare("
            INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, quantity, instructions)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $piStmt->execute([1, 'Simvastatin', '20 mg', '1x sehari', '30 hari', 30, 'Diminum malam sebelum tidur']);
        $piStmt->execute([1, 'Vitamin B Complex', '1 tablet', '1x sehari', '14 hari', 14, 'Setelah makan pagi']);

        // 12. Audit Logs
        $logs = [
            [1, 'LOGIN', 'auth', null, 'success', 'User admin login', '10.0.2.15'],
            [2, 'FINALIZE_RECORD', 'medical_record', 1, 'success', 'dr. Sari finalized record #1', '10.0.2.31'],
            [5, 'CREATE_APPOINTMENT', 'appointment', 1, 'success', 'Vincent created appointment #1', '10.0.2.15'],
            [6, 'CREATE_APPOINTMENT', 'appointment', 5, 'success', 'Ayu created appointment #5', '10.0.2.77'],
            [null, 'LOGIN_FAILED', 'auth', null, 'failed', 'Failed login for vincent@mail.demo', '10.0.2.15']
        ];
        $alStmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, result, description, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($logs as $l) {
            $alStmt->execute([$l[0], $l[1], $l[2], $l[3], $l[4], $l[5], $l[6], $now]);
        }
    }
}
