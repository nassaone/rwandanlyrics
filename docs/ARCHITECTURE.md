
## Search (Phase 6)
- `App\Services\SearchService` — LIKE-based search over song titles, artist names and aliases, album titles (and album artists). Same visibility rules as the rest of the site. Ranking: exact title, then prefix, then contains, then newest.
- Wildcards (`%`, `_`) are escaped with `!`, so user input is always literal. Queries are trimmed, whitespace-collapsed and capped at 100 characters; minimum 2.
- `/search` is `noindex,follow` and rate limited (60/min).
- Deferred: MySQL FULLTEXT and lyrics-text search (need a MySQL test environment), diacritic-insensitive matching, typo tolerance, autocomplete.
