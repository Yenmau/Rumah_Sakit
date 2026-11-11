<?php
/**
 * Authentication, Database Session Hardening & RBAC (COMP6695 Secure Programming)
 * Implements SHA-256 Hashed Database Sessions & CSRF Verification
 */

namespace Backend\Core;

use PDO;

class Auth
{
    private static ?array $currentUser = null;
    private static ?array $currentSession = null;
    private static ?string $currentRawCsrf = null;

    /**
     * Map between database roles and frontend Indonesian roles
     */
    public static function normalizeRole(string $role): string
    {
        return match (strtolower(trim($role))) {
            'pasien', 'patient' => 'patient',
            'dokter', 'doctor' => 'doctor',
            'admin' => 'admin',
            default => $role
        };
    }

    public static function displayRole(string $role): string
    {
        return match (strtolower(trim($role))) {
            'patient', 'pasien' => 'pasien',
            'doctor', 'dokter' => 'dokter',
            'admin' => 'admin',
            default => $role
        };
    }

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            session_name('RS_SESSION_ID');
            session_set_cookie_params([
                'lifetime' => 7200,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            @session_start();
        }
    }

    public static function login(array $user, ?array $doctor = null, ?array $patient = null): array
    {
        self::startSession();

        // 1. Generate Raw Tokens (never stored in plaintext in the database)
        $rawSessionToken = bin2hex(random_bytes(32));
        $hashedSessionId = hash('sha256', $rawSessionToken);

        $rawCsrfToken = bin2hex(random_bytes(32));
        $hashedCsrfToken = hash('sha256', $rawCsrfToken);

        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + 7200); // 2 hours
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Agent', 0, 255);

        $pdo = Database::getConnection();

