<?php
/**
 * Input Validation & Sanitization (COMP6695 Secure Programming)
 */

namespace Backend\Core;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $ruleParam = null;

                if (str_contains($rule, ':')) {
                    [$ruleName, $ruleParam] = explode(':', $rule, 2);
                }

                $this->applyRule($field, $value, $ruleName, $ruleParam, $data);
                
                // If required fails, skip subsequent rules for this field
                if ($ruleName === 'required' && isset($this->errors[$field])) {
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param, array $allData): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "Kolom $field wajib diisi.");
                }
                break;

            case 'email':
                if ($value !== null && $value !== '') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 191) {
                        $this->addError($field, "Format email tidak valid.");
                    }
                }
                break;

            case 'min':
                $min = (int) $param;
                if ($value !== null && strlen((string) $value) < $min) {
                    $this->addError($field, "Kolom $field minimal $min karakter.");
                }
                break;

            case 'max':
                $max = (int) $param;
                if ($value !== null && strlen((string) $value) > $max) {
                    $this->addError($field, "Kolom $field maksimal $max karakter.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if ($value !== null && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, "Nilai pada $field tidak valid.");
                }
                break;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, "Kolom $field harus berupa angka.");
                }
                break;

            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "Kolom $field harus berupa bilangan bulat.");
                }
                break;

            case 'date':
                if ($value !== null && $value !== '') {
                    $d = \DateTime::createFromFormat('Y-m-d', (string) $value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        $this->addError($field, "Format tanggal harus YYYY-MM-DD.");
                    }
                }
                break;

            case 'time':
                if ($value !== null && $value !== '') {
                    if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', (string) $value)) {
                        $this->addError($field, "Format jam harus HH:MM (24 jam).");
                    }
                }
                break;

            case 'same':
                $otherVal = $allData[$param] ?? null;
                if ($value !== $otherVal) {
                    $this->addError($field, "Kolom $field harus sama dengan $param.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): string
    {
        return reset($this->errors) ?: 'Validasi data gagal.';
    }

    /**
     * Context-aware XSS escaping helper
     */
    public static function escape(?string $str): string
    {
        if ($str === null) {
            return '';
        }
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitizes raw string input (stripping null-bytes, control characters)
     */
    public static function sanitize(?string $str): string
    {
        if ($str === null) {
            return '';
        }
        // Remove null-bytes and carriage-return injection
        $str = str_replace(["\0", "\r"], '', $str);
        return trim($str);
    }
}
