<?php
/**
 * Response Handler with Security Headers
 */

namespace Backend\Core;

class ResponseException extends \Exception
{
    public int $status;
    public array $payload;

    public function __construct(int $status, string $message, array $payload = [])
    {
        parent::__construct($message, $status);
        $this->status = $status;
        $this->payload = $payload;
    }
}

class Response
{
    public static bool $testMode = false;

    public static function applySecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        $sec = require __DIR__ . '/../config/security.php';
        foreach ($sec['headers'] as $header => $value) {
            header("$header: $value");
        }
    }

    public static function json(mixed $data, int $status = 200): void
    {
        self::applySecurityHeaders();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
        if (self::$testMode) {
            throw new ResponseException($status, is_array($data) ? ($data['message'] ?? 'OK') : 'OK', is_array($data) ? $data : []);
        }

        echo $json;
        exit;
    }

    public static function success(mixed $data = [], int $status = 200): void
    {
        if (is_array($data) && !isset($data['ok'])) {
            $data = array_merge(['ok' => true], $data);
        } elseif (!is_array($data)) {
            $data = ['ok' => true, 'data' => $data];
        }
        self::json($data, $status);
    }

    public static function error(int $status, string $message, mixed $detail = null): void
    {
        self::applySecurityHeaders();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        
        $payload = [
            'ok' => false,
            'status' => $status,
            'message' => $message,
        ];
        if ($detail !== null) {
            $payload['detail'] = $detail;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
        if (self::$testMode) {
            throw new ResponseException($status, $message, $payload);
        }

        echo $json;
        exit;
    }
}
