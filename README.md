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

## The settings screen

**Authors → Settings** is five tabs:

| Tab | Holds |
|---|---|
| Author index | who appears in `[author_bio_list]` |
| General | site name, the three URLs, and the shortcode defaults |
| Content | section toggles and the pitch box copy |
| Appearance | typeface, corners, content width, density |
| Colors | the three seeds, plus the ten derived colors behind a disclosure |

Author index opens first because it is the tab you come back to; the rest are
set once when a site is configured. The tab is remembered per browser, so it
survives saving.

All five tabs are one form — every field is submitted on every save, whichever
tab is open. Colors are native WordPress color pickers, and clearing one is
what hands it back to being derived.

With JavaScript off the tabs are inert and the page falls back to one long
scroll with a heading per section, which is what it was before the tabs.

## Colors

Templates are built from three CSS custom properties — `--abio-ink`,
`--abio-paper` and `--abio-accent` — with everything else derived from them via
`color-mix()`. On activation the plugin reads Elementor's global colors or the
Bricks palette and seeds those three. **Authors → Settings** shows what was
detected and lets you override any of them.

### Derived colours

The ten mixed colours — wash, line, muted, dim, soft, faint, accent-soft and
the three on-ink values — each get a field under **Derived colours**. Every
field is blank by default and shows the mixed value it will use, so you can see
what you are about to override. Fill one in and it wins; clear it and the mix
resumes.

Leave them blank where you can. Derivation is what makes the palette re-tone
itself when a site changes its brand colour — override several and a future
brand change stops carrying through on its own.

### Shape and spacing

Three more controls under the same screen:

| Control | Options | What it changes |
|---|---|---|
| Corners | Square, Soft (default), Rounded | `--abio-radius-sm/md/pill`, every rounded corner in the stylesheet |
| Content width | Narrow (−20%), Default, Wide (+15%) | `--abio-width`, a multiplier on each template's own max-width |
| Density | Compact (−15%), Default, Roomy (+20%) | `--abio-space`, a multiplier on every padding and gap |

Width and density are proportional rather than absolute, so each template keeps
its own character — the masthead stays narrower than the product-UI template
instead of all ten collapsing to one measure. At the default setting the
multiplier is `1` and every value computes to exactly the pixel it was authored
as, so a site that never opens these renders identically.

Round portraits are not affected by the corner setting: a circular portrait is
template 5's identity, not a corner radius, and "Square" must not flatten it.

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

## The author index

`[author_bio_list]` lists authors as a vertical index. Each row carries the
portrait, the kicker, the name, the role and the short line, with the name
linking to their archive.

The index has its own view per template, so `template=7` gives you the sports
desk's boxed cards on a wash and `template=9` gives you the research note's
numbered entries on a single bordered sheet, matching the profile page a reader
lands on when they click through. With no `template` attribute it follows the
default set in Authors → Settings.

Templates 1 and 3 lay the index out as a grid of boxes that flows
horizontally and wraps; template 7 keeps its cards full width, one per row. A
box is Paper with a hairline on a wash ground — the wash is what makes it read
as a box, and the copy is still on Paper, never on the wash. The grids use
`auto-fill`, so a box stays the same size whether the index has two authors or
twenty.

The kicker sits above the name as a tracked uppercase eyebrow, which is where
all ten single-profile templates put it. It falls back to "Author" when a
profile does not set one, so a row is never unlabelled.

```
[author_bio_list]
[author_bio_list template=7]
[author_bio_list orderby="posts" order="desc"]
[author_bio_list count=10]
```

| Attribute | Default | Meaning |
|---|---|---|
| `template` | settings default | which template's look to inherit, `1`–`10` or a slug |
| `count` | `0` (all) | how many authors to list |
| `orderby` | `name` | `name`, `posts` (most published first), or `recent` |
| `order` | `asc` | `asc` or `desc` |
| `heading` | — | replaces the default "Authors · N" label |
| `profiles` | settings | `0` lists only the selected authors |
| `users` | settings | comma-separated IDs, logins or nicenames to list |

It renders inside the same root element as a single profile, so it inherits the
selected template's palette, typeface, corner language and label treatment from
the same tokens — plus the details that genuinely differ: an inverted heading
band in templates 4, 6, 7 and 10, circular headshots in 5, rounded cards in 8.

### Who appears

**Authors → Settings → Author index** decides:

- **Configured authors** — on by default, listing everyone with a saved Author
  Profile. Switch it off to list only the people you pick, which is how you
  curate an exact index.
- **Select authors** — pick people from a dropdown and each one stays as a
  removable token beneath it, so the current selection is readable at a glance
  instead of being scattered through a long scrolling list. Anyone chosen appears
  whether or not they have an Author Profile, shown from what WordPress holds:
  display name and avatar. Somebody who already has a profile is listed once,
  from that profile.

Two attributes override those settings for a single placement:
`profiles="0"` ignores the saved profiles, and `users="12,45"` (IDs, logins or
nicenames) replaces the selected list.

No index shows a published-article count. Counting one is a query per author,
so it now runs only for `orderby="posts"`, where the order depends on it.

## Structured data

**Off by default.** Turn it on in **Authors → Settings → General →
Structured data**. It opts in rather than out because an SEO plugin — Yoast and
Rank Math both do this — already describes authors on an author archive, and two
`Person` graphs for one person is worse than none from here. Switch it on where
this plugin is rendering a page nothing else covers.

With it on, both shortcodes emit schema.org JSON-LD.

A profile page emits a `ProfilePage` whose `mainEntity` is a `Person` — the
markup Google documents for author and profile pages. An index emits an
`ItemList` of `Person` nodes. Each `Person` is anchored to the author archive
URL with an `@id` of `<archive>#person`, so the node in an index and the node
on that author's own page describe one person rather than two.

Fields map straight across, and only when they resolve:

| Profile field | Property |
|---|---|
| Name | `name` |
| Job title | `jobTitle` |
| Bio, else short line | `description` (markup stripped) |
| Portrait | `image` |
| Location | `homeLocation` |
| Site name | `worksFor` |
| Areas of focus | `knowsAbout` |
| Credentials | `hasCredential` |
| Follows with a URL | `sameAs` |

Nothing is inferred or padded. A key whose value does not resolve is dropped
rather than emitted empty, credentials are passed through verbatim with no
invented issuer or date, and `dateCreated`/`dateModified` appear only where
there is an Author Profile post to date — a profile built from a WordPress user
alone has no authored record behind it.

The setting is the base answer and `abio_schema_enabled` has the last word in
both directions, so a site can force it on where the setting is off, or off
where it is on. It also receives the context, which is how you keep the profile
graph and drop the index one:

```php
add_filter( 'abio_schema_enabled', function ( $on, $context ) {
	return 'profile' === $context;
}, 10, 2 );
```

The graphs are also filterable before output: `abio_schema_person`,
`abio_schema_profile_page` and `abio_schema_item_list`.

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

Build it from the tag rather than by hand — `.gitattributes` already lists
what a release excludes, so there is no list to keep in step:

```bash
git archive --format=zip --prefix=author-bio/ -o author-bio-1.2.0.zip v1.2.0
```

The filename carries the version, the `--prefix` does not: that prefix names
the folder the plugin installs into, so it stays `author-bio/` every time.

## Requirements

WordPress 6.0+, PHP 7.4+. No Composer, no npm, no build step, no ACF.
