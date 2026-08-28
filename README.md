# Author Bio

A WordPress plugin that renders a complete author page from one shortcode.

Content lives in a dedicated **Authors** admin screen — not in user meta, not
in ACF fields attached to the user object. Each Author Profile is linked to a
WordPress user, so dropping the shortcode into an author archive template
renders whichever author the URL resolves to.

## Usage

Put the shortcode in a page, or in your theme's `author.php`:

    [author_bio template=3]

| Attribute | Default | Meaning |
|---|---|---|
| `template` | `1` (settings) | `1`–`10`, or a slug (see below) |
| `user` | resolved from context | user ID, login, or nicename |
| `id` | — | an Author Profile post ID, bypassing user resolution |
| `count` | `6` (settings) | how many articles to list |
| `post_type` | `post` (settings) | comma-separated post types for the article list |
| `hide` | — | comma-separated sections to omit |
| `others` | `2` | other-author cards; `0` disables |

Sections accepted by `hide`: `stats`, `gallery`, `focus`, `edits`,
`experience`, `credentials`, `follows`, `others`, `pitch`.

## Templates

| # | Slug | Character |
|---|---|---|
| 1 | `classic-sidebar` | Editorial two-column with a sticky right rail |
| 2 | `resume` | Full-height left sidebar, CV-style main column |
| 3 | `editorial-masthead` | Centered masthead, narrow measure, gallery band |
| 4 | `bento` | 12-column card grid |
| 5 | `numbered-rail` | Sticky numbered navigation beside numbered sections |
| 6 | `dossier` | Full-bleed dark header and footer |
| 7 | `sports-desk` | Uppercase dark banner, boxed cards, box-score list |
| 8 | `fintech` | Rounded product-UI cards, status pills |
| 9 | `research-note` | Single paper sheet, numbered analyst sections |
| 10 | `brand-feature` | Oversized hero, asymmetric gallery, dark statement band |

## Author resolution

1. The `user` or `id` attribute, when given.
2. The queried author on an author archive.
3. The current post's author on a singular view.

If no published profile is linked to the resolved author, the shortcode renders
nothing. Users who can edit posts see a diagnostic line instead.

## Permissions

Managing Author Profiles requires its own capabilities, granted to
Administrators and Editors on activation — publishing an ordinary post is not
enough. The Author role can still write posts as usual but cannot create,
edit, or link an Author Profile.

## Colors

Templates are built from three CSS custom properties — `--abio-ink`,
`--abio-paper` and `--abio-accent` — with everything else derived from them via
`color-mix()`. On activation the plugin reads Elementor's global colors or the
Bricks palette and seeds those three. **Authors → Settings** shows what was
detected and lets you override any of them.

## Requirements

WordPress 6.0+, PHP 7.4+. No Composer, no npm, no build step, no ACF.
