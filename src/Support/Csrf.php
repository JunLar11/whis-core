<?php

namespace Whis\Support;

class Csrf
{
    private const SESSION_KEY = '_csrf_tokens';
    private const DEFAULT_KEY = 'default';
    private const DEFAULT_TTL = 7200; // 2 horas
    private const MAX_TOKENS_PER_KEY = 25;

    public static function generate(string $key = self::DEFAULT_KEY, int $ttl = self::DEFAULT_TTL): string
    {
        $key = self::normalizeKey($key);
        $tokens = self::tokens();

        self::pruneExpired($tokens);

        $token = bin2hex(random_bytes(32));

        if (!isset($tokens[$key]) || !is_array($tokens[$key])) {
            $tokens[$key] = [];
        }

        $tokens[$key][$token] = time() + $ttl;
        $tokens[$key] = self::limitBucket($tokens[$key]);

        self::save($tokens);

        return $token;
    }

    public static function validate(
        string $key,
        ?string $token,
        bool $consume = true
    ): bool {
        $key = self::normalizeKey($key);
        $token = trim((string) $token);

        if ($token === '') {
            return false;
        }

        $tokens = self::tokens();
        self::pruneExpired($tokens);

        $bucket = $tokens[$key] ?? [];

        if (!is_array($bucket) || empty($bucket)) {
            self::save($tokens);
            return false;
        }

        $matchedToken = null;

        foreach ($bucket as $storedToken => $expiresAt) {
            if (hash_equals((string) $storedToken, $token)) {
                $matchedToken = (string) $storedToken;
                break;
            }
        }

        if ($matchedToken === null) {
            self::save($tokens);
            return false;
        }

        if ($consume) {
            unset($tokens[$key][$matchedToken]);
        }

        self::save($tokens);

        return true;
    }

    public static function field(string $key = self::DEFAULT_KEY, ?string $token = null): string
    {
        $key = self::normalizeKey($key);
        $token = $token ?: self::generate($key);

        return sprintf(
            '<input type="hidden" name="_csrf_key" value="%s">%s<input type="hidden" name="_token" value="%s">',
            self::e($key),
            PHP_EOL,
            self::e($token)
        );
    }

    public static function meta(string $key = self::DEFAULT_KEY, ?string $token = null): string
    {
        $key = self::normalizeKey($key);
        $token = $token ?: self::generate($key);

        return sprintf(
            '<meta name="csrf-key" content="%s">%s<meta name="csrf-token" content="%s">',
            self::e($key),
            PHP_EOL,
            self::e($token)
        );
    }

    public static function forgetKey(string $key): void
    {
        $key = self::normalizeKey($key);
        $tokens = self::tokens();

        unset($tokens[$key]);

        self::save($tokens);
    }

    public static function forgetAll(): void
    {
        session()->remove(self::SESSION_KEY);
        session()->remove('_token'); // compatibilidad con versión vieja
    }

    private static function tokens(): array
    {
        $tokens = session()->get(self::SESSION_KEY, []);

        return is_array($tokens) ? $tokens : [];
    }

    private static function save(array $tokens): void
    {
        session()->set(self::SESSION_KEY, $tokens);
    }

    private static function pruneExpired(array &$tokens): void
    {
        $now = time();

        foreach ($tokens as $key => $bucket) {
            if (!is_array($bucket)) {
                unset($tokens[$key]);
                continue;
            }

            foreach ($bucket as $token => $expiresAt) {
                if ((int) $expiresAt < $now) {
                    unset($tokens[$key][$token]);
                }
            }

            if (empty($tokens[$key])) {
                unset($tokens[$key]);
            }
        }
    }

    private static function limitBucket(array $bucket): array
    {
        arsort($bucket);

        return array_slice(
            $bucket,
            0,
            self::MAX_TOKENS_PER_KEY,
            true
        );
    }

    private static function normalizeKey(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            return self::DEFAULT_KEY;
        }

        $key = preg_replace('/[^a-zA-Z0-9_.:-]/', '', $key) ?? '';

        return $key !== '' ? $key : self::DEFAULT_KEY;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}