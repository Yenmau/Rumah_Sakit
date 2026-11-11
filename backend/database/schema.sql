-- =====================================================================
-- Medical Appointment Records Platform - MySQL 8.0.16+ (InnoDB, utf8mb4)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS medical_platform
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medical_platform;

-- ---------------------------------------------------------------------
-- 1. AUTH & AKUN
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  user_id              INT AUTO_INCREMENT PRIMARY KEY,
  name                 VARCHAR(100) NOT NULL,
  email                VARCHAR(150) NOT NULL,
  password_hash        VARCHAR(255) NOT NULL,                -- bcrypt / argon2
  role                 ENUM('patient','doctor','admin') NOT NULL,
  account_status       ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  failed_login_attempts INT NOT NULL DEFAULT 0,
  locked_until         DATETIME NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
  session_id       CHAR(64) PRIMARY KEY,                     -- SHA-256 hash dari token
  user_id          INT NOT NULL,
  csrf_token       CHAR(64) NOT NULL,                        -- disimpan ter-hash SHA-256
  ip_address       VARCHAR(45) NOT NULL,
  user_agent       VARCHAR(255) NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at       DATETIME NOT NULL,
  revoked_at       DATETIME NULL,
  KEY idx_sessions_user (user_id),
  KEY idx_sessions_expires (expires_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  log_id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,                                      -- NULL jika pre-auth (login gagal)
  action      VARCHAR(50) NOT NULL,                          -- login, view_record, update_diagnosis, ...
  entity_type VARCHAR(50) NOT NULL,
  entity_id   INT NULL,
  result      ENUM('success','failed') NOT NULL,
  description TEXT NULL,                                     -- tanpa isi medis
  ip_address  VARCHAR(45) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_user (user_id),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. PROFIL DOKTER & PASIEN
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
  doctor_id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id             INT NOT NULL,
  license_number      VARCHAR(50) NOT NULL,
  years_of_experience INT NOT NULL DEFAULT 0,
  consultation_fee    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verified_by         INT NULL,                              -- user admin
  verified_at         DATETIME NULL,
  rejection_reason    TEXT NULL,
  UNIQUE KEY uq_doctors_user (user_id),
  UNIQUE KEY uq_doctors_license (license_number),
  CONSTRAINT fk_doctors_user     FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_doctors_verifier FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS patients (
  patient_id    INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  date_of_birth DATE NOT NULL,
  gender        ENUM('male','female') NOT NULL,              -- sesuaikan dgn kebutuhan
  phone         VARCHAR(20) NULL,
  address       TEXT NULL,
  UNIQUE KEY uq_patients_user (user_id),
  CONSTRAINT fk_patients_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS specializations (
  specialization_id   INT AUTO_INCREMENT PRIMARY KEY,
  specialization_name VARCHAR(100) NOT NULL,
  description         TEXT NULL,
  UNIQUE KEY uq_spec_name (specialization_name)
) ENGINE=InnoDB;

-- relasi many-to-many dokter <-> spesialisasi
CREATE TABLE IF NOT EXISTS doctor_specializations (
  doctor_id         INT NOT NULL,
  specialization_id INT NOT NULL,
  PRIMARY KEY (doctor_id, specialization_id),
  CONSTRAINT fk_ds_doctor FOREIGN KEY (doctor_id)         REFERENCES doctors(doctor_id) ON DELETE CASCADE,
  CONSTRAINT fk_ds_spec   FOREIGN KEY (specialization_id) REFERENCES specializations(specialization_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. JADWAL DOKTER
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctor_availability (               -- jadwal rutin mingguan
  availability_id INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id       INT NOT NULL,
  day_of_week     ENUM('mon','tue','wed','thu','fri','sat','sun') NOT NULL,
  start_time      TIME NOT NULL,
  end_time        TIME NOT NULL,
  is_active       BOOLEAN NOT NULL DEFAULT TRUE,
  KEY idx_avail_doctor (doctor_id, day_of_week),
  CONSTRAINT fk_avail_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
  CONSTRAINT chk_avail_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doctor_schedule_exceptions (        -- cuti / jam khusus di tanggal tertentu
  exception_id   INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id      INT NOT NULL,                   -- boleh banyak per tanggal
  exception_date DATE NOT NULL,
  exception_type ENUM('leave','custom_hours') NOT NULL,
  start_time     TIME NULL,                      -- NULL jika leave, wajib jika custom_hours
  end_time       TIME NULL,
  reason         VARCHAR(255) NULL,
  created_by     INT NOT NULL,                   -- user (dokter/admin)
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_exc_doctor_date (doctor_id, exception_date),
  CONSTRAINT fk_exc_doctor  FOREIGN KEY (doctor_id)  REFERENCES doctors(doctor_id) ON DELETE CASCADE,
  CONSTRAINT fk_exc_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_exc_times CHECK (
    (exception_type = 'leave'        AND start_time IS NULL     AND end_time IS NULL) OR
    (exception_type = 'custom_hours' AND start_time IS NOT NULL AND end_time IS NOT NULL AND end_time > start_time)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. APPOINTMENT & QUEUE (ANTRIAN)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
  appointment_id       INT AUTO_INCREMENT PRIMARY KEY,
  patient_id           INT NOT NULL,
  doctor_id            INT NOT NULL,
  appointment_start    DATETIME NOT NULL,
  appointment_end      DATETIME NOT NULL,
  status               ENUM('pending','confirmed','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  reason               TEXT NULL,
  rescheduled_from_id  INT NULL,                 -- appointment sebelumnya
  rescheduled_by       INT NULL,                 -- user yang melakukan reschedule
  rejection_reason     TEXT NULL,
  rejected_by          INT NULL,
  rejected_at          DATETIME NULL,
  cancellation_reason  TEXT NULL,
  cancelled_by         INT NULL,
  cancelled_at         DATETIME NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_appt_doctor_time  (doctor_id, appointment_start),
  KEY idx_appt_patient_time (patient_id, appointment_start),
  KEY idx_appt_status (status),
  CONSTRAINT fk_appt_patient      FOREIGN KEY (patient_id)          REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_doctor       FOREIGN KEY (doctor_id)           REFERENCES doctors(doctor_id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_resched_from FOREIGN KEY (rescheduled_from_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
  CONSTRAINT fk_appt_resched_by   FOREIGN KEY (rescheduled_by)      REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT fk_appt_rejected_by  FOREIGN KEY (rejected_by)         REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT fk_appt_cancelled_by FOREIGN KEY (cancelled_by)        REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT chk_appt_time CHECK (appointment_end > appointment_start)
) ENGINE=InnoDB;

-- Tabel antrian (Queue) untuk mendukung fitur antrian pasien & dokter
CREATE TABLE IF NOT EXISTS appointment_queues (
  queue_id       INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  doctor_id      INT NOT NULL,
  queue_date     DATE NOT NULL,
  queue_number   INT NOT NULL,
  queue_status   ENUM('waiting','serving','completed','skipped') NOT NULL DEFAULT 'waiting',
  called_at      DATETIME NULL,
  completed_at   DATETIME NULL,
  UNIQUE KEY uq_queue_appointment (appointment_id),
  KEY idx_queue_lookup (doctor_id, queue_date, queue_number),
  CONSTRAINT fk_queue_appt   FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
  CONSTRAINT fk_queue_doctor FOREIGN KEY (doctor_id)      REFERENCES doctors(doctor_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. REKAM MEDIS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medical_records (
  record_id      INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,                   -- maks 1 record per appointment
  diagnosis      TEXT NOT NULL,
  notes          TEXT NULL,                      -- NULL saat draft
  record_status  ENUM('draft','finalized') NOT NULL DEFAULT 'draft',
  finalized_at   DATETIME NULL,
  created_by     INT NOT NULL,                   -- doctor_id
  updated_by     INT NULL,                       -- doctor_id
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_record_appointment (appointment_id),
  CONSTRAINT fk_record_appt    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
  CONSTRAINT fk_record_creator FOREIGN KEY (created_by)     REFERENCES doctors(doctor_id) ON DELETE CASCADE,
  CONSTRAINT fk_record_updater FOREIGN KEY (updated_by)     REFERENCES doctors(doctor_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- riwayat revisi (diisi setiap kali record yang sudah final diubah)
CREATE TABLE IF NOT EXISTS medical_record_revisions (
  revision_id            INT AUTO_INCREMENT PRIMARY KEY,
  record_id              INT NOT NULL,
  revised_by             INT NOT NULL,           -- doctor_id
  previous_diagnosis     TEXT NULL,
  previous_notes         TEXT NULL,
  previous_record_status ENUM('draft','finalized') NOT NULL,
  revision_reason        VARCHAR(255) NOT NULL,
  revised_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rev_record (record_id),
  CONSTRAINT fk_rev_record FOREIGN KEY (record_id)  REFERENCES medical_records(record_id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_doctor FOREIGN KEY (revised_by) REFERENCES doctors(doctor_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. RESEP
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prescriptions (
  prescription_id   INT AUTO_INCREMENT PRIMARY KEY,
  record_id         INT NOT NULL,                -- tidak unik: 1 record bisa banyak resep
  prescription_date DATE NOT NULL,
  KEY idx_presc_record (record_id),
  CONSTRAINT fk_presc_record FOREIGN KEY (record_id) REFERENCES medical_records(record_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prescription_items (
  item_id         INT AUTO_INCREMENT PRIMARY KEY,
  prescription_id INT NOT NULL,
  medicine_name   VARCHAR(150) NOT NULL,
  dosage          VARCHAR(100) NOT NULL,
  frequency       VARCHAR(100) NOT NULL,
  duration        VARCHAR(100) NOT NULL,
  quantity        INT NOT NULL,
  instructions    TEXT NULL,
  KEY idx_item_presc (prescription_id),
  CONSTRAINT fk_item_presc FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
  CONSTRAINT chk_item_qty CHECK (quantity > 0)
) ENGINE=InnoDB;
