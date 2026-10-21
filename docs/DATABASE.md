# Rwandanlyrics — Database (Phase 2)

Dev database: SQLite. Production target: MySQL. Timestamps stored in UTC.

| Table | Purpose |
|---|---|
| languages | Language labels (name, optional code) |
| genres | Genres with unique slug |
| artists | Profile, SEO fields, publication + verification status; `normalized_name` is UNIQUE to block duplicates |
| artist_aliases | Alternative spellings; unique per artist by normalized form; `approved` flag |
| genre_artist | Artist ↔ genre |
| albums | Title, slug, type (album/single/ep), release date, publication status, SEO |
| album_artist | Album ↔ artist (`is_primary`) |
| songs | Title, slug, optional album + track number, language, status, rights fields, SEO, canonical, noindex, created_by/updated_by |
| song_artist | Song ↔ artist with `role` (primary/featured/additional) and `position` |
| genre_song | Song ↔ genre |
| lyrics | Lyrics revisions per song (`revision_number`, `is_current`) |

## Rules
- Public visibility lives in one place: `Song::publiclyVisible()` = status published AND published_at <= now AND rights_status authorized.
- Album deletion keeps songs (`album_id` set to NULL). Artist/song deletion cascades to pivot rows and lyrics.
- Slugs are generated uniquely on create (`same-title`, `same-title-2`). Redirects on slug change come in Phase 5/8.
- Seeding: `DemoSeeder` (fictional data, placeholder text) runs only in the `local` environment.

## Not yet built
users/roles, submissions, corrections, rights complaints, redirects, pages, analytics, settings, audit logs, media — later phases.

## Phase 5a additions
| Table | Purpose |
|---|---|
| audit_logs | Who did what: `user_id` (null for system actions), `action` (e.g. `song.created`, `song.rights_changed`), subject type/id/label, small `meta` JSON. Never stores lyrics text or secrets. |
| users.role, users.suspended_at | Added in Phase 4 |

Rules: published/scheduled content has `published_at` stored in UTC; admin forms use `ADMIN_TIMEZONE` (default Africa/Kigali). A slug is locked once `published_at` is set. `php artisan rwandanlyrics:publish-scheduled` runs every minute via the scheduler.
