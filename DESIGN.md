---
name: Author Bio
description: A near-monochrome, container-relative author-page system that takes its color from its host.
colors:
  ink: "#17181a"
  paper: "#fbfbfa"
  accent: "#17181a"
  wash: "#f4f4f2"
  line: "#e2e2df"
  muted: "#8a8c90"
  dim: "#5a5c60"
  soft: "#6f7175"
  faint: "#b0b0ab"
  accent-soft: "#6f7175"
  onink: "#c6c6c1"
  onink-dim: "#a8a8a3"
  onink-line: "#3a3c40"
typography:
  display:
    fontFamily: "inherit"
    fontSize: "64px"
    fontWeight: 500
    lineHeight: 1.02
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "inherit"
    fontSize: "28px"
    fontWeight: 500
    lineHeight: 1.2
    letterSpacing: "normal"
  title:
    fontFamily: "inherit"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: "normal"
  body:
    fontFamily: "inherit"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.65
    letterSpacing: "normal"
  label:
    fontFamily: "inherit"
    fontSize: "11px"
    fontWeight: 500
    lineHeight: 1
    letterSpacing: "0.18em"
rounded:
  none: "0"
  sm: "4px"
  md: "6px"
  pill: "99px"
  circle: "50%"
spacing:
  xs: "6px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
  xxl: "32px"
components:
  chip:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.dim}"
    rounded: "{rounded.none}"
    padding: "4px 10px"
  chip-on-dark:
    backgroundColor: "transparent"
    textColor: "{colors.onink}"
    rounded: "{rounded.none}"
    padding: "4px 10px"
  cta:
    backgroundColor: "transparent"
    textColor: "{colors.accent}"
    rounded: "{rounded.none}"
    padding: "9px 15px"
  cta-hover:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.paper}"
    rounded: "{rounded.none}"
    padding: "9px 15px"
  cta-on-dark:
    backgroundColor: "transparent"
    textColor: "{colors.wash}"
    rounded: "{rounded.none}"
    padding: "9px 15px"
  panel-dark:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.wash}"
    rounded: "{rounded.none}"
  media-empty:
    backgroundColor: "{colors.wash}"
    textColor: "{colors.faint}"
    rounded: "{rounded.none}"
    height: "60px"
  stat-tile:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.none}"
    padding: "26px 24px"
  missing-notice:
    backgroundColor: "transparent"
    textColor: "{colors.muted}"
    rounded: "{rounded.none}"
    padding: "12px 16px"
---

# Design System: Author Bio

## Overview

**Creative North Star: "The Dossier"**

A dossier is a record assembled about a person: an identifying photograph,
filed credentials, a track record, source material with dates attached. This
system renders exactly that, and its visual restraint is the argument. Nothing
floats, nothing animates, nothing is styled to persuade — because the page's
job is to be believed, and decoration reads as salesmanship on a page whose
entire purpose is establishing that a named human knows what they are talking
about. The evidence is the design.

The register is editorial and factual, but the subject is a person rather than
a case file, so the system stays warm rather than clinical. That warmth is
carried in the paper (a slightly off-white `#fbfbfa`, never pure white), in
generous line-height on running prose, in real portraits at scale, and in a
measure held to 58–70 characters so a biography reads like writing rather than
a form field. The tracked uppercase labels supply the archival precision; the
prose supplies the human.

Color is not decoration here — it is inheritance. Only three seed values exist,
and on a configured site all three come from the host's own palette, with the
remaining ten derived from them by mixing. The system therefore has no fixed
brand of its own by design, and its identity lives entirely in structure:
hairline rules, tracked uppercase labels, sharp corners, honest empty states,
and a rigorously flat surface. Type is inherited too: the page speaks in the
host's own voice.

**Key Characteristics:**

- Near-monochrome, derived from two seeds plus an accent that adapt to the host site
- Flat by construction — no shadows, no gradients, no motion of any kind
- Hairline rules and tonal fills carry every separation
- One inherited typeface throughout; labels separate by case, tracking and weight alone
- Sizes itself to its container, never to the viewport
- Absence renders as less page, never as a placeholder or an empty frame

## Colors

A near-monochrome greyscale generated from two seeds, plus one accent that is
the only channel permitted to carry hue.

Token names are deliberately identical to the CSS custom properties
(`--abio-ink`, `--abio-paper`, …) so there is no translation cost between this
document and the stylesheet.

### Primary

- **Accent** — the single hue-bearing token. Reserved for interactive
  affordance only: link hover and the call-to-action's border, text, and filled
  hover state. It defaults to the same value as Ink, which means an
  unconfigured site is genuinely monochrome and the accent only becomes visible
  once someone sets it. Nothing structural may depend on it.
