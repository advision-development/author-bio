# Author Bio — Design Spec

Date: 2026-08-28
Status: Approved

## Purpose

A WordPress plugin that renders a complete author page from a single shortcode:

```
[author_bio]
[author_bio template=3]
```

Content is authored in a dedicated Author Profile admin screen — not in user
meta, not in ACF fields bolted onto the user object. Each profile is linked to
one WordPress user, so dropping the shortcode into an existing author archive
template renders the profile of whichever author the URL resolves to.

Source design: Claude Design project `73890754-c363-4c78-abbf-4789014a789b`,
file `Author Page Templates.dc.html` — ten author-page layouts sharing one data
schema, plus `author-data.json` defining that schema.

## Success Criteria

1. `[author_bio template=N]` for N in 1..10 renders the corresponding layout.
2. With no attributes, on an author archive, it renders the queried author's
   profile.
3. All content in the design — images, bio, history, credentials, socials,
   stats, and the latest-articles list — is driven by the profile plus the
   plugin's global settings. No template edits required to change content.
4. The latest-articles section reflects real published posts by that author.
5. Colors adapt to the host site rather than shipping a fixed brand palette.
6. Adding a field means one entry in the field schema, not four edits.

## Non-Goals

- No Gutenberg block or page-builder widget. Shortcode only.
- No public permalink for a profile. The author archive is the canonical URL.
- No automated test harness. Verification is manual on a wp-lab instance.
- No `portraitWide` field. It exists in `author-data.json` but no template
  consumes it.

## Architecture

```
author-bio.php                 bootstrap, constants, autoloader, activation hook
includes/
  class-fields.php             field schema — single source of truth
  class-post-type.php          registers CPT author_profile
  class-metaboxes.php          renders and saves admin UI from the schema
  class-settings.php           options page: site fields, pitch, palette, defaults
  class-palette.php            Elementor/Bricks detection -> CSS custom properties
  class-profile.php            loads a profile by user or ID -> normalized array
  class-articles.php           WP_Query -> edits[] with type, status, read time
  class-stats.php              stat tile resolution (auto / manual)
  class-shortcode.php          attribute parsing, author resolution, render
  class-assets.php             conditional enqueue
templates/
  template-1.php … template-10.php
assets/
  css/author-bio.css
  admin/admin.css
  admin/admin.js
```

Layering: `class-profile.php` is the only component templates talk to. Templates
receive a finished array and contain no queries, no option reads, and no
conditionals beyond "is this section non-empty".

## Data Contract

`Profile::to_array()` returns the shape defined by `author-data.json`, so each
template is a direct port of its DC markup:

```php
[
  'site'        => ['name','editorialUrl','contactUrl','authorsUrl'],
  'author'      => ['kicker','name','role','location','since','badges',
                    'bio','short','portrait'],
  'stats'       => [ ['value','label'], … ],          // 4
  'gallery'     => ['heading','note','items' => [ ['image','label','caption','short'], … ]],
  'focus'       => [ ['title','body'], … ],
  'edits'       => [ ['date','type','status','title','url','summary','readTime'], … ],
  'experience'  => [ ['years','title','org','body'], … ],
  'credentials' => [ 'string', … ],
  'follows'     => [ ['handle','url'], … ],
  'others'      => [ ['name','role','url'], … ],
  'pitch'       => ['title','body','cta'],
  'nav'         => [ ['num','label','href'], … ],
]
```

Port rules from the DC source:

| DC construct | PHP |
|---|---|
| `<sc-for list="{{ x }}" as="i">` | `foreach ( $x as $i )` |
| `<sc-if value="{{ c }}">` | `if ( $c )` |
| `{{ value }}` | `esc_html()` — `wp_kses_post()` for bio |
| `href="{{ url }}"` | `esc_url()` |
| `style-hover="…"` | a real `:hover` rule in the stylesheet |
| inline `style="…"` | a class in the stylesheet |

Templates 7, 9, and 10 additionally use derived index values — `focus[].n`
("01"), `focus[].sub` ("1.1"), `gallery[].n` (1) — computed in `Profile` the
same way the DC `renderVals()` computes them.

The static `nav` list (`01 Areas of focus`, `02 Latest edits`,
`03 Experience`) is built by `Profile` and filtered to sections that are both
present in the template and non-empty.

