# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Primary — in-house editorial staff at Advision-managed properties.** They
create and maintain one Author Profile per writer from the WordPress admin.
They know the content domain, but they are filling in a person's real
credentials and history, which is slower and more considered than ordinary post
editing, and it is done rarely: a profile is written once and revised
occasionally, not touched daily.

**Downstream — readers and search engines on the rendered author page.** They
do not use the admin at all. They arrive at an author archive from a byline and
are deciding whether this person's analysis is worth trusting.

Deployment is currently limited to sites Advision operates. Wider distribution
(client sites, wp.org, commercial) is **not decided** and must not be assumed by
future work.

The plugin is distributed from its own GitHub repository
(`advision-development/author-bio`, public) and reports updates into the
WordPress dashboard from published releases, including unattended updates on
sites that enable them. That means an installed copy is expected to keep
itself current, and a release is the unit of delivery — not a file copied into
a site repository.

## Product Purpose

Turn a byline into a credible, documented person, using one installable plugin
instead of per-site custom author templates.

A single `[author_bio]` shortcode renders a complete author page from a
dedicated Author Profile record linked to a WordPress user. Dropped into an
existing author archive template it resolves whichever author the URL names, so
a site adopts it by placing one shortcode rather than by building a page.

Two confirmed jobs:

1. **Author authority for search.** Demonstrable expertise, credentials, and
   publishing history are a ranking and trust asset for the kind of analytical
   content these properties publish.
2. **Editorial transparency.** Documented author identity, credentials, and a
   route to the editorial policy, presented consistently.

**Important qualifier the team stated explicitly:** not every property this
runs on is YMYL. Future work must not treat this as a regulated-compliance
tool or design around a compliance obligation that only some sites carry.

## Positioning

The author record is a first-class content type linked to a WordPress user, not
a pile of custom fields bolted onto the user object. That distinction is the
mechanism:

- Authors who are not WordPress users, and users who are not authors, are both
  representable.
- The profile survives independently of the user account and its role.
- One record feeds ten interchangeable layouts, so a site changes its author
  page by changing one shortcode attribute rather than rebuilding a template.
- The page adapts to the host site's colors and to the width of the container
  it is placed in, rather than imposing a fixed brand or requiring a full-width
  page.

## Operating Context

- **Authoring:** WordPress admin, an "Authors" menu holding Author Profile
  records plus one settings screen. Profiles are written once, then revised
  occasionally. Managing profiles requires editor-level capability;
  author-role users deliberately cannot create or edit them.
- **Placement:** a shortcode in a page, or in the theme's author archive
  template. The plugin does not own a route or a permalink of its own — the
  author archive remains the canonical author URL.
- **Confirmed host environments:** Elementor, Bricks, and block themes
  (Twenty Twenty-Five and similar). Classic PHP themes were **not** named as a
  target; support for them is untested rather than promised.
- **Content it reads from the live site:** published posts by the linked user
  supply the latest-articles list and the automatic stat tiles, so parts of the
  page stay current with no editorial effort.

## Capabilities and Constraints

Confirmed and implemented:

- Ten interchangeable author-page layouts, selected per shortcode by number or
  slug.
- An author index, `[author_bio_list]`, listing every saved profile vertically
  with headshot, name, role and published-article count, inheriting the look of
  whichever template is selected.
- Author resolution order: explicit attribute, then the author archive's
  queried object, then the current post's author.
- Per-profile content: identity, biography, badges, credentials, portrait,
  gallery, areas of focus, experience history, follows, and four stat tiles.
- Stat tiles resolve automatically (published count, first published year,
  count within one post type) or hold a typed value; a tile that cannot
  compute is omitted rather than rendered empty.
- Site-wide settings for site name, editorial-policy / contact / authors URLs,
  a contributor pitch, rendering defaults, palette overrides, typeface, and
  on/off toggles for the pitch box and the breadcrumb trail.
