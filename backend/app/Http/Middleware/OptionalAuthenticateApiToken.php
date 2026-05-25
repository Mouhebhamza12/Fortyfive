<?php

namespace App\Http\Middleware;

use App\Models\AuthToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->cookie($this->authCookieName()) ?: $request->bearerToken();

        if (! $bearerToken) {
            return $next($request);
        }

        $token = AuthToken::query()
            ->where('token_hash', hash('sha256', $bearerToken))
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('user')
            ->first();

        if (! $token || ! $token->user) {
            return $next($request);
        }

        $currentUserAgent = mb_substr((string) $request->userAgent(), 0, 1000);

        if ($token->user_agent !== $currentUserAgent) {
            return $next($request);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('auth_token', $token);

        return $next($request);
    }

    protected function authCookieName(): string
    {
        $configuredName = Config::get('session.auth_cookie_name') ?: env('AUTH_COOKIE_NAME');

        if ($configuredName) {
            return $configuredName;
        }

        $isSecureCookie = filter_var(
            env('AUTH_COOKIE_SECURE', ! app()->environment('local')),
            FILTER_VALIDATE_BOOL
        );

        return $isSecureCookie ? '__Host-auth_session' : 'auth_token';
    }
}
