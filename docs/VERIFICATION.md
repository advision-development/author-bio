# Author Bio — Manual Verification Checklist

There is no automated test suite for this plugin (see the project's Global
Constraints). This checklist is the human-run equivalent of Task 13's
verification matrix. It must be worked through on a real WordPress install —
it cannot be scripted or run in CI.

## Installing the plugin

1. Build the zip the way a release does, so you are verifying what ships.
   `.gitattributes` carries the exclusions, so nothing has to be listed here —
   and the hand-rolled `zip -r` this used to recommend left `CLAUDE.md` in the
   package:

       git archive --format=zip --prefix=author-bio/ -o /tmp/author-bio-test.zip HEAD

   To test uncommitted work, commit it to a scratch branch first and archive
   that; `git archive` reads the tree, not the working directory.

2. In wp-admin, go to **Plugins → Add New → Upload Plugin**, upload that zip,
   and activate it.
3. Turn on `WP_DEBUG` (and ideally `WP_DEBUG_LOG`) in `wp-config.php` before
   starting — several checks below depend on seeing (or not seeing) PHP
   notices.

## Test content needed before you start

Set this up once, then run every check below against it:

- **Two WordPress users**, each with several published posts, spread across
  **two different categories** (so the article list's category label has
  something to show and differ between authors).
- **One of those posts edited well after its original publish date** (more
  than a day later), so the "Updated" vs. "Published" article status has a
  case to show.
- **An Author Profile for each user**, each linked via the "Linked user"
  field, with **every field group filled in**:
  - Identity (kicker, name, role, location, since, badges, portrait, bio,
    short bio)
  - Gallery (heading, note, and at least two gallery items with images)
  - Areas of focus (at least two rows)
  - Experience (at least one row)
  - Credentials (at least one row)
  - Follows (at least one row)
  - **All four stat tile modes exercised** across the stat rows (i.e. at
    least one tile in each of the four available modes, including one set to
    "not shown")
- A third profile intentionally left mostly empty (linked user and name
  only) for the empty-state checks.
- A fourth user with **no** Author Profile at all, for the missing-profile
  checks.

Keep a page (or the theme's author archive template) with
`[author_bio template=1]` in it so you can visit `/author/<login>/`-style
URLs for the resolution checks.

---

## Rendering

- [ ] **1. All ten templates render.** For N = 1 through 10, place
      `[author_bio template=N]` on a page and view it. Each of the ten
      renders its distinct layout, and with `WP_DEBUG` on, none produce a PHP
      notice or warning.
- [ ] **2. Slugs match numbers.** For each template, render it once by number
      and once by its slug and confirm identical output:

      [author_bio template=1]   ==  [author_bio template="classic-sidebar"]
      [author_bio template=2]   ==  [author_bio template="resume"]
      [author_bio template=3]   ==  [author_bio template="editorial-masthead"]
      [author_bio template=4]   ==  [author_bio template="bento"]
      [author_bio template=5]   ==  [author_bio template="numbered-rail"]
      [author_bio template=6]   ==  [author_bio template="dossier"]
      [author_bio template=7]   ==  [author_bio template="sports-desk"]
      [author_bio template=8]   ==  [author_bio template="fintech"]
      [author_bio template=9]   ==  [author_bio template="research-note"]
      [author_bio template=10]  ==  [author_bio template="brand-feature"]

- [ ] **3. Invalid template falls back to 1, without erroring.**

      [author_bio template=99]
      [author_bio template=banana]

      Expected: both render exactly what `[author_bio template=1]` renders,
      no PHP error either way.

## Resolution

- [ ] **4. Author archive resolution.** Add `[author_bio template=1]` to the
      theme's author archive template (or to a page assigned as one), then
      visit the archive URL for each of the two test authors. Expected: each
      archive shows that author's own profile, not the other's.
- [ ] **5. Explicit `user` attribute, by login and by ID.**

      [author_bio user=<login>]
      [author_bio user=<id>]

      Expected: both resolve to the same profile as that user's archive.
- [ ] **6. Explicit `id` attribute.**

      [author_bio id=<profile post id>]

      Expected: renders that Author Profile directly.
- [ ] **7. No linked profile.** Visit the archive (or a page with
      `[author_bio user=<user with no profile>]`) for the fourth test user.
      Expected: renders nothing when logged out; when logged in as an editor
      (or higher), shows a diagnostic line naming the resolved user by ID and
      display name, e.g. "Author Bio: no published author profile is linked
      to user #7 (Jane Doe)." When no author can be resolved at all (e.g. an
      `[author_bio]` with no attributes on a page that is not an author
      archive or singular view), the line says plainly that no author could
      be resolved.

