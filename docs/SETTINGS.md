# Site settings

Super administrators edit settings at `/admin/settings`. Nobody else can see or change them.

## Where values come from
1. `.env` for the R2 keys only (`R2_ACCOUNT_ID`, `R2_BUCKET`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_PUBLIC_URL`). If set, the admin field is locked.
2. The `settings` table.
3. The built-in default (`App\Support\SettingsSchema`).

Adding a setting: add one entry in `SettingsSchema::all()`. The admin form, validation and storage follow from it. To make it affect the site, read it with `SettingsService::value()` or copy it into config in `SettingsService::applyToConfig()`.

## Secrets
- R2 keys and the SMTP password are encrypted with `APP_KEY` before saving, shown only as the last four characters, and never logged. Leaving a secret field empty keeps the saved value.
- **Back up `APP_KEY` separately.** If it is lost or changed, saved secrets cannot be decrypted and must be entered again.
- The audit log records which settings changed, never their values.

## Ads and custom code
- A master switch must be on. When off, no custom code, analytics or ad slot is output.
- Nothing is output on `/admin` pages.
- The code boxes are output as written. Only super administrators can edit them. Paste only code from sources you trust.
- Before turning ads or analytics on: update the privacy policy and add a cookie notice where required.
- `/ads.txt` is served from the "ads.txt content" field and returns 404 when empty.

## Cloudflare R2
1. In Cloudflare, open R2 and create a bucket.
2. Allow public access (the r2.dev address, or connect your own domain).
3. Create an API token with Object Read and Write for that bucket.
4. Enter account ID, bucket, key ID, secret and public URL on the Storage tab, save, press Test connection, then switch on "Store uploads in Cloudflare R2".
- Only new uploads go to R2. Each media row remembers its disk, so older files keep working.
- Moving existing files to R2 is not automated yet.
