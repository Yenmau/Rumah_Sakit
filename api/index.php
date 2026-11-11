<?php
/**
 * Main API Entry Point & Route Registry
 * RS SehatSentosa (COMP6695 Secure Programming)
 */

// Error handling configuration: hide errors from output in production
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Autoload backend classes
require_once __DIR__ . '/../backend/core/Database.php';
require_once __DIR__ . '/../backend/core/Request.php';
require_once __DIR__ . '/../backend/core/Response.php';
require_once __DIR__ . '/../backend/core/Router.php';
require_once __DIR__ . '/../backend/core/Auth.php';
require_once __DIR__ . '/../backend/core/Validator.php';
require_once __DIR__ . '/../backend/core/CSRF.php';
require_once __DIR__ . '/../backend/core/AuditLogger.php';
require_once __DIR__ . '/../backend/database/seeder.php';

// Controllers
require_once __DIR__ . '/../backend/controllers/AuthController.php';
require_once __DIR__ . '/../backend/controllers/SpecializationController.php';
require_once __DIR__ . '/../backend/controllers/DoctorPublicController.php';
require_once __DIR__ . '/../backend/controllers/PatientAppointmentController.php';
require_once __DIR__ . '/../backend/controllers/PatientRecordController.php';
require_once __DIR__ . '/../backend/controllers/PatientProfileController.php';
require_once __DIR__ . '/../backend/controllers/DoctorAppointmentController.php';
require_once __DIR__ . '/../backend/controllers/DoctorQueueController.php';
require_once __DIR__ . '/../backend/controllers/DoctorRecordController.php';
require_once __DIR__ . '/../backend/controllers/DoctorProfileController.php';
require_once __DIR__ . '/../backend/controllers/AdminDoctorController.php';
require_once __DIR__ . '/../backend/controllers/AdminUserController.php';
require_once __DIR__ . '/../backend/controllers/AdminAppointmentController.php';
require_once __DIR__ . '/../backend/controllers/AdminAuditLogController.php';

use Backend\Core\Database;
use Backend\Core\Request;
use Backend\Core\Response;
use Backend\Core\Router;
use Backend\Database\Seeder;
use Backend\Controllers\AuthController;
use Backend\Controllers\SpecializationController;
use Backend\Controllers\DoctorPublicController;
use Backend\Controllers\PatientAppointmentController;
use Backend\Controllers\PatientRecordController;
use Backend\Controllers\PatientProfileController;
use Backend\Controllers\DoctorAppointmentController;
use Backend\Controllers\DoctorQueueController;
use Backend\Controllers\DoctorRecordController;
use Backend\Controllers\DoctorProfileController;
use Backend\Controllers\AdminDoctorController;
use Backend\Controllers\AdminUserController;
use Backend\Controllers\AdminAppointmentController;
use Backend\Controllers\AdminAuditLogController;

// Auto-initialize DB tables if not yet created
try {
    $pdo = Database::getConnection();
    // Check if users table exists
    $driver = Database::getDriver();
    $tblCheck = ($driver === 'mysql') 
        ? "SHOW TABLES LIKE 'users'" 
        : "SELECT name FROM sqlite_master WHERE type='table' AND name='users'";
    $exists = $pdo->query($tblCheck)->fetch();
    if (!$exists) {
        $raw = file_get_contents(__DIR__ . '/../backend/database/schema.sql');
        if ($driver === 'sqlite') {
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
        } else {
            $sql = $raw;
        }
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            $pdo->exec($stmt);
        }
        Seeder::run();
    }
} catch (\Throwable $e) {
    // If DB fails, error out securely
    Response::error(500, 'Koneksi ke database gagal. Pastikan konfigurasi database sudah benar.');
}

$request = new Request();
$router = new Router();

