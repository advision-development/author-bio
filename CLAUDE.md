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
# 1. Bump BOTH, in the same commit.
#    author-bio.php   ->  * Version: 1.1.0
#    readme.txt       ->  Stable tag: 1.1.0
git commit -am "release: 1.1.0"
git push origin main

# 2. Tag it. Both v1.1.0 and 1.1.0 are accepted by the updater.
git tag -a v1.1.0 -m "Author Bio 1.1.0"
git push origin v1.1.0

# 3. Build the distributable. export-ignore in .gitattributes strips docs/,
#    PRODUCT.md, DESIGN.md, .impeccable/ and .superpowers/ — never hand-roll
#    an exclude list, and never zip the working tree.
git archive --format=zip --prefix=author-bio/ -o /tmp/author-bio.zip v1.1.0

# 4. Publish, attaching that zip.
gh release create v1.1.0 --repo advision-development/author-bio \
  --title "Author Bio 1.1.0" --notes-file /tmp/notes.md --latest \
  /tmp/author-bio.zip
```

**Always attach the zip.** `ABIO_Updater::package_url()` prefers an attached
`.zip` and falls back to GitHub's generated source archive, which unpacks to
`author-bio-<tag>/`. The updater renames that folder on the way in, so updates
survive it — but a human doing a manual first install from the source archive
ends up with a wrongly named plugin directory. Say so in release notes.

Sanity-check an archive before publishing: one top-level `author-bio/` folder,
and nothing from `docs/`.

```bash
unzip -Z1 /tmp/author-bio.zip | cut -d/ -f1 | sort -u   # => author-bio
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
- **Base element styles use `:where()`.** `.abio a` is (0,1,1) and outranks any
  single-class component rule, so a bare base rule silently strips every button
  of its own colour. This is what made a filled CTA render black-on-black.
- **Three colour seeds only** — ink, paper, accent. Everything else is
  `color-mix()` off them, which is how the palette re-tones to a host site. A
  literal hex belongs only in the seed declarations and their `@supports`
  fallback.
- **Wash is never behind body text.** It is a page ground. A box holding copy is
  Paper plus a hairline.
- **No font family outside `--abio-font`,** and no webfont ever. Type is
  inherited from the host theme by default.
- **Fixed-track grids collapse to their content.** Stat tiles and gallery items
  come from variable-length lists; a grid that keeps four tracks for three items
  paints a visibly dead cell, because these grids show their gaps.
- **Absence renders as less page.** Never a placeholder value, a blank cell, or
  an empty bordered frame. `ABIO_Profile` drops what cannot resolve, and
  templates guard every section with `! empty()`.
- **Never build an article teaser with `get_the_excerpt()`.** Generating an
  excerpt runs `the_content`, and page builders hook that filter to inject
  their widget CSS — which then arrives in the teaser as prose. Elementor's
  stylesheet appearing mid-sentence on a live site is how this was found.
  `ABIO_Articles::summary()` works from raw `post_content` and re-applies
  `wptexturize()` by hand, because smart quotes are the only part of that
  pipeline a teaser wants.
- **Never fabricate profile content.** Credentials, badges, affiliations and
  bylines are claims the site stands behind. Where material is missing the
  correct behaviour is to show less. Do not attach demo data to a real person.

## Architecture in one paragraph

`ABIO_Fields` is the field schema and the single source of truth; the admin UI
and the save path are both generated from it, so adding a field is one entry.
`ABIO_Profile` is the only thing templates talk to — it returns a finished array
and the ten templates are dumb views over it, containing no queries and no
option reads. `ABIO_Shortcode` resolves the author (explicit attribute → author
archive's queried object → current post's author) and includes one template.
Colour comes from `ABIO_Palette`, articles and stats from `ABIO_Articles` and
`ABIO_Stats`.

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
