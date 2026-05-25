# Security Deployment Checklist

## Before deploy

- Rotate the Google OAuth client secret and replace `backend/.env`.
- Set `APP_ENV=production`.
- Keep `APP_DEBUG=false`.
- Set a real `APP_URL` for the backend.
- Set a real `FRONTEND_URL` for the frontend.
- Set `AUTH_COOKIE_SECURE=true`.
- Set `AUTH_COOKIE_SAME_SITE=lax` unless your frontend and backend are truly same-origin and you have verified `strict` will not break OAuth or auth fetches.
- Set `SESSION_SECURE_COOKIE=true`.
- Leave `AUTH_COOKIE_NAME` empty unless you have a specific reason to override it. The app will use a hardened `__Host-` cookie name automatically when secure cookies are enabled.
- Set `TRUSTED_PROXIES=*` only if every request reaches Laravel through Cloudflare/Vercel or another trusted proxy layer.
- If not always behind a proxy, set `TRUSTED_PROXIES` to the exact proxy IPs instead of `*`.

## Cloudflare / proxy

- Enable HTTPS end to end.
- Use `Full (strict)` SSL mode in Cloudflare.
- Enable automatic HTTPS redirects.
- Keep the origin server reachable only through the proxy if your host supports it.
- Do not expose raw origin IPs publicly if you can avoid it.

## Cookies and auth

- Confirm `auth_token` is sent as `HttpOnly`.
- Confirm the auth cookie is sent as `Secure` in production.
- Confirm the auth cookie name starts with `__Host-` in production.
- Confirm the site works with cookie auth over your real frontend and backend domains.
- Confirm Google OAuth still works after production cookie settings are applied.
- Keep Google sign-in as the primary recovery path if you are not running transactional email.

## Secrets

- Never commit `.env`.
- Store OAuth secrets only in server-side environment variables.
- Rotate any secret that was pasted into chat, screenshots, tickets, or commits.

## Logging and monitoring

- Keep application logs enabled.
- Do not log secrets, tokens, or passwords.
- Review failed login volume and Google OAuth failures after launch.

## After deploy

- Test sign up.
- Test sign in.
- Test Google sign in.
- Test sign out.
- Confirm `/api/auth/me` returns `401` when signed out.
- Confirm cookies are marked `Secure` and `HttpOnly` in the browser.
- Confirm state-changing auth requests are rejected from untrusted origins.
- Confirm no Laravel welcome page is exposed from the backend root.
- Confirm only the intended public routes are reachable.

## Current public route surface

- `GET /auth/google/redirect`
- `GET /auth/google/callback`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me` with auth cookie
- `POST /api/auth/logout` with auth cookie

Anything outside that list should be considered unnecessary unless you intentionally add it later.
