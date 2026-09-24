<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class Mailer
{
    private function connect(): array
    {
        $cfg = config('app.mail');
        $host = (string) ($cfg['host'] ?? '');
        $port = (int) ($cfg['port'] ?? 465);
        $encryption = strtolower((string) ($cfg['encryption'] ?? 'ssl'));

        if ($host === '' || ($cfg['username'] ?? '') === '' || ($cfg['password'] ?? '') === '') {
            throw new RuntimeException('Mail transport is not configured.');
        }

        $transport = $encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $error, 15, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            throw new RuntimeException('Could not connect to the mail server.');
        }
        stream_set_timeout($socket, 15);

        $this->expect($socket, [220]);
        $this->command($socket, 'EHLO producers-summit', [250]);
        if ($encryption === 'tls') {
            $this->command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not establish encrypted mail transport.');
            }
            $this->command($socket, 'EHLO producers-summit', [250]);
        }
        $this->command($socket, 'AUTH LOGIN', [334]);
        $this->command($socket, base64_encode((string) $cfg['username']), [334]);
        $this->command($socket, base64_encode((string) $cfg['password']), [235]);

        return [$socket, $host];
    }

    /**
     * @param array<string,string> $headers
     * @param array<string,array{data:string,type:string}> $inlineImages keyed by content id
     */
    public function send(string $to, string $subject, string $html, string $text, array $headers = [], array $inlineImages = []): void
    {
        $cfg = config('app.mail');
        [$socket, $host] = $this->connect();

        try {
            $this->command($socket, 'MAIL FROM:<' . $this->address((string) $cfg['from']) . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $this->address($to) . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $boundary = '=_producers_' . bin2hex(random_bytes(12));
            $relatedBoundary = '=_producers_rel_' . bin2hex(random_bytes(12));
            $hasInline = $inlineImages !== [];

            $messageHeaders = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->mailbox((string) $cfg['from_name'], (string) $cfg['from']),
                'To: <' . $this->address($to) . '>',
                'Subject: ' . $this->encoded($subject),
                'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $host . '>',
                'MIME-Version: 1.0',
                'Content-Type: multipart/' . ($hasInline ? 'related' : 'alternative') . '; boundary="'
                    . ($hasInline ? $relatedBoundary : $boundary) . '"'
                    . ($hasInline ? '; type="multipart/alternative"' : ''),
            ];

            foreach ($headers as $name => $value) {
                $messageHeaders[] = $this->header($name) . ': ' . $this->header($value);
            }

            // The readable part is always text + HTML; inline images wrap it in multipart/related.
            $alternative = '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
                . quoted_printable_encode($text) . "\r\n"
                . '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
                . quoted_printable_encode($html) . "\r\n"
                . '--' . $boundary . "--\r\n";

            if (!$hasInline) {
                $body = implode("\r\n", $messageHeaders) . "\r\n\r\n" . $alternative;
            } else {
                $parts = '--' . $relatedBoundary . "\r\nContent-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n\r\n"
                    . $alternative;

                foreach ($inlineImages as $cid => $image) {
                    $parts .= '--' . $relatedBoundary . "\r\n"
                        . 'Content-Type: ' . $this->header((string) $image['type']) . "\r\n"
                        . "Content-Transfer-Encoding: base64\r\n"
                        . 'Content-ID: <' . $this->header((string) $cid) . ">\r\n"
                        . 'Content-Disposition: inline; filename="' . $this->header(str_contains((string) $cid, '.') ? (string) $cid : $cid . '.png') . "\"\r\n\r\n"
                        . chunk_split(base64_encode((string) $image['data']), 76, "\r\n");
                }

                $parts .= '--' . $relatedBoundary . "--\r\n";
                $body = implode("\r\n", $messageHeaders) . "\r\n\r\n" . $parts;
            }

            $body = preg_replace('/(?m)^\./', '..', $body) ?? $body;
            fwrite($socket, $body . ".\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket @param int[] $codes */
    private function command($socket, string $command, array $codes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $codes);
    }

    /** @param resource $socket @param int[] $codes */
    private function expect($socket, array $codes): void
    {
        $reply = '';
        do {
            $line = fgets($socket, 4096);
            if ($line === false) {
                throw new RuntimeException('The mail server closed the connection.');
            }
            $reply .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('Mail server replied: ' . trim(mb_substr($reply, 0, 160)));
        }
    }

    private function address(string $value): string
    {
        $value = trim(str_replace(["\r", "\n"], '', $value));
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }
        return $value;
    }

    private function mailbox(string $name, string $email): string
    {
        return $this->encoded($this->header($name)) . ' <' . $this->address($email) . '>';
    }

    private function encoded(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($this->header($value)) . '?=';
    }

    private function header(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