- **Accent Soft** — the accent lightened toward paper for link hover, mixed on
  the same 61% curve that produces Soft from Ink. Identical to Soft until the
  accent is overridden; the two diverge only when it is.

### Neutral

- **Ink** — body text, headings, and the fill of every inverted panel. The
  darkest value in the system and one of the two seeds everything else derives
  from.
- **Paper** — page and card ground. Off-white rather than pure white, which is
  what keeps the system warm rather than clinical.
- **Wash** — the recessed *page* ground and the text colour on inverted
  panels. It is never the fill behind body text: a box that holds copy sits
  on Paper and separates with a hairline, so the tonal step is reserved for
  telling a card apart from the page beneath it.
- **Line** — every border and rule in the system. One pixel, always.
- **Muted** — labels, captions, and metadata. The quietest readable tone.
- **Dim** — secondary prose, the biography, chip text. Softer than Ink without
  dropping to label weight.
- **Soft** — tertiary prose and item summaries.
- **Faint** — ordinal numbers, placeholder captions, and other marks that must
  register as present without competing.
- **On-Ink / On-Ink Dim / On-Ink Line** — the reduced palette available inside
  an inverted panel: primary text, secondary text, and border respectively.

### Named Rules

**The Two-Seed Rule.** Only Ink, Paper, and Accent are ever set directly.
Every other color is derived from them by `color-mix()` at a fixed percentage,
which is why the whole system re-tones coherently when a host site's palette is
detected. Never hardcode a color that could be derived, and never introduce a
fourth seed.

**The Inheritance Rule.** This system has no brand color of its own and must
never acquire one. A literal hex belongs only in the seed declarations and in
the `@supports` fallback beneath them. Everywhere else, use a token.

**The Contrast Floor Rule.** A detected Ink is only accepted if it clears 7:1
against the resolved Paper; below that it falls back to the default per
channel, keeping a good detected Paper even when the Ink is rejected. Body text
and inverted panels both depend on that value, so an adaptive palette may never
be allowed to drift below a readable floor.

## Typography

**Family:** inherited from the host theme. A Typeface setting can override it
with one of four system stacks — System UI, Grotesque, Humanist, or Serif —
but the default is to take whatever the site already uses.

No webfont is loaded and none should be. The system ships zero font requests,
which is a deliberate property of something that installs into sites it does
not control — no layout shift, no third-party request, no consent surface.

**Character:** the system has no typographic voice of its own, by choice. It
inherits colour from its host and it inherits type the same way, so an author
page reads as part of the site rather than a panel transplanted into it. What
the system does own is how type is used: one family, five roles, and a label
treatment that separates metadata from content through case, tracking and
weight instead of by switching family. Figures in labels and data columns are
set `tabular-nums`, so dates, years and counts still align in a column without
a monospace doing it.

### Hierarchy

- **Display** (400–700, 34–84px per template, line-height 0.95–1.05, tracking
  −0.04em to −0.015em): the author's name, once per page. The single largest
  element and the only place negative tracking appears. Size is a per-template
  decision — the frontmatter value is representative, not a cap.
- **Headline** (500–600, 24–36px): section headings such as "Areas of focus"
  and "Latest edits". Frequently paired with a hairline bottom rule.
- **Title** (600, 14–20px, line-height ~1.35): item headings — a focus area, a
  role in the career history, an article title in the byline list.
- **Body** (400, 13–15px, line-height 1.6–1.7): running prose and summaries.
  Biography and long-form measure is capped at 58–70 characters.
- **Label** (500, 10–11px, tracking 0.08–0.2em, uppercase, tabular figures):
  everything metadata — kickers, section eyebrows, dates, statuses, read
  times, ordinals, stat units, gallery captions. One weight step above body
  compensates for the lost family contrast.

### Named Rules

**The Label Rule.** If a piece of text is metadata rather than content — a
date, a status, an ordinal, a unit, a field name — it is set uppercase,
tracked at 0.1em or wider, one weight step above body, in Muted, with
`tabular-nums`. Case and tracking carry the archival register; a second
family is not needed to say this is a record, and reaching for monospace to
mean technical is costume rather than function.

**The Measure Rule.** Running prose is capped in `ch`, never in pixels, at
58–70 characters. A biography that runs the full width of a wide container
stops reading like prose.

**The One Name Rule.** Display type appears once per page, on the author's
name. Nothing else competes at that scale.

