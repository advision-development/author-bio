# Author Bio — working notes

A WordPress plugin. `[author_bio]` renders a complete author page from a
dedicated Author Profile post type linked to a WordPress user, across ten
interchangeable layouts.

- **Product truth** — `PRODUCT.md`. Who it's for, what it's for, what's decided.
- **Visual system** — `DESIGN.md` (plus `.impeccable/design.json`). Normative.
- **Verification** — `docs/VERIFICATION.md`. The checklist of record.
- **Design source** — `docs/design/author-page-templates.dc.html`, the comp the
  ten templates were ported from. 1293 lines; read only the range you need.

---

## Release protocol

Releases are the unit of delivery. Installed copies self-update from this
repository's GitHub releases via `ABIO_Updater`, so a release is what ships —
not a file copied into a site repo.

**The version header is the trigger.** The tag must compare higher than the
`Version:` header of the installed copy or nothing is offered. Bumping the
header is the step that cannot be skipped.

```bash
# 1. Bump all THREE, in the same commit. Missing the constant is easy: the
#    header drives the update check, the constant drives asset cache-busting.
#    author-bio.php   ->  * Version:     1.1.0
#    author-bio.php   ->  define( 'ABIO_VERSION', '1.1.0' );
#    readme.txt       ->  Stable tag: 1.1.0
#
#    Verify they agree before committing — this must print one distinct value:
grep -ho "1\.[0-9]*\.[0-9]*" author-bio.php readme.txt | sort -u

git commit -am "release: 1.1.0"
git push origin main

# 2. Tag it. Both v1.1.0 and 1.1.0 are accepted by the updater.
git tag -a v1.1.0 -m "Author Bio 1.1.0"
git push origin v1.1.0

# 3. Build the distributable. export-ignore in .gitattributes strips docs/,
#    PRODUCT.md, DESIGN.md, CLAUDE.md, .impeccable/ and .superpowers/ — never
#    hand-roll an exclude list, and never zip the working tree.
#
#    The filename carries the version; the --prefix does not. That prefix is
#    what names the installed folder, so it stays `author-bio/` for every
#    release regardless of what the file is called.
git archive --format=zip --prefix=author-bio/ -o /tmp/author-bio-1.1.0.zip v1.1.0

# 4. Publish, attaching that zip.
gh release create v1.1.0 --repo advision-development/author-bio \
  --title "Author Bio 1.1.0" --notes-file /tmp/notes.md --latest \
  /tmp/author-bio-1.1.0.zip
```

**Always attach the zip, and attach exactly one.**
`ABIO_Updater::package_url()` walks the release's assets and takes the **first**
one whose name ends in `.zip`; it matches on the extension, not on a filename,
which is why versioning the filename is safe. It also means a second zip asset
on the same release makes the choice arbitrary — if you need to replace a
published asset, delete the old one before uploading the new one rather than
after. With no zip at all it falls back to GitHub's generated source archive,
which unpacks to `author-bio-<tag>/`. The updater renames that folder on the way
in, so updates survive it — but a human doing a manual first install from the
source archive ends up with a wrongly named plugin directory. Say so in release
notes.

Sanity-check an archive before publishing: one top-level `author-bio/` folder,
and nothing from `docs/`.

```bash
unzip -Z1 /tmp/author-bio-1.1.0.zip | cut -d/ -f1 | sort -u   # => author-bio
```

### Version numbering

Semver against behaviour an installing site can observe. A template port, a new
field, or a new setting is a minor; a fix that changes rendering is a patch. If
a change alters the committed visual world, `DESIGN.md` is updated in the same
commit — the doc is not allowed to lag the code.

---

## Invariants that are easy to break

These were each a real bug at some point. The full doctrine is in `DESIGN.md`;
this is the short list of things that look harmless and are not.

- **Container queries, never viewport media queries.** The shortcode lands in a
  full-bleed builder section on one site and a ~620px themed content column on
  the next, and `@media` cannot tell those apart. All 30 breakpoint blocks are
  `@container` against `.abio`, which declares `container-type: inline-size`.
- **A class on an `li` — or on the `ul`/`ol` — loses to the reset.**
  `.abio li { padding: 0 }` is (0,1,1); `.abio-l1__rows > li` ties it and wins
  on source order, which is why every row rule ends in `li`. The same rule
  covers the list element itself: `.abio ul, .abio ol` zero margin *and*
  padding, so anything setting `max-width`, `margin: 0 auto` or `padding` on a
  rows container must be written `ul.abio-lN__rows` / `ol.…`. Bare-class
  versions fail silently — the block renders full-bleed with no inset, which is
  exactly how the sports-desk index shipped its cards edge-to-edge until a
  screenshot caught it. Do not weaken the reset with `:where()` here — unlike
  the link rule, it is what defends list markup against host CSS.
- **Base element styles use `:where()`.** `.abio a` is (0,1,1) and outranks any
  single-class component rule, so a bare base rule silently strips every button
  of its own colour. This is what made a filled CTA render black-on-black.