## Content Type

CPT `author_profile`:

- `public => false`, `show_ui => true`, `show_in_menu => true`
- Menu label "Authors", dashicon `dashicons-id-alt`
- `supports => ['title']` — title is an internal label only
- No rewrite rules, no archive, no permalink

Post meta is prefixed `_abio_`. Each repeater group is stored as one serialized
array under a single key. The linked user is `_abio_user` (int), reverse-looked
up via `meta_query`.

An admin notice warns when two profiles link to the same user; the lowest post
ID wins at render time so behavior stays deterministic.

## Fields

### Per profile

| Field | Key | Type | Notes |
|---|---|---|---|
| Linked user | `user` | user select | required for auto stats and article queries |
| Kicker | `kicker` | text | default "Author" |
| Display name | `name` | text | falls back to the linked user's display name |
| Role | `role` | text | |
| Location | `location` | text | |
| Contributing since | `since` | text | falls back to year of first published post |
| Short line | `short` | text | used by templates 8 and 10 |
| Bio | `bio` | textarea | `wp_kses_post` on output |
| Badges | `badges` | repeater (text) | |
| Portrait | `portrait` | media (1:1) | |
| Gallery heading | `gallery_heading` | text | |
| Gallery note | `gallery_note` | text | |
| Gallery items | `gallery_items` | repeater (image, label, caption, short) | |
| Areas of focus | `focus` | repeater (title, body) | |
| Experience | `experience` | repeater (years, title, org, body) | |
| Credentials | `credentials` | repeater (text) | |
| Follows | `follows` | repeater (handle, url) | |
| Stat tiles | `stats` | 4 fixed rows | see below |

### Stat tiles

Each of the four tiles stores a mode plus its payload:

- `auto_bylines` — count of published posts by the linked user across the
  article post types set in global settings. Label is editable.
- `auto_since` — four-digit year of the linked user's earliest published post.
- `auto_type_count` — count in one chosen post type.
- `manual` — a literal value and label.

This covers both computed tiles ("21 Pieces bylined") and ones WordPress cannot
know ("1 Tools built").

A tile in an auto mode with no linked user falls back to rendering nothing, and
the tile is omitted from the array rather than rendered blank.

### Global settings

Options page under the Authors menu, stored in one option `abio_settings`:

- Site name (defaults to `get_bloginfo('name')`), editorial policy URL,
  contact URL, authors index URL
- Pitch: title, body, CTA label
- Palette: the three seed colors plus detection source and a re-detect action
- Defaults: template, article count, article post types

## Articles ("Latest edits")

`Articles::for_user( $user_id, $args )` runs a `WP_Query`:

- `author => $user_id`, `post_status => publish`
- `post_type` from the shortcode attr, else settings default, else `post`
- `posts_per_page` from the shortcode attr, else settings default (6)
- `orderby => date`, `order => DESC`
- `ignore_sticky_posts => true`, `no_found_rows => true`

Field derivation per row:

- `date` — `get_the_date()` in the site's format
- `type` — the post type's singular label; for `post` specifically, the primary
  category name when one exists, else the label
- `status` — "Updated" when `post_modified` is more than a day later than
  `post_date`, else "Published"
- `title` / `url` — `get_the_title()` / `get_permalink()`
- `summary` — `get_the_excerpt()`
- `readTime` — `ceil( str_word_count( wp_strip_all_tags( $content ) ) / 200 )`
  rendered as "N min"

No transient caching. The query is object-cached by WordPress and runs once per
page render.

## Shortcode

```
[author_bio]
[author_bio template=3]
[author_bio template="editorial-masthead"]
[author_bio user=12 count=8 post_type="post,review"]
[author_bio hide="experience,gallery" others=0]
```

| Attribute | Default | Meaning |
|---|---|---|
| `template` | settings default (1) | `1`–`10` or a layout slug |
| `user` / `id` | resolved from context | user ID, user login, or profile post ID |
| `count` | settings default (6) | articles to list |
| `post_type` | settings default (`post`) | comma-separated |
| `hide` | empty | comma-separated sections to omit |
| `others` | 2 | other-author cards; `0` disables |

Slug aliases: `classic-sidebar`, `resume`, `editorial-masthead`, `bento`,
`numbered-rail`, `dossier`, `sports-desk`, `fintech`, `research-note`,
`brand-feature`.

