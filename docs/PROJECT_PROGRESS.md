# Rwandanlyrics — Project Progress

Last updated: 2026-10-20

| Phase | Name | Status |
|---|---|---|
| 0 | Environment audit and planning | Complete |
| 1 | Laravel foundation | Complete (verified) |
| 2 | Database and domain models | Complete (verified) |
| 3 | Public website | Complete (tests verified; visual check on phone by owner) |
| 4 | Authentication and authorization | Not started |
| 5 | Admin dashboard and content management | Not started |
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
Phase 4: authentication and authorization (login, roles, policies, admin/contributor boundaries).

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
