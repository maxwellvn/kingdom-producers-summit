<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    private static string $base = '';

    /**
     * Detect the base path the app is mounted at (e.g. /gsap-site/producers/producers)
     * so links work whether the app lives at the domain root or a sub-folder.
     */
    public static function boot(): void
    {
        $configured = (string) Config::get('app.url', '');
        if ($configured !== '') {
            $path = parse_url($configured, PHP_URL_PATH) ?: '';
            self::$base = rtrim($path, '/');
            return;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        $dir = preg_replace('#/public$#', '', $dir) ?? $dir;
        self::$base = rtrim($dir, '/');
    }

    public static function base(): string
    {
        return self::$base;
    }

    public static function to(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return self::$base . '/' . $path;
    }

    /** Current request path relative to the app base, e.g. "/register". */
    public static function currentPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (self::$base !== '' && str_starts_with($uri, self::$base)) {
            $uri = substr($uri, strlen(self::$base));
        }
        $uri = preg_replace('#^/public#', '', $uri) ?? $uri;
        $uri = '/' . trim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }
}
