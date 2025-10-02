<?php

declare(strict_types=1);

use Infrastructure\Security\JwtIssuerFirebase;

it('issues and verifies RS256 JWT', function () {
    $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($res, $privPem);
    $pubPem = openssl_pkey_get_details($res)['key'];

    $issuer = new JwtIssuerFirebase($privPem, $pubPem, 'kid1', 'vacation-api'); // :contentReference[oaicite:0]{index=0}
    $jwt = $issuer->issueAccessToken(['sub' => 'E1', 'role' => 100], 300);
    $claims = $issuer->parseAndVerify($jwt);

    expect($claims['sub'])->toBe('E1');
    expect($claims['role'])->toBe(100);
    expect($claims['iss'])->toBe('vacation-api');
});
