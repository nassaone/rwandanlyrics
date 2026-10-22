# Analytics (first-party view counting)

## What is counted
A view of a public song, artist or album page. One count per visitor per page per day.

## How a visitor is identified
A SHA-256 hash of IP + browser string + today's date + the app key. It changes every day, cannot be reversed, and is the only thing stored (table `view_events`). No raw IP address, no browser string, no cookie and no local storage is used. Rows in `view_events` are deleted after 2 days (`rwandanlyrics:prune-analytics`, scheduled daily). Only anonymous daily totals (`daily_views`) are kept long term.

## What is not counted
Bots and crawlers, link previews, prefetch requests, non-browser clients, visitors sending Do Not Track or Global Privacy Control, signed-in editors/admins, error pages and anything not publicly visible. Set `ANALYTICS_ENABLED=false` to switch counting off.

## Where it shows
- Public: "Popular this week" on the homepage. Only publicly visible songs with at least `analytics.min_views` (3) real views in the last `analytics.popular_days` (7) days. The section is hidden when nothing qualifies. Nothing is invented or padded.
- Admin: Analytics page (editors and above) with totals and top songs, artists, albums for 7, 30 or 90 days.

## Popularity formula
Total views in the window, highest first. No editorial boost yet.

## Cookies and consent
No cookies are set, so no consent banner is needed for this. Adding third-party analytics or ads later would change that (see the privacy policy and GOVERNANCE.md).

## Deferred
Outbound-link click tracking, ad placement slots, per-song charts over time, referrer/source reports, caching of the popular list.
