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
`experience`, `credentials`, `follows`, `others`, `pitch`, `breadcrumbs`.

**Authors → Settings → Sections** carries site-wide toggles for the pitch box
and the breadcrumb trail. A shortcode's `hide` can still suppress either on one
page; the setting is the default, not an override of it.

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

## Layout

The templates size themselves to **the container they are placed in**, not to
the browser window. The stylesheet makes the shortcode's root element a CSS
container (`container-type: inline-size`) and all breakpoints are `@container`
queries, so `[author_bio]` lays out correctly whether it sits in a full-width
section or a narrow themed content column. Placing it inside a constrained
column no longer produces a desktop layout crushed into a narrow space.

Container queries are supported in Chrome/Edge 105+, Safari 16+ and Firefox
110+. On older browsers every template falls back to its widest layout.

## Typography

The author page uses **your theme's typeface** by default, the same way it uses
your palette. **Authors → Settings → Typeface** can override that with one of
four system stacks (System UI, Grotesque, Humanist, Serif) when a theme's font
does not suit a dense profile page.

No webfont is ever loaded. Metadata — dates, statuses, ordinals, stat units —
is set uppercase and tracked rather than in a monospace, with `tabular-nums`
so figures still align in a column.

## Unconfigured authors

By default an author with no Author Profile still gets a page. The shortcode
falls back to what WordPress already holds about the user — display name,
Biographical Info, avatar — and pairs it with their real published articles and
two derived figures: how much they have published, and since when.

That means one `[author_bio]` in an author archive template covers every author
on the site. Authors who have been written up properly get the full page;
everyone else gets an honest one rather than a blank.

Nothing is invented. A field WordPress does not hold — role, location,
credentials, areas of focus, career history — is left out, so the section does
not appear at all. The avatar is used only where the site has avatars enabled
under **Settings → Discussion**.

Switch it off under **Authors → Settings → Sections → Unconfigured authors** to
leave those archives empty instead; users who can edit posts then see a line
explaining what was missing.

## Updates

The plugin checks its own GitHub releases and reports updates in the WordPress
dashboard like any other plugin. It uses the `Update URI` mechanism built into
WordPress 5.8+, so there is no update library and no scheduled task of its own —
WordPress asks during its normal update check, at most once every six hours.

**Automatic updates** work the moment a site enables them for this plugin under
**Plugins → Enable auto-updates**. Nothing extra is required. A site that
filters `auto_update_plugin` to false, as some managed hosts do, will still
show the update but not apply it unattended.

**Plugins → Author Bio → Check for updates** forces a check immediately rather
than waiting for the cache to expire.

### Cutting a release

1. Bump `Version:` in `author-bio.php` and `Stable tag:` in `readme.txt`, and
   commit.
2. Tag and publish a GitHub release. `v1.2.3` and `1.2.3` are both accepted.
3. Attach a zip built with the plugin in a top-level `author-bio/` folder.

The tag has to compare higher than the installed `Version:` header for anything
to appear — that comparison is the whole trigger.

Step 3 is optional but preferred: the attached zip installs exactly what you
built, without the repo's `docs/`, `PRODUCT.md` or `DESIGN.md`. If no zip is
attached the plugin falls back to GitHub's generated source archive and renames
its folder on the way in, so the update still lands in the right place; it just
carries the development files with it.

```bash
zip -r author-bio.zip author-bio \
  -x 'author-bio/.git/*' 'author-bio/docs/*' 'author-bio/.superpowers/*' \
     'author-bio/.impeccable/*' 'author-bio/PRODUCT.md' 'author-bio/DESIGN.md'
```

## Requirements

WordPress 6.0+, PHP 7.4+. No Composer, no npm, no build step, no ACF.
