<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status = 200,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return (new self($body, $status))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(array $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        return (new self($json, $status))->header('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return (new self('', $status))->header('Location', $to);
    }

    public static function download(string $body, string $filename, string $mime = 'text/csv'): self
    {
        return (new self($body))
            ->header('Content-Type', $mime . '; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