- **Three colour seeds, and derivation is still the default.** Ink, paper and
  accent are the seeds; the other ten colours are `color-mix()` off them, which
  is how the palette re-tones to a host site. In the stylesheet a literal hex
  still belongs only in the seed declarations and their `@supports` fallback.
  Authors → Settings now exposes the ten derived colours as *optional*
  overrides — blank means "keep deriving", and only a filled field is emitted
  into the root element's inline style, where it outranks the `color-mix()`.
  Do not turn that into a full manual palette by giving those fields defaults:
  the moment every colour is literal, a site that changes its brand colour no
  longer re-tones, which is the property the three seeds exist to provide.
  `ABIO_Palette::MIXES` is the single description of how each one is mixed, and
  it must stay in step with the `@supports` block by hand — the CSS is not
  generated from it.
- **The two scales are multipliers that must be inert at 1.** `--abio-space`
  (density) and `--abio-width` (content width) are unitless, and every value
  they touch is written `calc(<authored px> * var(--abio-…))` so that at 1 it
  computes to exactly the authored pixel. That is what makes the settings safe
  to ship: a site that never opens them renders identically. Verified once by
  measuring every element's box geometry before and after the refactor — all
  92 rects on a profile page were byte-identical. If you add a padding or gap,
  wrap it the same way or density silently stops applying to it.
- **Wash is never behind body text.** It is a page ground. A box holding copy is
  Paper plus a hairline.
- **No font family outside `--abio-font`,** and no webfont ever. Type is
  inherited from the host theme by default.
- **Fixed-track grids collapse to their content.** Stat tiles and gallery items
  come from variable-length lists; a grid that keeps four tracks for three items
  paints a visibly dead cell, because these grids show their gaps.
- **Absence renders as less page.** Never a placeholder value, a blank cell, or
  an empty bordered frame. `ABIO_Profile` drops what cannot resolve, and
  templates guard every section with `! empty()`. The sharpest version of
  this bug is a decorative stand-in that can never be filled — the comp's
  hatched avatar circle shipped in "Other authors" for weeks before anyone
  noticed it was not a portrait waiting for an image, it was a drawing. If
  a comp shows a placeholder, ask what real data belongs there.
- **Never build an article teaser with `get_the_excerpt()`.** Generating an
  excerpt runs `the_content`, and page builders hook that filter to inject
  their widget CSS — which then arrives in the teaser as prose. Elementor's
  stylesheet appearing mid-sentence on a live site is how this was found.
  `ABIO_Articles::summary()` works from raw `post_content` and re-applies
  `wptexturize()` by hand, because smart quotes are the only part of that
  pipeline a teaser wants.
- **`get_users( capability: … )` can return the same person twice** when they
  hold several capability-granting roles — it happens on bookmakersreview.
  De-duplicate by ID before rendering a list of users, or a multi-select
  shows duplicate options and can submit an ID twice.
- **The settings screen is one form, and it must stay one form.** The five
  tabs are `display` toggled on one `<form>`; every field posts on every save.
  Giving each tab its own form looks tidier and silently destroys data:
  `ABIO_Settings::sanitize()` reads a missing checkbox as "off" — correct,
  because an unchecked box posts nothing — so saving the Colors tab would
  switch off Pitch box, Breadcrumbs and Unconfigured authors and empty the
  selected-authors list. Verified by counting inputs: all 32 settings keys
  appear inside the single form.
- **The admin is WordPress's surface, not this plugin's.** `DESIGN.md` governs
  the author page. The settings screen uses core's `nav-tab`, `form-table`,
  `button`, `wp-color-picker` and `--wp-admin-theme-color`, so it follows the
  user's admin colour scheme and stays legible when core restyles. The design
  detector will flag a couple of admin values against `DESIGN.md`; those are
  documented exceptions at the top of `assets/admin/settings.css`, not drift to
  correct. Do not import the Dossier palette into the admin.
- **Never reconstruct a screen's hook suffix.** `ABIO_Settings::assets()`
  compares against the value `add_submenu_page()` returned, captured into
  `$hook_suffix`. The obvious guess is wrong: the page under the Authors menu
  reports `admin_page_abio-settings`, not `author_profile_page_abio-settings`.
  A wrong guess loads the colour picker on no screen or on every screen.
- **Never fabricate profile content.** Credentials, badges, affiliations and
  bylines are claims the site stands behind. Where material is missing the
  correct behaviour is to show less. Do not attach demo data to a real person.
  This governs `ABIO_Schema` as hard as it governs the templates: structured
  data is the machine-readable version of the same claims, and a padded graph
  is a padded claim. `prune()` drops every key that did not resolve, credentials
  carry a name and nothing else — no invented issuer, level or date — and
  `dateCreated`/`dateModified` are emitted only for a post-backed profile,
  because a virtual one has no authored record to date.
