# Rwandanlyrics — Project Progress

Last updated: 2026-10-20

| Phase | Name | Status |
|---|---|---|
| 0 | Environment audit and planning | Complete |
| 1 | Laravel foundation | Complete (verified) |
| 2 | Database and domain models | Complete (verified) |
| 3 | Public website | Complete (tests verified; visual check on phone by owner) |
| 4 | Authentication and authorization | Complete (tests verified) |
| 5 | Admin dashboard and content management | **Complete** (5a content CRUD, 5b media, redirects, merge, users) |
| 6 | Search and discovery | Not started |
| 7 | Contributor and editorial workflow | Not started |
| 8 | SEO and indexing | Not started |
| 9 | Rights, privacy, governance | Not started |
| 10 | Analytics and monetization readiness | Not started |
| 11 | Performance, accessibility, security audit | Not started |
| 12 | Deployment and launch | Not started |

## Environment (Codespace, audited 2026-10-20)
- PHP 8.4.15 (Xdebug on), Composer 2.10.3, Node 24.21.0, npm 11.19.0
- Database drivers: pdo_sqlite only. No MySQL client/server, no pdo_mysql.
- Missing optional extensions: gd, intl, zip, bcmath
- Development DB: SQLite (dev only). Production target: MySQL.

## Phase 1 — what exists
- Laravel (with Tailwind 4 + Vite), app name Rwandanlyrics, `.env` git-ignored
- Light design tokens (purple accent), layout with skip link, mobile menu (Alpine), search form
- Blade components: button, badge, card, alert, empty-state, search-form
- Home page with honest empty state; custom 404/500/503 pages; `/up` health check
- Feature tests: home renders, 404 page, health check

## Verification (run in Codespace, 2026-10-20)
- Pint: pass
- `php artisan test`: 5 passed (8 assertions)
- `npm run build`: succeeded
- Page confirmed styled on a phone through the forwarded port

## Known issues / notes
- `AppServiceProvider` forces HTTPS and the public Codespaces URL when `CODESPACES=true` (dev-only; needed for CSS to load through the port proxy). Port 8000 is hardcoded there.
- Search form posts to `/search`, which does not exist until Phase 6.
- Use `npm run build` (not `npm run dev`) for phone previews; rebuild after CSS/template changes.
- MySQL FULLTEXT cannot be tested until a devcontainer with MySQL is added (before Phase 6).
- Image resizing needs `gd` or Imagick (Phase 5).

## Next
Phase 5b: user management (roles, suspension), media uploads, redirects on slug change, duplicate-artist merge.

## Phase 2 — what exists (verified 2026-10-20)
- 7 migrations: languages, genres, artists, artist_aliases, genre_artist, albums (+album_artist), songs (+song_artist, genre_song, lyrics)
- Enums: PublicationStatus, RightsStatus, VerificationStatus, AlbumType
- Models with relations, unique-slug trait, `Song::publiclyVisible()` scope; artist `normalized_name` unique to block duplicates
- Factories for all core models; `DemoSeeder` (fictional data, placeholder text) runs only in the local environment
- docs/DATABASE.md describes the schema and rules

## Phase 2 verification
- `php artisan migrate:fresh --seed`: succeeded (local SQLite only)
- Pint: pass
- `php artisan test`: 14 passed (23 assertions)
- Bug found and fixed during verification: artist↔genre pivot table name (`genre_artist`) set explicitly on both models

## Phase 2 notes
- Migrations and tests have run only on SQLite. They have not been run on MySQL yet; do this before deployment.
- Redirects, users/roles, submissions, rights complaints, analytics, settings, audit logs and media tables come in later phases.

## Phase 3 — what exists (verified 2026-10-20)
- Public routes: `/`, `/songs`, `/songs/{slug}`, `/artists`, `/artists/{slug}`, `/albums/{slug}`, `/genres`, `/genres/{slug}`
- All public queries go through `Song::publiclyVisible()` / `Artist::published()` / `Album::published()`; non-visible records return 404
- Homepage sections (Latest lyrics, Artists, Albums, Genres) appear only when they have data; honest empty state otherwise
- Song page: lyrics reading layout, artist/album/genre links, related songs, share button, per-song noindex support
- Components: breadcrumbs, song-row; nav links in header, mobile menu and footer; paginated lists (20 per page)

