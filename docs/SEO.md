# SEO and indexing

## What is indexed
- Songs: only publicly visible (published, date passed, rights Authorized) and not marked noindex.
- Artists and albums: published, not noindex, and with at least one publicly visible song. Pages without one are still viewable but send `X-Robots-Tag: noindex, follow` (middleware `NoindexThinContent`) and are left out of the sitemap.
- Genres: only those with a visible song.
- Search results, login, account, contribution and admin pages are never indexed.

## Sitemaps
`/sitemap.xml` is an index of `/sitemaps/{pages|songs|artists|albums|genres}-{n}.xml`, 1000 URLs per file. Generated live from the database with the same visibility rules as the site, so a takedown disappears immediately. Caching is deferred to Phase 11 (needs invalidation on publish/unpublish/rights change).

## robots.txt
Dynamic (`SeoController::robots`). Outside `APP_ENV=production` it disallows everything, so Codespaces, staging and local copies are never indexed. In production it hides admin, account, contribution, auth and search pages and lists the sitemap. The default static `public/robots.txt` was removed because it would shadow the route.

## Meta and structured data
- Descriptions: an editor-written `seo_description` wins; otherwise a plain sentence built from real fields (no invented facts).
- JSON-LD: WebSite with SearchAction (home), MusicRecording (song), MusicGroup (artist), MusicAlbum (album). Lyric text is never included. Output is encoded so it cannot close the script tag.
- Open Graph and Twitter card basics in the layout. Per-page share images are deferred.
- Canonical is the current URL without the query string.

## Before launch (see DEPLOYMENT.md)
Set `APP_ENV=production` and `APP_URL` to the final https domain, pick one host (www or bare) and 301 the other at the web server, enforce https, then submit `/sitemap.xml` in Google Search Console and Bing Webmaster Tools.