Author resolution order:

1. `user` or `id` attribute
2. `get_queried_object()` on an author archive
3. The current post's `post_author` on a singular view

No profile found: render nothing. Users with `edit_posts` see an inline hint
naming the missing profile so the blank is diagnosable.

"Other authors" auto-lists other published profiles, excluding the current one,
ordered by name, limited by `others`. Each card links to
`get_author_posts_url()` for that profile's linked user.

## Styling

The DC source uses inline styles exclusively, is desktop-only, and expresses
hover through a non-standard `style-hover` attribute. The port moves all of it
into one stylesheet.

- Root element: `<div class="abio abio--t3">`. Every rule is scoped under
  `.abio` with enough specificity to survive theme and builder resets.
- Class names follow the design's own section names: `abio__hero`,
  `abio__stats`, `abio__focus`, `abio__edits`, `abio__experience`,
  `abio__rail`, `abio__pitch`.
- Breakpoints at 1024 / 768 / 560: multi-column grids collapse toward one
  column, sticky rails unstick, and display headings (72–84px in templates 3,
  6, 7, 10) scale down.
- `style-hover` attributes become `:hover` rules.
- Images render through `wp_get_attachment_image()` so srcset and alt text come
  from the media library. The design's hatched placeholder boxes remain as the
  empty state when no image is set.

## Palette

Colors resolve through CSS custom properties on `.abio`:

| Property | Role in the design |
|---|---|
| `--abio-ink` | `#17181a` — text, dark panels |
| `--abio-paper` | `#fbfbfa` — card and page background |
| `--abio-accent` | links, CTA fills |
| `--abio-wash` | `#f4f4f2` — derived |
| `--abio-line` | `#e2e2df` — derived |
| `--abio-muted` | `#8a8c90` — derived |
| `--abio-invert` | `#f4f4f2` on dark panels — derived |

Only ink, paper, and accent are detected or configured. The remaining four are
derived with `color-mix( in srgb, … )` so detection only has to get three
values right.

Detection, at activation and on demand from the settings page:

- **Elementor** — `get_option('elementor_active_kit')`, then the kit's
  `_elementor_page_settings` meta, reading `system_colors` for the `text`,
  `primary`, and background entries.
- **Bricks** — `bricks_global_settings` / the Bricks color palette option.
- **Neither** — the design's neutrals above.

The settings page shows the detected source and resolved values and allows
overriding any of the three. An explicit override survives re-detection until
cleared.

## Assets

`class-assets.php` registers the stylesheet on `init` and never enqueues it
globally. `Shortcode::render()` calls `wp_enqueue_style()` itself. Because the
shortcode runs during `the_content` — after `wp_enqueue_scripts` has already
fired — WordPress prints the late-enqueued stylesheet in the footer. To avoid a
flash of unstyled layout, the shortcode also prints the palette custom
properties and a minimal layout skeleton inline on the root element, so the
first paint is already structured.

Admin assets load only on the `author_profile` edit screen and the settings
page. `admin.js` is vanilla — repeater add/remove/reorder by cloning a
`<template>` row, and the WordPress media modal for image fields. No build step,
no npm.

## Security

- All output escaped: `esc_html`, `esc_attr`, `esc_url`; `wp_kses_post` for the
  bio only.
- Metabox saves are nonce-checked and gated on `current_user_can('edit_post')`.
- Settings use the Settings API with registered sanitizers per field; URLs
  through `esc_url_raw`, colors through a hex validator.
- External links (`follows[].url`) render with `rel="nofollow ugc noopener"`
  and `target="_blank"`.

## Verification

Manual, on a wp-lab instance:

1. Create two users with published posts of differing ages and post types.
2. Create profiles for both, filling every field group including all four stat
   modes.
3. Drop `[author_bio template=N]` for N = 1..10 on a page; confirm each layout
   matches its DC counterpart.
4. Visit an author archive with a bare `[author_bio]` in the template; confirm
   it resolves the URL's author.
5. Check the empty states: no profile, no portrait, empty repeaters, no
   published posts.
6. Check responsive behavior at 1440 / 1024 / 768 / 375.
7. Install on an Elementor site and a Bricks site; confirm palette detection
   and manual override.
