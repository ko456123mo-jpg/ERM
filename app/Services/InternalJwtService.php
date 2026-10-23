<?php

namespace App\Services;

use RuntimeException;

/** Issues short-lived, scoped service-to-service RS256 tokens for the tool gateway. */
class InternalJwtService
{
    public function issue(string $scope): string
    {
        $privateKeyPath = (string) config('services.fastapi.jwt_private_key_path', '');
        if ($privateKeyPath === '' || !is_file($privateKeyPath) || !is_readable($privateKeyPath)) {
            throw new RuntimeException('ERM_JWT_PRIVATE_KEY_PATH must reference a readable RSA private key file.');
        }
        $privatePem = file_get_contents($privateKeyPath);
        if ($privatePem === false) {
            throw new RuntimeException('Could not read the configured JWT private key.');
        }
        $privateKey = openssl_pkey_get_private($privatePem);
        if ($privateKey === false) {
            throw new RuntimeException('Could not load the configured JWT private key.');
        }
        $keyDetails = openssl_pkey_get_details($privateKey);
        if (($keyDetails['type'] ?? null) !== OPENSSL_KEYTYPE_RSA || ($keyDetails['bits'] ?? 0) < 2048) {
            throw new RuntimeException('The service JWT key must be RSA with at least 2048 bits.');
        }

        if (!preg_match('/^[a-z][a-z0-9:_-]{1,63}$/', $scope)) {
            throw new RuntimeException('Invalid internal JWT scope.');
        }

        $now = time();
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        $claims = [
            'iss' => (string) config('services.fastapi.jwt_issuer', 'erm-laravel'),
            'aud' => (string) config('services.fastapi.jwt_audience', 'erm-tool-gateway'),
            'sub' => 'erm-laravel-service',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 60,
            'jti' => bin2hex(random_bytes(16)),
            'scope' => $scope,
        ];

        $unsigned = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)) . '.'
            . $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        if (!openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the service JWT.');
        }

        return $unsigned . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
