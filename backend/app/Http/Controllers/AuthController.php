<?php

namespace App\Http\Controllers;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $data['email'] = Str::lower(trim($data['email']));
        $data['name'] = trim($data['name']);

        $user = User::create($data);
        [$plainTextToken, $token] = $this->issueToken($user, $request);

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => $this->serializeUser($user),
            'expires_at' => $token->expires_at?->toIso8601String(),
        ], 201)->cookie($this->authCookie($plainTextToken));
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        [$plainTextToken, $token] = $this->issueToken($user, $request);

        return response()->json([
            'message' => 'Signed in successfully.',
            'user' => $this->serializeUser($user),
            'expires_at' => $token->expires_at?->toIso8601String(),
        ])->cookie($this->authCookie($plainTextToken));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['required', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'delivery_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['name'] = trim($data['name']);
        $data['email'] = Str::lower(trim($data['email']));
        $data['phone'] = trim($data['phone']);
        $data['city'] = trim($data['city']);
        $data['shipping_address'] = trim($data['shipping_address']);
        $data['delivery_note'] = isset($data['delivery_note']) ? trim($data['delivery_note']) : null;

        $user->update($data);

        return response()->json([
            'message' => 'Saved.',
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('auth_token')?->delete();

        return response()->json([
            'message' => 'Signed out successfully.',
        ])->withoutCookie($this->authCookieName(), '/', null);
    }

    public function googleRedirect(): RedirectResponse
    {
        if (! $this->googleOauthConfigured()) {
            return redirect()->away($this->frontendUrl('/sign-in?oauth_error=google_not_configured'));
        }

        return Socialite::driver('google')
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            Log::error('Google OAuth callback failed.', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'error' => $request->query('error'),
                'error_description' => $request->query('error_description'),
                'state' => $request->query('state'),
            ]);

            return redirect()->away($this->frontendUrl('/sign-in?oauth_error=google_auth_failed'));
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                'email' => Str::lower($googleUser->getEmail()),
                'password' => Str::random(32),
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->fill([
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
            ]);

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            if (! $user->name && ($googleUser->getName() || $googleUser->getNickname())) {
                $user->name = $googleUser->getName() ?: $googleUser->getNickname();
            }

            $user->save();
        }

        [$plainTextToken] = $this->issueToken($user, $request);

        return redirect()
            ->away($this->frontendUrl('/google-auth/callback'))
            ->withCookie($this->authCookie($plainTextToken));
    }

    /**
     * @return array{0: string, 1: \App\Models\AuthToken}
     */
    protected function issueToken(User $user, Request $request): array
    {
        $user->authTokens()->delete();
        AuthToken::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $plainTextToken = Str::random(80);
        $ttlMinutes = max(60, (int) env('AUTH_TOKEN_TTL_MINUTES', 60 * 24 * 7));

        $token = AuthToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'last_used_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        return [$plainTextToken, $token];
    }

    protected function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'city' => $user->city,
            'shipping_address' => $user->shipping_address,
            'delivery_note' => $user->delivery_note,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'google_avatar' => $user->google_avatar,
            'is_admin' => $user->is_admin,
        ];
    }

    protected function frontendUrl(string $path): string
    {
        return rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/').$path;
    }

    protected function googleOauthConfigured(): bool
    {
        return filled(Config::get('services.google.client_id'))
            && filled(Config::get('services.google.client_secret'))
            && filled(Config::get('services.google.redirect'));
    }

    protected function authCookie(string $plainTextToken): \Symfony\Component\HttpFoundation\Cookie
    {
        $ttlMinutes = max(60, (int) env('AUTH_TOKEN_TTL_MINUTES', 60 * 24 * 7));
        $isSecureCookie = filter_var(
            env('AUTH_COOKIE_SECURE', ! app()->environment('local')),
            FILTER_VALIDATE_BOOL
        );

        return Cookie::make(
            $this->authCookieName(),
            $plainTextToken,
            $ttlMinutes,
            '/',
            null,
            $isSecureCookie,
            true,
            false,
            env('AUTH_COOKIE_SAME_SITE', 'lax')
        );
    }

    protected function authCookieName(): string
    {
        $configuredName = env('AUTH_COOKIE_NAME');

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
