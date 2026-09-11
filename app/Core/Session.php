<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        // Filling in the form or leaving to make a transfer both take a while,
        // so a 24-minute default would strand people mid-registration.
        $lifetime = 8 * 60 * 60;
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        // Sessions live in the database, so a redeploy does not wipe them and
        // every instance of the site sees the same ones. If the database is
        // unreachable the site cannot do much anyway, but fall back to files
        // in the app's own directory rather than the shared system temp dir,
        // where other applications' cleanup would delete them early.
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        $useDatabase = true;
        try {
            $pdo = Database::connection();
            // Until migration 021 has run there is no table to keep them in.
            $pdo->query('SELECT 1 FROM sessions LIMIT 1');
            session_set_save_handler(new DatabaseSessionHandler($pdo, $lifetime), true);
        } catch (\Throwable $e) {
            $useDatabase = false;
            error_log('Database sessions unavailable, using files: ' . $e->getMessage());
        }
        if (!$useDatabase) {
            $store = BASE_PATH . '/storage/sessions';
            if (!is_dir($store)) {
                @mkdir($store, 0700, true);
            }
            if (is_dir($store) && is_writable($store)) {
                session_save_path($store);
            }
        }

        session_name('producers_summit_session');

        // An external service returning with a cross-site POST sends no session
        // cookie. Starting a session there would issue a new one and overwrite
        // whatever the browser already held, signing the organiser out. Such a
        // request gets a scratch session that is never written back.
        if (self::isCookielessCallback()) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        }

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Rotate the session id periodically to limit fixation windows. The old
        // file is left for the garbage collector rather than deleted outright,
        // so a request already in flight with the previous id is not thrown out
        // mid-payment.
        $now = time();
        if (!isset($_SESSION['_rotated_at'])) {
            $_SESSION['_rotated_at'] = $now;
        } elseif ($now - $_SESSION['_rotated_at'] > 1800) {
            session_regenerate_id(false);
            $_SESSION['_rotated_at'] = $now;
        }

        // Age flash data: anything flashed on the previous request is available now, then gone.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    /** A callback from an external service, arriving without our session cookie. */
    private static function isCookielessCallback(): bool
    {
        if (isset($_COOKIE['producers_summit_session'])) {
            return false;
        }

        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';

        return str_ends_with(rtrim($path, '/'), '/admin/kingschat/callback');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_now'][$key] ?? $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION['_flash_now'][$key]) || isset($_SESSION[$key]);
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_next'][$key] = $value;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_rotated_at'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }
}