// ==========================================
// 👤 Modul Autentikasi & Akun
// ==========================================
$router->post('/api/login', [AuthController::class, 'login']);
$router->post('/api/logout', [AuthController::class, 'logout']);
$router->post('/api/register/pasien', [AuthController::class, 'registerPasien']);
$router->post('/api/register/dokter', [AuthController::class, 'registerDokter']);
$router->post('/api/change-password', [AuthController::class, 'changePassword'], ['csrf', 'auth']);
$router->get('/api/me', [AuthController::class, 'me']);
$router->get('/api/csrf-token', [AuthController::class, 'csrfToken']);

// ==========================================
// 🏥 Modul Publik (Spesialisasi & Dokter)
// ==========================================
$router->get('/api/specializations', [SpecializationController::class, 'index']);
$router->get('/api/doctors', [DoctorPublicController::class, 'index']);
$router->get('/api/doctors/:id', [DoctorPublicController::class, 'show']);
$router->get('/api/doctors/:id/slots', [DoctorPublicController::class, 'slots']);

// ==========================================
// 🧑 Modul Pasien (Appointments, Antrian, Rekam Medis)
// ==========================================
$router->get('/api/pasien/appointments', [PatientAppointmentController::class, 'index'], ['auth', 'role:pasien']);
$router->get('/api/pasien/appointments/upcoming', [PatientAppointmentController::class, 'upcoming'], ['auth', 'role:pasien']);
$router->get('/api/pasien/appointments/:id', [PatientAppointmentController::class, 'show'], ['auth', 'role:pasien']);
$router->post('/api/pasien/appointments', [PatientAppointmentController::class, 'store'], ['csrf', 'auth', 'role:pasien']);
$router->post('/api/pasien/appointments/:id/cancel', [PatientAppointmentController::class, 'cancel'], ['csrf', 'auth', 'role:pasien']);
$router->post('/api/pasien/appointments/:id/reschedule', [PatientAppointmentController::class, 'reschedule'], ['csrf', 'auth', 'role:pasien']);

// 🆕 Posisi Antrian Pasien & Yang Sedang Dilayani
$router->get('/api/pasien/queue/status', [PatientAppointmentController::class, 'queueStatus'], ['auth', 'role:pasien']);
$router->get('/api/pasien/appointments/:id/queue', [PatientAppointmentController::class, 'queueStatus'], ['auth', 'role:pasien']);

// Rekam Medis & Profil Pasien
$router->get('/api/pasien/records', [PatientRecordController::class, 'index'], ['auth', 'role:pasien']);
$router->get('/api/pasien/records/:id', [PatientRecordController::class, 'show'], ['auth', 'role:pasien']);
$router->get('/api/pasien/profile', [PatientProfileController::class, 'show'], ['auth', 'role:pasien']);
$router->put('/api/pasien/profile', [PatientProfileController::class, 'update'], ['csrf', 'auth', 'role:pasien']);

// ==========================================
// 🩺 Modul Dokter (Appointments, Antrian, Rekam Medis, Jadwal, Cuti)
// ==========================================
$router->get('/api/dokter/appointments', [DoctorAppointmentController::class, 'index'], ['auth', 'role:dokter']);
$router->post('/api/dokter/appointments/:id/accept', [DoctorAppointmentController::class, 'accept'], ['csrf', 'auth', 'role:dokter']);
$router->post('/api/dokter/appointments/:id/reject', [DoctorAppointmentController::class, 'reject'], ['csrf', 'auth', 'role:dokter']);
$router->post('/api/dokter/appointments/:id/complete', [DoctorAppointmentController::class, 'complete'], ['csrf', 'auth', 'role:dokter']);

// 🆕 Panggil Antrian Dokter (Queue Next & Status)
$router->get('/api/dokter/queue/today', [DoctorQueueController::class, 'today'], ['auth', 'role:dokter']);
$router->post('/api/dokter/queue/next', [DoctorQueueController::class, 'next'], ['csrf', 'auth', 'role:dokter']);
$router->post('/api/dokter/queue/call', [DoctorQueueController::class, 'call'], ['csrf', 'auth', 'role:dokter']);

