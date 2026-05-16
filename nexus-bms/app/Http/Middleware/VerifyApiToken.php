<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) env('IOT_API_TOKEN', '');
        $provided = (string) ($request->header('X-API-Token') ?? $request->bearerToken() ?? '');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
