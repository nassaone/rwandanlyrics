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
