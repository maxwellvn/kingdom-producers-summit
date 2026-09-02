<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small declarative validator. Rules are pipe-separated strings:
 *   'name' => 'required|min:2|max:120'
 *   'email' => 'required|email|max:190'
 *   'mode' => 'required|in:onsite,online,initiative'
 *   'interests' => 'array|max_items:8|in:a,b,c'
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $labels = [],
    ) {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = array_filter(explode('|', $ruleString));
            $value = $this->data[$field] ?? null;
            $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $isArray = in_array('array', $rules, true);

            $present = $isArray
                ? (is_array($value) && $value !== [])
                : (is_string($value) && trim($value) !== '');

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                if ($name === 'required' && !$present) {
                    $this->errors[$field] = "{$label} is required.";
                    break;
                }

                if (!$present) {
                    continue;
                }

                $error = $this->check($name, $arg, $value, $label, $isArray);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break;
                }
            }
        }

        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function check(string $rule, ?string $arg, mixed $value, string $label, bool $isArray): ?string
    {
        switch ($rule) {
            case 'array':
                return is_array($value) ? null : "{$label} is invalid.";

            case 'max_items':
                return count((array) $value) <= (int) $arg ? null : "Choose at most {$arg} options for {$label}.";

            case 'min':
                return mb_strlen((string) $value) >= (int) $arg ? null : "{$label} must be at least {$arg} characters.";

            case 'max':
                return mb_strlen((string) $value) <= (int) $arg ? null : "{$label} must be {$arg} characters or fewer.";

            case 'email':
                return filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false
                    ? null : 'Enter a valid email address.';

            case 'phone':
                return preg_match('/^\+?[0-9 ()\-]{7,20}$/', (string) $value) === 1
                    ? null : 'Enter a valid phone number.';

            case 'in':
                $allowed = explode(',', (string) $arg);
                $values = $isArray ? (array) $value : [(string) $value];
                foreach ($values as $v) {
                    if (!in_array((string) $v, $allowed, true)) {
                        return "{$label} contains an invalid selection.";
                    }
                }
                return null;

            case 'accepted':
                return in_array((string) $value, ['1', 'on', 'yes', 'true'], true) ? null : "You must accept {$label}.";

            case 'url':
                return filter_var((string) $value, FILTER_VALIDATE_URL) !== false ? null : 'Enter a valid URL.';

            default:
                return null;
        }
    }
}
