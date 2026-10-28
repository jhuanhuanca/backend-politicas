<?php

namespace App\Domain\Moderation\Services;

class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->encode(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $account): string
    {
        $label = rawurlencode('Política:'.$account);

        return 'otpauth://totp/'.$label.'?secret='.$secret.'&issuer='.rawurlencode('Política').'&digits=6&period=30';
    }

    public function code(string $secret, ?int $timestamp = null): string
    {
        return $this->codeForStep($secret, $this->step($timestamp));
    }

    public function match(string $secret, string $code, ?int $lastStep = null, ?int $timestamp = null): ?int
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $current = $this->step($timestamp);

        foreach ([$current - 1, $current, $current + 1] as $step) {
            if ($step < 0 || ($lastStep !== null && $step <= $lastStep)) {
                continue;
            }

            if (hash_equals($this->codeForStep($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    private function step(?int $timestamp): int
    {
        return intdiv($timestamp ?? time(), 30);
    }

    private function codeForStep(string $secret, int $step): string
    {
        $counter = pack('N*', 0, $step);
        $hash = hash_hmac('sha1', $counter, $this->decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function encode(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';

        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $secret;
    }

    private function decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/\s+/', '', $secret) ?? '');
        $bits = '';

        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                continue;
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8) as $chunk) {
            $bytes .= chr(bindec($chunk));
        }

        return $bytes;
    }
}
