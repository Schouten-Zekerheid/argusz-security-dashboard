<?php

namespace Tests\Unit;

use App\Services\OidcService;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class OidcServiceTest extends TestCase
{
    public function test_throws_when_token_is_invalid_jwt(): void
    {
        $jwksUrl = 'https://token.actions.githubusercontent.com/.well-known/jwks';

        Http::fake([
            $jwksUrl => Http::response([
                'keys' => [],
            ]),
        ]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid OIDC token');

        (new OidcService)->validate('this.is.not.a.valid.jwt');
    }

    public function test_throws_when_jwks_request_fails(): void
    {
        $jwksUrl = 'https://token.actions.githubusercontent.com/.well-known/jwks';

        Http::fake([
            $jwksUrl => Http::response(null, 500),
        ]);

        $this->expectException(UnauthorizedHttpException::class);

        (new OidcService)->validate('some.jwt.token');
    }
}
