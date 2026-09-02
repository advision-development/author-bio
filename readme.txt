=== Author Bio ===
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.0.0

Renders a full author page from an [author_bio] shortcode, backed by a
dedicated Author Profile admin screen linked to a WordPress user.

Managing Author Profiles requires its own capabilities, granted to
Administrators and Editors on activation; the Author role can publish posts
as usual but cannot create, edit, or link an Author Profile.

== Usage ==

Put the shortcode in a page, or in your theme's author.php:

[author_bio template=3]

Attributes:

template   - 1-10, or a slug (see Templates below). Default: 1 (settings).
user       - user ID, login, or nicename. Default: resolved from context.
id         - an Author Profile post ID, bypassing user resolution. Default: none.
count      - how many articles to list. Default: 6 (settings).
post_type  - comma-separated post types for the article list. Default: post (settings).
hide       - comma-separated sections to omit. Default: none.
others     - other-author cards; 0 disables. Default: 2.

Sections accepted by hide: stats, gallery, focus, edits, experience,
credentials, follows, others, pitch.

Templates:

1  classic-sidebar     - Editorial two-column with a sticky right rail
2  resume              - Full-height left sidebar, CV-style main column
3  editorial-masthead  - Centered masthead, narrow measure, gallery band
4  bento               - 12-column card grid
5  numbered-rail       - Sticky numbered navigation beside numbered sections
6  dossier             - Full-bleed dark header and footer
7  sports-desk         - Uppercase dark banner, boxed cards, box-score list
8  fintech             - Rounded product-UI cards, status pills
9  research-note       - Single paper sheet, numbered analyst sections
10 brand-feature       - Oversized hero, asymmetric gallery, dark statement band

Examples:

[author_bio]                              author resolved from the current archive
[author_bio template=3]                   pick one of ten layouts
[author_bio template="editorial-masthead"]
[author_bio user=12 count=8 post_type="post,review"]
[author_bio hide="experience,gallery" others=0]
