<?php

class JWT
{
    private static $algo = 'HS256';

    private static function getSecretKey(): string
    {
        // ✅ Only read, do not load
        $secret = $_ENV['JWT_SECRET_KEY'] ?? getenv('JWT_SECRET_KEY');
        if (!$secret) {
            throw new Exception('JWT_SECRET_KEY not found in environment');
        }
        return $secret;
    }

    public static function encode(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);
        // $payload['exp'] = time() + $expire_in_seconds;

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", self::getSecretKey(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    public static function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token structure');
        }

        list($header, $payload, $signature) = $parts;

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::getSecretKey(), true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid token signature');
        }

        if (isset($decodedPayload['exp']) && time() >= $decodedPayload['exp']) {
            throw new Exception('Token has expired');
        }

        return $decodedPayload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
