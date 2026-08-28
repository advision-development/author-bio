# Author Bio Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A WordPress plugin whose `[author_bio template=N]` shortcode renders any of ten complete author-page layouts from a dedicated Author Profile admin screen linked to a WordPress user.

**Architecture:** A CPT (`author_profile`) holds the content; one field schema drives both the admin UI and the save/sanitize path; one aggregator class (`ABIO_Profile`) turns a profile plus live post data into a single normalized array; ten template files are dumb views over that array, ported 1:1 from the source design.

**Tech Stack:** PHP 7.4+, WordPress 6.0+. No Composer, no npm, no build step. Vanilla JS for the admin repeaters, `wp.media` for image pickers. Plain CSS with custom properties.

**Spec:** `docs/superpowers/specs/2026-08-28-author-bio-design.md`

**Design source (vendored, read-only):**
- `docs/design/author-page-templates.dc.html` — the ten layouts
- `docs/design/author-data.json` — the data shape
- `docs/design/support.js` — the DC runtime (reference only; nothing ported from it)

## Global Constraints

- **Prefix:** every global class, function, constant, option, meta key, CSS class, and CSS custom property is prefixed `abio` / `ABIO_` / `_abio_` / `--abio-`. No exceptions.
- **PHP floor:** 7.4. No `match`, no enums, no constructor property promotion, no `?->`.
- **WordPress floor:** 6.0.
- **No dependencies.** No Composer, no npm, no ACF, no CMB2. If a task seems to need a library, it does not.
- **Every file** starts with `<?php` and `defined( 'ABSPATH' ) || exit;` (PHP files only; templates included).
- **Escaping is mandatory:** `esc_html()` for text, `esc_attr()` for attributes, `esc_url()` for hrefs and `src`, `wp_kses_post()` for the bio field and nothing else.
- **Meta keys** are `_abio_` + the schema key, always via `ABIO_Fields::meta_key()`. Never hand-written.
- **Text domain:** `author-bio`, in every `__()` / `esc_html__()` call.
- **Templates receive `$d`** (the profile array) and `$hide` (array of suppressed section slugs) in scope. Templates never call `get_option`, `WP_Query`, or `get_post_meta`.
- **Section slugs** used by `hide` and by `nav`, fixed for the whole plugin: `gallery`, `focus`, `edits`, `experience`, `credentials`, `follows`, `others`, `pitch`, `stats`.
- **Verification is manual.** There is no test suite. Every task ends with `php -l` on the files it touched plus a stated manual check on the wp-lab instance created in Task 1.

---

### Task 1: Plugin skeleton, CPT, and a live wp-lab instance

**Files:**
- Create: `author-bio.php`
- Create: `includes/class-plugin.php`
- Create: `includes/class-post-type.php`
- Create: `.gitignore`
- Create: `readme.txt`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `ABIO_VERSION` (string), `ABIO_FILE` (string), `ABIO_PATH` (string, trailing slash), `ABIO_URL` (string, trailing slash)
  - `ABIO_Plugin::init(): void` — the single bootstrap entry point
  - `ABIO_Post_Type::SLUG` = `'author_profile'`
  - `ABIO_Post_Type::register(): void`
  - `ABIO_Post_Type::find_by_user( int $user_id ): int` — profile post ID, or `0`

- [ ] **Step 1: Create the plugin bootstrap**

`author-bio.php`:

```php
<?php
/**
 * Plugin Name: Author Bio
 * Description: Renders a full author page from an [author_bio] shortcode, backed by a dedicated Author Profile admin.
 * Version:     1.0.0
 * Author:      Advision Development
 * Text Domain: author-bio
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

define( 'ABIO_VERSION', '1.0.0' );
define( 'ABIO_FILE', __FILE__ );
define( 'ABIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'ABIO_URL', plugin_dir_url( __FILE__ ) );

require_once ABIO_PATH . 'includes/class-plugin.php';

ABIO_Plugin::init();
```

- [ ] **Step 2: Create the bootstrap class**

`includes/class-plugin.php`. The `$files` list grows in later tasks; only the two that exist now are listed.

```php
<?php

defined( 'ABSPATH' ) || exit;

class ABIO_Plugin {

	/**
	 * Wire everything up. Called once, from the plugin bootstrap.
	 */
	public static function init() {
		$files = array(
			'includes/class-post-type.php',
		);

		foreach ( $files as $file ) {
			require_once ABIO_PATH . $file;
		}

		add_action( 'init', array( 'ABIO_Post_Type', 'register' ) );
		register_activation_hook( ABIO_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Activation: register the post type once so its rewrite state is clean.
	 */
	public static function activate() {
		ABIO_Post_Type::register();
	}
}
```

- [ ] **Step 3: Create the post type**

`includes/class-post-type.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

class ABIO_Post_Type {

	const SLUG = 'author_profile';

	/**
	 * Register the Author Profile post type. Data-only: no permalink, no archive.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'Authors', 'author-bio' ),
			'singular_name'      => __( 'Author Profile', 'author-bio' ),
			'add_new'            => __( 'Add New', 'author-bio' ),
			'add_new_item'       => __( 'Add New Author Profile', 'author-bio' ),
			'edit_item'          => __( 'Edit Author Profile', 'author-bio' ),
			'new_item'           => __( 'New Author Profile', 'author-bio' ),
			'view_item'          => __( 'View Author Profile', 'author-bio' ),
			'search_items'       => __( 'Search Author Profiles', 'author-bio' ),
			'not_found'          => __( 'No author profiles found', 'author-bio' ),
			'not_found_in_trash' => __( 'No author profiles in trash', 'author-bio' ),
			'menu_name'          => __( 'Authors', 'author-bio' ),
		);

		register_post_type(
			self::SLUG,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'hierarchical'        => false,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-id-alt',
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
			)
		);
	}

	/**
	 * Find the profile linked to a WordPress user.
	 *
	 * Lowest post ID wins when more than one profile claims the same user, so
	 * rendering stays deterministic while the duplicate is being sorted out.
	 *
	 * @param int $user_id
	 * @return int Profile post ID, or 0 when there is none.
	 */
	public static function find_by_user( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return 0;
		}

		$found = get_posts(
			array(
				'post_type'        => self::SLUG,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array(
					array(
						'key'   => '_abio_user',
						'value' => $user_id,
					),
				),
			)
		);

		return empty( $found ) ? 0 : (int) $found[0];
	}
}
```

- [ ] **Step 4: Create `.gitignore` and `readme.txt`**

`.gitignore`:

```
.DS_Store
*.zip
node_modules/
vendor/
```

`readme.txt`:

```
=== Author Bio ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0

Renders a full author page from an [author_bio] shortcode, backed by a
dedicated Author Profile admin screen linked to a WordPress user.

== Usage ==

[author_bio]                              author resolved from the current archive
[author_bio template=3]                   pick one of ten layouts
[author_bio template="editorial-masthead"]
[author_bio user=12 count=8 post_type="post,review"]
[author_bio hide="experience,gallery" others=0]
```

- [ ] **Step 5: Syntax-check**

Run: `find . -name '*.php' -not -path './docs/*' -print0 | xargs -0 -n1 php -l`
Expected: `No syntax errors detected` for every file.

- [ ] **Step 6: Create the wp-lab instance**

Use the `wp-lab` skill to create a temporary WordPress instance and get a magic admin login URL. Record the site URL and the login URL in your working notes — every later task verifies against this same instance.

- [ ] **Step 7: Install and activate**

Zip the plugin directory (excluding `docs/` and `.git/`) and upload it through **Plugins → Add New → Upload Plugin** on the wp-lab instance, then activate.

```bash
zip -r /tmp/author-bio.zip . -x '.git/*' 'docs/*' '*.zip'
```

Expected: an **Authors** menu with a person-card icon appears in the admin sidebar, at roughly the position of Comments. **Authors → Add New** opens an edit screen with only a title field.

- [ ] **Step 8: Commit**

```bash
git add author-bio.php includes/ .gitignore readme.txt
git commit -m "feat: plugin skeleton and author_profile post type"
```

---

### Task 2: Field schema

**Files:**
- Create: `includes/class-fields.php`
- Modify: `includes/class-plugin.php` — add `includes/class-fields.php` to `$files`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `ABIO_Fields::groups(): array` — the metabox groups, each `array( 'id' => string, 'title' => string, 'fields' => array )`
  - `ABIO_Fields::fields(): array` — every field, flattened, keyed by field key
  - `ABIO_Fields::meta_key( string $key ): string` — `'_abio_' . $key`
  - `ABIO_Fields::sanitize( array $field, $value )` — sanitized value matching the field's type
  - Field types, used by Task 3's renderer: `text`, `textarea`, `url`, `user`, `media`, `repeater`, `stats`
  - Repeater fields carry `'subfields' => array( key => array( 'label' => string, 'type' => string ) )`

- [ ] **Step 1: Write the schema class**

`includes/class-fields.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * The single source of truth for profile fields.
 *
 * The admin UI, the save path, and the profile reader are all generated from
 * this schema. Adding a field means adding one entry here.
 */
class ABIO_Fields {

	const PREFIX = '_abio_';

	/**
	 * Metabox groups, in the order they appear on the edit screen.
	 *
	 * @return array
	 */
	public static function groups() {
		return array(
			array(
				'id'     => 'identity',
				'title'  => __( 'Identity', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'user',
						'label' => __( 'Linked WordPress user', 'author-bio' ),
						'type'  => 'user',
						'help'  => __( 'Required. Drives the articles list, the automatic stats, and which author archive this profile answers to.', 'author-bio' ),
					),
					array(
						'key'         => 'kicker',
						'label'       => __( 'Kicker', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'Author', 'author-bio' ),
					),
					array(
						'key'   => 'name',
						'label' => __( 'Display name', 'author-bio' ),
						'type'  => 'text',
						'help'  => __( 'Leave blank to use the linked user\'s display name.', 'author-bio' ),
					),
					array(
						'key'         => 'role',
						'label'       => __( 'Role', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'Crypto & Commodities Analyst', 'author-bio' ),
					),
					array(
						'key'         => 'location',
						'label'       => __( 'Location', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'Oregon, US', 'author-bio' ),
					),
					array(
						'key'         => 'since',
						'label'       => __( 'Contributing since', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( '2026', 'author-bio' ),
						'help'        => __( 'Leave blank to use the year of the linked user\'s first published post.', 'author-bio' ),
					),
					array(
						'key'   => 'portrait',
						'label' => __( 'Portrait', 'author-bio' ),
						'type'  => 'media',
						'help'  => __( 'Square crop. Used by templates 1, 2, 4, 6, 7 and 9.', 'author-bio' ),
					),
				),
			),
			array(
				'id'     => 'bio',
				'title'  => __( 'Biography', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'short',
						'label' => __( 'Short line', 'author-bio' ),
						'type'  => 'text',
						'help'  => __( 'One sentence. Used by templates 8 and 10.', 'author-bio' ),
					),
					array(
						'key'   => 'bio',
						'label' => __( 'Biography', 'author-bio' ),
						'type'  => 'textarea',
						'rows'  => 8,
					),
					array(
						'key'       => 'badges',
						'label'     => __( 'Badges', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'text' => array(
								'label' => __( 'Badge', 'author-bio' ),
								'type'  => 'text',
							),
						),
					),
					array(
						'key'       => 'credentials',
						'label'     => __( 'Credentials', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'text' => array(
								'label' => __( 'Credential', 'author-bio' ),
								'type'  => 'text',
							),
						),
					),
				),
			),
			array(
				'id'     => 'stats',
				'title'  => __( 'Stat tiles', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'stats',
						'label' => __( 'Stat tiles', 'author-bio' ),
						'type'  => 'stats',
						'help'  => __( 'Four tiles. Automatic tiles recalculate on every page load; manual tiles show exactly what you type.', 'author-bio' ),
					),
				),
			),
			array(
				'id'     => 'gallery',
				'title'  => __( 'Gallery', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'gallery_heading',
						'label' => __( 'Gallery heading', 'author-bio' ),
						'type'  => 'text',
					),
					array(
						'key'   => 'gallery_note',
						'label' => __( 'Gallery note', 'author-bio' ),
						'type'  => 'text',
					),
					array(
						'key'       => 'gallery_items',
						'label'     => __( 'Gallery items', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'image'   => array(
								'label' => __( 'Image', 'author-bio' ),
								'type'  => 'media',
							),
							'label'   => array(
								'label' => __( 'Label', 'author-bio' ),
								'type'  => 'text',
							),
							'caption' => array(
								'label' => __( 'Caption', 'author-bio' ),
								'type'  => 'text',
							),
							'short'   => array(
								'label' => __( 'Short label', 'author-bio' ),
								'type'  => 'text',
							),
						),
					),
				),
			),
			array(
				'id'     => 'focus',
				'title'  => __( 'Areas of focus', 'author-bio' ),
				'fields' => array(
					array(
						'key'       => 'focus',
						'label'     => __( 'Areas of focus', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'title' => array(
								'label' => __( 'Title', 'author-bio' ),
								'type'  => 'text',
							),
							'body'  => array(
								'label' => __( 'Body', 'author-bio' ),
								'type'  => 'textarea',
							),
						),
					),
				),
			),
			array(
				'id'     => 'experience',
				'title'  => __( 'Experience', 'author-bio' ),
				'fields' => array(
					array(
						'key'       => 'experience',
						'label'     => __( 'Experience', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'years' => array(
								'label' => __( 'Years', 'author-bio' ),
								'type'  => 'text',
							),
							'title' => array(
								'label' => __( 'Title', 'author-bio' ),
								'type'  => 'text',
							),
							'org'   => array(
								'label' => __( 'Organisation', 'author-bio' ),
								'type'  => 'text',
							),
							'body'  => array(
								'label' => __( 'Body', 'author-bio' ),
								'type'  => 'textarea',
							),
						),
					),
				),
			),
			array(
				'id'     => 'follows',
				'title'  => __( 'Follows', 'author-bio' ),
				'fields' => array(
					array(
						'key'       => 'follows',
						'label'     => __( 'Follows', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'handle' => array(
								'label' => __( 'Handle', 'author-bio' ),
								'type'  => 'text',
							),
							'url'    => array(
								'label' => __( 'URL', 'author-bio' ),
								'type'  => 'url',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Every field, flattened and keyed by field key.
	 *
	 * @return array
	 */
	public static function fields() {
		$flat = array();

		foreach ( self::groups() as $group ) {
			foreach ( $group['fields'] as $field ) {
				$flat[ $field['key'] ] = $field;
			}
		}

		return $flat;
	}

	/**
	 * Meta key for a schema key.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function meta_key( $key ) {
		return self::PREFIX . $key;
	}

	/**
	 * Sanitize a submitted value according to its field type.
	 *
	 * @param array $field Field definition from the schema.
	 * @param mixed $value Raw submitted value.
	 * @return mixed
	 */
	public static function sanitize( $field, $value ) {
		switch ( $field['type'] ) {
			case 'user':
			case 'media':
				return absint( $value );

			case 'textarea':
				return wp_kses_post( wp_unslash( $value ) );

			case 'url':
				return esc_url_raw( wp_unslash( $value ) );

			case 'repeater':
				return self::sanitize_repeater( $field, $value );

			case 'stats':
				return self::sanitize_stats( $value );

			case 'text':
			default:
				return sanitize_text_field( wp_unslash( $value ) );
		}
	}

	/**
	 * Sanitize a repeater: an array of rows, each row keyed by subfield.
	 *
	 * Rows where every subfield is empty are dropped, so an untouched blank row
	 * never reaches the front end.
	 *
	 * @param array $field
	 * @param mixed $value
	 * @return array
	 */
	private static function sanitize_repeater( $field, $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$rows = array();

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			$empty = true;

			foreach ( $field['subfields'] as $sub_key => $sub ) {
				$raw           = isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : '';
				$clean[ $sub_key ] = self::sanitize( $sub, $raw );

				if ( '' !== $clean[ $sub_key ] && 0 !== $clean[ $sub_key ] ) {
					$empty = false;
				}
			}

			if ( ! $empty ) {
				$rows[] = $clean;
			}
		}

		return $rows;
	}

	/**
	 * Sanitize the four stat tiles.
	 *
	 * Each tile: mode (auto_bylines|auto_since|auto_type_count|manual),
	 * post_type (for auto_type_count), value (for manual), label.
	 *
	 * @param mixed $value
	 * @return array
	 */
	private static function sanitize_stats( $value ) {
		$modes = array( 'auto_bylines', 'auto_since', 'auto_type_count', 'manual', 'off' );
		$tiles = array();

		for ( $i = 0; $i < 4; $i++ ) {
			$row  = isset( $value[ $i ] ) && is_array( $value[ $i ] ) ? $value[ $i ] : array();
			$mode = isset( $row['mode'] ) ? sanitize_key( $row['mode'] ) : 'off';

			if ( ! in_array( $mode, $modes, true ) ) {
				$mode = 'off';
			}

			$tiles[] = array(
				'mode'      => $mode,
				'post_type' => isset( $row['post_type'] ) ? sanitize_key( $row['post_type'] ) : '',
				'value'     => isset( $row['value'] ) ? sanitize_text_field( wp_unslash( $row['value'] ) ) : '',
				'label'     => isset( $row['label'] ) ? sanitize_text_field( wp_unslash( $row['label'] ) ) : '',
			);
		}

		return $tiles;
	}
}
```

