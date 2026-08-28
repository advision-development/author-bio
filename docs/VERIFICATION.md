# Author Bio — Manual Verification Checklist

There is no automated test suite for this plugin (see the project's Global
Constraints). This checklist is the human-run equivalent of Task 13's
verification matrix. It must be worked through on a real WordPress install —
it cannot be scripted or run in CI.

## Installing the plugin

1. From the plugin's working directory, zip the plugin folder, excluding the
   non-plugin directories:

       zip -r author-bio.zip . -x 'docs/*' -x '.git/*' -x '.superpowers/*'

2. In wp-admin, go to **Plugins → Add New → Upload Plugin**, upload
   `author-bio.zip`, and activate it.
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
      (or higher), shows the diagnostic line "Author Bio: no published
      author profile is linked to this author."

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

---

Record any failing line here (or in your own notes) with enough detail to
reproduce it, fix the underlying issue, and re-run just that line before
considering Task 13 complete.