        // 2. Persist to sessions table
        $stmt = $pdo->prepare("
            INSERT INTO sessions (session_id, user_id, csrf_token, ip_address, user_agent, created_at, last_activity_at, expires_at)
            VALUES (:sid, :uid, :csrf, :ip, :ua, :now, :now, :exp)
        ");
        $stmt->execute([
            ':sid' => $hashedSessionId,
            ':uid' => $user['user_id'],
            ':csrf' => $hashedCsrfToken,
            ':ip' => $ip,
            ':ua' => $ua,
            ':now' => $now,
            ':exp' => $expiresAt
        ]);

        // 3. Set secure cookie with raw session token
        if (!headers_sent()) {
            setcookie('RS_SESSION_TOKEN', $rawSessionToken, [
                'expires' => time() + 7200,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        // Also store in PHP $_SESSION for fast in-memory access
        $_SESSION['raw_session_token'] = $rawSessionToken;
        $_SESSION['raw_csrf_token'] = $rawCsrfToken;
        $_SESSION['user_id'] = $user['user_id'];

        self::$currentRawCsrf = $rawCsrfToken;

        return self::formatSessionPayload($user, $doctor, $patient, $rawCsrfToken);
    }

    public static function authenticate(Request $request): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        self::startSession();

        // Check raw token from cookie, header, or $_SESSION
        $rawToken = $_COOKIE['RS_SESSION_TOKEN'] 
            ?? $request->header('x-session-token') 
            ?? ($_SESSION['raw_session_token'] ?? null);

        if (!$rawToken) {
            return null;
        }

        $hashedSessionId = hash('sha256', (string) $rawToken);
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            SELECT s.*, u.user_id, u.name, u.email, u.role, u.account_status, u.locked_until
            FROM sessions s
            JOIN users u ON u.user_id = s.user_id
            WHERE s.session_id = :sid AND s.revoked_at IS NULL AND s.expires_at > :now
            LIMIT 1
        ");
        $stmt->execute([':sid' => $hashedSessionId, ':now' => $now]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        // Check idle timeout (30 minutes of inactivity)
        $lastActivity = strtotime($row['last_activity_at']);
        if ((time() - $lastActivity) > 1800) {
            // Revoke session
            $pdo->prepare("UPDATE sessions SET revoked_at = :now WHERE session_id = :sid")
                ->execute([':now' => $now, ':sid' => $hashedSessionId]);
            return null;
        }

        // Check account status
        if ($row['account_status'] === 'suspended') {
            return null;
        }

        // Touch last_activity_at
        $pdo->prepare("UPDATE sessions SET last_activity_at = :now WHERE session_id = :sid")
            ->execute([':now' => $now, ':sid' => $hashedSessionId]);

        // Fetch associated doctor or patient
        $doctorId = null;
        $patientId = null;

        if ($row['role'] === 'doctor') {
            $d = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = :uid LIMIT 1");
            $d->execute([':uid' => $row['user_id']]);
            $doctorId = $d->fetchColumn() ?: null;
        } elseif ($row['role'] === 'patient') {
            $p = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = :uid LIMIT 1");
            $p->execute([':uid' => $row['user_id']]);
            $patientId = $p->fetchColumn() ?: null;
        }

        self::$currentSession = $row;
        self::$currentUser = [
            'user_id' => (int) $row['user_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],                     // 'patient', 'doctor', 'admin'
            'display_role' => self::displayRole($row['role']), // 'pasien', 'dokter', 'admin'
            'doctor_id' => $doctorId ? (int) $doctorId : null,
            'patient_id' => $patientId ? (int) $patientId : null,
            'account_status' => $row['account_status']
        ];

        return self::$currentUser;
    }

    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        $req = new Request();
        return self::authenticate($req);
    }

    public static function getCurrentSession(): ?array
    {
        return self::$currentSession;
    }

    public static function getRawCsrfToken(): string
    {
        if (self::$currentRawCsrf) {
            return self::$currentRawCsrf;
        }
        if (!empty($_SESSION['raw_csrf_token'])) {
            return $_SESSION['raw_csrf_token'];
        }
        return '';
    }

    public static function formatSessionPayload(array $user, ?array $doctor = null, ?array $patient = null, ?string $rawCsrf = null): array
    {
        $role = $user['role'];
        $displayRole = self::displayRole($role);

        $subtitle = match ($role) {
            'admin' => 'Kendali penuh sistem',
            'doctor' => 'Dokter Praktik',
            'patient' => 'Pasien terdaftar',
            default => 'Pengguna'
        };

        return [
            'user_id' => (int) $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $displayRole, // Provide 'pasien' / 'dokter' / 'admin' for frontend compatibility
            'raw_role' => $role,
            'subtitle' => $subtitle,
            'doctor_id' => $doctor ? (int) $doctor['doctor_id'] : null,
            'patient_id' => $patient ? (int) $patient['patient_id'] : null,
            'authorized' => true,
            'csrf_token' => $rawCsrf ?: self::getRawCsrfToken(),
            'expires_at' => (time() + 7200) * 1000,
        ];
    }

    public static function requireAuth(): array
    {
        $user = self::user();
        if (!$user) {
            Response::error(401, 'Silakan masuk terlebih dahulu.');
        }
        return $user;
    }

    public static function requireRole(string|array $allowedRoles): array
    {
        $user = self::requireAuth();
        $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

        // Normalize roles for comparison
        $normalizedAllowed = array_map([self::class, 'normalizeRole'], $allowed);
        $userRoleNormalized = self::normalizeRole($user['role']);

        if (!in_array($userRoleNormalized, $normalizedAllowed, true)) {
            AuditLogger::log(
                $user['user_id'],
                'ACCESS_DENIED',
                'route',
                null,
                'failed',
                'Attempted to access role-restricted endpoint'
            );
            Response::error(403, 'Anda tidak memiliki hak akses untuk tindakan ini.');
        }

        return $user;
    }

    public static function logout(): void
    {
        self::startSession();

        $rawToken = $_COOKIE['RS_SESSION_TOKEN'] ?? ($_SESSION['raw_session_token'] ?? null);
        if ($rawToken) {
            $hashed = hash('sha256', (string) $rawToken);
            $pdo = Database::getConnection();
            $pdo->prepare("UPDATE sessions SET revoked_at = :now WHERE session_id = :sid")
                ->execute([':now' => date('Y-m-d H:i:s'), ':sid' => $hashed]);
        }

        if (!headers_sent()) {
            setcookie('RS_SESSION_TOKEN', '', time() - 42000, '/');
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
        }

        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }

        self::$currentUser = null;
        self::$currentSession = null;
    }

    public static function hashPassword(string $password): string
    {
        $config = require __DIR__ . '/../config/security.php';
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $config['auth']['bcrypt_cost']]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