- [ ] **Step 2: Register the file**

In `includes/class-plugin.php`, change the `$files` array to:

```php
		$files = array(
			'includes/class-fields.php',
			'includes/class-post-type.php',
		);
```

- [ ] **Step 3: Syntax-check**

Run: `php -l includes/class-fields.php && php -l includes/class-plugin.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Commit**

```bash
git add includes/class-fields.php includes/class-plugin.php
git commit -m "feat: profile field schema"
```

---

### Task 3: Admin metaboxes

**Files:**
- Create: `includes/class-metaboxes.php`
- Create: `assets/admin/admin.css`
- Create: `assets/admin/admin.js`
- Modify: `includes/class-plugin.php` — register the file and its hooks

**Interfaces:**
- Consumes: `ABIO_Fields::groups()`, `ABIO_Fields::meta_key()`, `ABIO_Fields::sanitize()`, `ABIO_Post_Type::SLUG`, `ABIO_Post_Type::find_by_user()`
- Produces:
  - `ABIO_Metaboxes::register(): void` — adds the metaboxes
  - `ABIO_Metaboxes::save( int $post_id ): void` — the `save_post` handler
  - `ABIO_Metaboxes::admin_assets( string $hook ): void` — enqueues admin CSS/JS on the profile screen only
  - Form field names: scalars are `abio[<key>]`; repeaters are `abio[<key>][<index>][<subkey>]`; stats are `abio[stats][<0-3>][mode|post_type|value|label]`

- [ ] **Step 1: Write the metabox class**

`includes/class-metaboxes.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves the Author Profile edit screen from the field schema.
 */
class ABIO_Metaboxes {

	const NONCE = 'abio_profile_nonce';

	/**
	 * One metabox per schema group.
	 */
	public static function register() {
		foreach ( ABIO_Fields::groups() as $group ) {
			add_meta_box(
				'abio_' . $group['id'],
				$group['title'],
				array( __CLASS__, 'render' ),
				ABIO_Post_Type::SLUG,
				'normal',
				'default',
				array( 'group' => $group )
			);
		}
	}

	/**
	 * Render one group's fields.
	 *
	 * @param WP_Post $post
	 * @param array   $box
	 */
	public static function render( $post, $box ) {
		$group = $box['args']['group'];

		if ( 'identity' === $group['id'] ) {
			wp_nonce_field( self::NONCE, self::NONCE );
			self::duplicate_warning( $post );
		}

		echo '<div class="abio-admin">';

		foreach ( $group['fields'] as $field ) {
			$value = get_post_meta( $post->ID, ABIO_Fields::meta_key( $field['key'] ), true );
			self::render_field( $field, $value );
		}

		echo '</div>';
	}

	/**
	 * Warn when another profile already claims this profile's linked user.
	 *
	 * @param WP_Post $post
	 */
	private static function duplicate_warning( $post ) {
		$user_id = (int) get_post_meta( $post->ID, ABIO_Fields::meta_key( 'user' ), true );

		if ( ! $user_id ) {
			return;
		}

		$winner = ABIO_Post_Type::find_by_user( $user_id );

		if ( $winner && $winner !== $post->ID ) {
			printf(
				'<div class="notice notice-warning inline"><p>%s</p></div>',
				sprintf(
					/* translators: %s: link to the profile that wins. */
					esc_html__( 'Another profile is already linked to this user and takes precedence: %s', 'author-bio' ),
					'<a href="' . esc_url( get_edit_post_link( $winner ) ) . '">' . esc_html( get_the_title( $winner ) ) . '</a>'
				)
			);
		}
	}

	/**
	 * Render one field, dispatching on type.
	 *
	 * @param array $field
	 * @param mixed $value
	 */
	private static function render_field( $field, $value ) {
		$name = 'abio[' . $field['key'] . ']';
		$id   = 'abio-' . $field['key'];

		echo '<div class="abio-field abio-field--' . esc_attr( $field['type'] ) . '">';
		echo '<label class="abio-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';

		switch ( $field['type'] ) {
			case 'user':
				wp_dropdown_users(
					array(
						'name'              => $name,
						'id'                => $id,
						'selected'          => (int) $value,
						'show_option_none'  => __( '— none —', 'author-bio' ),
						'option_none_value' => 0,
						'capability'        => array( 'edit_posts' ),
					)
				);
				break;

			case 'media':
				self::render_media( $name, $id, (int) $value );
				break;

			case 'textarea':
				$rows = isset( $field['rows'] ) ? (int) $field['rows'] : 4;
				echo '<textarea class="large-text" rows="' . esc_attr( $rows ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">' . esc_textarea( $value ) . '</textarea>';
				break;

			case 'repeater':
				self::render_repeater( $field, is_array( $value ) ? $value : array() );
				break;

			case 'stats':
				self::render_stats( is_array( $value ) ? $value : array() );
				break;

			case 'url':
			case 'text':
			default:
				$type        = 'url' === $field['type'] ? 'url' : 'text';
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				echo '<input type="' . esc_attr( $type ) . '" class="large-text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
				break;
		}

		if ( isset( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * A media picker: hidden attachment ID, thumbnail, choose/remove buttons.
	 *
	 * @param string $name
	 * @param string $id
	 * @param int    $attachment_id
	 */
	private static function render_media( $name, $id, $attachment_id ) {
		$thumb = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';

		echo '<div class="abio-media" data-abio-media>';
		echo '<div class="abio-media__preview" data-abio-media-preview>' . $thumb . '</div>';
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $attachment_id ) . '" data-abio-media-input />';
		echo '<button type="button" class="button" data-abio-media-choose>' . esc_html__( 'Choose image', 'author-bio' ) . '</button> ';
		echo '<button type="button" class="button-link abio-media__remove" data-abio-media-remove>' . esc_html__( 'Remove', 'author-bio' ) . '</button>';
		echo '</div>';
	}

	/**
	 * A repeater: existing rows, a hidden <template> row, and an Add button.
	 *
	 * @param array $field
	 * @param array $rows
	 */
	private static function render_repeater( $field, $rows ) {
		echo '<div class="abio-repeater" data-abio-repeater data-key="' . esc_attr( $field['key'] ) . '">';
		echo '<div class="abio-repeater__rows" data-abio-repeater-rows>';

		$index = 0;

		foreach ( $rows as $row ) {
			self::render_repeater_row( $field, $row, (string) $index );
			$index++;
		}

		echo '</div>';

		echo '<template data-abio-repeater-template>';
		self::render_repeater_row( $field, array(), '__i__' );
		echo '</template>';

		echo '<button type="button" class="button" data-abio-repeater-add>' . esc_html__( 'Add row', 'author-bio' ) . '</button>';
		echo '</div>';
	}

	/**
	 * One repeater row.
	 *
	 * @param array  $field
	 * @param array  $row
	 * @param string $index Numeric index, or the literal __i__ placeholder.
	 */
	private static function render_repeater_row( $field, $row, $index ) {
		echo '<div class="abio-row" data-abio-repeater-row>';
		echo '<span class="abio-row__handle dashicons dashicons-menu" data-abio-repeater-handle></span>';
		echo '<div class="abio-row__fields">';

		foreach ( $field['subfields'] as $sub_key => $sub ) {
			$name  = 'abio[' . $field['key'] . '][' . $index . '][' . $sub_key . ']';
			$value = isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : '';

			echo '<div class="abio-row__field">';
			echo '<span class="abio-row__label">' . esc_html( $sub['label'] ) . '</span>';

			if ( 'textarea' === $sub['type'] ) {
				echo '<textarea class="large-text" rows="3" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			} elseif ( 'media' === $sub['type'] ) {
				self::render_media( $name, '', (int) $value );
			} else {
				$type = 'url' === $sub['type'] ? 'url' : 'text';
				echo '<input type="' . esc_attr( $type ) . '" class="large-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
			}

			echo '</div>';
		}

		echo '</div>';
		echo '<button type="button" class="button-link abio-row__remove" data-abio-repeater-remove>' . esc_html__( 'Remove', 'author-bio' ) . '</button>';
		echo '</div>';
	}

	/**
	 * The four stat tiles.
	 *
	 * @param array $tiles
	 */
	private static function render_stats( $tiles ) {
		$modes = array(
			'off'             => __( '— not shown —', 'author-bio' ),
			'auto_bylines'    => __( 'Automatic: pieces bylined', 'author-bio' ),
			'auto_since'      => __( 'Automatic: contributing since', 'author-bio' ),
			'auto_type_count' => __( 'Automatic: count in one post type', 'author-bio' ),
			'manual'          => __( 'Manual value', 'author-bio' ),
		);

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		echo '<div class="abio-stats">';

		for ( $i = 0; $i < 4; $i++ ) {
			$tile = isset( $tiles[ $i ] ) ? $tiles[ $i ] : array();
			$mode = isset( $tile['mode'] ) ? $tile['mode'] : 'off';
			$base = 'abio[stats][' . $i . ']';

			echo '<div class="abio-stat" data-abio-stat>';
			printf( '<span class="abio-stat__num">%d</span>', $i + 1 );

			echo '<select name="' . esc_attr( $base . '[mode]' ) . '" data-abio-stat-mode>';
			foreach ( $modes as $key => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $key ),
					selected( $mode, $key, false ),
					esc_html( $label )
				);
			}
			echo '</select>';

			echo '<select name="' . esc_attr( $base . '[post_type]' ) . '" data-abio-stat-post-type>';
			foreach ( $post_types as $pt ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $pt->name ),
					selected( isset( $tile['post_type'] ) ? $tile['post_type'] : '', $pt->name, false ),
					esc_html( $pt->labels->singular_name )
				);
			}
			echo '</select>';

			printf(
				'<input type="text" name="%s" value="%s" placeholder="%s" data-abio-stat-value />',
				esc_attr( $base . '[value]' ),
				esc_attr( isset( $tile['value'] ) ? $tile['value'] : '' ),
				esc_attr__( 'Value', 'author-bio' )
			);

			printf(
				'<input type="text" name="%s" value="%s" placeholder="%s" />',
				esc_attr( $base . '[label]' ),
				esc_attr( isset( $tile['label'] ) ? $tile['label'] : '' ),
				esc_attr__( 'Label', 'author-bio' )
			);

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Save every schema field.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ABIO_Post_Type::SLUG !== get_post_type( $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each value is sanitized by ABIO_Fields::sanitize().
		$submitted = isset( $_POST['abio'] ) && is_array( $_POST['abio'] ) ? $_POST['abio'] : array();

		foreach ( ABIO_Fields::fields() as $key => $field ) {
			$raw   = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
			$clean = ABIO_Fields::sanitize( $field, $raw );

			update_post_meta( $post_id, ABIO_Fields::meta_key( $key ), $clean );
		}
	}

	/**
	 * Admin CSS and JS, on the profile edit screen only.
	 *
	 * @param string $hook
	 */
	public static function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ABIO_Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style( 'abio-admin', ABIO_URL . 'assets/admin/admin.css', array(), ABIO_VERSION );
		wp_enqueue_script( 'abio-admin', ABIO_URL . 'assets/admin/admin.js', array(), ABIO_VERSION, true );
	}
}
```

- [ ] **Step 2: Write the admin JavaScript**

`assets/admin/admin.js`. Three behaviors: repeater add/remove, drag-free reorder via the row handle is out of scope — rows are ordered by position and reordering is done by editing text; media picker; stat-mode field visibility.

```js
( function () {
	'use strict';

	/**
	 * Renumber a repeater's field names so indexes stay contiguous after a removal.
	 */
	function renumber( repeater ) {
		var key = repeater.getAttribute( 'data-key' );
		var rows = repeater.querySelectorAll( '[data-abio-repeater-row]' );

		Array.prototype.forEach.call( rows, function ( row, index ) {
			var inputs = row.querySelectorAll( '[name]' );

			Array.prototype.forEach.call( inputs, function ( input ) {
				input.name = input.name.replace(
					new RegExp( '^abio\\[' + key + '\\]\\[[^\\]]*\\]' ),
					'abio[' + key + '][' + index + ']'
				);
			} );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var add = event.target.closest( '[data-abio-repeater-add]' );

		if ( add ) {
			event.preventDefault();

			var repeater = add.closest( '[data-abio-repeater]' );
			var template = repeater.querySelector( '[data-abio-repeater-template]' );
			var rows = repeater.querySelector( '[data-abio-repeater-rows]' );
			var index = rows.querySelectorAll( '[data-abio-repeater-row]' ).length;

			rows.insertAdjacentHTML(
				'beforeend',
				template.innerHTML.split( '__i__' ).join( String( index ) )
			);

			return;
		}

		var remove = event.target.closest( '[data-abio-repeater-remove]' );

		if ( remove ) {
			event.preventDefault();

			var owner = remove.closest( '[data-abio-repeater]' );
			remove.closest( '[data-abio-repeater-row]' ).remove();
			renumber( owner );

			return;
		}

		var choose = event.target.closest( '[data-abio-media-choose]' );

		if ( choose ) {
			event.preventDefault();
			openMedia( choose.closest( '[data-abio-media]' ) );

			return;
		}

		var clear = event.target.closest( '[data-abio-media-remove]' );

		if ( clear ) {
			event.preventDefault();

			var field = clear.closest( '[data-abio-media]' );
			field.querySelector( '[data-abio-media-input]' ).value = '';
			field.querySelector( '[data-abio-media-preview]' ).innerHTML = '';
		}
	} );

	/**
	 * Open the WordPress media modal and write the chosen attachment back.
	 */
	function openMedia( field ) {
		var frame = wp.media( {
			title: 'Select image',
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var size = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			field.querySelector( '[data-abio-media-input]' ).value = attachment.id;
			field.querySelector( '[data-abio-media-preview]' ).innerHTML =
				'<img src="' + size + '" alt="" />';
		} );

		frame.open();
	}

	/**
	 * Show only the inputs that the selected stat mode actually uses.
	 */
	function syncStat( tile ) {
		var mode = tile.querySelector( '[data-abio-stat-mode]' ).value;

		tile.querySelector( '[data-abio-stat-post-type]' ).hidden = mode !== 'auto_type_count';
		tile.querySelector( '[data-abio-stat-value]' ).hidden = mode !== 'manual';
	}

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches( '[data-abio-stat-mode]' ) ) {
			syncStat( event.target.closest( '[data-abio-stat]' ) );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-abio-stat]' ),
			syncStat
		);
	} );
}() );
```

- [ ] **Step 3: Write the admin stylesheet**

`assets/admin/admin.css`:

```css
.abio-admin .abio-field { margin: 0 0 18px; }
.abio-admin .abio-field__label { display: block; font-weight: 600; margin: 0 0 5px; }
.abio-admin .description { margin: 4px 0 0; }

