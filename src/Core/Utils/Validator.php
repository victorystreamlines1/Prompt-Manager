<?php
/**
 * ============================================
 * Input Validator Utility
 * ============================================
 * 
 * PURPOSE:
 * Validates and sanitizes user input.
 * Provides common validation rules.
 * 
 * INPUTS:
 * - Data arrays to validate
 * - Validation rules
 * 
 * OUTPUTS:
 * - Validation results
 * - Sanitized data
 * 
 * SIDE EFFECTS:
 * - None (pure functions)
 * 
 * ============================================
 */

namespace App\Core\Utils;

class Validator
{
    private array $errors = [];
    private array $data = [];

    /**
     * Validate data against rules.
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if valid
     * 
     * Example rules:
     * [
     *     'name'  => 'required|string|min:3|max:100',
     *     'email' => 'required|email',
     *     'age'   => 'numeric|min:0|max:150',
     * ]
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        $this->data = $data;

        foreach ($rules as $field => $ruleString) {
            $fieldRules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply a single validation rule.
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule and parameter
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;

        // Skip other rules if value is empty and not required
        if (($value === null || $value === '') && $ruleName !== 'required') {
            return;
        }

        match($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'string'   => $this->validateString($field, $value),
            'numeric'  => $this->validateNumeric($field, $value),
            'integer'  => $this->validateInteger($field, $value),
            'email'    => $this->validateEmail($field, $value),
            'url'      => $this->validateUrl($field, $value),
            'min'      => $this->validateMin($field, $value, $param),
            'max'      => $this->validateMax($field, $value, $param),
            'between'  => $this->validateBetween($field, $value, $param),
            'in'       => $this->validateIn($field, $value, $param),
            'regex'    => $this->validateRegex($field, $value, $param),
            'alpha'    => $this->validateAlpha($field, $value),
            'alphaNum' => $this->validateAlphaNum($field, $value),
            'boolean'  => $this->validateBoolean($field, $value),
            'array'    => $this->validateArray($field, $value),
            'json'     => $this->validateJson($field, $value),
            default    => null, // Unknown rules are ignored
        };
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required");
        }
    }

    private function validateString(string $field, mixed $value): void
    {
        if (!is_string($value)) {
            $this->addError($field, "The {$field} field must be a string");
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, "The {$field} field must be numeric");
        }
    }

    private function validateInteger(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0 && $value !== '0') {
            $this->addError($field, "The {$field} field must be an integer");
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} field must be a valid email");
        }
    }

    private function validateUrl(string $field, mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$field} field must be a valid URL");
        }
    }

    private function validateMin(string $field, mixed $value, ?string $param): void
    {
        $min = (float) $param;
        
        if (is_string($value)) {
            if (strlen($value) < $min) {
                $this->addError($field, "The {$field} field must be at least {$min} characters");
            }
        } elseif (is_numeric($value)) {
            if ($value < $min) {
                $this->addError($field, "The {$field} field must be at least {$min}");
            }
        } elseif (is_array($value)) {
            if (count($value) < $min) {
                $this->addError($field, "The {$field} field must have at least {$min} items");
            }
        }
    }

    private function validateMax(string $field, mixed $value, ?string $param): void
    {
        $max = (float) $param;
        
        if (is_string($value)) {
            if (strlen($value) > $max) {
                $this->addError($field, "The {$field} field must not exceed {$max} characters");
            }
        } elseif (is_numeric($value)) {
            if ($value > $max) {
                $this->addError($field, "The {$field} field must not exceed {$max}");
            }
        } elseif (is_array($value)) {
            if (count($value) > $max) {
                $this->addError($field, "The {$field} field must not exceed {$max} items");
            }
        }
    }

    private function validateBetween(string $field, mixed $value, ?string $param): void
    {
        $parts = explode(',', $param);
        if (count($parts) !== 2) return;
        
        $min = (float) $parts[0];
        $max = (float) $parts[1];
        
        if (is_numeric($value) && ($value < $min || $value > $max)) {
            $this->addError($field, "The {$field} field must be between {$min} and {$max}");
        }
    }

    private function validateIn(string $field, mixed $value, ?string $param): void
    {
        $allowed = explode(',', $param);
        if (!in_array($value, $allowed)) {
            $this->addError($field, "The {$field} field must be one of: " . implode(', ', $allowed));
        }
    }

    private function validateRegex(string $field, mixed $value, ?string $param): void
    {
        if (!preg_match($param, $value)) {
            $this->addError($field, "The {$field} field format is invalid");
        }
    }

    private function validateAlpha(string $field, mixed $value): void
    {
        if (!ctype_alpha($value)) {
            $this->addError($field, "The {$field} field must contain only letters");
        }
    }

    private function validateAlphaNum(string $field, mixed $value): void
    {
        if (!ctype_alnum($value)) {
            $this->addError($field, "The {$field} field must contain only letters and numbers");
        }
    }

    private function validateBoolean(string $field, mixed $value): void
    {
        $valid = [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no'];
        if (!in_array($value, $valid, true)) {
            $this->addError($field, "The {$field} field must be true or false");
        }
    }

    private function validateArray(string $field, mixed $value): void
    {
        if (!is_array($value)) {
            $this->addError($field, "The {$field} field must be an array");
        }
    }

    private function validateJson(string $field, mixed $value): void
    {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, "The {$field} field must be valid JSON");
        }
    }

    /**
     * Add validation error.
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors.
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for each field.
     * 
     * @return array
     */
    public function getFirstErrors(): array
    {
        $first = [];
        foreach ($this->errors as $field => $messages) {
            $first[$field] = $messages[0] ?? '';
        }
        return $first;
    }

    /**
     * Check if validation passed.
     * 
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed.
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    // =========================================
    // STATIC SANITIZATION METHODS
    // =========================================

    /**
     * Sanitize string (trim and remove tags).
     */
    public static function sanitizeString(?string $value): string
    {
        return trim(strip_tags($value ?? ''));
    }

    /**
     * Sanitize for HTML output.
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize table/column name (SQL injection prevention).
     */
    public static function sanitizeName(?string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name ?? '');
    }

    /**
     * Sanitize integer.
     */
    public static function sanitizeInt(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float.
     */
    public static function sanitizeFloat(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize email.
     */
    public static function sanitizeEmail(?string $value): string
    {
        return filter_var($value ?? '', FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL.
     */
    public static function sanitizeUrl(?string $value): string
    {
        return filter_var($value ?? '', FILTER_SANITIZE_URL);
    }
}