**The Inherited Voice Rule.** Never declare a font family outside the
`--abio-font` token. The page belongs to its host, and a hardcoded family is
the fastest way to make it look pasted on.

## Layout

The system is built on a small number of composed grids rather than one global
grid. Each template sets its own container maximum — 1000px to 1280px
depending on how much rail it carries — and its own column split, most often a
content column against a 300–420px sidebar, or a symmetric two- or four-up for
tiled content.

**Spacing rhythm** clusters tightly: 6/8/10/12px for intra-component gaps,
16/20/24px between related blocks, 32/44/56/72px between major sections. Card
padding sits at 24–32px, stat tiles at 26px vertical.

**Responsive behavior is container-relative, not viewport-relative.** The
shortcode root declares `container-type: inline-size` and all thirty
breakpoint blocks are `@container` queries at 1024px, 768px, and 560px. This
is an invariant, not a preference: the same shortcode lands in a full-bleed
builder section on one site and a ~620px themed content column on the next,
and a viewport query cannot tell those apart. At 1024 multi-column bodies
collapse and sticky rails go static; at 768 side padding drops to 20px, display
type roughly halves, and table-style rows stack; at 560 everything is single
column.

Grids that declare a fixed track count must collapse when their content is
shorter than that count — either through a count modifier class or by letting a
trailing odd cell span the remaining tracks. A grid that leaves an empty track
is a defect, not a layout.

### Named Rules

**The Container Rule.** Never write a viewport media query in this system. The
page does not know how wide the window is and must not care.

**The No-Dead-Track Rule.** Any grid whose children come from a variable-length
list adapts its track count to what actually rendered. Empty cells are visible
in this system because grids paint their gaps.

## Elevation & Depth

The system is currently flat throughout: zero box-shadows, zero gradients, and
zero transitions or animations in the entire stylesheet. Depth is expressed
three ways — a one-pixel Line border, a tonal step between Paper and Wash, and
full inversion to an Ink panel.

This flatness is **provisional rather than doctrinal**. It is what the source
comp specified and what the implementation carries, but the system is
explicitly open to gaining elevation later. Future work may introduce shadow
or motion; it should do so as a considered addition to this section rather than
as a one-off on a single component, and it should keep in mind that the plugin
renders inside host pages it does not control, where a floating element is more
likely to collide with the theme than a flat one.

### Named Rules

**The Optional-Section Rule.** The pitch box and the breadcrumb trail are
both site-configurable and both arrive as data — an emptied group or an
empty array — so a template's ordinary `! empty()` guard is the only thing
that has to know about them. Never gate a section on a settings read inside
a template.

**The White-Copy Rule.** Body text is never set on the recessed grey. Any
box that holds copy — a pitch panel, a sidebar, a coverage tile, a career
card — is filled Paper and bounded by a hairline. Wash tells a card from
the page behind it; it never sits behind a paragraph.

**The Hairline Rule.** Separation is one pixel of Line. Not two, not a
double rule, not a shadow standing in for a border.

## Shapes

Corners are square by default (0px), and the great majority of the system is
built that way — cards, chips, buttons, panels, image frames, and stat tiles
all meet at right angles. Two deliberate exceptions exist: circular crops
(`50%`) for the small round author avatars in cross-links, and one template
that speaks product-UI throughout with 6px cards, 4px inner elements, and 99px
status pills.

**Radius is a per-template decision, not a system-level constraint.** Each of
the ten layouts sets its own corner language; most chose square. A new template
may choose otherwise, provided it commits to that choice consistently across
every surface it owns rather than mixing radii within one layout.

Borders are uniformly one pixel of Line. Image frames carry the same border as
cards, so a photograph sits in the page with the same weight as a panel.

The recurring silhouette is the **hatched frame**: a 45° repeating linear
gradient in Line over Wash, at 6px stripes, with a small uppercase caption centered in it.
It stands in for any image that has not been supplied and is the system's most
recognizable single mark.

## Components

### Buttons

Only one button exists — the call to action — and it is a bordered text button,
never a solid fill at rest.

- **Shape:** square (0px), one-pixel Accent border
- **Default:** transparent ground, Accent text, 9px/15px padding, 13px
- **Hover:** inverts to a solid Accent fill with Paper text, and the underline
  that links normally receive is suppressed
- **On an inverted panel:** border drops to Soft and text to Wash; hover
  inverts to a Wash fill with Ink text
- **Template dialects:** the product-UI template uses a full-width filled
  button at 4px radius; the brand-feature template uses a larger filled button
  at 14px/26px. Both are deliberate.