.abio-media { display: flex; align-items: center; gap: 10px; }
.abio-media__preview img { display: block; max-width: 80px; height: auto; border: 1px solid #dcdcde; }
.abio-media__remove { color: #b32d2e; }

.abio-repeater__rows { display: flex; flex-direction: column; gap: 10px; margin: 0 0 10px; }
.abio-row {
	display: grid;
	grid-template-columns: 24px minmax(0, 1fr) 70px;
	gap: 10px;
	align-items: start;
	padding: 12px;
	background: #f6f7f7;
	border: 1px solid #dcdcde;
}
.abio-row__handle { color: #a7aaad; padding-top: 4px; cursor: default; }
.abio-row__fields { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
.abio-row__label { display: block; font-size: 12px; color: #646970; margin: 0 0 2px; }
.abio-row__remove { color: #b32d2e; justify-self: end; }

.abio-stats { display: flex; flex-direction: column; gap: 10px; }
.abio-stat {
	display: grid;
	grid-template-columns: 24px 220px 160px 120px minmax(0, 1fr);
	gap: 10px;
	align-items: center;
	padding: 10px 12px;
	background: #f6f7f7;
	border: 1px solid #dcdcde;
}
.abio-stat__num { color: #a7aaad; font-family: ui-monospace, Menlo, monospace; }
.abio-stat [hidden] { display: none; }
```

- [ ] **Step 4: Register hooks**

In `includes/class-plugin.php`, add `'includes/class-metaboxes.php'` to `$files` (after `class-post-type.php`) and add these hooks in `init()`:

```php
		add_action( 'add_meta_boxes', array( 'ABIO_Metaboxes', 'register' ) );
		add_action( 'save_post', array( 'ABIO_Metaboxes', 'save' ) );
		add_action( 'admin_enqueue_scripts', array( 'ABIO_Metaboxes', 'admin_assets' ) );
```

- [ ] **Step 5: Syntax-check**

Run: `php -l includes/class-metaboxes.php && php -l includes/class-plugin.php && node --check assets/admin/admin.js`
Expected: no syntax errors. (If `node` is unavailable, skip the JS check — Step 6 exercises it.)

- [ ] **Step 6: Verify on wp-lab**

Re-zip and re-upload the plugin, then:

1. **Authors → Add New**. Seven metaboxes appear: Identity, Biography, Stat tiles, Gallery, Areas of focus, Experience, Follows.
2. Set a title, pick a linked user, type a role, choose a portrait via **Choose image** — the thumbnail appears.
3. In Biography, click **Add row** under Badges twice, fill both, **Remove** the first — the second survives.
4. In Stat tiles, set tile 1 to "Automatic: pieces bylined" — the Value box hides. Set tile 3 to "Manual value" — the Value box shows and the post-type select hides.
5. Fill at least two Areas of focus, two Experience rows, two Credentials, two Follows, and three Gallery items.
6. **Publish**, then reload the edit screen. Every value persists, in order, with no blank rows.

- [ ] **Step 7: Commit**

```bash
git add includes/class-metaboxes.php includes/class-plugin.php assets/admin/
git commit -m "feat: author profile admin metaboxes"
```

---

### Task 4: Settings page

**Files:**
- Create: `includes/class-settings.php`
- Modify: `includes/class-plugin.php` — register the file and its hooks

**Interfaces:**
- Consumes: `ABIO_Post_Type::SLUG`
- Produces:
  - `ABIO_Settings::OPTION` = `'abio_settings'`
  - `ABIO_Settings::defaults(): array`
  - `ABIO_Settings::all(): array` — stored values merged over defaults
  - `ABIO_Settings::get( string $key, $fallback = null )`
  - `ABIO_Settings::register(): void` — Settings API registration
  - `ABIO_Settings::menu(): void` — adds the submenu page
  - Settings keys: `site_name`, `editorial_url`, `contact_url`, `authors_url`, `pitch_title`, `pitch_body`, `pitch_cta`, `default_template`, `default_count`, `default_post_types`, `palette_ink`, `palette_paper`, `palette_accent`

Palette fields are registered here but left blank; Task 5 fills them by detection.

- [ ] **Step 1: Write the settings class**

`includes/class-settings.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide options: the fields the design treats as global rather than
 * per-author, plus rendering defaults and the palette seeds.
 */
class ABIO_Settings {

	const OPTION = 'abio_settings';
	const GROUP  = 'abio_settings_group';

	/**
	 * @return array
	 */
	public static function defaults() {
		return array(
			'site_name'          => get_bloginfo( 'name' ),
			'editorial_url'      => '',
			'contact_url'        => '',
			'authors_url'        => '',
			'pitch_title'        => __( 'Write for us', 'author-bio' ),
			'pitch_body'         => '',
			'pitch_cta'          => __( 'Contact the desk', 'author-bio' ),
			'default_template'   => '1',
			'default_count'      => 6,
			'default_post_types' => 'post',
			'palette_ink'        => '',
			'palette_paper'      => '',
			'palette_accent'     => '',
		);
	}

	/**
	 * @return array
	 */
	public static function all() {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * @param string $key
	 * @param mixed  $fallback
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();

		if ( ! isset( $all[ $key ] ) || '' === $all[ $key ] ) {
			return null === $fallback ? '' : $fallback;
		}

		return $all[ $key ];
	}

	/**
	 * Add the settings page under the Authors menu.
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . ABIO_Post_Type::SLUG,
			__( 'Author Bio Settings', 'author-bio' ),
			__( 'Settings', 'author-bio' ),
			'manage_options',
			'abio-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register the option and its sanitizer.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * @param mixed $input
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();

		foreach ( array( 'site_name', 'pitch_title', 'pitch_cta', 'default_post_types' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
		}

		foreach ( array( 'editorial_url', 'contact_url', 'authors_url' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( $input[ $key ] ) : '';
		}

		$clean['pitch_body'] = isset( $input['pitch_body'] ) ? wp_kses_post( $input['pitch_body'] ) : '';

		$template                    = isset( $input['default_template'] ) ? absint( $input['default_template'] ) : 1;
		$clean['default_template']   = (string) min( 10, max( 1, $template ) );

		$count                  = isset( $input['default_count'] ) ? absint( $input['default_count'] ) : 6;
		$clean['default_count'] = min( 50, max( 1, $count ) );

		foreach ( array( 'palette_ink', 'palette_paper', 'palette_accent' ) as $key ) {
			$raw           = isset( $input[ $key ] ) ? trim( $input[ $key ] ) : '';
			$clean[ $key ] = self::sanitize_hex( $raw );
		}

		return $clean;
	}

	/**
	 * Accept a 3- or 6-digit hex color, or an empty string.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function sanitize_hex( $value ) {
		if ( '' === $value ) {
			return '';
		}

		$hex = sanitize_hex_color( $value );

		return $hex ? $hex : '';
	}

	/**
	 * Render the settings page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$values = self::all();

		echo '<div class="wrap"><h1>' . esc_html__( 'Author Bio Settings', 'author-bio' ) . '</h1>';
		echo '<form method="post" action="options.php">';

		settings_fields( self::GROUP );

		self::section(
			__( 'Site', 'author-bio' ),
			array(
				array( 'site_name', __( 'Site name', 'author-bio' ), 'text' ),
				array( 'editorial_url', __( 'Editorial policy URL', 'author-bio' ), 'url' ),
				array( 'contact_url', __( 'Contact URL', 'author-bio' ), 'url' ),
				array( 'authors_url', __( 'Authors index URL', 'author-bio' ), 'url' ),
			),
			$values
		);

		self::section(
			__( 'Pitch box', 'author-bio' ),
			array(
				array( 'pitch_title', __( 'Title', 'author-bio' ), 'text' ),
				array( 'pitch_body', __( 'Body', 'author-bio' ), 'textarea' ),
				array( 'pitch_cta', __( 'Button label', 'author-bio' ), 'text' ),
			),
			$values
		);

		self::section(
			__( 'Defaults', 'author-bio' ),
			array(
				array( 'default_template', __( 'Default template (1–10)', 'author-bio' ), 'text' ),
				array( 'default_count', __( 'Articles shown', 'author-bio' ), 'text' ),
				array( 'default_post_types', __( 'Article post types (comma-separated)', 'author-bio' ), 'text' ),
			),
			$values
		);

		self::palette_section( $values );

		submit_button();

		echo '</form></div>';
	}

	/**
	 * Render one titled table of fields.
	 *
	 * @param string $title
	 * @param array  $fields Each: array( key, label, type ).
	 * @param array  $values
	 */
	private static function section( $title, $fields, $values ) {
		echo '<h2>' . esc_html( $title ) . '</h2><table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $field ) {
			list( $key, $label, $type ) = $field;

			$name  = self::OPTION . '[' . $key . ']';
			$value = isset( $values[ $key ] ) ? $values[ $key ] : '';

			echo '<tr><th scope="row"><label for="abio-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';

			if ( 'textarea' === $type ) {
				echo '<textarea class="large-text" rows="3" id="abio-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			} else {
				echo '<input type="' . esc_attr( 'url' === $type ? 'url' : 'text' ) . '" class="regular-text" id="abio-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * The palette section. Task 5 adds the detection readout and re-detect button.
	 *
	 * @param array $values
	 */
	private static function palette_section( $values ) {
		echo '<h2>' . esc_html__( 'Palette', 'author-bio' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Leave blank to use the value detected from your page builder, or the plugin default when no builder is present.', 'author-bio' ) . '</p>';

		self::section(
			'',
			array(
				array( 'palette_ink', __( 'Ink (text and dark panels)', 'author-bio' ), 'text' ),
				array( 'palette_paper', __( 'Paper (card background)', 'author-bio' ), 'text' ),
				array( 'palette_accent', __( 'Accent (links and buttons)', 'author-bio' ), 'text' ),
			),
			$values
		);
	}
}
```

- [ ] **Step 2: Register hooks**

In `includes/class-plugin.php`, add `'includes/class-settings.php'` to `$files` and these hooks:

```php
		add_action( 'admin_menu', array( 'ABIO_Settings', 'menu' ) );
		add_action( 'admin_init', array( 'ABIO_Settings', 'register' ) );
```

- [ ] **Step 3: Syntax-check**

Run: `php -l includes/class-settings.php && php -l includes/class-plugin.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Verify on wp-lab**

Re-upload, then **Authors → Settings**:

1. Four sections render: Site, Pitch box, Defaults, Palette.
2. Site name is prefilled with the site's own name.
3. Fill contact URL, pitch title/body/CTA, and save — values persist after reload.
4. Enter `banana` in Ink and save — it saves as blank (the hex sanitizer rejects it). Enter `#17181a` and save — it persists.
5. Enter `99` in "Default template" and save — it clamps to `10`.

- [ ] **Step 5: Commit**

```bash
git add includes/class-settings.php includes/class-plugin.php
git commit -m "feat: global settings page"
```

---

### Task 5: Palette detection

**Files:**
- Create: `includes/class-palette.php`
- Modify: `includes/class-plugin.php` — register the file and the re-detect handler
- Modify: `includes/class-settings.php` — show the detected source and add the re-detect button

**Interfaces:**
- Consumes: `ABIO_Settings::get()`, `ABIO_Settings::OPTION`
- Produces:
  - `ABIO_Palette::detect(): array` — `array( 'source' => 'elementor'|'bricks'|'default', 'ink' => '#rrggbb', 'paper' => '#rrggbb', 'accent' => '#rrggbb' )`
  - `ABIO_Palette::resolve(): array` — same shape, with any non-empty settings override applied over the cached detection
  - `ABIO_Palette::css_vars(): string` — `'--abio-ink:#17181a;--abio-paper:#fbfbfa;--abio-accent:#17181a'`
  - `ABIO_Palette::store_detection(): void` — runs detection and caches it in option `abio_palette_detected`
  - Defaults, taken from the design: ink `#17181a`, paper `#fbfbfa`, accent `#17181a`

- [ ] **Step 1: Write the palette class**

`includes/class-palette.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the three seed colors the templates build everything else from.
 *
 * Only ink, paper and accent are detected or configured; wash, line, muted and
 * invert are derived in CSS with color-mix(), so detection only has to get
 * three values right.
 */
class ABIO_Palette {

	const CACHE = 'abio_palette_detected';

	const DEFAULT_INK    = '#17181a';
	const DEFAULT_PAPER  = '#fbfbfa';
	const DEFAULT_ACCENT = '#17181a';

	/**
	 * Detect seed colors from the active page builder.
	 *
	 * @return array
	 */
	public static function detect() {
		$elementor = self::detect_elementor();

		if ( $elementor ) {
			return $elementor;
		}

		$bricks = self::detect_bricks();

		if ( $bricks ) {
			return $bricks;
		}

		return array(
			'source' => 'default',
			'ink'    => self::DEFAULT_INK,
			'paper'  => self::DEFAULT_PAPER,
			'accent' => self::DEFAULT_ACCENT,
		);
	}

	/**
	 * Elementor stores its global colors on the active kit post.
	 *
	 * @return array|false
	 */
	private static function detect_elementor() {
		$kit_id = (int) get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return false;
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( ! is_array( $settings ) || empty( $settings['system_colors'] ) || ! is_array( $settings['system_colors'] ) ) {
			return false;
		}

		$by_id = array();

		foreach ( $settings['system_colors'] as $color ) {
			if ( isset( $color['_id'], $color['color'] ) ) {
				$by_id[ $color['_id'] ] = $color['color'];
			}
		}

		$ink    = isset( $by_id['text'] ) ? $by_id['text'] : '';
		$accent = isset( $by_id['primary'] ) ? $by_id['primary'] : '';

		if ( ! $ink && ! $accent ) {
			return false;
		}

		return array(
			'source' => 'elementor',
			'ink'    => self::hex( $ink, self::DEFAULT_INK ),
			'paper'  => self::DEFAULT_PAPER,
			'accent' => self::hex( $accent, self::DEFAULT_ACCENT ),
		);
	}

	/**
	 * Bricks stores a list of palettes, each with a list of colors.
	 *
	 * @return array|false
	 */
	private static function detect_bricks() {
		$palettes = get_option( 'bricks_color_palette' );

		if ( ! is_array( $palettes ) || empty( $palettes ) ) {
			return false;
		}

		$first = reset( $palettes );

		if ( ! is_array( $first ) || empty( $first['colors'] ) || ! is_array( $first['colors'] ) ) {
			return false;
		}

		$values = array();

		foreach ( $first['colors'] as $color ) {
			if ( isset( $color['hex'] ) && $color['hex'] ) {
				$values[] = $color['hex'];
			}
		}

		if ( empty( $values ) ) {
			return false;
		}

		return array(
			'source' => 'bricks',
			'ink'    => self::hex( isset( $values[0] ) ? $values[0] : '', self::DEFAULT_INK ),
			'paper'  => self::DEFAULT_PAPER,
			'accent' => self::hex( isset( $values[1] ) ? $values[1] : '', self::DEFAULT_ACCENT ),
		);
	}

	/**
	 * Validate a hex color, falling back when the builder handed us something else.
	 *
	 * @param string $value
	 * @param string $fallback
	 * @return string
	 */
	private static function hex( $value, $fallback ) {
		$hex = sanitize_hex_color( is_string( $value ) ? trim( $value ) : '' );

		return $hex ? $hex : $fallback;
	}

	/**
	 * Run detection and cache the result.
	 */
	public static function store_detection() {
		update_option( self::CACHE, self::detect(), false );
	}

	/**
	 * Cached detection, running it once if it has never run.
	 *
	 * @return array
	 */
	public static function detected() {
		$cached = get_option( self::CACHE );

		if ( ! is_array( $cached ) || empty( $cached['source'] ) ) {
			self::store_detection();
			$cached = get_option( self::CACHE );
		}

		return $cached;
	}

	/**
	 * Detection with any explicit settings override applied on top.
	 *
	 * @return array
	 */
	public static function resolve() {
		$palette = self::detected();

		foreach ( array( 'ink', 'paper', 'accent' ) as $key ) {
			$override = ABIO_Settings::get( 'palette_' . $key, '' );

			if ( $override ) {
				$palette[ $key ] = $override;
			}
		}

		return $palette;
	}

	/**
	 * The inline custom-property declaration for the shortcode root element.
	 *
	 * @return string
	 */
	public static function css_vars() {
		$palette = self::resolve();

		return sprintf(
			'--abio-ink:%s;--abio-paper:%s;--abio-accent:%s',
			$palette['ink'],
			$palette['paper'],
			$palette['accent']
		);
	}

	/**
	 * Handle the settings page's re-detect button.
	 */
	public static function handle_redetect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'abio_redetect' );

		self::store_detection();

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => ABIO_Post_Type::SLUG,
					'page'      => 'abio-settings',
					'detected'  => '1',
				),
				admin_url( 'edit.php' )
			)
		);

		exit;
	}
}
```

- [ ] **Step 2: Show detection state on the settings page**

In `includes/class-settings.php`, replace the body of `palette_section()` with:

```php
	private static function palette_section( $values ) {
		$detected = ABIO_Palette::detected();

		$sources = array(
			'elementor' => __( 'Elementor global colors', 'author-bio' ),
			'bricks'    => __( 'Bricks color palette', 'author-bio' ),
			'default'   => __( 'plugin defaults (no page builder detected)', 'author-bio' ),
		);

		$source = isset( $sources[ $detected['source'] ] ) ? $sources[ $detected['source'] ] : $detected['source'];

		echo '<h2>' . esc_html__( 'Palette', 'author-bio' ) . '</h2>';

		printf(
			'<p class="description">%s</p>',
			sprintf(
				/* translators: 1: detection source, 2: ink, 3: paper, 4: accent. */
				esc_html__( 'Detected from %1$s — ink %2$s, paper %3$s, accent %4$s. Leave a field blank to use the detected value.', 'author-bio' ),
				esc_html( $source ),
				esc_html( $detected['ink'] ),
				esc_html( $detected['paper'] ),
				esc_html( $detected['accent'] )
			)
		);

		printf(
			'<p><a class="button" href="%s">%s</a></p>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=abio_redetect' ), 'abio_redetect' ) ),
			esc_html__( 'Re-detect from page builder', 'author-bio' )
		);

		self::section(
			'',
			array(
				array( 'palette_ink', __( 'Ink (text and dark panels)', 'author-bio' ), 'text' ),
				array( 'palette_paper', __( 'Paper (card background)', 'author-bio' ), 'text' ),
				array( 'palette_accent', __( 'Accent (links and buttons)', 'author-bio' ), 'text' ),
			),
			$values
		);
	}
```

- [ ] **Step 3: Register hooks**

In `includes/class-plugin.php`, add `'includes/class-palette.php'` to `$files` (before `class-settings.php`), add the re-detect handler, and run detection on activation:

```php
		add_action( 'admin_post_abio_redetect', array( 'ABIO_Palette', 'handle_redetect' ) );
```

and in `activate()`, after `ABIO_Post_Type::register();`:

```php
		ABIO_Palette::store_detection();
```

- [ ] **Step 4: Syntax-check**

Run: `php -l includes/class-palette.php && php -l includes/class-settings.php && php -l includes/class-plugin.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Verify on wp-lab**

Re-upload, then **Authors → Settings**:

1. The Palette section reports a source. On a plain wp-lab install that is "plugin defaults (no page builder detected)" with ink `#17181a`, paper `#fbfbfa`, accent `#17181a`.
2. Click **Re-detect from page builder** — the page reloads without error and the readout is unchanged.
3. Enter `#0a3d62` in Accent and save — the readout still shows the *detected* accent while the field holds the override. (The override is applied by `resolve()`, which the front end uses; the readout deliberately reports detection.)

- [ ] **Step 6: Commit**

```bash
git add includes/class-palette.php includes/class-settings.php includes/class-plugin.php
git commit -m "feat: palette detection for elementor and bricks"
```

---

### Task 6: Articles query

**Files:**
- Create: `includes/class-articles.php`
- Modify: `includes/class-plugin.php` — register the file

**Interfaces:**
- Consumes: `ABIO_Settings::get()`
- Produces:
  - `ABIO_Articles::for_user( int $user_id, array $args ): array` — rows of `array( 'date', 'type', 'status', 'title', 'url', 'summary', 'readTime' )`
    - `$args`: `array( 'post_type' => array<string>, 'count' => int )`
  - `ABIO_Articles::post_types( string $csv = '' ): array` — validated post type slugs, always non-empty
  - `ABIO_Articles::read_time( string $content ): string` — `'6 min'`
  - `ABIO_Articles::status_for( WP_Post $post ): string` — `'Published'` or `'Updated'`
  - `ABIO_Articles::type_for( WP_Post $post ): string`

- [ ] **Step 1: Write the articles class**

`includes/class-articles.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Builds the "Latest edits" list from real published posts.
 */
class ABIO_Articles {

	/** Words per minute used for the read-time estimate. */
	const WPM = 200;

	/** A post modified more than this long after publication reads as "Updated". */
	const UPDATED_AFTER = DAY_IN_SECONDS;

	/**
	 * @param int   $user_id
	 * @param array $args post_type (array of slugs), count (int)
	 * @return array
	 */
	public static function for_user( $user_id, $args = array() ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		$defaults = array(
			'post_type' => self::post_types(),
			'count'     => (int) ABIO_Settings::get( 'default_count', 6 ),
		);

		$args  = array_merge( $defaults, $args );
		$count = max( 1, (int) $args['count'] );

		$query = new WP_Query(
			array(
				'author'              => $user_id,
				'post_type'           => $args['post_type'],
				'post_status'         => 'publish',
				'posts_per_page'      => $count,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'suppress_filters'    => false,
			)
		);

		$rows = array();

		foreach ( $query->posts as $post ) {
			$rows[] = array(
				'date'     => get_the_date( '', $post ),
				'type'     => self::type_for( $post ),
				'status'   => self::status_for( $post ),
				'title'    => get_the_title( $post ),
				'url'      => get_permalink( $post ),
				'summary'  => get_the_excerpt( $post ),
				'readTime' => self::read_time( $post->post_content ),
			);
		}

		return $rows;
	}

	/**
	 * Validate a comma-separated post-type list, falling back to settings and
	 * then to 'post'. Never returns an empty array.
	 *
	 * @param string $csv
	 * @return array
	 */
	public static function post_types( $csv = '' ) {
		if ( '' === $csv ) {
			$csv = (string) ABIO_Settings::get( 'default_post_types', 'post' );
		}

		$requested = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
		$valid     = array();

		foreach ( $requested as $slug ) {
			$slug = sanitize_key( $slug );

			if ( $slug && post_type_exists( $slug ) ) {
				$valid[] = $slug;
			}
		}

		return empty( $valid ) ? array( 'post' ) : $valid;
	}

	/**
	 * "Updated" once a post has been meaningfully edited after publication.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	public static function status_for( $post ) {
		$published = (int) get_post_time( 'U', true, $post );
		$modified  = (int) get_post_modified_time( 'U', true, $post );

		if ( $modified - $published > self::UPDATED_AFTER ) {
			return __( 'Updated', 'author-bio' );
		}

		return __( 'Published', 'author-bio' );
	}

	/**
	 * The primary category name for posts, the post type's singular label
	 * otherwise.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	public static function type_for( $post ) {
		if ( 'post' === $post->post_type ) {
			$terms = get_the_terms( $post, 'category' );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$first = reset( $terms );

				if ( 'uncategorized' !== $first->slug ) {
					return $first->name;
				}
			}
		}

		$object = get_post_type_object( $post->post_type );

		return $object ? $object->labels->singular_name : $post->post_type;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public static function read_time( $content ) {
		$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		$minutes = max( 1, (int) ceil( $words / self::WPM ) );

		/* translators: %d: whole minutes. */
		return sprintf( __( '%d min', 'author-bio' ), $minutes );
	}
}
```

- [ ] **Step 2: Register the file**

Add `'includes/class-articles.php'` to `$files` in `includes/class-plugin.php`.

- [ ] **Step 3: Syntax-check**

Run: `php -l includes/class-articles.php && php -l includes/class-plugin.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add includes/class-articles.php includes/class-plugin.php
git commit -m "feat: latest articles query"
```

The behavior of this class is verified end-to-end in Task 9, once the shortcode can render it.

---

### Task 7: Stat tiles

**Files:**
- Create: `includes/class-stats.php`
- Modify: `includes/class-plugin.php` — register the file

**Interfaces:**
- Consumes: `ABIO_Articles::post_types()`
- Produces:
  - `ABIO_Stats::resolve( array $tiles, int $user_id ): array` — rows of `array( 'value' => string, 'label' => string )`, tiles that resolve to nothing omitted
  - `ABIO_Stats::byline_count( int $user_id, array $post_types ): int`
  - `ABIO_Stats::first_year( int $user_id ): string` — four-digit year, or `''`

- [ ] **Step 1: Write the stats class**

`includes/class-stats.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Turns the four configured stat tiles into value/label pairs.
 *
 * Automatic tiles are computed per render; manual tiles pass straight through.
 * A tile that cannot produce a value is dropped rather than rendered blank, so
 * templates never show an empty cell.
 */
class ABIO_Stats {

	/**
	 * @param array $tiles Stored tile config: mode, post_type, value, label.
	 * @param int   $user_id
	 * @return array
	 */
	public static function resolve( $tiles, $user_id ) {
		if ( ! is_array( $tiles ) ) {
			return array();
		}

		$user_id  = absint( $user_id );
		$resolved = array();

		foreach ( $tiles as $tile ) {
			$mode  = isset( $tile['mode'] ) ? $tile['mode'] : 'off';
			$label = isset( $tile['label'] ) ? $tile['label'] : '';
			$value = '';

			switch ( $mode ) {
				case 'manual':
					$value = isset( $tile['value'] ) ? $tile['value'] : '';
					break;

				case 'auto_bylines':
					if ( $user_id ) {
						$count = self::byline_count( $user_id, ABIO_Articles::post_types() );
						$value = $count ? (string) $count : '';
					}
					break;

				case 'auto_type_count':
					$post_type = isset( $tile['post_type'] ) ? $tile['post_type'] : '';

					if ( $user_id && $post_type && post_type_exists( $post_type ) ) {
						$count = self::byline_count( $user_id, array( $post_type ) );
						$value = $count ? (string) $count : '';
					}
					break;

				case 'auto_since':
					if ( $user_id ) {
						$value = self::first_year( $user_id );
					}
					break;

				case 'off':
				default:
					continue 2;
			}

			if ( '' === $value ) {
				continue;
			}

			$resolved[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		return $resolved;
	}

	/**
	 * Published posts by this user across the given post types.
	 *
	 * @param int   $user_id
	 * @param array $post_types
	 * @return int
	 */
	public static function byline_count( $user_id, $post_types ) {
		$query = new WP_Query(
			array(
				'author'                 => absint( $user_id ),
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * The year of this user's earliest published post.
	 *
	 * @param int $user_id
	 * @return string Four-digit year, or an empty string.
	 */
	public static function first_year( $user_id ) {
		$oldest = get_posts(
			array(
				'author'                 => absint( $user_id ),
				'post_type'              => ABIO_Articles::post_types(),
				'post_status'            => 'publish',
				'numberposts'            => 1,
				'orderby'                => 'date',
				'order'                  => 'ASC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $oldest ) ) {
			return '';
		}

		return get_the_date( 'Y', $oldest[0] );
	}
}
```

- [ ] **Step 2: Register the file**

Add `'includes/class-stats.php'` to `$files` in `includes/class-plugin.php`, after `class-articles.php`.

- [ ] **Step 3: Syntax-check**

Run: `php -l includes/class-stats.php && php -l includes/class-plugin.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add includes/class-stats.php includes/class-plugin.php
git commit -m "feat: stat tile resolution"
```

Verified end-to-end in Task 9.

---

### Task 8: Profile aggregator

**Files:**
- Create: `includes/class-profile.php`
- Modify: `includes/class-plugin.php` — register the file

**Interfaces:**
- Consumes: `ABIO_Fields::meta_key()`, `ABIO_Post_Type::SLUG`, `ABIO_Post_Type::find_by_user()`, `ABIO_Settings::get()`, `ABIO_Articles::for_user()`, `ABIO_Stats::resolve()`
- Produces:
  - `ABIO_Profile::for_user( int $user_id ): ABIO_Profile|null`
  - `ABIO_Profile::for_post( int $post_id ): ABIO_Profile|null`
  - `ABIO_Profile::user_id(): int`
  - `ABIO_Profile::to_array( array $args ): array` — the full template data array
    - `$args`: `array( 'count' => int, 'post_type' => array<string>, 'others' => int, 'hide' => array<string> )`
  - The array's exact shape is the one in the spec's Data Contract section.

- [ ] **Step 1: Write the profile class**

`includes/class-profile.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Turns one Author Profile post, plus live site data, into the single array
 * every template renders from.
 */
class ABIO_Profile {

	/** @var int */
	private $post_id;

	/** @var int */
	private $user_id;

	/**
	 * @param int $post_id
	 */
	private function __construct( $post_id ) {
		$this->post_id = (int) $post_id;
		$this->user_id = (int) $this->meta( 'user' );
	}

	/**
	 * @param int $post_id
	 * @return ABIO_Profile|null
	 */
	public static function for_post( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ABIO_Post_Type::SLUG !== get_post_type( $post_id ) ) {
			return null;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return null;
		}

		return new self( $post_id );
	}

	/**
	 * @param int $user_id
	 * @return ABIO_Profile|null
	 */
	public static function for_user( $user_id ) {
		return self::for_post( ABIO_Post_Type::find_by_user( $user_id ) );
	}

	/**
	 * @return int
	 */
	public function user_id() {
		return $this->user_id;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	private function meta( $key ) {
		return get_post_meta( $this->post_id, ABIO_Fields::meta_key( $key ), true );
	}

	/**
	 * A repeater's rows, always an array.
	 *
	 * @param string $key
	 * @return array
	 */
	private function rows( $key ) {
		$value = $this->meta( $key );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Flatten a single-column repeater ('badges', 'credentials') to strings.
	 *
	 * @param string $key
	 * @return array
	 */
	private function strings( $key ) {
		$out = array();

		foreach ( $this->rows( $key ) as $row ) {
			if ( isset( $row['text'] ) && '' !== $row['text'] ) {
				$out[] = $row['text'];
			}
		}

		return $out;
	}

	/**
	 * Build the template data array.
	 *
	 * @param array $args count, post_type, others, hide
	 * @return array
	 */
	public function to_array( $args = array() ) {
		$defaults = array(
			'count'     => (int) ABIO_Settings::get( 'default_count', 6 ),
			'post_type' => ABIO_Articles::post_types(),
			'others'    => 2,
			'hide'      => array(),
		);

		$args = array_merge( $defaults, $args );
		$hide = (array) $args['hide'];

		$edits = in_array( 'edits', $hide, true )
			? array()
			: ABIO_Articles::for_user(
				$this->user_id,
				array(
					'count'     => $args['count'],
					'post_type' => $args['post_type'],
				)
			);

		$data = array(
			'site'        => $this->site(),
			'author'      => $this->author(),
			'stats'       => in_array( 'stats', $hide, true ) ? array() : ABIO_Stats::resolve( $this->rows( 'stats' ), $this->user_id ),
			'gallery'     => in_array( 'gallery', $hide, true ) ? $this->empty_gallery() : $this->gallery(),
			'focus'       => in_array( 'focus', $hide, true ) ? array() : $this->focus(),
			'edits'       => $edits,
			'experience'  => in_array( 'experience', $hide, true ) ? array() : $this->rows( 'experience' ),
			'credentials' => in_array( 'credentials', $hide, true ) ? array() : $this->strings( 'credentials' ),
			'follows'     => in_array( 'follows', $hide, true ) ? array() : $this->rows( 'follows' ),
			'others'      => in_array( 'others', $hide, true ) ? array() : $this->others( (int) $args['others'] ),
			'pitch'       => in_array( 'pitch', $hide, true ) ? array( 'title' => '', 'body' => '', 'cta' => '' ) : $this->pitch(),
		);

		$data['nav'] = $this->nav( $data );

		return $data;
	}

	/**
	 * @return array
	 */
	private function site() {
		return array(
			'name'        => ABIO_Settings::get( 'site_name', get_bloginfo( 'name' ) ),
			'editorialUrl' => ABIO_Settings::get( 'editorial_url', '' ),
			'contactUrl'  => ABIO_Settings::get( 'contact_url', '' ),
			'authorsUrl'  => ABIO_Settings::get( 'authors_url', '' ),
		);
	}

	/**
	 * @return array
	 */
	private function author() {
		$name = $this->meta( 'name' );

		if ( '' === $name && $this->user_id ) {
			$name = get_the_author_meta( 'display_name', $this->user_id );
		}

		$since = $this->meta( 'since' );

		if ( '' === $since && $this->user_id ) {
			$since = ABIO_Stats::first_year( $this->user_id );
		}

		$kicker = $this->meta( 'kicker' );

		return array(
			'kicker'   => '' === $kicker ? __( 'Author', 'author-bio' ) : $kicker,
			'name'     => $name,
			'role'     => $this->meta( 'role' ),
			'location' => $this->meta( 'location' ),
			'since'    => $since,
			'badges'   => $this->strings( 'badges' ),
			'bio'      => $this->meta( 'bio' ),
			'short'    => $this->meta( 'short' ),
			'portrait' => (int) $this->meta( 'portrait' ),
			'url'      => $this->user_id ? get_author_posts_url( $this->user_id ) : '',
		);
	}

	/**
	 * @return array
	 */
	private function empty_gallery() {
		return array(
			'heading' => '',
			'note'    => '',
			'items'   => array(),
		);
	}

	/**
	 * Gallery items carry a 1-based index; template 9 labels them "Exhibit N".
	 *
	 * @return array
	 */
	private function gallery() {
		$items = array();
		$n     = 1;

		foreach ( $this->rows( 'gallery_items' ) as $row ) {
			$row['image'] = isset( $row['image'] ) ? (int) $row['image'] : 0;
			$row['n']     = $n;
			$items[]      = $row;
			$n++;
		}

		return array(
			'heading' => $this->meta( 'gallery_heading' ),
			'note'    => $this->meta( 'gallery_note' ),
			'items'   => $items,
		);
	}

	/**
	 * Focus rows carry the derived indexes templates 7, 9 and 10 print:
	 * n = "01", sub = "1.1".
	 *
	 * @return array
	 */
	private function focus() {
		$out = array();
		$i   = 1;

		foreach ( $this->rows( 'focus' ) as $row ) {
			$row['n']   = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
			$row['sub'] = '1.' . $i;
			$out[]      = $row;
			$i++;
		}

		return $out;
	}

	/**
	 * Other published profiles, excluding this one.
	 *
	 * @param int $limit
	 * @return array
	 */
	private function others( $limit ) {
		$limit = (int) $limit;

		if ( $limit < 1 ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'        => ABIO_Post_Type::SLUG,
				'post_status'      => 'publish',
				'numberposts'      => $limit,
				'exclude'          => array( $this->post_id ),
				'orderby'          => 'title',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		$out = array();

		foreach ( $ids as $id ) {
			$profile = self::for_post( $id );

			if ( ! $profile ) {
				continue;
			}

			$author = $profile->author();

			$out[] = array(
				'name' => $author['name'],
				'role' => $author['role'],
				'url'  => $author['url'],
			);
		}

		return $out;
	}

	/**
	 * @return array
	 */
	private function pitch() {
		return array(
			'title' => ABIO_Settings::get( 'pitch_title', '' ),
			'body'  => ABIO_Settings::get( 'pitch_body', '' ),
			'cta'   => ABIO_Settings::get( 'pitch_cta', '' ),
		);
	}

	/**
	 * The "On this page" list, limited to sections that actually have content.
	 *
	 * @param array $data
	 * @return array
	 */
	private function nav( $data ) {
		$candidates = array(
			array( 'focus', __( 'Areas of focus', 'author-bio' ) ),
			array( 'edits', __( 'Latest edits', 'author-bio' ) ),
			array( 'experience', __( 'Experience', 'author-bio' ) ),
		);

		$nav = array();
		$n   = 1;

		foreach ( $candidates as $candidate ) {
			list( $key, $label ) = $candidate;

			if ( empty( $data[ $key ] ) ) {
				continue;
			}

			$nav[] = array(
				'num'   => str_pad( (string) $n, 2, '0', STR_PAD_LEFT ),
				'label' => $label,
				'href'  => '#abio-' . $key,
			);

			$n++;
		}

		return $nav;
	}
}
```

Note: `others()` calls the private `author()` on another instance of the same class, which PHP permits.

- [ ] **Step 2: Register the file**

Add `'includes/class-profile.php'` to `$files` in `includes/class-plugin.php`, after `class-stats.php`.

- [ ] **Step 3: Syntax-check**

Run: `php -l includes/class-profile.php && php -l includes/class-plugin.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add includes/class-profile.php includes/class-plugin.php
git commit -m "feat: profile data aggregator"
```

Verified end-to-end in Task 9.

---

### Task 9: Shortcode, assets, base stylesheet, and template 1

This is the task that makes the plugin do its job. It is the largest one; everything after it is a repeat of its template half.

**Files:**
- Create: `includes/class-view.php`
- Create: `includes/class-assets.php`
- Create: `includes/class-shortcode.php`
- Create: `templates/template-1.php`
- Create: `assets/css/author-bio.css`
- Modify: `includes/class-plugin.php` — register the files and the shortcode

**Interfaces:**
- Consumes: `ABIO_Profile::for_user()`, `ABIO_Profile::for_post()`, `ABIO_Post_Type::SLUG`, `ABIO_Settings::get()`, `ABIO_Articles::post_types()`, `ABIO_Palette::css_vars()`
- Produces:
  - `ABIO_View::media( int $id, string $size, string $label, string $class = '' ): string` — an `<img>`, or a labelled placeholder when the attachment is missing
  - `ABIO_Assets::register(): void`, `ABIO_Assets::enqueue(): void`
  - `ABIO_Shortcode::register(): void`
  - `ABIO_Shortcode::render( array $atts ): string`
  - `ABIO_Shortcode::resolve_user( array $atts ): int`
  - `ABIO_Shortcode::template_number( string $value ): int` — 1..10
  - `ABIO_Shortcode::SLUGS` — the slug→number map
  - Template contract: each `templates/template-N.php` runs with `$d` (profile array) and `$hide` (array of section slugs) in scope, and echoes markup only — no root element, no `<div class="abio">`; the shortcode supplies the wrapper.

- [ ] **Step 1: Write the view helper**

`includes/class-view.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Small rendering helpers shared by every template.
 */
class ABIO_View {

	/**
	 * An attachment image, or the design's labelled placeholder when there is
	 * no attachment. Templates always call this rather than reaching for
	 * wp_get_attachment_image() directly, so the empty state stays consistent.
	 *
	 * @param int    $id    Attachment ID.
	 * @param string $size  Registered image size.
	 * @param string $label Placeholder caption, e.g. "portrait 1:1".
	 * @param string $class Extra class on the returned element.
	 * @return string
	 */
	public static function media( $id, $size, $label, $class = '' ) {
		$id      = absint( $id );
		$classes = trim( 'abio-media ' . $class );

		if ( $id && wp_attachment_is_image( $id ) ) {
			return wp_get_attachment_image(
				$id,
				$size,
				false,
				array( 'class' => $classes )
			);
		}

		return sprintf(
			'<span class="%s abio-media--empty" aria-hidden="true">%s</span>',
			esc_attr( $classes ),
			esc_html( $label )
		);
	}
}
```

- [ ] **Step 2: Write the assets class**

`includes/class-assets.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * Front-end assets. The stylesheet is registered always and enqueued only by
 * the shortcode, so pages without an author bio load nothing.
 */
class ABIO_Assets {

	const HANDLE = 'abio';

	public static function register() {
		wp_register_style(
			self::HANDLE,
			ABIO_URL . 'assets/css/author-bio.css',
			array(),
			ABIO_VERSION
		);
	}

	/**
	 * Called from the shortcode. Running during the_content means
	 * wp_enqueue_scripts has already fired, so WordPress prints this in the
	 * footer; the shortcode also inlines the palette so first paint is correct.
	 */
	public static function enqueue() {
		wp_enqueue_style( self::HANDLE );
	}
}
```

- [ ] **Step 3: Write the shortcode**

`includes/class-shortcode.php`:

```php
<?php

defined( 'ABSPATH' ) || exit;

/**
 * [author_bio] — resolves an author, loads their profile, renders a template.
 */
class ABIO_Shortcode {

	const TAG = 'author_bio';

	/** Layout slugs, in template order. */
	const SLUGS = array(
		'classic-sidebar'    => 1,
		'resume'             => 2,
		'editorial-masthead' => 3,
		'bento'              => 4,
		'numbered-rail'      => 5,
		'dossier'            => 6,
		'sports-desk'        => 7,
		'fintech'            => 8,
		'research-note'      => 9,
		'brand-feature'      => 10,
	);

	public static function register() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
	}

	/**
	 * @param array|string $atts
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'template'  => ABIO_Settings::get( 'default_template', '1' ),
				'user'      => '',
				'id'        => '',
				'count'     => '',
				'post_type' => '',
				'hide'      => '',
				'others'    => '2',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		$profile = self::resolve_profile( $atts );

		if ( ! $profile ) {
			return self::missing_notice();
		}

		$number = self::template_number( $atts['template'] );
		$file   = ABIO_PATH . 'templates/template-' . $number . '.php';

		if ( ! file_exists( $file ) ) {
			return '';
		}

		$hide = array_filter( array_map( 'sanitize_key', explode( ',', (string) $atts['hide'] ) ) );

		$d = $profile->to_array(
			array(
				'count'     => '' === $atts['count'] ? (int) ABIO_Settings::get( 'default_count', 6 ) : absint( $atts['count'] ),
				'post_type' => ABIO_Articles::post_types( (string) $atts['post_type'] ),
				'others'    => absint( $atts['others'] ),
				'hide'      => $hide,
			)
		);

		ABIO_Assets::enqueue();

		ob_start();

		printf(
			'<div class="abio abio--t%d" style="%s">',
			$number,
			esc_attr( ABIO_Palette::css_vars() )
		);

		include $file;

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * @param array $atts
	 * @return ABIO_Profile|null
	 */
	private static function resolve_profile( $atts ) {
		// An explicit profile post ID wins outright.
		if ( '' !== $atts['id'] && ABIO_Post_Type::SLUG === get_post_type( absint( $atts['id'] ) ) ) {
			return ABIO_Profile::for_post( absint( $atts['id'] ) );
		}

		$user_id = self::resolve_user( $atts );

		return $user_id ? ABIO_Profile::for_user( $user_id ) : null;
	}

	/**
	 * Resolution order: explicit attribute, then the author archive's queried
	 * object, then the current post's author.
	 *
	 * @param array $atts
	 * @return int
	 */
	public static function resolve_user( $atts ) {
		$explicit = '' !== $atts['user'] ? $atts['user'] : $atts['id'];

		if ( '' !== $explicit ) {
			if ( is_numeric( $explicit ) ) {
				return absint( $explicit );
			}

			$user = get_user_by( 'login', sanitize_user( $explicit ) );

			if ( ! $user ) {
				$user = get_user_by( 'slug', sanitize_title( $explicit ) );
			}

			return $user ? (int) $user->ID : 0;
		}

		if ( is_author() ) {
			$queried = get_queried_object();

			if ( $queried instanceof WP_User ) {
				return (int) $queried->ID;
			}
		}

		if ( is_singular() ) {
			$post_id = get_queried_object_id();

			if ( $post_id ) {
				return (int) get_post_field( 'post_author', $post_id );
			}
		}

		return 0;
	}

	/**
	 * @param string $value Number 1-10 or a layout slug.
	 * @return int
	 */
	public static function template_number( $value ) {
		$value = is_string( $value ) ? strtolower( trim( $value ) ) : $value;

		if ( isset( self::SLUGS[ $value ] ) ) {
			return self::SLUGS[ $value ];
		}

		$number = absint( $value );

		return ( $number >= 1 && $number <= 10 ) ? $number : 1;
	}

	/**
	 * Editors get a diagnosable blank; everyone else gets nothing.
	 *
	 * @return string
	 */
	private static function missing_notice() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return '<p class="abio-missing">' . esc_html__( 'Author Bio: no published author profile is linked to this author.', 'author-bio' ) . '</p>';
	}
}
```

- [ ] **Step 4: Write the base stylesheet**

`assets/css/author-bio.css`. This is the shared half — tokens, resets, and the component classes every template reuses. Per-template blocks are appended by Tasks 9–12, each under its own `.abio--tN` selector.

```css
/* ---------------------------------------------------------------- tokens */

.abio {
	/* Seeds. Overridden inline by the shortcode from the resolved palette. */
	--abio-ink: #17181a;
	--abio-paper: #fbfbfa;
	--abio-accent: #17181a;

	/* Derived. Literal fallbacks first; color-mix versions below. */
	--abio-wash: #f4f4f2;
	--abio-line: #e2e2df;
	--abio-muted: #8a8c90;
	--abio-dim: #5a5c60;
	--abio-soft: #6f7175;
	--abio-faint: #b0b0ab;
	--abio-onink: #c6c6c1;
	--abio-onink-dim: #a8a8a3;
	--abio-onink-line: #3a3c40;

	--abio-font: "Helvetica Neue", Helvetica, Arial, sans-serif;
	--abio-mono: ui-monospace, Menlo, Monaco, "Cascadia Mono", monospace;

	color: var(--abio-ink);
	font-family: var(--abio-font);
	-webkit-font-smoothing: antialiased;
	text-rendering: optimizeLegibility;
}

@supports (color: color-mix(in srgb, #000 50%, #fff)) {
	.abio {
		--abio-wash: color-mix(in srgb, var(--abio-paper) 97%, var(--abio-ink));
		--abio-line: color-mix(in srgb, var(--abio-paper) 89%, var(--abio-ink));
		--abio-muted: color-mix(in srgb, var(--abio-ink) 50%, var(--abio-paper));
		--abio-dim: color-mix(in srgb, var(--abio-ink) 71%, var(--abio-paper));
		--abio-soft: color-mix(in srgb, var(--abio-ink) 61%, var(--abio-paper));
		--abio-faint: color-mix(in srgb, var(--abio-ink) 33%, var(--abio-paper));
		--abio-onink: color-mix(in srgb, var(--abio-paper) 77%, var(--abio-ink));
		--abio-onink-dim: color-mix(in srgb, var(--abio-paper) 64%, var(--abio-ink));
		--abio-onink-line: color-mix(in srgb, var(--abio-paper) 15%, var(--abio-ink));
	}
}

/* ---------------------------------------------------------------- resets */

.abio *,
.abio *::before,
.abio *::after { box-sizing: border-box; }

.abio h1,
.abio h2,
.abio h3,
.abio h4,
.abio p,
.abio ul,
.abio ol,
.abio li,
.abio figure,
.abio figcaption { margin: 0; padding: 0; }

.abio ul,
.abio ol { list-style: none; }

.abio a { color: inherit; text-decoration: none; }
.abio a:hover { color: var(--abio-soft); text-decoration: underline; text-underline-offset: 3px; }

.abio img { max-width: 100%; height: auto; display: block; }

.abio-missing {
	padding: 12px 16px;
	border: 1px dashed var(--abio-line);
	color: var(--abio-muted);
	font: 13px/1.5 var(--abio-font);
}

/* ------------------------------------------------------------ components */

/* Image placeholder — the design's hatched box, kept as the empty state. */
.abio-media--empty {
	display: grid;
	place-items: center;
	background-image: repeating-linear-gradient(
		45deg,
		var(--abio-line) 0 6px,
		var(--abio-wash) 6px 12px
	);
	border: 1px solid var(--abio-line);
	color: var(--abio-faint);
	font: 11px var(--abio-mono);
	min-height: 60px;
}

img.abio-media { border: 1px solid var(--abio-line); }

.abio-kicker {
	font: 11px/1 var(--abio-mono);
	letter-spacing: .16em;
	text-transform: uppercase;
	color: var(--abio-muted);
}

.abio-eyebrow {
	font: 11px/1 var(--abio-mono);
	letter-spacing: .14em;
	text-transform: uppercase;
	color: var(--abio-muted);
}

.abio-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.abio-chips a,
.abio-chips li > span {
	display: block;
	border: 1px solid var(--abio-line);
	padding: 4px 10px;
	font-size: 12px;
	color: var(--abio-dim);
	background: var(--abio-paper);
}

.abio-prose {
	font-size: 15px;
	line-height: 1.65;
	color: var(--abio-dim);
	text-wrap: pretty;
}
.abio-prose p + p { margin-top: 1em; }

.abio-cta {
	align-self: start;
	display: inline-block;
	border: 1px solid var(--abio-ink);
	padding: 9px 15px;
	font-size: 13px;
	color: var(--abio-ink);
}
.abio-cta:hover {
	background: var(--abio-ink);
	color: var(--abio-paper);
	text-decoration: none;
}

.abio-panel--dark { background: var(--abio-ink); color: var(--abio-wash); }
.abio-panel--dark .abio-kicker,
.abio-panel--dark .abio-eyebrow { color: var(--abio-muted); }
.abio-panel--dark .abio-cta { border-color: var(--abio-soft); color: var(--abio-wash); }
.abio-panel--dark .abio-cta:hover { background: var(--abio-wash); color: var(--abio-ink); }
.abio-panel--dark .abio-media--empty {
	background-image: repeating-linear-gradient(45deg, #232427 0 7px, #2c2d31 7px 14px);
	border-color: var(--abio-onink-line);
	color: var(--abio-soft);
}

@supports (color: color-mix(in srgb, #000 50%, #fff)) {
	.abio-panel--dark .abio-media--empty {
		background-image: repeating-linear-gradient(
			45deg,
			color-mix(in srgb, var(--abio-ink) 92%, var(--abio-paper)) 0 7px,
			color-mix(in srgb, var(--abio-ink) 86%, var(--abio-paper)) 7px 14px
		);
	}
}
```

- [ ] **Step 5: Write template 1**

`templates/template-1.php`, ported from `docs/design/author-page-templates.dc.html:36-177`. Every inline style in that range becomes one of the classes written in Step 6.

```php
<?php
/**
 * Template 1 — Classic sidebar.
 *
 * Ported from docs/design/author-page-templates.dc.html:36-177
 *
 * @var array $d    Profile data.
 * @var array $hide Suppressed section slugs.
 */

defined( 'ABSPATH' ) || exit;

$a = $d['author'];
?>
<div class="abio-t1">

	<nav class="abio-t1__crumbs">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'author-bio' ); ?></a>
		<?php if ( $d['site']['authorsUrl'] ) : ?>
			<span>/</span>
			<a href="<?php echo esc_url( $d['site']['authorsUrl'] ); ?>"><?php esc_html_e( 'Authors', 'author-bio' ); ?></a>
		<?php endif; ?>
		<span>/</span>
		<span><?php echo esc_html( $a['name'] ); ?></span>
	</nav>

	<div class="abio-t1__grid">
		<main class="abio-t1__main">

			<header class="abio-t1__header">
				<div class="abio-t1__portrait">
					<?php echo ABIO_View::media( $a['portrait'], 'medium', 'portrait 1:1', 'abio-t1__portrait-img' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<?php if ( ! empty( $d['gallery']['items'] ) ) : ?>
						<ul class="abio-t1__thumbs">
							<?php foreach ( $d['gallery']['items'] as $g ) : ?>
								<li title="<?php echo esc_attr( $g['caption'] ); ?>">
									<?php echo ABIO_View::media( $g['image'], 'thumbnail', $g['short'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<div class="abio-t1__intro">
					<span class="abio-kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
					<h1 class="abio-t1__name"><?php echo esc_html( $a['name'] ); ?></h1>
					<?php if ( $a['role'] ) : ?>
						<p class="abio-t1__role"><?php echo esc_html( $a['role'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $a['badges'] ) ) : ?>
						<ul class="abio-chips">
							<?php foreach ( $a['badges'] as $badge ) : ?>
								<li><span><?php echo esc_html( $badge ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( $a['bio'] ) : ?>
						<div class="abio-prose abio-t1__bio"><?php echo wp_kses_post( wpautop( $a['bio'] ) ); ?></div>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( ! empty( $d['stats'] ) ) : ?>
				<ul class="abio-t1__stats">
					<?php foreach ( $d['stats'] as $s ) : ?>
						<li>
							<span class="abio-t1__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<span class="abio-t1__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $d['focus'] ) ) : ?>
				<section id="abio-focus" class="abio-t1__section">
					<h2 class="abio-t1__h2"><?php esc_html_e( 'Areas of focus', 'author-bio' ); ?></h2>
					<ul class="abio-t1__focus">
						<?php foreach ( $d['focus'] as $f ) : ?>
							<li>
								<h3><?php echo esc_html( $f['title'] ); ?></h3>
								<p><?php echo esc_html( $f['body'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['edits'] ) ) : ?>
				<section id="abio-edits" class="abio-t1__section">
					<h2 class="abio-t1__h2"><?php esc_html_e( 'Latest edits', 'author-bio' ); ?></h2>
					<ul class="abio-t1__edits">
						<?php foreach ( $d['edits'] as $e ) : ?>
							<li>
								<div class="abio-t1__edit-meta">
									<span><?php echo esc_html( $e['date'] ); ?></span>
									<span class="abio-t1__edit-status"><?php echo esc_html( $e['status'] ); ?></span>
								</div>
								<div class="abio-t1__edit-body">
									<span class="abio-t1__edit-type"><?php echo esc_html( $e['type'] ); ?></span>
									<h3><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
									<p><?php echo esc_html( $e['summary'] ); ?></p>
									<span class="abio-t1__edit-time"><?php echo esc_html( $e['readTime'] ); ?></span>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $d['experience'] ) ) : ?>
				<section id="abio-experience" class="abio-t1__section">
					<h2 class="abio-t1__h2"><?php esc_html_e( 'Experience', 'author-bio' ); ?></h2>
					<ul class="abio-t1__exp">
						<?php foreach ( $d['experience'] as $x ) : ?>
							<li>
								<span class="abio-t1__exp-years"><?php echo esc_html( $x['years'] ); ?></span>
								<div>
									<h3><?php echo esc_html( $x['title'] ); ?></h3>
									<span class="abio-t1__exp-org"><?php echo esc_html( $x['org'] ); ?></span>
									<p><?php echo esc_html( $x['body'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

		</main>

		<aside class="abio-t1__rail">

			<?php if ( ! empty( $d['nav'] ) ) : ?>
				<nav class="abio-t1__toc">
					<h3 class="abio-eyebrow"><?php esc_html_e( 'On this page', 'author-bio' ); ?></h3>
					<ol>
						<?php foreach ( $d['nav'] as $n ) : ?>
							<li>
								<span class="abio-t1__toc-num"><?php echo esc_html( $n['num'] ); ?></span>
								<a href="<?php echo esc_attr( $n['href'] ); ?>"><?php echo esc_html( $n['label'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $d['credentials'] ) ) : ?>
				<div class="abio-t1__block">
					<h3><?php esc_html_e( 'Credentials', 'author-bio' ); ?></h3>
					<ul class="abio-t1__list">
						<?php foreach ( $d['credentials'] as $c ) : ?>
							<li><?php echo esc_html( $c ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['follows'] ) ) : ?>
				<div class="abio-t1__block">
					<h3><?php esc_html_e( 'Follows', 'author-bio' ); ?></h3>
					<ul class="abio-chips">
						<?php foreach ( $d['follows'] as $h ) : ?>
							<li><a href="<?php echo esc_url( $h['url'] ); ?>" rel="nofollow ugc noopener" target="_blank"><?php echo esc_html( $h['handle'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $d['others'] ) ) : ?>
				<div class="abio-t1__block">
					<h3><?php esc_html_e( 'Other authors', 'author-bio' ); ?></h3>
					<ul class="abio-t1__others">
						<?php foreach ( $d['others'] as $o ) : ?>
							<li>
								<span class="abio-t1__others-dot"></span>
								<span class="abio-t1__others-text">
									<a href="<?php echo esc_url( $o['url'] ); ?>"><?php echo esc_html( $o['name'] ); ?></a>
									<span><?php echo esc_html( $o['role'] ); ?></span>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $d['pitch']['title'] ) : ?>
				<div class="abio-t1__pitch">
					<h3><?php echo esc_html( $d['pitch']['title'] ); ?></h3>
					<p><?php echo esc_html( $d['pitch']['body'] ); ?></p>
					<?php if ( $d['site']['contactUrl'] && $d['pitch']['cta'] ) : ?>
						<a class="abio-cta" href="<?php echo esc_url( $d['site']['contactUrl'] ); ?>"><?php echo esc_html( $d['pitch']['cta'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</aside>
	</div>
</div>
```

- [ ] **Step 6: Append template 1's styles**

Append to `assets/css/author-bio.css`:

```css
/* ------------------------------------------------ 1 · classic sidebar */

.abio--t1 { background: var(--abio-paper); }
.abio-t1 { max-width: 1180px; margin: 0 auto; padding: 28px 32px 72px; }

.abio-t1__crumbs { display: flex; gap: 8px; font-size: 12px; color: var(--abio-muted); margin-bottom: 28px; }

.abio-t1__grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 56px; align-items: start; }
.abio-t1__main { display: flex; flex-direction: column; gap: 44px; min-width: 0; }

.abio-t1__header { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 32px; align-items: start; }
.abio-t1__portrait { display: flex; flex-direction: column; gap: 8px; }
.abio-t1__portrait .abio-media { aspect-ratio: 1; width: 100%; object-fit: cover; }
.abio-t1__thumbs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.abio-t1__thumbs .abio-media { aspect-ratio: 1; width: 100%; object-fit: cover; font-size: 9px; min-height: 0; }

.abio-t1__intro { display: flex; flex-direction: column; gap: 14px; padding-top: 4px; }
.abio-t1__name { font-size: 46px; font-weight: 500; letter-spacing: -.015em; line-height: 1.05; }
.abio-t1__role { font-size: 17px; color: var(--abio-dim); }
.abio-t1__bio { max-width: 60ch; color: var(--abio-dim); }

.abio-t1__stats {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 1px;
	background: var(--abio-line);
	border: 1px solid var(--abio-line);
}
.abio-t1__stats li { background: var(--abio-paper); padding: 26px 24px; display: flex; flex-direction: column; gap: 10px; }
.abio-t1__stat-value { font-size: 30px; line-height: 1; }
.abio-t1__stat-label { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--abio-muted); }

.abio-t1__section { display: flex; flex-direction: column; gap: 20px; }
.abio-t1__h2 { font-size: 24px; font-weight: 500; padding-bottom: 12px; border-bottom: 1px solid var(--abio-line); }

.abio-t1__focus { display: grid; grid-template-columns: 1fr 1fr; gap: 24px 32px; }
.abio-t1__focus li { display: flex; flex-direction: column; gap: 8px; }
.abio-t1__focus h3 { font-size: 15px; font-weight: 600; }
.abio-t1__focus p { font-size: 14px; line-height: 1.6; color: var(--abio-soft); text-wrap: pretty; }

.abio-t1__edits { display: flex; flex-direction: column; }
.abio-t1__edits li {
	display: grid;
	grid-template-columns: 120px minmax(0, 1fr);
	gap: 24px;
	padding: 20px 0;
	border-bottom: 1px solid var(--abio-wash);
}
.abio-t1__edit-meta { display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--abio-muted); }
.abio-t1__edit-status { font: 10px/1 var(--abio-mono); letter-spacing: .1em; text-transform: uppercase; }
.abio-t1__edit-body { display: flex; flex-direction: column; gap: 7px; }
.abio-t1__edit-type { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--abio-muted); }
.abio-t1__edit-body h3 { font-size: 16px; font-weight: 600; line-height: 1.35; }
.abio-t1__edit-body p { font-size: 14px; line-height: 1.6; color: var(--abio-soft); text-wrap: pretty; }
.abio-t1__edit-time { font-size: 12px; color: var(--abio-muted); }

.abio-t1__exp { display: flex; flex-direction: column; gap: 24px; }
.abio-t1__exp li { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 24px; }
.abio-t1__exp li > div { display: flex; flex-direction: column; gap: 6px; }
.abio-t1__exp-years,
.abio-t1__exp-org { font-size: 13px; color: var(--abio-muted); }
.abio-t1__exp h3 { font-size: 15px; font-weight: 600; }
.abio-t1__exp p { font-size: 14px; line-height: 1.6; color: var(--abio-soft); text-wrap: pretty; }

.abio-t1__rail { display: flex; flex-direction: column; gap: 32px; position: sticky; top: 80px; }
.abio-t1__toc ol { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.abio-t1__toc li { display: flex; gap: 10px; font-size: 14px; }
.abio-t1__toc-num { font: 11px var(--abio-mono); color: var(--abio-faint); padding-top: 2px; }

.abio-t1__block { display: flex; flex-direction: column; gap: 12px; padding-top: 24px; border-top: 1px solid var(--abio-line); }
.abio-t1__block h3 { font-size: 14px; font-weight: 600; }
.abio-t1__list { display: flex; flex-direction: column; gap: 10px; }
.abio-t1__list li { font-size: 13px; line-height: 1.55; color: var(--abio-soft); }

.abio-t1__others { display: flex; flex-direction: column; gap: 14px; }
.abio-t1__others li { display: grid; grid-template-columns: 44px minmax(0, 1fr); gap: 12px; align-items: center; }
.abio-t1__others-dot {
	aspect-ratio: 1;
	border-radius: 50%;
	background-image: repeating-linear-gradient(45deg, var(--abio-line) 0 4px, var(--abio-wash) 4px 8px);
	border: 1px solid var(--abio-line);
}
.abio-t1__others-text { display: flex; flex-direction: column; gap: 3px; }
.abio-t1__others-text a { font-size: 13px; font-weight: 600; }
.abio-t1__others-text span { font-size: 12px; color: var(--abio-muted); line-height: 1.4; }

.abio-t1__pitch {
	display: flex;
	flex-direction: column;
	gap: 10px;
	padding: 20px;
	background: var(--abio-wash);
	border: 1px solid var(--abio-line);
}
.abio-t1__pitch h3 { font-size: 14px; font-weight: 600; }
.abio-t1__pitch p { font-size: 13px; line-height: 1.55; color: var(--abio-soft); }

@media (max-width: 1024px) {
	.abio-t1__grid { grid-template-columns: minmax(0, 1fr); gap: 40px; }
	.abio-t1__rail { position: static; }
	.abio-t1__stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
	.abio-t1 { padding: 20px 20px 56px; }
	.abio-t1__header { grid-template-columns: minmax(0, 1fr); gap: 24px; }
	.abio-t1__portrait { max-width: 220px; }
	.abio-t1__name { font-size: 34px; }
	.abio-t1__focus { grid-template-columns: minmax(0, 1fr); }
	.abio-t1__edits li,
	.abio-t1__exp li { grid-template-columns: minmax(0, 1fr); gap: 10px; }
}

@media (max-width: 560px) {
	.abio-t1__stats { grid-template-columns: minmax(0, 1fr); }
}
```

- [ ] **Step 7: Register everything**

In `includes/class-plugin.php`, the `$files` array becomes exactly:

```php
		$files = array(
			'includes/class-fields.php',
			'includes/class-post-type.php',
			'includes/class-metaboxes.php',
			'includes/class-palette.php',
			'includes/class-settings.php',
			'includes/class-articles.php',
			'includes/class-stats.php',
			'includes/class-profile.php',
			'includes/class-view.php',
			'includes/class-assets.php',
			'includes/class-shortcode.php',
		);
```

and add these hooks:

```php
		add_action( 'init', array( 'ABIO_Shortcode', 'register' ) );
		add_action( 'wp_enqueue_scripts', array( 'ABIO_Assets', 'register' ) );
```

- [ ] **Step 8: Syntax-check**

Run: `find . -name '*.php' -not -path './docs/*' -print0 | xargs -0 -n1 php -l`
Expected: `No syntax errors detected` for every file.

- [ ] **Step 9: Verify on wp-lab**

Re-upload the plugin. Set up content first, then check rendering:

1. Publish at least six posts authored by the profile's linked user, across two categories, and edit one of them a week after its publish date (set the modified date by editing and re-saving after changing the publish date to the past).
2. Create a second author profile linked to a second user, so "Other authors" has something to show.
3. Fill **Authors → Settings**: contact URL, authors index URL, pitch title/body/CTA.
4. Create a page containing `[author_bio template=1]` and view it.

Expected:
- The classic-sidebar layout renders: breadcrumb, portrait with three gallery thumbs, name, role, badge chips, bio, a stats strip, Areas of focus in two columns, Latest edits, Experience, and a right rail with On this page / Credentials / Follows / Other authors / the pitch box.
- The Latest edits rows show real post titles linking to real permalinks, real dates, a category name in the type slot, "N min" read times, and "Updated" on the post you back-dated.
- Automatic stat tiles show real numbers; the manual tile shows what you typed.
- No image set anywhere shows the hatched placeholder with its label, not a broken image.
- `[author_bio template=1 count=2]` shows two edits; `[author_bio template=1 hide="experience"]` drops the Experience section *and* its "On this page" entry.
- `[author_bio user=<second user id> template=1]` renders the other profile.
- Put `[author_bio]` in a page and view it as a logged-out user with no matching profile: nothing renders. As an editor: the "no published author profile" line renders.
- Resize to 1024, 768 and 375: the rail drops below the main column, the header stacks, and nothing overflows horizontally.

- [ ] **Step 10: Commit**

```bash
git add includes/ templates/ assets/css/ 
git commit -m "feat: author_bio shortcode and template 1"
```

---

## Template Porting Convention (Tasks 10–12)

Templates 2–10 are mechanical ports of the same data array Task 9 already built. Each task below gives the exact source line range in `docs/design/author-page-templates.dc.html` and the structure to produce. Follow these rules for every one of them:

1. **Read the source range first.** Every inline `style="…"` in that range becomes a class in `assets/css/author-bio.css`. Do not leave inline styles in the PHP; the only inline style in the output is the palette on the root element, which the shortcode already emits.
2. **File header.** Same docblock as `templates/template-1.php`, with the template's own name and line range, `defined( 'ABSPATH' ) || exit;`, and `$a = $d['author'];`.
3. **No root wrapper.** The shortcode emits `<div class="abio abio--tN" style="…">`. The template's outermost element is `<div class="abio-tN">`.
4. **Class naming.** Template-specific classes are `abio-tN__thing`. Reuse the shared components from Task 9 wherever the design's styling matches them: `abio-kicker`, `abio-eyebrow`, `abio-chips`, `abio-prose`, `abio-cta`, `abio-panel--dark`, and `ABIO_View::media()` for every image.
5. **Guard every section** with `if ( ! empty( $d['<key>'] ) )`, exactly as template 1 does. A profile with no gallery must not render an empty gallery frame.
6. **Section IDs** stay `abio-focus`, `abio-edits`, `abio-experience` in every template, so `$d['nav']` anchors work everywhere.
7. **Escaping.** `esc_html()` for text, `esc_url()` for hrefs, `esc_attr()` for attributes, `wp_kses_post( wpautop( $a['bio'] ) )` for the bio. External `follows` links carry `rel="nofollow ugc noopener" target="_blank"`.
8. **Hardcoded design copy** — headings like "Areas of focus", "Latest edits", "Scouting report", "Box score · recent bylines" — becomes `esc_html_e( '…', 'author-bio' )`. Keep the design's wording.
9. **Colors.** Every hex in the source maps to a token, never a literal: `#17181a`→`var(--abio-ink)`, `#fbfbfa`→`var(--abio-paper)`, `#f4f4f2`→`var(--abio-wash)`, `#e2e2df`→`var(--abio-line)`, `#ececea`→`var(--abio-wash)`, `#8a8c90`→`var(--abio-muted)`, `#5a5c60`→`var(--abio-dim)`, `#6f7175`→`var(--abio-soft)`, `#b0b0ab`/`#a0a09b`→`var(--abio-faint)`, `#c6c6c1`→`var(--abio-onink)`, `#a8a8a3`→`var(--abio-onink-dim)`, `#3a3c40`→`var(--abio-onink-line)`, `#d8d8d4`→`var(--abio-line)`, `#4a4c50`/`#3a3c40` in body text→`var(--abio-dim)`. Dark regions use `.abio-panel--dark`.
10. **Responsive.** Every template ends with the same three breakpoints: `1024px` (multi-column body grids collapse, sticky rails go static, 4-up stat rows become 2-up), `768px` (side padding drops to 20px, display headings scale to roughly half, two-column content lists become one column, multi-column table-style rows stack), `560px` (everything single column, stat rows 1-up). Nothing may scroll horizontally at 375px.
11. **Verification, every template:** `php -l` the new file, re-upload, render `[author_bio template=N]` on the test page, and compare against the design side by side at 1440px. Then check 1024 / 768 / 375.

---

### Task 10: Templates 2, 3 and 4

**Files:**
- Create: `templates/template-2.php`, `templates/template-3.php`, `templates/template-4.php`
- Modify: `assets/css/author-bio.css` — append one block per template

**Interfaces:**
- Consumes: `$d`, `$hide`, `ABIO_View::media()` — the Task 9 template contract, unchanged.
- Produces: nothing new. These are views only.

- [ ] **Step 1: Template 2 — Résumé** (`docs/design/author-page-templates.dc.html:179-289`)

Root `.abio-t2`. Two columns, `320px minmax(0,1fr)`, max-width 1120, the sidebar full-height with `background: var(--abio-wash)` and `border-right: 1px solid var(--abio-line)`.

Sidebar (`.abio-t2__aside`, padding `40px 32px 64px`, gap 32), in order:
1. Portrait 1:1 plus the three gallery thumbs in a 3-column grid (same shape as template 1).
2. `h1` name at 34px/500, role 15px, then `location · Since since` at 13px muted.
3. Four blocks, each `border-top: 1px solid var(--abio-line)`, `padding-top: 24px`, heading in `.abio-eyebrow`: **Credentials** (list), **Verified** (`$a['badges']`, plain list not chips), **Follows** (vertical links, not chips), **Other authors** (name + role stacked).

Main (`.abio-t2__main`, padding `48px 48px 72px`, gap 44):
1. `<span class="abio-kicker">` reading `<kicker> profile`, then the bio at 22px/1.5, max 64ch.
2. Stats: 4-column grid, gap 24, `border-top`/`border-bottom` line, padding `22px 0`, value 28px.
3. `#abio-focus`, `#abio-experience`, `#abio-edits` — each a `180px minmax(0,1fr)` grid whose left cell is an `.abio-eyebrow` heading. Experience and Edits carry `border-top: 1px solid var(--abio-line); padding-top: 36px`.
   - Experience rows: title and years on one baseline-aligned justified row, then org, then body.
   - Edits rows: an uppercase 11px meta row of date / type / status, then the linked title at 15px/600, `border-bottom` line. No summary, no read time — template 2 deliberately omits them.

Responsive: at 1024 the sidebar becomes a full-width block above the main column (`grid-template-columns: minmax(0,1fr)`, sidebar `min-height:auto`, drop the right border); at 768 the `180px 1fr` section grids collapse to one column and stats go 2-up; at 560 stats go 1-up.

- [ ] **Step 2: Template 3 — Editorial masthead** (`docs/design/author-page-templates.dc.html:291-397`)

Root `.abio-t3`, `padding-bottom: 80px`.

1. Centered masthead, max 1000, padding `64px 32px 40px`: kicker reading `<kicker> · <location>`, `h1` at 76px/400 letter-spacing -.02em, role in italic 22px, badges as a centered 12px uppercase row (not chips — plain text, gap 20).
2. Gallery band, max 1240: 3-column grid, gap 16, each item a 4:3 image with a centered 12px caption below.
3. Body column, max 720, padding `56px 32px 0`, gap 52:
   - Bio at 21px/1.65.
   - Stats: `display:flex; flex-wrap:wrap; justify-content:space-between; gap:36px`, `border-top: 1px solid var(--abio-ink)`, `border-bottom: 1px solid var(--abio-line)`, padding `24px 0`, each tile centered with a 32px value.
   - `#abio-focus`: centered 15px uppercase heading; items are `h3` 20px/500 plus 15px body, each with a bottom hairline.
   - `#abio-experience`: centered items — years in mono, `h3` 20px/500, org, body.
   - `#abio-edits`: items with an uppercase meta row (`date` then `type · status`), `h3` 19px/500 linked, then the summary at 14px.
4. Footer band, max 1240, `margin-top: 56px`: a 3-column grid with `gap: 1px` over a `var(--abio-line)` background and a matching border, so the cells read as hairline-separated. Cells: **Credentials**, **Follows** (chips), **Other authors**.

Responsive: at 1024 the gallery goes 2-up and `h1` to 56px; at 768 gallery 1-up, `h1` 40px, footer band single column, stats wrap centred; at 560 padding drops to 20px.

- [ ] **Step 3: Template 4 — Bento** (`docs/design/author-page-templates.dc.html:399-511`)

Root `.abio-t4`, `background: var(--abio-wash)`, padding 32. Inside, a 12-column grid, gap 16, max 1280. Every cell is `background: var(--abio-paper); border: 1px solid var(--abio-line)` unless stated.

Cells in source order, with their spans:
1. `span 3` — portrait 1:1, then badges as a plain 12px list.
2. `span 5` — kicker, `h1` 40px/500, role 16px, bio 14px. Vertically centered.
3. `span 4` — a nested `1fr 1fr` grid of four stat cards, each its own bordered cell with a 34px value and an uppercase label pinned to the bottom (`justify-content: space-between`).
4. `span 12` — three gallery figures side by side, each a 170px-tall image with a 12px caption.
5. `#abio-focus` `span 8` — eyebrow heading, then a 2-column item grid.
6. `#abio-edits` `span 4` **and `grid-row: span 2`** — eyebrow heading, then a compact list: a 10px uppercase date/status row and a 14px linked title per row.
7. `#abio-experience` `span 8` — eyebrow heading, 2-column items.
8. `span 4` **Credentials**, `span 4` **Follows** (chips), `span 4` **Other authors**.
9. `span 4` — the pitch cell, using `.abio-panel--dark`.

The row-2 span on the edits cell is what gives this layout its shape; keep it.

Responsive: at 1024 every `span 8` becomes `span 12`, `span 4`/`span 5`/`span 3` become `span 6`, and the edits cell drops its row span; at 768 every cell is `span 12` and the gallery stacks; at 560 the nested stat grid goes 1-up.

- [ ] **Step 4: Syntax-check**

Run: `php -l templates/template-2.php && php -l templates/template-3.php && php -l templates/template-4.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Verify on wp-lab**

Re-upload. On the test page, render `[author_bio template=2]`, `[author_bio template=3]` and `[author_bio template=4]` in turn, and also `[author_bio template="resume"]`, `[author_bio template="editorial-masthead"]` and `[author_bio template="bento"]` to confirm the slug aliases resolve to the same output. Compare each against its design range at 1440px, then check 1024 / 768 / 375 for overflow.

- [ ] **Step 6: Commit**

```bash
git add templates/template-2.php templates/template-3.php templates/template-4.php assets/css/author-bio.css
git commit -m "feat: templates 2-4 (resume, editorial masthead, bento)"
```

---

### Task 11: Templates 5, 6 and 7

**Files:**
- Create: `templates/template-5.php`, `templates/template-6.php`, `templates/template-7.php`
- Modify: `assets/css/author-bio.css`

**Interfaces:**
- Consumes: `$d`, `$hide`, `ABIO_View::media()`.
- Produces: nothing new.

- [ ] **Step 1: Template 5 — Numbered rail** (`docs/design/author-page-templates.dc.html:513-601`)

Root `.abio-t5`, max 1200, grid `220px minmax(0,1fr)`, gap 64, padding `40px 32px 80px`.

Left rail, `position: sticky; top: 80px; align-self: start`:
1. A 44px round avatar (`.abio-t5__avatar`, the same hatched placeholder treatment as template 1's "other authors" dot, or the portrait image when set) beside the name at 13px/600 with the kicker under it at 11px.
2. The `$d['nav']` list as an `<ol>` with a `border-top` and 16px top padding: each row a `26px 1fr` grid with the number in mono at `var(--abio-faint)`.
3. A `border-top` block of `follows` handles stacked vertically at 12px muted.

Main column:
1. Header, padding-bottom 44: kicker `<kicker> · <location>`, `h1` 60px/400 max 18ch, role 19px, bio 16px/1.7 max 62ch, then a 3-column gallery capped at 520px wide (1:1 images with 11px captions), then stats as an inline wrapping row of `value` 24px beside an uppercase label.
2. `#abio-focus` — `border-top: 1px solid var(--abio-ink)`, padding `44px 0`, a baseline row of the section number in mono beside an `h2` at 28px/500, then 2-column items.
3. `#abio-edits` — `border-top: 1px solid var(--abio-line)`, same heading shape, rows as `minmax(0,1fr) 110px` with the right column right-aligned and holding date / type / read time.
4. `#abio-experience` — same, rows as `160px minmax(0,1fr)`.

The design hardcodes the section numbers `01`, `02`, `03`. Take them from `$d['nav']` instead — matching on the nav entry whose `href` is `#abio-focus`, `#abio-edits`, `#abio-experience` — so a hidden section does not leave a gap in the sequence. When a section has no nav entry, render its heading with no number.

Responsive: at 1024 the rail goes full-width and static above the main column and the nav list becomes a horizontal wrapping row; at 768 `h1` drops to 38px, focus goes 1-column, edits and experience rows stack with the meta column moving below and left-aligned; at 560 the gallery goes 2-up.

- [ ] **Step 2: Template 6 — Full-bleed dossier** (`docs/design/author-page-templates.dc.html:603-713`)

Root `.abio-t6`. Full-bleed dark header and dark footer, light body between.

1. Header, `.abio-panel--dark`: inner max 1280, padding `64px 40px`, grid `minmax(0,1fr) 420px`, gap 64, vertically centered.
   - Left: kicker reading `<kicker> · <location> · Since <since>`, `h1` 64px/600 letter-spacing -.03em, role 21px in `var(--abio-onink)`, bio 16px/1.7 in `var(--abio-onink-dim)` max 58ch, badges as bordered chips using `var(--abio-onink-line)`.
   - Right: a 2×2 square grid, gap 10 — the three gallery items (each showing its label top-left and caption bottom-left over the image) plus the portrait in the fourth cell.
2. Stats band: `background: var(--abio-paper)`, `border-bottom: 1px solid var(--abio-line)`; inner max 1280, 4-column grid; each cell padding `28px 0`, `border-right: 1px solid var(--abio-wash)`, value 38px/600 on the same baseline as an uppercase label.
3. `#abio-focus`, `#abio-edits`, `#abio-experience` — each max 1280, padding `72px 40px 0`, opening with a 13px uppercase heading with `border-bottom: 1px solid var(--abio-ink)`.
   - Focus: 4-column items, each led by a `2px` tall, `28px` wide ink rule above a 17px title.
   - Edits: rows as `120px 150px minmax(0,1fr) 80px` — date, `type · status`, title plus summary, right-aligned read time.
   - Experience: 2-column items with 20px titles.
4. Footer, `.abio-panel--dark`, `margin-top: 72px`: max 1280, padding `56px 40px`, 3 columns gap 48 — **Follows** (chips on `var(--abio-onink-line)` borders), **Other authors**, and the pitch with its CTA.

Responsive: at 1024 the header becomes one column with the image grid below and capped at 420px, focus goes 2-up, edits rows become `120px minmax(0,1fr)` with type and read time moving under the title; at 768 `h1` 40px, stats 2-up, focus 1-up, footer 1 column; at 560 stats 1-up.

- [ ] **Step 3: Template 7 — Sports desk** (`docs/design/author-page-templates.dc.html:715-838`)

Root `.abio-t7`, `background: var(--abio-wash)`.

1. Top band, `.abio-panel--dark`: max 1240, padding `44px 40px`, grid `180px minmax(0,1fr)`, gap 36, centered.
   - Portrait at `aspect-ratio: 3/4`.
   - Kicker `<kicker> · <location>` with letter-spacing `.24em`; `h1` 72px/700 uppercase, line-height .95, letter-spacing -.035em; role 18px in `var(--abio-onink)`; then a 4-column stat row above a `border-top: 1px solid var(--abio-onink-line)`, each stat a 34px/700 value beside a 10px mono uppercase label.
2. Body: max 1240, padding `36px 40px 80px`, grid `minmax(0,1fr) 300px`, gap 36.

   Main column, gap 24, every block a card (`background: var(--abio-paper); border: 1px solid var(--abio-line); padding: 32px`), each opening with a mono 10px uppercase heading at letter-spacing `.2em`:
   - **Scouting report** — bio at 17px/1.7, then a 3-column gallery of 130px-tall images with 11px captions.
   - `#abio-focus` **Beats covered** — 2-column items, each a `30px minmax(0,1fr)` grid with `$f['n']` in mono in the left cell and an uppercase 15px/700 title in the right.
   - `#abio-edits` **Box score · recent bylines** — a list with `border-top: 1px solid var(--abio-ink)`; rows are `96px 120px minmax(0,1fr) 60px` — mono date, mono `type · status`, 15px/600 linked title, right-aligned mono read time.
   - `#abio-experience` **Career line** — 2-column items, each with `border-left: 2px solid var(--abio-ink); padding-left: 16px`.

   Aside, sticky at 80, gap 24, cards with padding 24 and mono 10px headings at letter-spacing `.16em`: **Credentials**, **Verified** (badges as a plain list), **Follows** (chips with mono 11px labels), **Other authors**, then the pitch as an `.abio-panel--dark` card with a 15px/700 uppercase title.

Responsive: at 1024 the aside goes full-width and static below the main column and the top band's stats go 2-up; at 768 the top band becomes one column with the portrait capped at 200px, `h1` 44px, focus and experience go 1-column, box-score rows stack into title-then-meta; at 560 `h1` 34px.

- [ ] **Step 4: Syntax-check**

Run: `php -l templates/template-5.php && php -l templates/template-6.php && php -l templates/template-7.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Verify on wp-lab**

Re-upload. Render `[author_bio template=5]`, `6` and `7`, plus the slugs `numbered-rail`, `dossier` and `sports-desk`. Compare against the design at 1440px; check 1024 / 768 / 375. Specifically confirm: template 5's section numbers stay contiguous when you render `[author_bio template=5 hide="focus"]`, and templates 6 and 7's dark panels take their color from `--abio-ink` (change the palette Ink override in settings to `#0b2545`, reload, and confirm both bands change).

- [ ] **Step 6: Commit**

```bash
git add templates/template-5.php templates/template-6.php templates/template-7.php assets/css/author-bio.css
git commit -m "feat: templates 5-7 (numbered rail, dossier, sports desk)"
```

---

### Task 12: Templates 8, 9 and 10

**Files:**
- Create: `templates/template-8.php`, `templates/template-9.php`, `templates/template-10.php`
- Modify: `assets/css/author-bio.css`

**Interfaces:**
- Consumes: `$d`, `$hide`, `ABIO_View::media()`.
- Produces: nothing new.

- [ ] **Step 1: Template 8 — Fintech product** (`docs/design/author-page-templates.dc.html:840-964`)

Root `.abio-t8`, `background: var(--abio-wash)`. This is the only template with rounded corners: cards use `border-radius: 6px`, inner elements 4px, status pills 99px.

1. Top bar: `background: var(--abio-paper)`, `border-bottom: 1px solid var(--abio-line)`; inner max 1200, padding `14px 32px`, a wrapping flex row with `<site name> · Author profile` in mono 10px uppercase on the left and the stats inline on the right (mono 13px/600 value beside a mono 10px uppercase label).
2. Body: max 1200, padding `32px 32px 80px`, grid `340px minmax(0,1fr)`, gap 24.

   Aside, sticky at 80, gap 16:
   - **Identity card** — a 72px rounded-square portrait beside the name at 22px/600 and role at 13px; then `$a['short']` at 13px/1.65; then a `border-top` meta list of justified label/value rows (`Location`, `Contributing since`) followed by one row per badge prefixed with a `✓` in `var(--abio-muted)`; then a full-width filled CTA (`background: var(--abio-ink); color: var(--abio-paper); border-radius: 4px`) linking to the contact URL with the pitch CTA label. Skip the meta rows whose value is empty.
   - **Credentials**, **Follows** (rounded chips), **Other authors** — plain cards with mono 10px headings.

   Main column, gap 16, cards with padding 28 and mono 10px uppercase headings:
   - **About** — bio at 16px/1.75, then a 3-column gallery of 140px-tall images with `border-radius: 4px`.
   - `#abio-focus` **Coverage** — 2-column tiles, each `background: var(--abio-wash); border: 1px solid var(--abio-wash); border-radius: 4px; padding: 18px`.
   - `#abio-edits` **Activity** — rows as `90px minmax(0,1fr) 92px` with `border-top: 1px solid var(--abio-wash)`: mono date, linked 15px/600 title over a 13px summary, and the status as a right-aligned pill (`border: 1px solid var(--abio-line); border-radius: 99px; padding: 3px 10px; font: 10px var(--abio-mono)`).
   - `#abio-experience` — rows as `150px minmax(0,1fr)`, mono years on the left.

Responsive: at 1024 the aside goes full-width and static above the main column, its cards laid out as a 2-column grid; at 768 one column throughout, gallery 1-up, coverage tiles 1-up, activity rows stack with the pill moving under the title; at 560 the top bar stats wrap to their own line.

- [ ] **Step 2: Template 9 — Research note** (`docs/design/author-page-templates.dc.html:966-1084`)

Root `.abio-t9`, `background: var(--abio-wash)`, padding `32px 0 80px`. Inside, one `<article>` at max 1000, `background: var(--abio-paper)`, `border: 1px solid var(--abio-line)`, padding `56px 64px`.

1. Header, `border-bottom: 2px solid var(--abio-ink)`, padding-bottom 24: a mono 10px uppercase line reading `<site name> · Analyst profile`, then a `minmax(0,1fr) 132px` grid — left holds `h1` 42px/600, role 17px, and `<location> · Contributing since <since>` at 14px muted; right holds the 1:1 portrait.
2. Summary row: `1fr 1fr` grid, padding `24px 0`, `border-bottom: 1px solid var(--abio-line)`. Left: a **Summary** heading over the bio at 14px/1.7. Right: **Key figures** — one row per stat, `display:flex; justify-content:space-between`, uppercase 12px label against a mono 13px/600 value, each row `border-bottom: 1px dotted var(--abio-line)`.
3. Five numbered sections, each a `190px minmax(0,1fr)` grid with gap 32, padding `32px 0`, `border-bottom: 1px solid var(--abio-line)`, and a mono 10px uppercase heading in the left cell:
   - `#abio-focus` **1 · Coverage universe** — an `<ol>` whose items are `34px minmax(0,1fr)` grids with `$f['sub']` (`1.1`, `1.2`, …) in mono on the left.
   - **2 · Exhibits** — a 3-column gallery of 120px-tall images, each captioned with `Exhibit <n>` in mono uppercase above the caption text.
   - `#abio-edits` **3 · Published notes** — a list with `border-top: 1px solid var(--abio-ink)`; rows `92px minmax(0,1fr) 108px`: mono date, 14px/600 linked title, right-aligned mono `type · status`.
   - `#abio-experience` **4 · Track record** — items with `<title> — <org>` and the years justified to the right on one baseline, then the body at 14px/1.7.
   - **5 · Qualifications** — a `1fr 1fr` grid: credentials list on the left; on the right, **Follows** as chips above **Other authors**.

   The section numbers are part of the heading text in the design. Number only the sections that render, so hiding one does not leave a gap — compute the number as you emit each section rather than hardcoding it.

Responsive: at 1024 article padding drops to `40px 32px` and the exhibit gallery goes 2-up; at 768 every `190px 1fr` section grid collapses to one column with the heading above its content, the summary row goes 1-column, `h1` 30px, the header portrait moves above the name at 96px, and published-note rows stack; at 560 padding `28px 20px`, exhibits 1-up.

- [ ] **Step 3: Template 10 — Brand feature** (`docs/design/author-page-templates.dc.html:1086-1200`)

Root `.abio-t10`, `background: var(--abio-paper)`.

1. Centered hero, max 1100, padding `88px 40px 48px`: kicker `<kicker> · <site name>` at letter-spacing `.24em`; `h1` 84px/700, line-height .98, letter-spacing -.04em, max 16ch; role 24px/1.4 max 32ch; then a filled dark CTA (`background: var(--abio-ink); color: var(--abio-paper); padding: 14px 26px`) to the contact URL.
2. Gallery band, max 1280, padding `0 40px`: a `1fr 1.4fr 1fr` grid, gap 12, `align-items: end` — the deliberate asymmetry is the point. Images are 300px tall with 12px captions.
3. Statement band, `.abio-panel--dark`, `margin-top: 56px`, padding `56px 40px`: inner max 1100, grid `minmax(0,1fr) 320px`, gap 56, centered — the bio at 26px/1.5 on the left, a 2×2 stat grid on the right with 36px/700 values over mono uppercase labels.
4. `#abio-focus`, max 1100, padding `72px 40px 0`: a centered heading pair — an `h2` at 36px/600 and `$a['short']` under it at 16px muted, max 52ch. The design hardcodes "What Jeff covers"; build it as `sprintf( esc_html__( 'What %s covers', 'author-bio' ), $first )` where `$first` is the first word of `$a['name']`, falling back to `esc_html__( 'Areas of focus', 'author-bio' )` when the name is empty. Items: a 2-column grid with `gap: 2px` over a `var(--abio-line)` background and a matching border, each cell `background: var(--abio-paper); padding: 32px`, led by `$f['n']` in mono.
5. `#abio-edits` **Recent work**, max 1100, padding `72px 40px 0`: centered `h2` 36px/600, then rows as `minmax(0,1fr) 130px` with `border-top: 1px solid var(--abio-line)` — left holds a mono `type · status` line, a 20px/600 linked title and a 15px summary; right holds mono date and read time, right-aligned.
6. `#abio-experience` **Background**: centered `h2`, then 2-column cards with `background: var(--abio-wash); padding: 28px`.
7. Footer strip, max 1100, padding `72px 40px 0`: 3 columns gap 40 — **Credentials** (list plus the badges as chips underneath), **Follows** (chips), **Other authors**. Add `padding-bottom: 80px` so the page does not end flush.

Responsive: at 1024 the gallery goes to three equal columns, the statement band to one column with the stats below, focus and experience stay 2-up; at 768 `h1` 46px, gallery 1-up, focus/experience/footer all 1-column, recent-work rows stack with the date line moving above the title; at 560 `h1` 34px, padding 20px.

- [ ] **Step 4: Syntax-check**

Run: `php -l templates/template-8.php && php -l templates/template-9.php && php -l templates/template-10.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Verify on wp-lab**

Re-upload. Render `[author_bio template=8]`, `9` and `10`, plus the slugs `fintech`, `research-note` and `brand-feature`. Compare against the design at 1440px; check 1024 / 768 / 375. Confirm template 9's section numbering stays contiguous under `[author_bio template=9 hide="focus"]`, and that template 10's focus heading reads "What <first name> covers".

- [ ] **Step 6: Commit**

```bash
git add templates/template-8.php templates/template-9.php templates/template-10.php assets/css/author-bio.css
git commit -m "feat: templates 8-10 (fintech, research note, brand feature)"
```

---

### Task 13: Admin polish, documentation, and full verification

**Files:**
- Modify: `includes/class-post-type.php` — add the linked-user admin column
- Modify: `readme.txt` — document every attribute and both slug forms
- Create: `README.md`
- Create: `docs/screenshots/.gitkeep`

**Interfaces:**
- Consumes: `ABIO_Fields::meta_key()`, `ABIO_Post_Type::SLUG`
- Produces:
  - `ABIO_Post_Type::columns( array $columns ): array`
  - `ABIO_Post_Type::column( string $column, int $post_id ): void`

- [ ] **Step 1: Add the linked-user column**

Append to `includes/class-post-type.php`, inside the class:

```php
	/**
	 * Add a Linked user column to the profile list table.
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;
		unset( $columns['date'] );

		$columns['abio_user'] = __( 'Linked user', 'author-bio' );

		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Render the Linked user column.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public static function column( $column, $post_id ) {
		if ( 'abio_user' !== $column ) {
			return;
		}

		$user_id = (int) get_post_meta( $post_id, ABIO_Fields::meta_key( 'user' ), true );

		if ( ! $user_id ) {
			echo '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No linked user', 'author-bio' ) . '</span>';
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			esc_html_e( 'Missing user', 'author-bio' );
			return;
		}

		printf(
			'<a href="%s">%s</a>',
			esc_url( get_author_posts_url( $user_id ) ),
			esc_html( $user->display_name )
		);
	}
```

And in `includes/class-plugin.php`, add:

```php
		add_filter( 'manage_' . ABIO_Post_Type::SLUG . '_posts_columns', array( 'ABIO_Post_Type', 'columns' ) );
		add_action( 'manage_' . ABIO_Post_Type::SLUG . '_posts_custom_column', array( 'ABIO_Post_Type', 'column' ), 10, 2 );
```

Note: `ABIO_Post_Type::SLUG` is referenced at `init()` time, which runs after the class file is required, so the constant resolves.

- [ ] **Step 2: Write `README.md`**

```markdown
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
`experience`, `credentials`, `follows`, `others`, `pitch`.

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

## Colors

Templates are built from three CSS custom properties — `--abio-ink`,
`--abio-paper` and `--abio-accent` — with everything else derived from them via
`color-mix()`. On activation the plugin reads Elementor's global colors or the
Bricks palette and seeds those three. **Authors → Settings** shows what was
detected and lets you override any of them.

## Requirements

WordPress 6.0+, PHP 7.4+. No Composer, no npm, no build step, no ACF.
```

- [ ] **Step 3: Update `readme.txt`**

Replace the `== Usage ==` section with the same attribute table and template list as `README.md`, in plain text.

- [ ] **Step 4: Syntax-check**

Run: `find . -name '*.php' -not -path './docs/*' -print0 | xargs -0 -n1 php -l`
Expected: `No syntax errors detected` for every file.

- [ ] **Step 5: Full verification pass on wp-lab**

Re-upload, then work the whole matrix. Every line must pass:

**Rendering**
1. `[author_bio template=N]` for N = 1..10 — each renders its layout, none produce a PHP notice with `WP_DEBUG` on.
2. Each of the ten slugs renders the same output as its number.
3. `[author_bio template=99]` and `[author_bio template=banana]` fall back to template 1 rather than erroring.

**Resolution**
4. Add `[author_bio template=1]` to the theme's author archive template (or a page assigned as one) and visit two different authors' archives — each shows its own profile.
5. `[author_bio user=<login>]` and `[author_bio user=<id>]` both resolve.
6. `[author_bio id=<profile post id>]` renders that profile directly.
7. An author with no profile renders nothing when logged out, and the diagnostic line when logged in as an editor.

**Data**
8. Article rows link to real posts, show real dates, show a category name for posts and a post-type label for other types, show "Updated" only for posts modified well after publication, and show plausible read times.
9. `count=2` and `post_type="post,page"` both take effect.
10. All four stat modes produce correct values; switching a tile to "not shown" removes it from every template.
11. `hide="focus,edits,experience,gallery,stats,credentials,follows,others,pitch"` renders a valid page with only the header.

**Empty states**
12. A profile with only a linked user and a name renders every template without a fatal error, a stray heading, or an empty bordered box.
13. Profiles with no portrait and no gallery show hatched placeholders, never broken images.

**Palette**
14. Set the Ink override to `#0b2545` and Paper to `#fdfdfb`; every template shifts, including the dark bands in 4, 6, 7 and 10.
15. Clear both overrides; the templates return to the detected values.

**Responsive**
16. At 1440 / 1024 / 768 / 375, no template scrolls horizontally and no text is clipped.

**Admin**
17. The Authors list table shows the Linked user column, linking to that user's archive.
18. Creating a second profile for an already-linked user shows the duplicate warning, and the front end keeps rendering the lower-ID profile.

Record anything that fails, fix it, and re-run the affected line.

- [ ] **Step 6: Commit**

```bash
git add includes/class-post-type.php includes/class-plugin.php README.md readme.txt docs/
git commit -m "feat: linked-user column and plugin documentation"
```

---

## Self-Review Notes

Checked against `docs/superpowers/specs/2026-08-28-author-bio-design.md`:

- Purpose, success criteria 1–6 — Tasks 9–13.
- Non-goals — no block/widget task, no permalink (Task 1 sets `public => false`), no test harness, no `portraitWide` field (absent from Task 2's schema).
- Architecture file list — every file has a creating task. `class-view.php` was added in Task 9 beyond the spec's list; it is the shared image/placeholder helper the ten templates all need, and its absence would have meant duplicating the placeholder markup ten times.
- Data contract — Task 8, with the derived `focus[].n`, `focus[].sub` and `gallery[].n` values templates 7, 9 and 10 need. `author.url` was added to the contract in Task 8 so "Other authors" cards can link without a second lookup.
- Content type, fields, stat tiles, global settings — Tasks 1, 2, 3, 4, 7.
- Articles — Task 6.
- Shortcode, attributes, resolution order, slug aliases, missing-profile behavior — Task 9.
- Styling, breakpoints, hover, `wp_get_attachment_image()` — Tasks 9–12.
- Palette — Task 5.
- Assets — Task 9 (`class-assets.php` plus the shortcode's direct enqueue).
- Security — escaping rules in the Global Constraints and the porting convention; nonce and capability checks in Task 3; sanitizers in Tasks 2 and 4.
- Verification — the spec's seven manual checks are all covered by Task 13 Step 5, except item 7 (Elementor and Bricks sites), which cannot be run on a plain wp-lab instance. Run it on a real site of each kind before shipping, or install the free Elementor plugin on the wp-lab instance and set two global colors to exercise the Elementor path.
