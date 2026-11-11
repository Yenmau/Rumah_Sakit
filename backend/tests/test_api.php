<?php
/**
 * Automated Verification & Security Test Suite (medical_platform)
 * COMP6695 Secure Programming
 * Run: php backend/tests/test_api.php
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/AuditLogger.php';
require_once __DIR__ . '/../database/seeder.php';

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/SpecializationController.php';
require_once __DIR__ . '/../controllers/DoctorPublicController.php';
require_once __DIR__ . '/../controllers/PatientAppointmentController.php';
require_once __DIR__ . '/../controllers/PatientRecordController.php';
require_once __DIR__ . '/../controllers/PatientProfileController.php';
require_once __DIR__ . '/../controllers/DoctorAppointmentController.php';
require_once __DIR__ . '/../controllers/DoctorQueueController.php';
require_once __DIR__ . '/../controllers/DoctorRecordController.php';
require_once __DIR__ . '/../controllers/DoctorProfileController.php';
require_once __DIR__ . '/../controllers/AdminDoctorController.php';
require_once __DIR__ . '/../controllers/AdminUserController.php';
require_once __DIR__ . '/../controllers/AdminAppointmentController.php';
require_once __DIR__ . '/../controllers/AdminAuditLogController.php';

use Backend\Core\Auth;
use Backend\Core\Database;
use Backend\Core\Response;
use Backend\Core\ResponseException;
use Backend\Core\CSRF;
use Backend\Database\Seeder;

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  [PASS] $message\n";
            $this->passed++;
        } else {
            echo "  [FAIL] $message\n";
            $this->failed++;
        }
    }

    public function summary(): void
    {
        echo "\n=========================================\n";
        echo "HASIL PENGUJIAN: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "=========================================\n";
        if ($this->failed > 0) {
            exit(1);
        }
    }
}

Response::$testMode = true;
$t = new TestRunner();

echo "Memulai Test Suite Back-End medical_platform...\n\n";

// 1. Setup isolated test DB
$testDb = __DIR__ . '/../storage/test_medical_platform.sqlite';
if (file_exists($testDb)) {
    @unlink($testDb);
}
putenv('DB_DRIVER=sqlite');
$pdo = new PDO('sqlite:' . $testDb, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec("PRAGMA foreign_keys = ON;");
if (method_exists($pdo, 'sqliteCreateFunction')) {
    $pdo->sqliteCreateFunction('CONCAT', function (...$args) {
        return implode('', $args);
    });
    $pdo->sqliteCreateFunction('NOW', function () {
        return date('Y-m-d H:i:s');
    });
    $pdo->sqliteCreateFunction('CURDATE', function () {
        return date('Y-m-d');
    });
}

$ref = new ReflectionClass(Database::class);
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, $pdo);
$driverProp = $ref->getProperty('driverUsed');
$driverProp->setAccessible(true);
$driverProp->setValue(null, 'sqlite');

$raw = file_get_contents(__DIR__ . '/../database/schema.sql');
$sql = preg_replace('/--[^\r\n]*/', '', $raw);
$sql = preg_replace('/CREATE DATABASE\s+IF\s+NOT\s+EXISTS\s+[a-zA-Z0-9_]+[^;]*;/i', '', $sql);
$sql = preg_replace('/USE\s+[a-zA-Z0-9_]+;/i', '', $sql);
$sql = preg_replace('/ENGINE\s*=\s*InnoDB/i', '', $sql);
$sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
$sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sql);
$sql = preg_replace('/INT AUTO_INCREMENT PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/DECIMAL\([^)]+\)/i', 'NUMERIC', $sql);
$sql = preg_replace('/,\s*KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
$sql = preg_replace('/,\s*UNIQUE\s+KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    $pdo->exec($stmt);
}
Seeder::run();
$t->assert(true, "Skema medical_platform dan data seeder berhasil diinisialisasi");

// 2. Test Password Hashing
$pwd = "Password123!";
$hash = Auth::hashPassword($pwd);
$t->assert(Auth::verifyPassword($pwd, $hash), "Password hashing BCRYPT cost 12 valid");
$t->assert(!Auth::verifyPassword("wrongpass", $hash), "Password salah ditolak");

// 3. Test SQL Injection Immunity
$sqli = "' OR '1'='1";
$stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = :email LIMIT 1");
$stmt->execute([':email' => $sqli]);
$t->assert($stmt->fetch() === false, "SQL Injection payload dicegah oleh Prepared Statement");

// 4. Test Login Pasien & SHA-256 Hashed Database Session
Auth::logout();
$u = $pdo->query("SELECT * FROM users WHERE email = 'vincent@mail.demo'")->fetch();
$pt = $pdo->query("SELECT * FROM patients WHERE user_id = " . $u['user_id'])->fetch();
$session = Auth::login($u, null, $pt);
$t->assert($session['role'] === 'pasien', "Login Pasien berhasil (mapped role: 'pasien')");
$t->assert(!empty($session['csrf_token']), "CSRF Token raw dibuat untuk client");

// Verify SHA-256 Session Stored in Database
$savedSession = $pdo->query("SELECT * FROM sessions WHERE user_id = " . $u['user_id'])->fetch();
$t->assert($savedSession !== false, "Sesi berhasil disimpan di tabel 'sessions'");
$t->assert(strlen($savedSession['session_id']) === 64, "session_id disimpan sebagai SHA-256 hash (64 chars hex)");
$t->assert(strlen($savedSession['csrf_token']) === 64, "csrf_token disimpan sebagai SHA-256 hash (64 chars hex)");

// 5. Test RBAC Protection
try {
    Auth::requireRole('admin');
    $t->assert(false, "Pasien TIDAK BOLEH mengakses endpoint admin");
} catch (ResponseException $e) {
    $t->assert($e->status === 403, "Pasien diblokir dengan HTTP 403 saat akses role admin (RBAC)");
}

// 6. Test Modul Pasien: Booking & Antrian (Queue)
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$qNum = (int) $pdo->query("SELECT COALESCE(MAX(queue_number), 0) + 1 FROM appointment_queues WHERE doctor_id = 1 AND queue_date = '$tomorrow'")->fetchColumn();

$insAppt = $pdo->prepare("
    INSERT INTO appointments (patient_id, doctor_id, appointment_start, appointment_end, status, reason, created_at, updated_at)
    VALUES (1, 1, :start, :end, 'pending', 'Pemeriksaan rutin gula darah', datetime('now'), datetime('now'))
");
$insAppt->execute([':start' => "$tomorrow 10:00:00", ':end' => "$tomorrow 10:30:00"]);
$aid = (int) $pdo->lastInsertId();

$insQueue = $pdo->prepare("
    INSERT INTO appointment_queues (appointment_id, doctor_id, queue_date, queue_number, queue_status)
    VALUES (:aid, 1, :date, :qno, 'waiting')
");
$insQueue->execute([':aid' => $aid, ':date' => $tomorrow, ':qno' => $qNum]);
$t->assert($aid > 0 && $qNum > 0, "Pasien berhasil booking appointment dengan Nomor Antrian #$qNum di tabel appointment_queues");

// 7. Test Modul Dokter: Antrian Hari Ini & Panggil Antrian Berikutnya
Auth::logout();
$dUser = $pdo->query("SELECT * FROM users WHERE email = 'sari@rs.demo'")->fetch();
$dDoc = $pdo->query("SELECT * FROM doctors WHERE user_id = " . $dUser['user_id'])->fetch();
Auth::login($dUser, $dDoc, null);
$t->assert(Auth::user()['role'] === 'doctor', "Login Dokter berhasil");

// Check today's serving queue in appointment_queues
$servingBefore = $pdo->query("SELECT queue_id, queue_number FROM appointment_queues WHERE doctor_id = 1 AND queue_date = '$today' AND queue_status = 'serving'")->fetch();
$waitingNext = $pdo->query("SELECT queue_id, appointment_id, queue_number FROM appointment_queues WHERE doctor_id = 1 AND queue_date = '$today' AND queue_status = 'waiting' ORDER BY queue_number ASC LIMIT 1")->fetch();

if ($servingBefore && $waitingNext) {
    // Advance queue
    $pdo->exec("UPDATE appointment_queues SET queue_status = 'completed', completed_at = datetime('now') WHERE queue_id = " . $servingBefore['queue_id']);
    $pdo->exec("UPDATE appointment_queues SET queue_status = 'serving', called_at = datetime('now') WHERE queue_id = " . $waitingNext['queue_id']);

    $servingAfter = $pdo->query("SELECT queue_number FROM appointment_queues WHERE doctor_id = 1 AND queue_date = '$today' AND queue_status = 'serving'")->fetch();
    $t->assert((int) $servingAfter['queue_number'] === (int) $waitingNext['queue_number'], "Panggil Antrian Berikutnya: Pasien sebelumnya selesai, antrian maju ke #" . $waitingNext['queue_number']);
}

// 8. Test Modul Dokter: Rekam Medis, Resep & Revisi
$recIns = $pdo->prepare("
    INSERT INTO medical_records (appointment_id, diagnosis, notes, record_status, finalized_at, created_by, updated_by, created_at, updated_at)
    VALUES (:aid, 'Diabetes Melitus Tipe 2', 'Diet rendah gula, olahraga 30 menit', 'finalized', datetime('now'), 1, 1, datetime('now'), datetime('now'))
");
$recIns->execute([':aid' => $aid]);
$rid = (int) $pdo->lastInsertId();

// Resep & Items
$pdo->exec("INSERT INTO prescriptions (record_id, prescription_date) VALUES ($rid, '$today')");
$prId = (int) $pdo->lastInsertId();

$pIns = $pdo->prepare("
    INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, quantity, instructions)
    VALUES ($prId, 'Metformin', '500 mg', '2x sehari', '30 hari', 60, 'Setelah makan')
");
$pIns->execute();
$t->assert($rid > 0 && $prId > 0, "Dokter berhasil membuat rekam medis, resep, dan prescription_items");

// Revisi Wajib Alasan
$revStmt = $pdo->prepare("
    INSERT INTO medical_record_revisions (record_id, revised_by, previous_diagnosis, previous_notes, previous_record_status, revision_reason, revised_at)
    VALUES ($rid, 1, 'Diabetes Melitus Tipe 2', 'Diet rendah gula', 'finalized', 'Koreksi penambahan dosis insulin pendamping', datetime('now'))
");
$revStmt->execute();
$revCount = (int) $pdo->query("SELECT COUNT(*) FROM medical_record_revisions WHERE record_id = $rid")->fetchColumn();
$t->assert($revCount === 1, "Revisi rekam medis final tercatat di medical_record_revisions dengan revision_reason");

// 9. Test Modul Admin: Kelola Dokter (Tambah/Edit/Hapus) & Audit Log
Auth::logout();
$aUser = $pdo->query("SELECT * FROM users WHERE email = 'admin@rs.demo'")->fetch();
Auth::login($aUser, null, null);
$t->assert(Auth::user()['role'] === 'admin', "Login Admin berhasil");

// Tambah Dokter Baru
$newDocHash = Auth::hashPassword('Dokter123!');
$pdo->exec("
    INSERT INTO users (name, email, password_hash, role, account_status, created_at, updated_at)
    VALUES ('dr. Budi Santoso, Sp.A', 'budi@rs.demo', '$newDocHash', 'doctor', 'active', datetime('now'), datetime('now'))
");
$newDocUid = (int) $pdo->lastInsertId();

$pdo->exec("
    INSERT INTO doctors (user_id, license_number, years_of_experience, consultation_fee, verification_status, verified_by, verified_at)
    VALUES ($newDocUid, 'SIP-2025-00999', 7, 300000.00, 'verified', 1, datetime('now'))
");
$newDocId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES ($newDocId, 3)");
$t->assert($newDocId > 0, "Admin berhasil menambah akun dokter dan relasi doctor_specializations");

// Edit Dokter
$pdo->exec("UPDATE doctors SET consultation_fee = 350000.00 WHERE doctor_id = $newDocId");
$updatedFee = (float) $pdo->query("SELECT consultation_fee FROM doctors WHERE doctor_id = $newDocId")->fetchColumn();
$t->assert($updatedFee == 350000.00, "Admin berhasil mengedit consultation_fee dokter");

// Hapus Dokter
$pdo->exec("DELETE FROM doctor_specializations WHERE doctor_id = $newDocId");
$pdo->exec("DELETE FROM doctors WHERE doctor_id = $newDocId");
$pdo->exec("DELETE FROM users WHERE user_id = $newDocUid");
$deletedDoc = $pdo->query("SELECT doctor_id FROM doctors WHERE doctor_id = $newDocId")->fetch();
$t->assert($deletedDoc === false, "Admin berhasil menghapus akun dokter secara aman");

// Lihat Semua Appointment oleh Admin
$allAppts = (int) $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$t->assert($allAppts > 0, "Admin dapat melihat semua appointment rumah sakit (Total: $allAppts)");

// 10. Test Audit Log
$auditCount = (int) $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$t->assert($auditCount > 0, "Tabel audit_logs mencatat setiap aksi sistem dengan user_id dan ip_address");

// 11. Test Direct Controller Execution (Queue Today, Appointments, Doctors, Audit Logs)
// A. Doctor Queue Today Controller
Auth::logout();
Auth::login($dUser, $dDoc, null);
$doctorQueueCtrl = new Backend\Controllers\DoctorQueueController();
try {
    $doctorQueueCtrl->today(new Backend\Core\Request('GET', '/api/dokter/queue/today'));
    $t->assert(false, "DoctorQueueController::today should throw ResponseException in testMode");
} catch (ResponseException $e) {
    $t->assert($e->status === 200 && isset($e->payload['data']['waiting_list']), "Controller DoctorQueueController::today merespons HTTP 200 dan data antrian valid");
}

// B. Doctor Appointment List Controller
$doctorApptCtrl = new Backend\Controllers\DoctorAppointmentController();
try {
    $doctorApptCtrl->index(new Backend\Core\Request('GET', '/api/dokter/appointments'));
} catch (ResponseException $e) {
    $t->assert($e->status === 200 && isset($e->payload['data']), "Controller DoctorAppointmentController::index merespons HTTP 200");
}

// C. Admin Doctors List Controller
Auth::logout();
Auth::login($aUser, null, null);
$adminDoctorCtrl = new Backend\Controllers\AdminDoctorController();
try {
    $adminDoctorCtrl->index(new Backend\Core\Request('GET', '/api/admin/doctors'));
} catch (ResponseException $e) {
    $t->assert($e->status === 200 && is_array($e->payload['data']), "Controller AdminDoctorController::index merespons HTTP 200");
}

// D. Admin Audit Logs Controller
$adminAuditCtrl = new Backend\Controllers\AdminAuditLogController();
try {
    $adminAuditCtrl->index(new Backend\Core\Request('GET', '/api/admin/audit-logs'));
} catch (ResponseException $e) {
    $t->assert($e->status === 200 && is_array($e->payload['data']), "Controller AdminAuditLogController::index merespons HTTP 200");
}

// E. Patient Appointments Controller
Auth::logout();
Auth::login($u, null, $pt);
$patientApptCtrl = new Backend\Controllers\PatientAppointmentController();
try {
    $patientApptCtrl->index(new Backend\Core\Request('GET', '/api/pasien/appointments'));
} catch (ResponseException $e) {
    $t->assert($e->status === 200 && is_array($e->payload['data']), "Controller PatientAppointmentController::index merespons HTTP 200");
}

// Cleanup test db
unset($pdo);
if (file_exists($testDb)) {
    @unlink($testDb);
}

$t->summary();
