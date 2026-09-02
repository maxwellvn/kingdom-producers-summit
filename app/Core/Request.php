<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        return new self($method, Url::currentPath(), $_GET, $_POST, $_SERVER);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** Trimmed string input (arrays returned as-is). */
    public function str(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_string($value) ? trim($value) : $default;
    }

    /** @return string[] */
    public function list(string $key): array
    {
        $value = $this->input($key, []);
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn ($v) => is_string($v) ? trim($v) : null,
            $value
        ), static fn ($v) => $v !== null && $v !== ''));
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function wantsJson(): bool
    {
        return str_contains((string) ($this->server['HTTP_ACCEPT'] ?? ''), 'application/json')
            || strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
