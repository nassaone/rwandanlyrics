# Rwandanlyrics — Security notes

Status after Phase 4. Update this file as later phases land.

## Implemented
- Authentication: Laravel session guard; login, logout (POST + CSRF), registration, password reset, profile, password change, account deletion.
- Passwords hashed (`hashed` cast); minimum length 8. Session ID regenerated on login; session invalidated on logout.
- Login, registration and password-reset POSTs are rate limited (5 per minute per IP, separate buckets). Password-reset responses do not reveal whether an email exists.
- Roles: user < contributor < editor < admin < super_admin (`App\Enums\Role`). `role` and `suspended_at` are not mass-assignable.
- Route protection: `auth` + `EnsureUserIsActive` (suspended users are logged out) + `EnsureRole:<minimum>`. `/admin` requires editor+, `/contributor` requires contributor+.
- Policies for Song, Artist, Album, Genre, Language (contributors create, editors update/publish, admins delete) and User (no self role changes; admins only manage users below them and cannot grant admin or super_admin; only super admins can delete other users).
- Account deletion requires the current password and is blocked for the last super administrator.
- Auth, account, admin and error pages are `noindex`.
- Secrets live in `.env` (git-ignored). `REGISTRATION_ENABLED` closes public sign-up.

## Creating the first super administrator
1. Register a normal account on the site.
2. Run: `php artisan rwandanlyrics:set-role you@example.com super_admin`
Role changes by this command are not yet written to an audit log (Phase 5).

## Production configuration required
- `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`.
- HTTPS everywhere; `SESSION_SECURE_COOKIE=true`; consider `SESSION_SAME_SITE=lax` (default).
- A real mail transport (`MAIL_*`). The default `log` mailer only writes reset emails to `storage/logs`, so password reset will not reach users until this is set.
- Least-privilege MySQL user.

## Not yet implemented
- Email verification (needs real mail settings; planned with deployment).
- User-management screens and audit log of role changes / suspensions (Phase 5).
- Two-factor authentication (not planned for the first release).
- Full-page caching must never be applied to pages that show account links; the header differs per user.

## Phase 5a additions
- All `/admin/*` routes require login, an active account and role editor or above; every action also calls a policy (`Gate::authorize`). Only admins can delete content; only admins can read the audit log.
- Admin forms validate with Form Requests; enums, foreign keys, slugs and URLs are validated; `canonical_url` must be a real URL.
- Significant changes (create, update, status, rights decisions, delete) are written to `audit_logs`.
- Scheduler: production needs a cron entry running `php artisan schedule:run` every minute.
- Known limitation: audit log entries are not tamper-proof against a database administrator.

## Phase 5b additions
- Uploads are validated by real image bytes (`getimagesize`), not the client MIME type or file name. Only JPG/PNG/WebP, 2 MB max, at least 200x200 pixels. Files are stored under random names.
- Alt text is required for every uploaded image.
- User management (role changes, suspension, deletion) is admin-only, audited, and blocked for the admin's own account. Suspending a user clears their remember token and database sessions.
- Manual redirects must be local paths, cannot target `/admin`, and cannot create loops.

## Phase 11 audit (2026-10-24)

### Checked and found fine
- Every route that changes data is behind login or a rate limit (login, register, forgot/reset password: 5/min; submissions: 20/hour; search: 60/min). No admin, contribute or account page is reachable without login.
- All POST forms carry CSRF tokens. All images have alt text. Pages set a language. The only unescaped output is the policy Markdown (HTML input stripped, unsafe links disallowed) and JSON-LD (encoded so it cannot close the script tag).
- All raw SQL is parameterized or static. No model uses `$guarded = []`; role and suspension fields are not mass assignable.
- Key queries use indexes (checked with query plans on SQLite).

### Fixed in Phase 11
- Added security headers on every response: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, and a partial `Content-Security-Policy` (base-uri, form-action, frame-ancestors, object-src). `Strict-Transport-Security` (1 year, no subdomains/preload) only in production over https.
- Turned off the framework's local-disk file route (`PUT /storage/{path}`), which had no rate limit and is unused.
- Added missing reverse-lookup indexes (artist to songs/albums, genre to songs/artists, songs.album_id, songs.language_id, lyrics current revision). Artist pages were scanning `song_artist`.
- Test that public pages run the same number of queries with 3 or 23 songs (guards against N+1).
- Launch checklist (`rwandanlyrics:preflight`) now also requires HTTPS-only session cookies and a real mail driver (password reset needs it).
- Removed the unused Laravel welcome page and the old `/contributor` placeholder.

### Known gaps (deliberate, not fixed)
- No script/style CSP yet: Alpine needs `unsafe-eval` and several forms use inline `confirm()` handlers. A strict CSP needs the Alpine CSP build and moving those handlers into Alpine, then a report-only trial.
- No two-factor login for staff, no email verification, and login lockout is rate limiting only.
- No caching layer, so nothing to invalidate yet. Add caching only together with invalidation on publish, unpublish and rights changes.
- Dependency audits (`composer audit`, `npm audit`) are a manual step; run them before each release.
