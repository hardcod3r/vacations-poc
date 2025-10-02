<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Application\Port\Security\JwtIssuer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtIssuerFirebase implements JwtIssuer
{
    public function __construct(
        private string $privateKeyPem,   // full PEM string
        private string $publicKeyPem,    // full PEM string
        private string $kid = 'k1',
        private string $issuer = 'vacation-api',
    ) {
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public function issueAccessToken(array $claims, int $ttlSeconds): string
    {
        $now = \time();
        $payload = $claims + [
            'iss' => $this->issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
        ];
        $headers = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $this->kid,
        ];

        return JWT::encode($payload, $this->privateKeyPem, 'RS256', null, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseAndVerify(string $jwt): array
    {
        $decoded = JWT::decode($jwt, new Key($this->publicKeyPem, 'RS256'));

        // cast stdClass -> array recursively
        /** @var array<string, mixed> $arr */
        $arr = \json_decode(\json_encode($decoded, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return $arr;
    }
}
