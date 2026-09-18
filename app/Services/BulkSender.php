<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Registration;
use App\Models\StreamEvent;

/**
 * Sends passes or live links to a whole group in the background, over one
 * mail connection, writing progress to a file the admin page polls. The
 * click returns at once; nothing waits on the mail server.
 */
final class BulkSender
{
    private static function stateFile(): string
    {
        return BASE_PATH . '/storage/bulk-send.json';
    }

    public static function state(): ?array
    {
        $raw = @file_get_contents(self::stateFile());
        $state = $raw === false ? null : json_decode($raw, true);
        if (!is_array($state)) {
            return null;
        }
        // A finished run stays on the page for ten minutes, then the panel goes away by itself.
        if (($state['phase'] ?? '') === 'done' && time() - (int) ($state['finished_at'] ?? 0) > 600) {
            return null;
        }

        return $state;
    }

    public static function running(): bool
    {
        $state = self::state();
        if ($state === null || ($state['phase'] ?? 'done') === 'done') {
            return false;
        }
        // Quiet for three minutes means the runner died.
        return time() - (int) ($state['updated_at'] ?? 0) < 180;
    }

    /** Start in the background. Returns false if it could not be launched. */
    public static function start(string $what, string $audience, string $by): bool
    {
        $php = self::cli();
        if ($php === null || !function_exists('exec')) {
            return false;
        }
        self::write(['phase' => 'starting', 'what' => $what, 'audience' => $audience, 'by' => $by,
            'total' => 0, 'done' => 0, 'sent' => 0, 'failed' => 0, 'started_at' => time(), 'updated_at' => time()]);
        $script = BASE_PATH . '/bin/bulk-send.php';
        $log = BASE_PATH . '/storage/bulk-send.log';
        exec(sprintf('(%s %s %s %s < /dev/null > %s 2>&1 &)', escapeshellarg($php), escapeshellarg($script), escapeshellarg($what), escapeshellarg($audience), escapeshellarg($log)));

        return true;
    }

    /** The whole job. Run by bin/bulk-send.php, never from a web request. */
    public static function run(string $what, string $audience): void
    {
        $what = $what === 'pass' ? 'pass' : 'live';
        $people = Announcer::recipients($what === 'pass' ? 'onsite' : $audience);
        $state = ['phase' => 'running', 'what' => $what, 'audience' => $audience, 'pid' => getmypid(),
            'total' => count($people), 'done' => 0, 'sent' => 0, 'failed' => 0, 'failures' => [], 'started_at' => time()];
        self::write($state);

        $timing = Announcer::timing();
        $work = function () use (&$state, $people, $what, $timing): void {
            foreach ($people as $person) {
                $ok = false;
                try {
                    if ($what === 'pass') {
                        $full = Registration::findByReference((string) $person['reference']) ?? $person;
                        (new RegistrationMail())->sendPass($full, $timing);
                    } else {
                        [$ok] = Announcer::deliver($person, Announcer::LIVE_LINK['subject'], Announcer::LIVE_LINK['body'], true, false);
                        if (!$ok) {
                            throw new \RuntimeException('not accepted by the mail server');
                        }
                    }
                    $ok = true;
                } catch (\Throwable $e) {
                    $state['failures'][] = $person['email'] . ': ' . mb_substr($e->getMessage(), 0, 80);
                    error_log('Bulk ' . $what . ' to ' . $person['email'] . ' failed: ' . $e->getMessage());
                }
                $state['done']++;
                $ok ? $state['sent']++ : $state['failed']++;
                if ($state['done'] % 5 === 0 || $state['done'] === $state['total']) {
                    self::write($state);
                }
            }
        };
        // One connection for the batch. If the mail server refuses the connection, try person by person
        // so each failure is recorded against a name rather than the job vanishing.
        try {
            Mailer::batch($work);
        } catch (\Throwable $e) {
            $state['error'] = 'Mail server: ' . mb_substr($e->getMessage(), 0, 120);
            self::write($state);
            if ($state['done'] === 0) {
                $work();
            }
        }

        $state['phase'] = 'done';
        $state['finished_at'] = time();
        self::write($state);
        StreamEvent::log('stream', sprintf('%s emailed to %d of %d people (%s)', $what === 'pass' ? 'Passes' : 'Live links', $state['sent'], $state['total'], $audience));
    }

    public static function fail(string $why): void
    {
        $state = self::state() ?? [];
        $state['phase'] = 'done';
        $state['error'] = $why;
        self::write($state);
    }

    private static function write(array $state): void
    {
        $state['updated_at'] = time();
        $file = self::stateFile();
        $fresh = !file_exists($file);
        if (@file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX) !== false && $fresh) {
            @chmod($file, 0666);
        }
    }

    private static function cli(): ?string
    {
        foreach ([PHP_BINARY, PHP_BINDIR . '/php', trim((string) @shell_exec('command -v php 2>/dev/null')), '/usr/local/bin/php', '/usr/bin/php', '/opt/homebrew/bin/php'] as $c) {
            if ($c !== '' && is_executable($c) && !str_contains($c, 'httpd') && !str_contains($c, 'apache')) {
                return $c;
            }
        }

        return null;
    }
}
