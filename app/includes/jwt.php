<?php
// Mini implementation JWT (HS256) - evite une dependance Composer pour un
// besoin aussi simple. Pour un vrai projet de production, preferez la
// librairie "firebase/php-jwt" (voir composer.json a la racine de /api).

function jwt_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_base64url_decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret, int $ttlSeconds = 3600): string
{
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttlSeconds;

    $segments = [
        jwt_base64url_encode(json_encode($header)),
        jwt_base64url_encode(json_encode($payload)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
    $segments[] = jwt_base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Renvoie le payload decode en cas de succes, ou null si le token est
 * invalide, mal signe, ou expire.
 */
function jwt_decode(string $token, string $secret): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$headerB64, $payloadB64, $signatureB64] = $parts;

    $expectedSignature = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true);
    $actualSignature = jwt_base64url_decode($signatureB64);

    if (!hash_equals($expectedSignature, $actualSignature)) {
        return null;
    }

    $payload = json_decode(jwt_base64url_decode($payloadB64), true);
    if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}
