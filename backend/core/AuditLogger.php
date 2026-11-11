<?php
/**
 * Audit Logger (COMP6695 Secure Programming)
 * Target Table: audit_logs (medical_platform)
 */

namespace Backend\Core;

use Throwable;

class AuditLogger
{
    public static function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        string $result = 'success',
        ?string $description = null,
        ?string $ip = null
    ): void {
        try {
            $pdo = Database::getConnection();
            $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

            // Sanitize description: Never log medical content
            $safeDesc = null;
            if ($description !== null) {
                // Strip potential medical or password information
                $safeDesc = mb_substr(strip_tags($description), 0, 500);
            }

            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, result, description, ip_address, created_at)
                VALUES (:uid, :action, :type, :eid, :result, :desc, :ip, datetime('now'))
            ");

            if (Database::getDriver() === 'mysql') {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, result, description, ip_address, created_at)
                    VALUES (:uid, :action, :type, :eid, :result, :desc, :ip, NOW())
                ");
            }

            $stmt->execute([
                ':uid' => $userId,
                ':action' => strtoupper($action),
                ':type' => $entityType,
                ':eid' => $entityId,
                ':result' => ($result === 'failed' || $result === 'failure') ? 'failed' : 'success',
                ':desc' => $safeDesc,
                ':ip' => $ip
            ]);
        } catch (Throwable $e) {
            $logDir = __DIR__ . '/../storage';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $line = sprintf(
                "[%s] AUDIT: UID=%s | Action=%s | Entity=%s#%s | Res=%s | IP=%s | Err=%s\n",
                date('Y-m-d H:i:s'),
                $userId ?? 'NULL',
                $action,
                $entityType,
                $entityId ?? 'NULL',
                $result,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $e->getMessage()
            );
            @file_put_contents($logDir . '/app.log', $line, FILE_APPEND);
        }
    }
}
