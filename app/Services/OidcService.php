<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OidcService
{
    public function validate(string $token): void
    {
        try {
            $jwks = Http::get((string) config('security.oidc.jwks_url'))->json();
            $keys = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keys);
        } catch (\Throwable) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid OIDC token');
        }

        if ($decoded->iss !== config('security.oidc.issuer')) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token issuer');
        }

        $aud = is_array($decoded->aud) ? $decoded->aud : [$decoded->aud];
        if (! in_array(config('security.oidc.audience'), $aud)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token audience');
        }

        $allowedRepositories = config('security.oidc.allowed_repositories', []);
        if ($allowedRepositories === [] || ! in_array($decoded->repository, $allowedRepositories, true)) {
            throw new UnauthorizedHttpException('Bearer', 'Unauthorized workflow');
        }
    }
}
