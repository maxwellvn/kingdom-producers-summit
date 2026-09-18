<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\Registration;
use App\Models\StreamEvent;

/**
 * Pretend to be a crowd. Each simulated viewer registers, signs in at the
 * gate with its own session, then does what the real player does: refreshes
 * the playlist and fetches a segment every six seconds, polls comments every
 * seven, heartbeats every twenty-five. Progress is written to a file the
 * admin page reads, and everything created is removed at the end.
 */
final class LoadTester
{
    public const MAX_VIEWERS = 500;
    public const MAX_SECONDS = 300;

    /** Cookie jars live under storage, which every user the site runs as can write. */
    private static function jarDir(): string
    {
        $dir = BASE_PATH . '/storage/loadtest';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }

        return $dir;
    }

    private static function stateFile(): string
    {
        return BASE_PATH . '/storage/loadtest.json';
    }

    public static function state(): ?array
    {
        $raw = @file_get_contents(self::stateFile());
        $state = $raw === false ? null : json_decode($raw, true);

        return is_array($state) ? $state : null;
    }

    public static function running(): bool
    {
        $state = self::state();
        if ($state === null || ($state['phase'] ?? 'done') === 'done') {
            return false;
        }
        // Still "starting" with no runner after fifteen seconds means it never came up.
        if (($state['phase'] ?? '') === 'starting' && empty($state['pid']) && time() - (int) ($state['started_at'] ?? 0) > 15) {
            $log = @file_get_contents(BASE_PATH . '/storage/loadtest.log');
            self::update(['phase' => 'done', 'error' => 'The runner did not start. ' . ($log ? 'It said: ' . trim(substr((string) $log, 0, 300)) : 'Nothing was written to storage/loadtest.log.')]);
            return false;
        }
        // A runner that died leaves a stale file; treat anything quiet for a minute as finished.
        return time() - (int) ($state['updated_at'] ?? 0) < 60;
    }

    /** Start the runner in the background and return at once. */
    public static function start(int $viewers, int $seconds): void
    {
        $viewers = max(1, min(self::MAX_VIEWERS, $viewers));
        $seconds = max(10, min(self::MAX_SECONDS, $seconds));
        self::cleanup(); // leftovers from any earlier run that died
        self::write(['phase' => 'starting', 'viewers' => $viewers, 'seconds' => $seconds, 'signed_in' => 0,
            'started_at' => time(), 'updated_at' => time(), 'log' => []]);

        $php = self::cli();
        if ($php === null) {
            self::update(['phase' => 'done', 'error' => 'The PHP command-line binary could not be found on this server, so the runner cannot be started.']);
            return;
        }
        if (!function_exists('exec')) {
            self::update(['phase' => 'done', 'error' => 'exec() is disabled on this server, so the runner cannot be started in the background.']);
            return;
        }

        $script = BASE_PATH . '/bin/load-test.php';
        $log = BASE_PATH . '/storage/loadtest.log';
        // A detached subshell with no terminal attached: survives the web request ending.
        $cmd = sprintf('(%s %s %d %d < /dev/null > %s 2>&1 &)', escapeshellarg($php), escapeshellarg($script), $viewers, $seconds, escapeshellarg($log));
        exec($cmd);
        self::update(['command' => $cmd]);
    }

    /**
     * The php command-line binary. Under Apache PHP_BINARY is empty, so look
     * beside the running build, then on the PATH, then the usual places.
     */
    private static function cli(): ?string
    {
        $candidates = [PHP_BINARY, PHP_BINDIR . '/php', trim((string) @shell_exec('command -v php 2>/dev/null')),
            '/usr/local/bin/php', '/usr/bin/php', '/opt/homebrew/bin/php'];
        foreach ($candidates as $c) {
            if ($c !== '' && is_executable($c) && !str_contains($c, 'httpd') && !str_contains($c, 'apache')) {
                return $c;
            }
        }

        return null;
    }

    public static function stop(): void
    {
        $state = self::state();
        if ($state !== null && !empty($state['pid'])) {
            if (function_exists('posix_kill')) {
                @posix_kill((int) $state['pid'], 15);
            } else {
                @exec('kill ' . (int) $state['pid'] . ' 2>/dev/null');
            }
        }
        self::cleanup();
        self::write($state ?? [], ['phase' => 'done', 'stopped' => true]);
    }

    private static function write(array $state, array $over = []): void
    {
        $state = array_merge($state, $over, ['updated_at' => time()]);
        $file = self::stateFile();
        $fresh = !file_exists($file);
        if (@file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX) === false) {
            error_log('Load test state could not be written to ' . $file);
            return;
        }
        if ($fresh) {
            @chmod($file, 0666); // the web server and the command line may run as different users
        }
    }

    private static function update(array $over): void
    {
        self::write(self::state() ?? [], $over);
    }

    /** Stop with an error: clean up and tell the page why. */
    public static function fail(string $why): void
    {
        StreamEvent::log('loadtest', 'Load test failed: ' . mb_substr($why, 0, 200));
        self::cleanup();
        self::update(['phase' => 'done', 'error' => $why]);
    }

    /** Remove every simulated viewer and its pass. */
    public static function cleanup(): void
    {
        $pdo = Database::connection();
        $pdo->exec("DELETE FROM watch_passes WHERE reference IN (SELECT reference FROM registrations WHERE email LIKE 'load-%@loadtest.invalid')");
        $pdo->exec("DELETE FROM presence WHERE reference IN (SELECT reference FROM registrations WHERE email LIKE 'load-%@loadtest.invalid')");
        // Anything a crashed run left behind: passes and presence with no registration.
        $pdo->exec("DELETE FROM watch_passes WHERE reference NOT IN (SELECT reference FROM registrations)");
        $pdo->exec("DELETE FROM presence WHERE reference IS NOT NULL AND reference <> '' AND reference NOT IN (SELECT reference FROM registrations)");
        $pdo->exec("DELETE FROM comments WHERE reference IN (SELECT reference FROM registrations WHERE email LIKE 'load-%@loadtest.invalid')");
        $pdo->exec("DELETE FROM registrations WHERE email LIKE 'load-%@loadtest.invalid'");
        foreach (glob(self::jarDir() . '/*.jar') ?: [] as $jar) {
            @unlink($jar);
        }
    }

    /** The whole run. Called by bin/load-test.php, never from a web request. */
    public static function run(int $viewers, int $seconds): void
    {
        $base = rtrim(site_url(), '/');
        self::update(['phase' => 'registering', 'pid' => getmypid(), 'base' => $base]);

        // Registrations.
        $svc = new RegistrationService();
        $emails = [];
        for ($i = 0; $i < $viewers; $i++) {
            $email = "load-{$i}-" . bin2hex(random_bytes(3)) . '@loadtest.invalid';
            $r = new Request('POST', '/register', [], ['participation' => 'online', 'first_name' => 'Load', 'last_name' => "Viewer{$i}",
                'email' => $email, 'phone' => '+447700900160', 'country' => 'United Kingdom', 'age_band' => '25-34', 'zone' => 'Load test',
                'field' => Registration::FIELDS[0], 'producer_stage' => 'build', 'consent_terms' => '1'], []);
            [$errors, $clean] = $svc->validate($r);
            if ($errors) {
                self::update(['phase' => 'done', 'error' => 'Could not register a test viewer: ' . json_encode($errors)]);
                return;
            }
            Registration::create($clean);
            $emails[] = $email;
        }

        // Sign-in.
        self::update(['phase' => 'signing_in']);
        $jars = []; $csrf = []; $signedIn = 0;
        foreach ($emails as $i => $email) {
            $jar = self::jarDir() . "/viewer-{$i}-" . getmypid() . '.jar';
            @unlink($jar); $jars[$i] = $jar;
            [, $gate] = self::http("$base/watch", $jar);
            preg_match('/name="_token" value="([^"]+)"/', $gate, $m);
            self::http("$base/watch", $jar, ['_token' => $m[1] ?? '', 'identifier' => $email]);
            [, $player] = self::http("$base/watch", $jar);
            if (str_contains($player, 'data-watch')) {
                $signedIn++;
                preg_match('/name="_token" value="([^"]+)"/', $player, $m);
                $csrf[$i] = $m[1] ?? '';
            } else {
                unset($jars[$i]);
            }
            if ($i % 10 === 0) {
                self::update(['signed_in' => $signedIn]);
            }
        }
        self::update(['signed_in' => $signedIn]);
        StreamEvent::log('loadtest', "Load test: {$signedIn} of {$viewers} simulated viewers signed in");
        if ($signedIn === 0) {
            self::cleanup();
            self::update(['phase' => 'done', 'error' => 'No test viewer could sign in. Is the stream switched on?']);
            return;
        }

        // What the player would fetch.
        $first = array_key_first($jars);
        [, $src] = self::http("$base/watch/source", $jars[$first], null, ['Accept: application/json']);
        $source = json_decode($src, true) ?: [];
        $kind = (string) ($source['kind'] ?? '');
        $origin = preg_replace('#^(https?://[^/]+).*#', '$1', $base);
        $abs = static fn (string $u) => str_starts_with($u, 'http') ? $u : $origin . $u;
        $segUrls = []; $variantUrl = '';
        $proxied = $kind === 'hls' && str_contains((string) ($source['source'] ?? ''), '/watch/hls');
        if ($proxied) {
            $playlistUrl = $abs((string) $source['source']);
            [, $master] = self::http($playlistUrl, $jars[$first], null, ['Accept: */*']);
            preg_match('/^(?!#)(\S+\.m3u8\S*)$/m', $master, $m);
            $variantUrl = isset($m[1]) ? $abs($m[1]) : $playlistUrl;
            [, $media] = self::http($variantUrl, $jars[$first], null, ['Accept: */*']);
            preg_match_all('/^(?!#)(\S+\.(?:ts|m4s|mp4)\S*)$/m', $media, $segs);
            $segUrls = array_map($abs, $segs[1] ?? []);
        }
        self::update(['phase' => 'running', 'kind' => $kind ?: 'off', 'proxied' => $proxied, 'segments_seen' => count($segUrls),
            'note' => $proxied ? 'Video passes through this server: every segment counts.'
                : ($kind === '' ? 'Stream is off: only heartbeats and comment polls are measured.'
                    : 'Video comes from the provider, not this server: only heartbeats and comment polls are measured.')]);

        // The run.
        $mh = curl_multi_init(); $handles = []; $stats = []; $inflight = 0;
        $now = microtime(true); $end = $now + $seconds; $lastWrite = 0;
        $next = []; $segIdx = [];
        foreach ($jars as $i => $_) {
            $next[$i] = ['playlist' => $now + mt_rand(0, 6000) / 1000, 'segment' => $now + mt_rand(0, 6000) / 1000,
                'comments' => $now + mt_rand(0, 7000) / 1000, 'beat' => $now + mt_rand(0, 25000) / 1000];
            $segIdx[$i] = 0;
        }
        $add = function (int $i, string $what, string $url, ?array $post = null) use (&$mh, &$handles, $jars, &$inflight, $csrf): void {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEJAR => $jars[$i], CURLOPT_COOKIEFILE => $jars[$i],
                CURLOPT_HTTPHEADER => ['Accept: */*', 'X-Requested-With: fetch']]);
            if ($post !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post + ['_token' => $csrf[$i] ?? '']));
            }
            $handles[(int) $ch] = ['what' => $what, 'start' => microtime(true)];
            curl_multi_add_handle($mh, $ch); $inflight++;
        };
        while (microtime(true) < $end || $inflight > 0) {
            $t = microtime(true);
            if ($t < $end) {
                foreach ($next as $i => $sched) {
                    if ($proxied && $t >= $sched['playlist']) { $add($i, 'playlist', $variantUrl); $next[$i]['playlist'] = $t + 6; }
                    if ($proxied && $segUrls && $t >= $sched['segment']) { $add($i, 'segment', $segUrls[$segIdx[$i] % count($segUrls)]); $segIdx[$i]++; $next[$i]['segment'] = $t + 6; }
                    if ($t >= $sched['comments']) { $add($i, 'comments', "$base/watch/comments?after=0"); $next[$i]['comments'] = $t + 7; }
                    if ($t >= $sched['beat']) { $add($i, 'beat', "$base/watch/beat", []); $next[$i]['beat'] = $t + 25; }
                }
            }
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.05);
            while ($info = curl_multi_info_read($mh)) {
                $ch = $info['handle']; $h = $handles[(int) $ch];
                $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                $stats[$h['what']][] = [(microtime(true) - $h['start']) * 1000, $status, (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD), $info['result'] !== CURLE_OK];
                curl_multi_remove_handle($mh, $ch); curl_close($ch); unset($handles[(int) $ch]); $inflight--;
            }
            if ($t - $lastWrite > 2) {
                $lastWrite = $t;
                self::update(['elapsed' => (int) round($t - ($end - $seconds)), 'results' => self::summarise($stats, $t - ($end - $seconds)), 'server' => self::serverLoad()]);
            }
        }
        $elapsed = microtime(true) - ($end - $seconds);
        $final = self::summarise($stats, $elapsed);
        StreamEvent::log('loadtest', sprintf('Load test finished: %d requests, %s%% errors, %s MB/s, load %s', $final['total'], $final['error_pct'], $final['mbps'], self::serverLoad()['load1']));
        self::cleanup();
        foreach ($jars as $j) { @unlink($j); }
        self::update(['phase' => 'done', 'elapsed' => (int) round($elapsed), 'results' => self::summarise($stats, $elapsed), 'server' => self::serverLoad()]);
    }

    private static function summarise(array $stats, float $elapsed): array
    {
        $out = ['by' => [], 'total' => 0, 'errors' => 0, 'mb' => 0.0];
        foreach ($stats as $what => $rows) {
            $ms = array_column($rows, 0); sort($ms); $n = count($ms);
            $bad = count(array_filter($rows, static fn ($r) => $r[3] || $r[1] >= 400 || $r[1] === 0));
            $mb = array_sum(array_column($rows, 2)) / 1048576;
            $out['by'][$what] = ['count' => $n, 'errors' => $bad, 'p50' => (int) $ms[(int) ($n * .5)], 'p95' => (int) $ms[(int) min($n - 1, $n * .95)], 'max' => (int) $ms[$n - 1], 'mb' => round($mb, 1)];
            $out['total'] += $n; $out['errors'] += $bad; $out['mb'] += $mb;
        }
        $out['mb'] = round($out['mb'], 1);
        $out['rps'] = $elapsed > 0 ? round($out['total'] / $elapsed, 1) : 0;
        $out['mbps'] = $elapsed > 0 ? round($out['mb'] / $elapsed, 2) : 0;
        $out['error_pct'] = $out['total'] > 0 ? round(100 * $out['errors'] / $out['total'], 2) : 0;

        return $out;
    }

    /** What the box itself is doing, as far as PHP can see. */
    public static function serverLoad(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cores = (int) (@shell_exec('nproc 2>/dev/null') ?: @shell_exec('sysctl -n hw.ncpu 2>/dev/null') ?: 1);
        $mem = @file_get_contents('/proc/meminfo');
        $memUsedPct = null;
        if ($mem && preg_match('/MemTotal:\s+(\d+)/', $mem, $t) && preg_match('/MemAvailable:\s+(\d+)/', $mem, $a)) {
            $memUsedPct = (int) round(100 * (1 - (int) $a[1] / (int) $t[1]));
        }

        return ['load1' => round((float) $load[0], 2), 'cores' => max(1, $cores), 'mem_used_pct' => $memUsedPct];
    }

    private static function http(string $url, string $jar, ?array $post = null, array $headers = ['Accept: text/html']): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HTTPHEADER => $headers]);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = (string) curl_exec($ch); $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);

        return [$status, $body];
    }
}
