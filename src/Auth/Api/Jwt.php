<?php

namespace Whis\Auth\Api;

use InvalidArgumentException;
use RuntimeException;

class Jwt
{
    public static function issue(
        int|string $subject,
        array $claims = [],
        ?int $ttlSeconds = null,
        ?string $secret = null
    ): string {
        $now = time();

        $ttlSeconds ??= (int) (config('api.jwt.ttl') ?: 3600);
        $secret ??= self::secret();

        $payload = array_merge([
            'iss' => config('api.jwt.issuer') ?: config('app.url') ?: 'whis',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'sub' => (string) $subject,
        ], $claims);

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE)),
            self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE)),
        ];

        $segments[] = self::sign(implode('.', $segments), $secret);

        return implode('.', $segments);
    }

    public static function decode(
        string $jwt,
        ?string $secret = null,
        bool $verifyLifetime = true
    ): array {
        $secret ??= self::secret();

        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('JWT mal formado.');
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;

        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);

        if (! is_array($header) || ! is_array($payload)) {
            throw new InvalidArgumentException('JWT inválido.');
        }

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new InvalidArgumentException('Algoritmo JWT no soportado.');
        }

        $expected = self::sign($encodedHeader . '.' . $encodedPayload, $secret);

        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Firma JWT inválida.');
        }

        if ($verifyLifetime) {
            self::verifyLifetime($payload);
        }

        self::verifyIssuerAndAudience($payload);

        return $payload;
    }

    public static function secret(): string
    {
        $secret = (string) (
            config('api.jwt.secret')
            ?: config('app.key')
            ?: ($_ENV['JWT_SECRET'] ?? $_ENV['APP_KEY'] ?? getenv('JWT_SECRET') ?: getenv('APP_KEY'))
        );

        if (trim($secret) === '') {
            throw new RuntimeException('JWT_SECRET o APP_KEY no está configurado.');
        }

        return $secret;
    }

    protected static function verifyLifetime(array $payload): void
    {
        $now = time();
        $leeway = (int) (config('api.jwt.leeway') ?: 0);

        if (isset($payload['nbf']) && $now + $leeway < (int) $payload['nbf']) {
            throw new InvalidArgumentException('JWT aún no es válido.');
        }

        if (isset($payload['iat']) && $now + $leeway < (int) $payload['iat']) {
            throw new InvalidArgumentException('JWT emitido en el futuro.');
        }

        if (isset($payload['exp']) && $now - $leeway >= (int) $payload['exp']) {
            throw new InvalidArgumentException('JWT expirado.');
        }
    }

    protected static function verifyIssuerAndAudience(array $payload): void
    {
        $issuer = config('api.jwt.issuer');
        $audience = config('api.jwt.audience');

        if ($issuer && isset($payload['iss']) && ! hash_equals((string) $issuer, (string) $payload['iss'])) {
            throw new InvalidArgumentException('Issuer JWT inválido.');
        }

        if ($audience && isset($payload['aud'])) {
            $aud = $payload['aud'];

            $valid = is_array($aud)
                ? in_array($audience, $aud, true)
                : hash_equals((string) $audience, (string) $aud);

            if (! $valid) {
                throw new InvalidArgumentException('Audience JWT inválido.');
            }
        }
    }

    protected static function sign(string $value, string $secret): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $value, $secret, true));
    }

    protected static function base64UrlEncode(string|false $value): string
    {
        if ($value === false) {
            throw new RuntimeException('No se pudo codificar JWT.');
        }

        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('JWT contiene base64 inválido.');
        }

        return $decoded;
    }
}
