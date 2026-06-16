<?php

namespace App\Http\Middleware;

use App\Services\OidcService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ValidateOidcToken
{
    public function __construct(
        private readonly OidcService $oidcService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            throw new UnauthorizedHttpException('Bearer', 'Unauthorized');
        }

        $this->oidcService->validate($token);

        return $next($request);
    }
}
