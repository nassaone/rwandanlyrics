# Rwandanlyrics — Project Progress

Last updated: 2026-10-20

| Phase | Name | Status |
|---|---|---|
| 0 | Environment audit and planning | Complete |
| 1 | Laravel foundation | Complete (verified) |
| 2 | Database and domain models | Not started |
| 3 | Public website | Not started |
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
Phase 2: migrations, models, factories, dev seeders, and tests for artists, aliases, songs, lyrics, albums, genres, languages, with publication and rights states.
