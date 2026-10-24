# Design system

## Principles
- Mobile-first, light, fast. System fonts only (no web-font downloads).
- Lyrics are the product: serif face, 19px, generous line height, 36rem measure, in a panel with a yellow rule.
- One accent colour. Rwanda's flag colours appear only as the 4px stripe above the header and the lyric-panel rule.

## Tokens (resources/css/app.css, @theme)
| Token | Value | Use |
|---|---|---|
| accent-600 / 700 | #1b6b45 / #14553a | links, buttons, brand |
| accent-50 / 100 | #f1f8f4 / #dcefe4 | hover fills, badges |
| sun | #fad201 | lyric rule, hero rule, stripe |
| sky | #1fa0dc | stripe only |
| ink / muted / line / surface | #14201a / #55625b / #e2e8e4 / #f6f8f6 | text, secondary text, borders, bands |

Text contrast: ink on white and muted on white both pass WCAG AA.

## Type
- Sans: system UI stack. Serif (`.lyrics` only): Iowan Old Style / Palatino / Georgia.
- h1 clamp(1.75rem, 1.3rem + 2vw, 2.5rem); h2 clamp(1.25rem, 1.1rem + .8vw, 1.625rem); h3 1.0625rem. Set in `@layer base`; do not repeat size classes on headings.

## Layout
- Container max-w-5xl, px-4 (sm:px-6). Header: slim sticky bar with menu; search band scrolls away.
- Touch targets: buttons, inputs, nav and footer links are at least 44px tall.
- Inputs are 16px text to avoid iOS zoom.

## Components
song-row (tap-area list row), card, badge, alert, field/select/textarea (min-h-11, rounded-lg), breadcrumbs, empty-state.

## Rules for new pages
1. Use existing tokens; no raw hex in views.
2. No new JS libraries; Alpine only for menus/toggles.
3. Add no queries when restyling (query-count tests guard home and song pages).
4. Check at 360px width before merging.