// Jadwal & Cuti
$router->get('/api/dokter/schedule', [DoctorAppointmentController::class, 'schedule'], ['auth', 'role:dokter']);
$router->put('/api/dokter/schedule', [DoctorAppointmentController::class, 'updateSchedule'], ['csrf', 'auth', 'role:dokter']);
$router->get('/api/dokter/leaves', [DoctorAppointmentController::class, 'leaves'], ['auth', 'role:dokter']);
$router->post('/api/dokter/leaves', [DoctorAppointmentController::class, 'storeLeave'], ['csrf', 'auth', 'role:dokter']);
$router->delete('/api/dokter/leaves/:id', [DoctorAppointmentController::class, 'deleteLeave'], ['csrf', 'auth', 'role:dokter']);

// Rekam Medis & Profil Dokter
$router->get('/api/dokter/records', [DoctorRecordController::class, 'index'], ['auth', 'role:dokter']);
$router->get('/api/dokter/records/:id', [DoctorRecordController::class, 'show'], ['auth', 'role:dokter']);
$router->post('/api/dokter/records', [DoctorRecordController::class, 'store'], ['csrf', 'auth', 'role:dokter']);
$router->put('/api/dokter/records/:id', [DoctorRecordController::class, 'update'], ['csrf', 'auth', 'role:dokter']);
$router->post('/api/dokter/records/:id/finalize', [DoctorRecordController::class, 'finalize'], ['csrf', 'auth', 'role:dokter']);
$router->get('/api/dokter/records/:id/revisions', [DoctorRecordController::class, 'revisions'], ['auth', 'role:dokter']);
$router->get('/api/dokter/profile', [DoctorProfileController::class, 'show'], ['auth', 'role:dokter']);
$router->put('/api/dokter/profile', [DoctorProfileController::class, 'update'], ['csrf', 'auth', 'role:dokter']);

// ==========================================
// 👑 Modul Admin (Dokter CRUD, User, Appointments, Audit Log, Spec)
// ==========================================
$router->get('/api/admin/summary', [AdminAuditLogController::class, 'summary'], ['auth', 'role:admin']);

// 🆕 Kelola Akun Dokter (Tambah / Edit / Hapus / Verifikasi)
$router->get('/api/admin/doctors', [AdminDoctorController::class, 'index'], ['auth', 'role:admin']);
$router->post('/api/admin/doctors', [AdminDoctorController::class, 'store'], ['csrf', 'auth', 'role:admin']);
$router->put('/api/admin/doctors/:id', [AdminDoctorController::class, 'update'], ['csrf', 'auth', 'role:admin']);
$router->delete('/api/admin/doctors/:id', [AdminDoctorController::class, 'destroy'], ['csrf', 'auth', 'role:admin']);
$router->get('/api/admin/doctors/pending', [AdminDoctorController::class, 'pending'], ['auth', 'role:admin']);
$router->post('/api/admin/doctors/:id/verify', [AdminDoctorController::class, 'verify'], ['csrf', 'auth', 'role:admin']);

// 🆕 Lihat Semua Appointment
$router->get('/api/admin/appointments', [AdminAppointmentController::class, 'index'], ['auth', 'role:admin']);

// Kelola User
$router->get('/api/admin/users', [AdminUserController::class, 'index'], ['auth', 'role:admin']);
$router->put('/api/admin/users/:id/status', [AdminUserController::class, 'updateStatus'], ['csrf', 'auth', 'role:admin']);
$router->post('/api/admin/users/:id/unlock', [AdminUserController::class, 'unlock'], ['csrf', 'auth', 'role:admin']);

// Spesialisasi & Audit Log
$router->get('/api/admin/specializations', [SpecializationController::class, 'index'], ['auth', 'role:admin']);
$router->post('/api/admin/specializations', [SpecializationController::class, 'store'], ['csrf', 'auth', 'role:admin']);
$router->put('/api/admin/specializations/:id', [SpecializationController::class, 'update'], ['csrf', 'auth', 'role:admin']);
$router->get('/api/admin/audit-logs', [AdminAuditLogController::class, 'index'], ['auth', 'role:admin']);

// Run router
$router->dispatch($request);
