<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFrontendOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $allowedOrigins = $this->allowedOrigins();
        $origin = $request->headers->get('Origin');
        $referer = $request->headers->get('Referer');

        if ($origin && in_array(rtrim($origin, '/'), $allowedOrigins, true)) {
            return $next($request);
        }

        if ($referer) {
            $refererOrigin = $this->originFromUrl($referer);

            if ($refererOrigin && in_array($refererOrigin, $allowedOrigins, true)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Origin not allowed.',
        ], 403);
    }

    /**
     * @return list<string>
     */
    protected function allowedOrigins(): array
    {
        return array_values(array_filter(array_unique([
            rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/'),
            rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
        ])));
    }

    protected function originFromUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return rtrim($origin, '/');
    }
}
