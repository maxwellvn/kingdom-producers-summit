<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Guards every address the server is asked to fetch.
 *
 * Without this, anything that takes a URL and requests it can be pointed at
 * the machine itself or the private network behind it.
 */
final class SafeUrl
{
    /** Nothing but ordinary web traffic. */
    private const SCHEMES = ['http', 'https'];

    /** Cloud metadata services, which hand out credentials to anything local. */
    private const BLOCKED_HOSTS = ['metadata.google.internal', 'metadata.goog'];

    public static function isPublicHttp(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }
        if (!in_array(strtolower($parts['scheme']), self::SCHEMES, true)) {
            return false;
        }

        $host = strtolower($parts['host']);
        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            return false;
        }

        foreach (self::addressesFor($host) as $ip) {
            if (!self::isPublicAddress($ip)) {
                return false;
            }
        }

        return self::addressesFor($host) !== [];
    }

    /** @return string[] */
    private static function addressesFor(string $host): array
    {
        // A literal address needs no lookup.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $addresses = [];
        foreach ($records as $record) {
            $addresses[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }
        $addresses = array_values(array_filter($addresses));

        if ($addresses === []) {
            $resolved = gethostbyname($host);
            if ($resolved !== $host) {
                $addresses[] = $resolved;
            }
        }

        return $addresses;
    }

    private static function isPublicAddress(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /** curl options that keep a request on the public web. */
    public static function curlGuards(): array
    {
        $options = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ];

        // Refuse file://, gopher:// and the rest, on the request and on redirects.
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'http,https';
            $options[CURLOPT_REDIR_PROTOCOLS_STR] = 'http,https';
        } elseif (defined('CURLPROTO_HTTP')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        return $options;
    }
}