- **JSON-LD is encoded with `JSON_HEX_TAG`.** It is the only thing standing
  between a profile field and a `</script>` breakout, since the payload is
  dropped inside a `<script>` element where `esc_html()` would corrupt the JSON.
  `ABIO_Schema::script()` is the single place any of this is emitted; do not
  hand-build a second one.

- **`get_post_meta( 0, … )` returns `false`, not `''`.** A virtual profile —
  the catch-all built from a WordPress user with no Author Profile post — has
  no post behind it, so every `'' === $value` fallback in `author()` silently
  failed and the page rendered with no name. `ABIO_Profile::meta()` normalises
  this. Watch for it anywhere a profile might not be post-backed.

## Architecture in one paragraph

`ABIO_Fields` is the field schema and the single source of truth; the admin UI
and the save path are both generated from it, so adding a field is one entry.
`ABIO_Profile` is the only thing templates talk to — it returns a finished array
and the ten templates are dumb views over it, containing no queries and no
option reads. `ABIO_Shortcode` resolves the author (explicit attribute → author
archive's queried object → current post's author) and includes one template; if
that author has no profile it falls back to `ABIO_Profile::fallback_for_user()`,
a virtual profile backed by the WordPress user alone.
Colour comes from `ABIO_Palette`, articles and stats from `ABIO_Articles` and
`ABIO_Stats`. `ABIO_Directory` is the one query path for listing profiles —
both `[author_bio_list]` and every template's "Other authors" block go through
it, so the two can never disagree about who counts as an author.
`ABIO_Schema` turns either shape into JSON-LD (`ProfilePage`/`Person` for a
profile, `ItemList` for an index) and both shortcodes append it after the styled
root. The index has ten views of its own, `templates/list-1.php` … `list-10.php`,
one per template; the row contract they all render — portrait, kicker, name,
role, short line — comes from `ABIO_Directory` and is the thing to hold constant
when editing them.

## Conventions

- Prefixes: `ABIO_` classes, `abio_` functions/options, `_abio_` meta,
  `abio-`/`--abio-` CSS. No exceptions.
- PHP 7.4 floor, WordPress 6.0 floor, **zero dependencies** — no Composer, no
  npm, no build step, no ACF. Deliberate; preserve it.
- Meta keys only via `ABIO_Fields::meta_key()`.
- Text domain `author-bio` on every translation call.
- Escape at output: `esc_html` / `esc_attr` / `esc_url`; `wp_kses_post` for the
  bio and the pitch body only.

## Testing

There is **no automated test suite**, by the owner's explicit choice. Do not add
one without asking.

- `find . -name '*.php' -not -path './docs/*' -print0 | xargs -0 -n1 php -l`
- Local PHP is 8.5, so `php -l` will **not** catch 7.4-floor violations
  (`match`, enums, promoted constructors, `?->`). Read for those.
- Behavioural verification means rendering it. Every real bug in this plugin's
  history was found by looking at output, not by reading source — a dead grid
  cell, a black-on-black button, a grey panel, an empty frame. Screenshot it.

### Running it locally

No stack is committed. Two routes that have worked:

- **Disposable:** WordPress on SQLite. Download core plus the
  `sqlite-database-integration` plugin, copy its `db.copy` to
  `wp-content/db.php`, install with wp-cli, serve with `php -S`. Set
  `WP_HTTP_BLOCK_EXTERNAL` or WordPress's own update check will hang with no
  network egress and take the built-in server down with it.
- **Realistic:** `/Users/core/dev/advision/bookmakersreview-site` — `make up`,
  then `make wp CMD="..."` for wp-cli. Real Bricks and Elementor, so it is the
  only place palette detection can be exercised properly. Its `wp-content` is a
  Docker volume with only some subdirectories bind-mounted; a scratch file has
  to go in a mounted path like `wp-content/plugins/`. Plugins there are
  gitignored unless whitelisted, so a copy of this plugin is invisible to their
  history.

Screenshots headlessly, since these are visual templates:

```bash
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless \
  --disable-gpu --hide-scrollbars --window-size=1440,1400 \
  --screenshot=/absolute/path.png "http://localhost:8080/?pagename=x"
```

Use an absolute output path — a relative one silently writes nothing.

## Known open items

- **Capability grants run on activation only.** Managing profiles needs
  editor-level caps, added by `ABIO_Post_Type::add_capabilities()` from the
  activation hook. Updating an already-active copy in place does not re-run it,
  and admins lose the Authors menu until they deactivate and reactivate. A
  version-checked re-sync is the fix and is not written yet.
- **Elementor accent mapping.** Our accent maps to Elementor's `primary`, not
  the colour Elementor itself calls "accent". Verified sensible on
  bookmakersreview (primary is their link/button blue) but it is a judgement
  call.
- **Bricks palette detection has never run against a real Bricks palette.**
  Elementor's path is verified; Bricks' is code-complete and untested.
