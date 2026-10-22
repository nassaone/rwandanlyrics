
## Search (Phase 6)
- `App\Services\SearchService` — LIKE-based search over song titles, artist names and aliases, album titles (and album artists). Same visibility rules as the rest of the site. Ranking: exact title, then prefix, then contains, then newest.
- Wildcards (`%`, `_`) are escaped with `!`, so user input is always literal. Queries are trimmed, whitespace-collapsed and capped at 100 characters; minimum 2.
- `/search` is `noindex,follow` and rate limited (60/min).
- Deferred: MySQL FULLTEXT and lyrics-text search (need a MySQL test environment), diacritic-insensitive matching, typo tolerance, autocomplete.

## Contributor workflow (Phase 7)
- `submissions` table: new songs and lyric corrections from contributors. Status pending / approved / rejected; reviewer, time and note recorded. Only contributor-supplied fields are mass assignable.
- Contributors (`/contribute`): submit, see only their own, max 10 pending, submit route rate limited (20/hour). Must confirm they have the right to submit the lyrics.
- Editors (`/admin/submissions`): oldest pending first, approve or decline. Declining requires a reason. A submission can be reviewed once.
- Approving a new song creates a DRAFT with rights "pending" (never public until an editor sets Authorized and publishes). The editor picks an existing artist, so free-text names never create duplicate artists.
- Approving a correction adds a new lyrics revision; earlier revisions are kept.
- Audited: `submission.created`, `submission.approved`, `submission.rejected`.