- Color adapts to the host: three seed colors detected from Elementor's global
  kit or the Bricks palette, with the rest derived, plus manual override.
- Layout responds to the width of its container rather than the viewport, so
  the shortcode survives a narrow themed content column.
- Typography is inherited from the host theme, with a settings override
  offering four system stacks. No webfont is ever loaded.
- Self-updating from GitHub releases via the WordPress `Update URI`
  mechanism, including unattended updates where a site enables them.
- A catch-all path: an author with no Author Profile still renders, from the
  WordPress user's name, Biographical Info and avatar plus their real articles
  and derived counts. One shortcode therefore covers every author on a site,
  and fields WordPress does not hold are omitted rather than guessed.

Technical constraints:

- PHP 7.4 floor, WordPress 6.0 floor.
- Zero dependencies — no Composer, no npm, no build step, no ACF or other
  field framework. This is deliberate and should be preserved.
- Shortcodes only — `[author_bio]` and `[author_bio_list]`. No Gutenberg block
  and no page-builder widget exist; adding either is an open product decision,
  not an assumed direction.
- No automated test suite, by explicit choice. Verification is manual, and
  `docs/VERIFICATION.md` is the checklist of record.

Terminology used consistently in code, admin, and docs: **Author Profile** (the
record), **linked user** (the WordPress user it points at), **stat tiles**,
**templates 1–10**.

Known open items future work must not treat as settled:

- Capability grants run only on plugin activation; an in-place update of an
  already-active install needs a deactivate/reactivate cycle.
- Palette detection has been verified against a real Elementor kit
  (bookmakersreview.com: ink from `text`, accent from `primary`). Two things
  remain open there — it maps our accent to Elementor's *primary* rather
  than the colour Elementor itself calls "accent", and it has still never
  run against a real Bricks palette.

## Brand Commitments

Authored by Advision Development. No voice, identity, or asset constraints have
been established for the plugin itself, and the templates deliberately take
their color from the host site rather than carrying a brand of their own.

## Evidence on Hand

- **Real authors exist; their profile material does not yet.** The writers are
  genuine and have real bylines and publishing history on the live sites, but
  credentials, areas of focus, experience copy, and portraits still have to be
  gathered person by person.
- **Everything in the source design is invented.** "Jefferson Hayes", "Trade
  The Event", the credentials, the stat figures, and the article titles are
  placeholder content from the design comp. None of it describes a real person
  or property and none of it may be carried into shipped copy or presented as
  real.
- Future work must not fabricate credentials, affiliations, qualifications,
  bylines, or verification claims. Where material is missing, the correct
  behavior is to show less, not to invent.
- Because profiles will be populated unevenly and over time, partial and empty
  profiles are the expected state at launch, not an edge case.

## Product Principles

1. **A person's record is entered once and reused everywhere.** Ten layouts,
   many sites, one profile per author — never a second copy of the same truth.
2. **Absence is shown honestly.** A missing credential, portrait, or stat
   renders as less page, never as a blank frame, a placeholder, or an invented
   value.
3. **The plugin adapts to its host.** Color comes from the site's own palette
   and layout from the container it is given. It never demands a full-width
   page or imposes a brand.
4. **The author page is an authority asset on some properties and plain
   credibility on others.** It must earn trust without assuming a compliance
   obligation that only part of the estate carries.
5. **Editorial effort goes into what only a human knows.** Anything WordPress
   already knows — publishing history, counts, dates — is derived, not typed.

## Accessibility & Inclusion

No formal standard has been adopted and none is claimed. The stated bar is
craft rather than compliance: no obvious failures. Concretely, future work
holds text contrast, visible focus states, sensible semantics, and keyboard
access, and the palette contrast guard on detected colors exists to keep an
adaptive palette from falling below a readable floor.

Recorded as a principle, not a requirement. If a property later carries a real
obligation, that is a new decision to capture here.
