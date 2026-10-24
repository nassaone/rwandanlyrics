# Accessibility

## Built in
Skip link, one `h1` per page, labelled form fields with visible errors, visible keyboard focus, `lang` on the page, alt text required for every uploaded image, `aria-current` on active navigation, landmarks (header, nav, main, footer), mobile-first layout that works without horizontal scrolling.

## Checked automatically
No `<img>` without alt, no `<html>` without lang, no unsafe `target="_blank"` links in the views the site uses.

## Still to check by hand before launch
- Keyboard only: tab through home, a song page, search, login and the admin song form. Focus must always be visible and never trapped.
- Screen reader pass (TalkBack or VoiceOver) on a song page and the contribution form.
- Contrast: confirm the purple accent on white and the muted grey text meet WCAG AA (4.5:1 for body text) in the browser's accessibility tools.
- Zoom to 200% and with large system text: nothing may overlap or disappear.
- Check that error messages are announced and tied to their fields.
