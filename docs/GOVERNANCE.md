# Rights, privacy and governance

## Public policy pages
`/privacy`, `/copyright`, `/takedown`, `/report-lyrics-errors`. The wording lives in `resources/policies/*.md` (owner-supplied text). Only the contact emails and site URL are placeholders, filled from `CONTACT_COPYRIGHT_EMAIL`, `CONTACT_CORRECTIONS_EMAIL`, `CONTACT_PRIVACY_EMAIL` and `APP_URL`. Update the effective date in the Markdown whenever the wording changes.

## Handling a takedown request (for admins)
1. Log the request (who, which URLs, date). Use the song's "Licensing notes" field for the reference.
2. If the complaint looks plausible, set the song's Rights status to **Removed** (or **Restricted** while you investigate). The song disappears at once from its page (404), search results and the sitemap. Nothing else needs to be done to hide it.
3. Reply to the complainant. The takedown page promises acknowledgment within five business days; only keep that promise if you can.
4. If the complaint is disputed and you restore it, set rights back to **Authorized** and note the evidence. Check the Activity log for who changed what.
5. For repeat infringement by a contributor, suspend the account (Admin > Users).

## Content gates (all enforced in code and tested)
A song is public only when it is published, its date has passed, and rights are Authorized. Rights Pending, Unknown, Restricted and Removed are never public. Contributor submissions never publish anything by themselves.

## Before launch
- Run `php artisan rwandanlyrics:preflight`. It fails while the contact emails are still the `@yourdomain.com` placeholders, or while debug is on, or APP_URL is not https.
- Have the policy texts reviewed by a qualified person. This project does not provide legal advice.
- No Terms of Use page exists yet (the copyright policy refers to "applicable terms"). Add one before launch.
- The privacy policy mentions comments and analytics/advertising. The site has no comments today and no analytics or ads are enabled yet. Keep the text accurate to what is actually running, and add a cookie-consent banner before enabling non-essential cookies (Phase 10).
- Removed content currently returns 404 (not 410). Fine for now.