## Data

- [ ] **8. Article row content.** On a template that lists articles (e.g.
      template 1 or 6), confirm each row: links to the real post, shows the
      real publish date, shows a category name for `post` items and the
      post type's singular label for other post types, shows "Updated"
      only for the post edited well after publication (and "Published" for
      the rest), and shows a plausible read time (based on word count).
- [ ] **9. `count` and `post_type` attributes take effect.**

      [author_bio count=2]
      [author_bio post_type="post,page"]

      Expected: the first lists at most 2 articles; the second includes
      pages as well as posts (if the test user has published pages).
- [ ] **10. Stat tiles.** Confirm all four stat modes on the test profile
      produce correct, plausible values, and that switching a tile's mode to
      "not shown" removes that tile from **every** template that displays
      stats (not just the one you're currently viewing).
- [ ] **11. Hiding every section.**

      [author_bio hide="focus,edits,experience,gallery,stats,credentials,follows,others,pitch"]

      Expected: still a valid, non-broken page, showing only the header
      (name/role/kicker area) — no empty boxes or stray section headings for
      the hidden sections.

## Empty states

- [ ] **12. Minimal profile.** Render all ten templates
      (`[author_bio id=<mostly-empty profile id>]` for each) against the
      profile that has only a linked user and a name. Expected: no fatal
      error, no stray section heading with nothing under it, no empty
      bordered box, on any template.
- [ ] **13. No portrait / no gallery images.** On that same minimal profile,
      confirm every template shows the hatched placeholder graphic for the
      portrait and for any gallery slots — never a broken image icon.

## Palette

- [ ] **14. Manual palette override.** In **Authors → Settings**, set the Ink
      override to `#0b2545` and Paper to `#fdfdfb`, save, then reload a page
      containing each template. Expected: every template's colors shift
      accordingly, including the dark bands in templates 4, 6, 7 and 10.
- [ ] **15. Clearing overrides.** Clear both the Ink and Paper override
      fields, save, and reload. Expected: templates return to the palette
      that was auto-detected on activation.

> **Needs a page-builder site:** palette auto-detection (Elementor global
> colors or the Bricks color palette) cannot be exercised on a plain wp-lab
> instance with no page builder installed. Run this on a real Elementor or
> Bricks site, or install the free Elementor plugin on the wp-lab instance,
> set two Elementor global colors, and re-activate the plugin (or use the
> Settings page's "Re-detect from page builder" button) to confirm it picks
> those colors up.

## Responsive

- [ ] **16. No horizontal scroll / no clipped text.** At each of 1440px,
      1024px, 768px, and 375px viewport widths, load every template and
      confirm: no template causes the page to scroll horizontally, and no
      text is visually clipped or overlapping.

## Admin

- [ ] **17. Linked user column.** In **Authors** (the post list table),
      confirm the "Linked user" column is present, shows each profile's
      linked user's display name, and that name links to that user's author
      archive. Profiles with no linked user show an em dash.
- [ ] **18. Duplicate-user warning and precedence.** Create a second Author
      Profile linked to a user who already has one. Expected: the admin
      shows a duplicate warning, and on the front end
      (`[author_bio user=<that user>]` or their archive), the **lower
      post ID** profile keeps rendering — not the newly created one.
- [ ] **19. Author Profile capability separation.** Create a plain Author
      user (no Editor/Administrator role) and log in as them. Expected: the
      **Authors** menu does not offer "Add New", any existing Author Profile
      opens read-only or not at all, and this is true even though the same
      user can freely publish an ordinary post. Confirm the reverse too: an
      Editor and an Administrator can both create, edit, and publish Author
      Profiles normally (deactivate and reactivate the plugin first if it was
      already active before this check was added, so the new capabilities
      get granted to those roles). Editors and Administrators without
      `list_users` (test with a role-editor plugin if needed to remove it
      from Editor) see only themselves — not a full user list — in the
      "Linked user" field.

---

Record any failing line here (or in your own notes) with enough detail to
reproduce it, fix the underlying issue, and re-run just that line before
considering Task 13 complete.

## Layout in context (added after the first local run)

- [ ] Place `[author_bio template=1]` inside a **narrow** container — a default
      constrained theme content column, or a page-builder column of roughly
      600px — at a wide desktop viewport. The template must collapse to its
      narrow layout (portrait above the header, single-column focus), not
      render a wide layout crushed into the column. This is what container
      queries fix; before them the breakpoints read the viewport and never
      fired here.
- [ ] Configure a profile so that fewer than four stat tiles resolve (set one
      tile to "not shown", or to an automatic count the author has none of).
      The stat strip must show only the tiles that resolved, evenly
      distributed, with no empty or grey trailing cell — check at full width,
      at roughly 700px, and on mobile.

## The author index (added with the ten bespoke index views)

- [ ] Put `[author_bio_list template=N]` on a page and step `N` through
      `1`–`10`. Each must be a visibly different composition, not the same
      layout re-tinted: cards on a wash for 1, 3 and 7; a single hairline
      column for 2; a dark header strip for 4; accent ordinals and round
      portraits for 5; a full-bleed dark head with 2px row rules for 6;
      rounded cards and an outlined pill for 8; one bordered sheet of numbered
      entries for 9; oversized names for 10. If two are indistinguishable, the
      per-template CSS has been lost — check the rows selector still carries
      its element qualifier (`ul.abio-lN__rows`).
- [ ] No index shows a published-article count, on any of the ten.
- [ ] Every row shows portrait, kicker, name, role and short line — and drops
      the ones the author has no value for rather than leaving a gap. Check
      against an author with a full profile *and* an author listed by ID with
      no profile at all; the latter has no role or short line, so those rows
      must simply be shorter.
- [ ] On a **Bricks or Elementor page**, confirm the shortcode is in the
      builder's own shortcode element. A builder page ignores `post_content`
      entirely, so editing the page content there changes nothing — which
      reads exactly like the template attribute being broken.

## Structured data

- [ ] With **Authors → Settings → General → Structured data** left off — the
      default — view source on a profile page and an index. There must be **no**
      `application/ld+json` block from this plugin at all. Any SEO plugin's own
      block is not ours; check the graph is not a `ProfilePage` or `ItemList`.
- [ ] Switch the setting on for the checks below, and decide deliberately
      whether to leave it on: if the site runs Yoast or Rank Math, the author
      archive will then carry two descriptions of the same person.

- [ ] View source on a profile page. There must be exactly one
      `application/ld+json` block, it must be `ProfilePage` with a `Person` in
      `mainEntity`, and it must parse — paste it into
      <https://validator.schema.org/>.
- [ ] View source on an index page. One `ItemList`, one `ListItem` per row.
- [ ] Confirm the `@id` of a person in the index is byte-identical to the
      `@id` on that author's own page. If they differ, the graph claims two
      people.
- [ ] Against a **minimal** profile: the graph must contain no empty strings,
      no empty arrays, and no `dateCreated`/`dateModified` when the profile is
      the virtual kind (a WordPress user with no Author Profile post).
- [ ] Against a profile with credentials: each is a bare
      `EducationalOccupationalCredential` with a `name`. If an issuer, date or
      level has appeared, something is fabricating claims.
- [ ] Put `</script>` and a tag into a profile's Name and Bio, save, and view
      source. The page must still have exactly one `<script>` opening tag in
      the JSON-LD block and the JSON must still parse.
- [ ] With it on, and the site running Yoast or Rank Math, confirm the author
      archive now carries two `Person` blocks. That is the duplication the
      default exists to avoid — switch the setting back off, or narrow it with
      `abio_schema_enabled` per context.

## Design tokens (added with the admin token controls)

- [ ] With every token setting left alone, a profile page and an index must
      render **identically** to the previous release. The controls are inert at
      their defaults by construction (`calc(24px * 1)`), so any visible change
      means a value was rewritten wrongly rather than wrapped.
- [ ] **Authors → Settings → Derived colours**: every one of the ten fields is
      blank, and each shows a mixed hex as its placeholder. A blank field must
      leave that colour absent from the root element's `style` attribute —
      inspect the element and confirm only `--abio-ink`, `--abio-paper` and
      `--abio-accent` are present.
- [ ] Fill in one derived colour, save, and confirm it appears in the inline
      style and wins over the `color-mix()`. Clear it and confirm it disappears
      again and the mix resumes.
- [ ] Change Ink or Accent and confirm every colour you did **not** override
      re-tones with it. This is the property the three-seed rule exists for; if
      it stops working, something gave the derived fields defaults.
- [ ] Corners → Square: every card, chip, pill and image frame is a right
      angle, **and template 5's round portraits are still circles**. Corners →
      Rounded: 8/12px corners. Template 5's portraits must not change in either
      direction.
- [ ] Content width → Narrow and Wide: all ten templates move together but keep
      their relative proportions — the masthead stays narrower than the
      product-UI template. No template may overflow its container at either
      setting; check inside a ~620px themed column, not just at full width.
- [ ] Density → Compact and Roomy: padding and gaps scale, type does not.
      Nothing collides, and no row loses its separation.
- [ ] Paste junk into a colour field (`red; background:url(x)`) and save. It
      must store as empty, never reach the `style` attribute.

## The settings screen (added with the tabs)

- [ ] **Authors → Settings** opens on **Author index**, not General.
- [ ] Click through all five tabs. Each shows only its own fields, the tab strip
      marks the active one, and the browser's address bar gains
      `#abio-panel-<tab>`.
- [ ] Focus a tab and press Left/Right/Home/End. The selection moves, focus
      follows it, and only the selected tab is in the tab order.
- [ ] **The save test that matters.** Switch off Pitch box on Content, select
      two authors on Author index, then go to the Colors tab and press Save
      Changes. Re-open both tabs: the toggle must still be off and the two
      authors must still be selected. If either reset, the tabs have been split
      into separate forms and every save is destroying the other tabs.
- [ ] Saving shows "Settings saved." and returns you to the tab you saved from.
- [ ] Each color field is a WordPress color picker. Picking a color changes the
      line beneath from "Using #…" to "Overriding #…"; pressing Clear changes it
      back. Save and confirm the value persisted.
- [ ] The derived-colors disclosure is closed on a site with no overrides, and
      opens by itself when one is set — a saved value must never be hidden.
- [ ] Disable JavaScript and reload. Every panel is visible in one scroll, each
      under its own heading, and Save Changes still saves everything.
- [ ] Switch the admin color scheme in your user profile. The focus ring on the
      tabs follows it rather than staying WordPress blue.

## The author token field

- [ ] **Authors → Settings → Author index.** Every already-selected author shows
      as a token under the dropdown, and the dropdown lists everyone else.
- [ ] Pick someone. They become a token and leave the dropdown; the dropdown
      returns to "Add an author…" with focus still on it, so several can be
      added in a row without reaching for the mouse.
- [ ] Remove a token. They reappear in the dropdown, in alphabetical position.
- [ ] **The duplication check.** Add two authors, save, and re-open. Each must
      appear once. Two of each means the fallback select kept its `name` and is
      posting alongside the tokens.
- [ ] Remove every token and save. The list must save as empty, not fall back to
      the previous selection.
- [ ] Add every eligible user. The dropdown disables itself and reads "Every
      eligible user has been added."
- [ ] Keyboard only: tab to the dropdown, choose with the keyboard, then tab to
      a token's × and press Enter. Each token's button names the person it
      removes rather than just saying "Remove".
- [ ] Disable JavaScript and reload. The original multi-select is there with the
      Command-click instruction, and saving from it still works.