## Phase 3 verification
- Pint: pass; `npm run build`: succeeded
- `php artisan test`: 24 passed (65 assertions)
- Bug found and fixed: `@endif@unless` in Blade (directive not recognised without a preceding non-word character)

## Phase 3 deferred (with reason)
- Trending lyrics and popular artists: need real view data (analytics, Phases 6 and 10); not faked
- Search results page: `/search` returns 404 until Phase 6
- Correction / rights-report links: Phases 7 and 9
- Copy-lyrics control: needs a per-song "copying permitted" rights flag (Phase 9)
- Official listening links and outbound click tracking: Phase 10
- JSON-LD, sitemap, canonical overrides: Phase 8 (basic canonical = current URL)

## Phase 4 — what exists (verified 2026-10-21)
- Hand-written auth (no Breeze, to avoid overwriting verified files): login, logout, register (configurable via `REGISTRATION_ENABLED`), forgot/reset password, account page (profile, password, delete)
- Roles: user, contributor, editor, admin, super_admin; `role` and `suspended_at` columns (not mass-assignable)
- Middleware: `EnsureUserIsActive` (suspended users are logged out), `EnsureRole:<minimum>`
- Policies: Song, Artist, Album, Genre, Language, User (no self role changes; admins cannot mint admins)
- `/admin` requires editor+; `/contributor` requires contributor+ (both are placeholders until Phase 5 and 7)
- Command `php artisan rwandanlyrics:set-role <email> [role]` creates the first super admin
- Auth POSTs rate limited (5/min, separate buckets); password reset does not reveal whether an email exists
- docs/SECURITY.md written

## Phase 4 verification
- Migration applied, Pint pass, `npm run build` succeeded
- `php artisan test`: 74 passed (280 assertions)

## Phase 4 deferred
- Email verification: needs real mail settings
- Reset emails currently go to `storage/logs` (log mailer); a real mail transport is required before launch
- User-management screens, audit log of role changes and suspensions: Phase 5
- Role changes by the artisan command are not audited yet

## Phase 5a — what exists (verified 2026-10-21)
- Admin area `/admin` (editor+): dashboard with real counts and actionable warnings, recent activity
- CRUD: songs (lyrics revisions, rights fields, SEO fields, featured artists, genres), artists (aliases, duplicate and alias-conflict blocking), albums, genres, languages
- Publication states: draft, pending review, scheduled, published, archived; `rwandanlyrics:publish-scheduled` runs every minute via the scheduler
- Slugs are locked once published (genre slugs always locked); admin dates entered in Africa/Kigali, stored in UTC
- Audit log (`audit_logs`) for create/update/status/rights/delete; page visible to admins only
- Policies enforced on every action; only admins can delete

## Phase 5a verification
- Migration applied, Pint pass, `npm run build` succeeded
- `php artisan test`: 100 passed (463 assertions)

## Phase 5a deferred to 5b or later
- User management screens and suspension, with audit entries
- Media uploads (covers, artist images) and `gd`-based resizing (extension not installed in this Codespace)
- Redirect table and automatic redirect when a published slug changes; duplicate-artist merge (depends on redirects)
- Bulk actions; artist social links; external listening links (Phase 10)
- Artist/album dropdowns list every record; replace with search-as-you-type for large catalogs
- Cache invalidation: no public caching exists yet; add with Phase 11

## Phase 5b — Media, redirects, merge, user management (2026-10-21)
- Image uploads for song/album covers and artist image/banner. Validated by real bytes, JPG/PNG/WebP, max 2 MB, min 200x200, random file names, alt text required. Media library with delete (admin only).
- Slugs stay editable after publication. Changing a published slug records a 301 redirect automatically; chains collapse to one hop; loops are removed. Manual redirects under Admin > Redirects (editors add, admins delete).
- Artist merge (admin only): moves songs, albums and genres, keeps the old name as an alias, redirects the old URL.
- User management (admin): role changes limited to roles below your own, suspend/unsuspend (clears sessions), delete is super admin only. All audited.
- Tests: 121 passing (534 assertions), verified by the owner's run. The four 5a tests that assumed locked slugs were replaced by `RedirectTest`.
- Not verified by test: real browser upload on a phone (manual check recommended), image resizing (gd not installed, originals are stored as uploaded).

### Next: Phase 6 — Search and discovery
MySQL FULLTEXT cannot be tested in this Codespace (no pdo_mysql). Decide before starting: add a devcontainer with a MySQL service, or build search with a LIKE fallback first.