**Focus:** a two-pixel Accent outline at a two-pixel offset, switching to Wash
inside an inverted panel — where the Accent can be as dark as the ground it
sits on. Applied to links, buttons, and anything carrying `tabindex`. Hover
alone is never an accessible affordance, so any new control inherits this or
declares its own equally visible treatment.

### Chips

- **Style:** one-pixel Line border, Paper ground, Dim text, 12px, 4px/10px
  padding, square corners, wrapping with an 8px gap
- **On an inverted panel:** transparent ground, On-Ink Line border, On-Ink text
- **Usage:** verification badges and social handles. Chips are never
  interactive-looking unless they are actually links.

### Cards / Containers

- **Corner Style:** square, except in the product-UI template (6px)
- **Background:** Paper, always — including nested tiles and sidebars that
  once used Wash. On the templates whose page ground is Wash, that step is
  what makes a card read as raised without a shadow; where the page is
  already Paper, the hairline does the work alone
- **Border:** one pixel of Line
- **Internal Padding:** 24–32px, 18px for small nested tiles
- **Shadow Strategy:** none — see Elevation & Depth

### Inverted Panel

The system's one dramatic move: a full-bleed Ink band with Wash text, used for
mastheads, stat bands, and closing calls to action. Inside it the palette
narrows to On-Ink, On-Ink Dim, and On-Ink Line, and both chips and the CTA
switch to their dark variants automatically.

Its use is **unrestricted** — any section may invert where contrast serves the
page. Four of the ten templates use it and the rest do not. When a page uses
several, watch that it does not read as banding.

### Stat Tile

A value in large plain type over a small uppercase unit label in Muted, both
set `tabular-nums` so a row of tiles aligns. Tiles
tile edge to edge over a Line ground so the one-pixel gaps read as rules.

The tile count is **variable, not fixed** — a tile whose value cannot be
computed is dropped rather than rendered blank, so the grid must adapt to the
number that actually resolved.

### Image Frame

Every image renders through one helper that returns either a real bordered
photograph or the hatched empty frame with a small uppercase caption naming
the expected crop. There is no third state and no broken-image case.

### Visually Hidden Text

`.abio-sr-only` clips text to a one-pixel box while leaving it in the
accessibility tree. It carries the "(opens in a new tab)" cue on outbound
links, a fact sighted users infer from context and screen reader users would
otherwise only discover after the tab had already switched.

### Missing Notice

The one diagnostic surface: a dashed Line border, Muted text at 13px, shown
only to users who can edit posts. It is deliberately the only dashed border in
the system, so it never reads as content.

## Do's and Don'ts

### Do:

- **Do** derive every color from Ink, Paper, and Accent. If a value can be
  mixed, mix it.
- **Do** set all metadata — dates, statuses, ordinals, units, field names —
  uppercase, tracked at 0.1em or wider, one weight above body, in Muted,
  with `tabular-nums`.
- **Do** cap running prose at 58–70 characters using `ch`.
- **Do** write breakpoints as `@container` queries against the shortcode root,
  at 1024px, 768px, and 560px.
- **Do** collapse any fixed-track grid to the number of items that actually
  rendered.
- **Do** render every image through the shared image helper so the hatched
  empty frame stays the single fallback.
- **Do** let a section disappear entirely when its content is empty.
- **Do** keep separation to one pixel of Line.
- **Do** write base element styles with `:where()` so they carry no
  specificity. `.abio a { color: inherit }` outranks `.abio-cta` and silently
  strips every button of its own colour; `:where(.abio) a` does not.
- **Do** give every interactive element a visible `:focus-visible` treatment
  that survives on both the light ground and an inverted panel.

### Don't:

- **Don't** introduce a fourth color seed, or hardcode a hex outside the seed
  declarations and their `@supports` fallback.
- **Don't** write a viewport media query. The system cannot see the viewport
  and must not act as though it can.
- **Don't** load a webfont, and don't declare a font family outside the
  `--abio-font` token. The system ships zero font requests and inherits its
  typeface from the host.
- **Don't** reach for monospace to signal technical intent. Column alignment
  is what `tabular-nums` is for.
- **Don't** render an empty frame, a blank cell, or a placeholder value where
  content is missing — show less page instead.
- **Don't** let Accent carry structure. It is interactive affordance only, and
  on most installs it is identical to Ink.
- **Don't** use a dashed border for anything but the editor-only missing
  notice.
- **Don't** rely on hover as the only signal that something is interactive, and
  never remove a focus outline without replacing it with a visible one.
- **Don't** mix radii within a single template. Commit to square or to a
  rounded dialect and hold it across every surface that template owns.
