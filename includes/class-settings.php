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
	 * The screen id WordPress generated for the settings page. Captured rather
	 * than reconstructed: the hook for a submenu under a post-type menu is not
	 * something to guess at, and guessing wrong means assets load nowhere or
	 * everywhere.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

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
			'typeface'           => 'theme',
			'show_pitch'         => 1,
			'show_breadcrumbs'   => 1,
			'fallback_profiles'  => 1,
			// Off by default. Yoast and Rank Math both already describe authors
			// on an author archive, and two Person graphs for one person is
			// worse than none from us.
			'show_schema'        => 0,
			'index_all_profiles' => 1,
			'index_users'        => array(),
			'palette_ink'        => '',
			'palette_paper'      => '',
			'palette_accent'     => '',
			// Derived colours: blank means "keep deriving from the seeds".
			'palette_wash'        => '',
			'palette_line'        => '',
			'palette_muted'       => '',
			'palette_dim'         => '',
			'palette_soft'        => '',
			'palette_faint'       => '',
			'palette_onink'       => '',
			'palette_onink_dim'   => '',
			'palette_onink_line'  => '',
			'palette_accent_soft' => '',
			'corners'             => 'soft',
			'content_width'       => 'default',
			'density'             => 'default',
		);
	}

	/**
	 * Corner styles. `circle` is absent on purpose: template 5's round portrait
	 * is that template's identity, not a corner radius, and "Square" must not
	 * flatten it into a box.
	 *
	 * @return array slug => label, sm, md, pill
	 */
	public static function corner_styles() {
		return array(
			'square' => array(
				'label' => __( 'Square', 'author-bio' ),
				'sm'    => '0',
				'md'    => '0',
				'pill'  => '0',
			),
			'soft'   => array(
				'label' => __( 'Soft — the designed default', 'author-bio' ),
				'sm'    => '4px',
				'md'    => '6px',
				'pill'  => '99px',
			),
			'round'  => array(
				'label' => __( 'Rounded', 'author-bio' ),
				'sm'    => '8px',
				'md'    => '12px',
				'pill'  => '99px',
			),
		);
	}

	/**
	 * Content-width steps. A multiplier rather than an absolute width, so each
	 * template keeps its own proportion — the masthead stays narrower than the
	 * product-UI template instead of all ten collapsing to one measure.
	 *
	 * @return array slug => label, value
	 */
	public static function content_widths() {
		return array(
			'narrow'  => array( 'label' => __( 'Narrow (−20%)', 'author-bio' ), 'value' => '0.8' ),
			'default' => array( 'label' => __( 'Default', 'author-bio' ), 'value' => '1' ),
			'wide'    => array( 'label' => __( 'Wide (+15%)', 'author-bio' ), 'value' => '1.15' ),
		);
	}

	/**
	 * Density steps, applied to every padding and gap.
	 *
	 * @return array slug => label, value
	 */
	public static function densities() {
		return array(
			'compact' => array( 'label' => __( 'Compact (−15%)', 'author-bio' ), 'value' => '0.85' ),
			'default' => array( 'label' => __( 'Default', 'author-bio' ), 'value' => '1' ),
			'roomy'   => array( 'label' => __( 'Roomy (+20%)', 'author-bio' ), 'value' => '1.2' ),
		);
	}

	/**
	 * The shape and spacing custom properties to put on the shortcode root.
	 *
	 * Only values that differ from the stylesheet's own defaults are returned:
	 * a site that never touched these settings emits nothing extra, and the
	 * declarations in author-bio.css stand.
	 *
	 * @return array property => value
	 */
	public static function shape_vars() {
		$out = array();

		$corners = self::corner_styles();
		$chosen  = (string) self::get( 'corners', 'soft' );

		if ( 'soft' !== $chosen && isset( $corners[ $chosen ] ) ) {
			$out['--abio-radius-sm']   = $corners[ $chosen ]['sm'];
			$out['--abio-radius-md']   = $corners[ $chosen ]['md'];
			$out['--abio-radius-pill'] = $corners[ $chosen ]['pill'];
		}

		$widths = self::content_widths();
		$width  = (string) self::get( 'content_width', 'default' );

		if ( 'default' !== $width && isset( $widths[ $width ] ) ) {
			$out['--abio-width'] = $widths[ $width ]['value'];
		}

		$densities = self::densities();
		$density   = (string) self::get( 'density', 'default' );

		if ( 'default' !== $density && isset( $densities[ $density ] ) ) {
			$out['--abio-space'] = $densities[ $density ]['value'];
		}

		return $out;
	}

	/**
	 * Typeface choices offered on the settings page.
	 *
	 * System stacks only, deliberately: the plugin ships zero font requests so
	 * it never adds a third-party connection, a layout shift, or a consent
	 * surface to a site it does not control. "theme" is not a stack — it means
	 * inherit whatever the host already uses, which is the default.
	 *
	 * @return array slug => array( label, stack )
	 */
	public static function typefaces() {
		return array(
			'theme'     => array(
				'label' => __( "Match the site's theme", 'author-bio' ),
				'stack' => '',
			),
			'system'    => array(
				'label' => __( 'System UI', 'author-bio' ),
				'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
			),
			'grotesque' => array(
				'label' => __( 'Grotesque (Helvetica Neue)', 'author-bio' ),
				'stack' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
			),
			'humanist'  => array(
				'label' => __( 'Humanist (Segoe UI, Roboto)', 'author-bio' ),
				'stack' => '"Segoe UI", Roboto, "Noto Sans", Ubuntu, Cantarell, sans-serif',
			),
			'serif'     => array(
				'label' => __( 'Serif (Georgia, Charter)', 'author-bio' ),
				'stack' => 'Charter, Georgia, "Iowan Old Style", "Times New Roman", serif',
			),
		);
	}

	/**
	 * The font stack to inline on the shortcode root, or '' to inherit.
	 *
	 * @return string
	 */
	public static function font_stack() {
		$faces = self::typefaces();
		$key   = (string) self::get( 'typeface', 'theme' );

		return isset( $faces[ $key ] ) ? $faces[ $key ]['stack'] : '';
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
		self::$hook_suffix = add_submenu_page(
			'edit.php?post_type=' . ABIO_Post_Type::SLUG,
			__( 'Author Bio Settings', 'author-bio' ),
			__( 'Settings', 'author-bio' ),
			'manage_options',
			'abio-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Colour pickers and the tab behaviour, on this screen only.
	 *
	 * wp-color-picker ships with WordPress, so this adds no dependency and no
	 * outside request — the same reason the typeface list is system stacks.
	 *
	 * @param string $hook
	 */
	public static function assets( $hook ) {
		if ( ! self::$hook_suffix || $hook !== self::$hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'abio-settings',
			ABIO_URL . 'assets/admin/settings.css',
			array( 'wp-color-picker' ),
			ABIO_VERSION
		);

		// In the head, not the footer: the script's first act is to mark the
		// document as scripted so the CSS can hide inactive panels. Deferred to
		// the footer, every panel would paint first and then collapse.
		wp_enqueue_script(
			'abio-settings',
			ABIO_URL . 'assets/admin/settings.js',
			array( 'jquery', 'wp-color-picker' ),
			ABIO_VERSION,
			false
		);
	}

	/**
	 * The tabs, in the order they appear.
	 *
	 * Author index leads because it is the one an editor returns to; the rest
	 * are set once when a site is configured.
	 *
	 * @return array slug => label
	 */
	public static function tabs() {
		return array(
			'index'      => __( 'Author index', 'author-bio' ),
			'general'    => __( 'General', 'author-bio' ),
			'content'    => __( 'Content', 'author-bio' ),
			'appearance' => __( 'Appearance', 'author-bio' ),
			'colors'     => __( 'Colors', 'author-bio' ),
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
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( self::scalar( $input[ $key ] ) ) : '';
		}

		foreach ( array( 'editorial_url', 'contact_url', 'authors_url' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( self::scalar( $input[ $key ] ) ) : '';
		}

		$clean['pitch_body'] = isset( $input['pitch_body'] ) ? wp_kses_post( self::scalar( $input['pitch_body'] ) ) : '';

		// An unchecked checkbox is absent from the POST body entirely, so a
		// missing key means off. Falling back to the default here would make a
		// toggle impossible to switch off.
		foreach ( array( 'show_pitch', 'show_breadcrumbs', 'fallback_profiles', 'index_all_profiles', 'show_schema' ) as $flag ) {
			$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		$chosen               = isset( $input['index_users'] ) ? (array) $input['index_users'] : array();
		$clean['index_users'] = array();

		foreach ( $chosen as $candidate ) {
			$id = absint( $candidate );

			// Only real users, and never the same person twice.
			if ( $id && get_userdata( $id ) && ! in_array( $id, $clean['index_users'], true ) ) {
				$clean['index_users'][] = $id;
			}
		}

		$face                = isset( $input['typeface'] ) ? sanitize_key( self::scalar( $input['typeface'] ) ) : 'theme';
		$clean['typeface']   = array_key_exists( $face, self::typefaces() ) ? $face : 'theme';

		$template                    = isset( $input['default_template'] ) ? absint( $input['default_template'] ) : 1;
		$clean['default_template']   = (string) min( 10, max( 1, $template ) );

		$count                  = isset( $input['default_count'] ) ? absint( $input['default_count'] ) : 6;
		$clean['default_count'] = min( 50, max( 1, $count ) );

		$colour_keys = array( 'palette_ink', 'palette_paper', 'palette_accent' );

		foreach ( array_keys( ABIO_Palette::MIXES ) as $derived ) {
			$colour_keys[] = 'palette_' . str_replace( '-', '_', $derived );
		}

		foreach ( $colour_keys as $key ) {
			$raw           = isset( $input[ $key ] ) ? trim( self::scalar( $input[ $key ] ) ) : '';
			$clean[ $key ] = self::sanitize_hex( $raw );
		}

		// Each of these is a choice from a fixed list, so an unknown value
		// falls back to the designed default rather than reaching the CSS.
		foreach ( array(
			'corners'       => array( 'soft', self::corner_styles() ),
			'content_width' => array( 'default', self::content_widths() ),
			'density'       => array( 'default', self::densities() ),
		) as $key => $spec ) {
			list( $fallback, $choices ) = $spec;

			$chosen        = isset( $input[ $key ] ) ? sanitize_key( self::scalar( $input[ $key ] ) ) : $fallback;
			$clean[ $key ] = array_key_exists( $chosen, $choices ) ? $chosen : $fallback;
		}

		return $clean;
	}

	/**
	 * WordPress hands sanitize_callback whatever was posted, which may be an
	 * array. Every string sanitizer below would fatal on one, so non-scalar
	 * input collapses to an empty string first.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private static function scalar( $value ) {
		return is_scalar( $value ) ? (string) $value : '';
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
		$tabs   = self::tabs();

		echo '<div class="wrap abio-settings">';
		echo '<h1>' . esc_html__( 'Author Bio Settings', 'author-bio' ) . '</h1>';

		// This page lives under the Authors menu rather than under Settings, so
		// WordPress does not print the "Settings saved." notice for it.
		settings_errors();

		echo '<div class="nav-tab-wrapper abio-tabs" role="tablist" aria-label="'
			. esc_attr__( 'Settings sections', 'author-bio' ) . '">';

		$first = true;

		foreach ( $tabs as $slug => $label ) {
			printf(
				'<a href="#abio-panel-%1$s" id="abio-tab-%1$s" class="nav-tab%2$s"'
					. ' role="tab" aria-controls="abio-panel-%1$s" aria-selected="%3$s" tabindex="%4$s">%5$s</a>',
				esc_attr( $slug ),
				$first ? ' nav-tab-active' : '',
				$first ? 'true' : 'false',
				$first ? '0' : '-1',
				esc_html( $label )
			);

			$first = false;
		}

		echo '</div>';

		echo '<form method="post" action="options.php">';

		settings_fields( self::GROUP );

		// Every panel is in one form and every field is always posted. Splitting
		// the tabs into a form each would silently wipe data: sanitize() reads a
		// missing checkbox as "off", so saving one tab would switch off every
		// toggle on the others and empty the selected-authors list.
		$first = true;

		foreach ( $tabs as $slug => $label ) {
			printf(
				// No tabindex: a tabpanel only needs to be focusable when it
				// holds nothing focusable, and every panel here is a form.
				// Giving it one also meant landing on the page at a tab's
				// fragment painted a focus ring around the whole panel.
				'<section id="abio-panel-%1$s" class="abio-panel%2$s" role="tabpanel"'
					. ' aria-labelledby="abio-tab-%1$s">',
				esc_attr( $slug ),
				$first ? ' is-active' : ''
			);

			// A heading per panel, so the page still reads as an ordered
			// document when the tabs are inert.
			echo '<h2 class="abio-panel__title">' . esc_html( $label ) . '</h2>';

			self::panel( $slug, $values );

			echo '</section>';

			$first = false;
		}

		submit_button();

		echo '</form></div>';
	}

	/**
	 * One tab's fields.
	 *
	 * @param string $slug
	 * @param array  $values
	 */
	private static function panel( $slug, $values ) {
		switch ( $slug ) {
			case 'index':
				self::index_fields( $values );
				break;

			case 'general':
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
					__( 'Shortcode defaults', 'author-bio' ),
					array(
						array( 'default_template', __( 'Default template (1–10)', 'author-bio' ), 'text' ),
						array( 'default_count', __( 'Articles shown', 'author-bio' ), 'text' ),
						array( 'default_post_types', __( 'Article post types (comma-separated)', 'author-bio' ), 'text' ),
					),
					$values
				);

				echo '<p class="description">'
					. esc_html__( 'These apply to any [author_bio] or [author_bio_list] that does not set the attribute itself.', 'author-bio' )
					. '</p>';

				self::section(
					__( 'Structured data', 'author-bio' ),
					array(
						array(
							'show_schema',
							__( 'schema.org JSON-LD', 'author-bio' ),
							'checkbox',
							__( 'Describe the author in structured data', 'author-bio' ),
						),
					),
					$values
				);

				echo '<p class="description">'
					. esc_html__( 'Off by default, and worth leaving off unless you know nothing else is doing this. An SEO plugin — Yoast and Rank Math both do — already describes authors on an author archive, and two descriptions of one person is worse than one. Switch it on where the plugin is rendering a page nothing else covers.', 'author-bio' )
					. '</p>';

				echo '<p class="description">'
					. esc_html__( 'When on, a profile page emits a ProfilePage with the author as its main entity, and an index emits an ItemList. Both are built only from fields that are filled in.', 'author-bio' )
					. '</p>';
				break;

			case 'content':
				self::section(
					__( 'Sections', 'author-bio' ),
					array(
						array(
							'show_pitch',
							__( 'Pitch box', 'author-bio' ),
							'checkbox',
							__( 'Show the contributor pitch and its contact button', 'author-bio' ),
						),
						array(
							'show_breadcrumbs',
							__( 'Breadcrumbs', 'author-bio' ),
							'checkbox',
							__( 'Show the breadcrumb trail above the profile', 'author-bio' ),
						),
						array(
							'fallback_profiles',
							__( 'Unconfigured authors', 'author-bio' ),
							'checkbox',
							__( 'Build a page from the WordPress user when no Author Profile exists', 'author-bio' ),
						),
					),
					$values
				);

				echo '<p class="description">'
					. esc_html__( 'The pitch appears as a bordered box in most templates and as the hero button in templates 8 and 10; switching it off removes both. Breadcrumbs are shown by template 1. Individual shortcodes can still suppress either with hide="pitch" or hide="breadcrumbs".', 'author-bio' )
					. '</p>';

				echo '<p class="description">'
					. esc_html__( 'With unconfigured authors enabled, an author who has no Author Profile still gets a page in the selected template: their name, their picture, their WordPress biography and their published articles. Nothing is invented — a field WordPress does not hold is left out, so the section simply does not appear. Switch it off to leave those archives empty instead.', 'author-bio' )
					. '</p>';

				self::section(
					__( 'Pitch box', 'author-bio' ),
					array(
						array( 'pitch_title', __( 'Title', 'author-bio' ), 'text' ),
						array( 'pitch_body', __( 'Body', 'author-bio' ), 'textarea' ),
						array( 'pitch_cta', __( 'Button label', 'author-bio' ), 'text' ),
					),
					$values
				);
				break;

			case 'appearance':
				self::typeface_field( $values );
				self::shape_section( $values );
				break;

			case 'colors':
				self::palette_section( $values );
				self::derived_section( $values );
				break;
		}
	}

	/**
	 * Render one titled table of fields.
	 *
	 * @param string $title
	 * @param array  $fields Each: array( key, label, type ).
	 * @param array  $values
	 */
	private static function section( $title, $fields, $values ) {
		if ( '' !== $title ) {
			echo '<h3>' . esc_html( $title ) . '</h3>';
		}

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $field ) {
			list( $key, $label, $type ) = $field;

			$name  = self::OPTION . '[' . $key . ']';
			$value = isset( $values[ $key ] ) ? $values[ $key ] : '';

			if ( 'checkbox' === $type ) {
				echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
			} else {
				echo '<tr><th scope="row"><label for="abio-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
			}

			if ( 'checkbox' === $type ) {
				printf(
					'<label><input type="checkbox" id="abio-%s" name="%s" value="1"%s /> %s</label>',
					esc_attr( $key ),
					esc_attr( $name ),
					checked( ! empty( $value ), true, false ),
					esc_html( isset( $field[3] ) ? $field[3] : '' )
				);
			} elseif ( 'textarea' === $type ) {
				echo '<textarea class="large-text" rows="3" id="abio-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			} elseif ( 'select' === $type ) {
				echo '<select id="abio-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">';

				foreach ( (array) ( isset( $field[3] ) ? $field[3] : array() ) as $slug => $label_text ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $slug ),
						selected( (string) $value, (string) $slug, false ),
						esc_html( $label_text )
					);
				}

				echo '</select>';
			} elseif ( 'hex' === $type ) {
				// A wp-color-picker with no data-default-color: that keeps the
				// button labelled "Clear" and clearing it empties the field,
				// which is what returns the colour to being derived. Setting a
				// default would relabel it and write a literal instead, quietly
				// pinning a value that was meant to follow the seeds.
				$resolved = isset( $field[3] ) ? $field[3] : '';

				printf(
					'<input type="text" class="abio-color" id="abio-%s" name="%s" value="%s" placeholder="%s" />',
					esc_attr( $key ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $resolved )
				);

				if ( '' !== $resolved ) {
					/* translators: %s: the color this field resolves to when left empty. */
					$using = sprintf( __( 'Using %s', 'author-bio' ), $resolved );
					/* translators: %s: the derived color being replaced. */
					$over = sprintf( __( 'Overriding %s', 'author-bio' ), $resolved );

					printf(
						'<p class="description abio-resolved">'
							. '<span class="abio-swatch" style="background:%1$s" aria-hidden="true"></span>'
							. '<span class="abio-resolved__text" data-using="%2$s" data-overriding="%3$s">%4$s</span></p>',
						esc_attr( $resolved ),
						esc_attr( $using ),
						esc_attr( $over ),
						esc_html( '' === $value ? $using : $over )
					);
				}
			} else {
				echo '<input type="' . esc_attr( 'url' === $type ? 'url' : 'text' ) . '" class="regular-text" id="abio-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Who appears in [author_bio_list].
	 *
	 * @param array $values
	 */
	private static function index_fields( $values ) {
		$all      = ! empty( $values['index_all_profiles'] );
		$selected = isset( $values['index_users'] ) ? (array) $values['index_users'] : array();

		echo '<table class="form-table" role="presentation"><tbody>';

		echo '<tr><th scope="row">' . esc_html__( 'Configured authors', 'author-bio' ) . '</th><td>';
		printf(
			'<label><input type="checkbox" name="%s" value="1"%s /> %s</label>',
			esc_attr( self::OPTION . '[index_all_profiles]' ),
			checked( $all, true, false ),
			esc_html__( 'Show all authors who have a saved Author Profile', 'author-bio' )
		);
		echo '<p class="description">'
			. esc_html__( 'Leave this on for the usual case. Switch it off to list only the authors you pick below, which is how you curate an exact index.', 'author-bio' )
			. '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row"><label for="abio-index_users">'
			. esc_html__( 'Select authors', 'author-bio' ) . '</label></th><td>';

		// The same population as a profile's Linked user field, so the two
		// screens offer the same people.
		$users = get_users(
			array(
				'capability' => array( 'edit_posts' ),
				'orderby'    => 'display_name',
				'order'      => 'ASC',
				'fields'     => array( 'ID', 'display_name', 'user_login' ),
			)
		);

		// A capability query can return the same person more than once when they
		// hold several capability-granting roles, and a duplicated option in a
		// multi-select is both confusing and able to submit an ID twice.
		$unique = array();

		foreach ( $users as $user ) {
			$unique[ (int) $user->ID ] = $user;
		}

		$users = array_values( $unique );

		if ( ! $users ) {
			echo '<p class="description">' . esc_html__( 'No users on this site can be listed as authors yet.', 'author-bio' ) . '</p>';
		} else {
			// The multi-select is the whole control when scripting is off, and
			// the data source when it is on: settings.js reads the options and
			// the current selection out of it, then builds the token field from
			// them. Nothing about who can be listed lives in the script.
			printf(
				'<div class="abio-tokens" data-name="%s" data-empty="%s" data-add="%s"'
					. ' data-full="%s" data-added="%s" data-removed="%s" data-remove="%s">',
				esc_attr( self::OPTION . '[index_users][]' ),
				esc_attr__( 'No authors added yet.', 'author-bio' ),
				esc_attr__( 'Add an author…', 'author-bio' ),
				esc_attr__( 'Every eligible user has been added.', 'author-bio' ),
				/* translators: %s: an author's name, announced after adding them. */
				esc_attr__( '%s added', 'author-bio' ),
				/* translators: %s: an author's name, announced after removing them. */
				esc_attr__( '%s removed', 'author-bio' ),
				/* translators: %s: an author's name, on the button that removes them. */
				esc_attr__( 'Remove %s', 'author-bio' )
			);

			printf(
				'<select id="abio-index_users" name="%s" multiple size="%d" class="abio-multiselect">',
				esc_attr( self::OPTION . '[index_users][]' ),
				(int) min( 12, max( 5, count( $users ) ) )
			);

			$chosen = array_map( 'absint', $selected );

			foreach ( $users as $user ) {
				$label = $user->display_name ? $user->display_name : $user->user_login;

				printf(
					'<option value="%d"%s data-login="%s">%s</option>',
					(int) $user->ID,
					selected( in_array( (int) $user->ID, $chosen, true ), true, false ),
					esc_attr( $user->user_login ),
					esc_html( $label )
				);
			}

			echo '</select>';
			echo '</div>';

			echo '<p class="description">'
				. esc_html__( 'Adds these people to the index whether or not they have an Author Profile. Anyone already covered by a profile is listed once, from that profile.', 'author-bio' )
				. '</p>';

			// The Command-click instruction is only true of the fallback, so it
			// is hidden once the token field replaces it.
			echo '<p class="description abio-tokens__fallback-hint">'
				. esc_html__( 'Hold Command or Control to select several, and to deselect.', 'author-bio' )
				. '</p>';
		}

		echo '</td></tr>';
		echo '</tbody></table>';
	}

	/**
	 * The typeface picker.
	 *
	 * @param array $values
	 */
	private static function typeface_field( $values ) {
		$current = isset( $values['typeface'] ) ? $values['typeface'] : 'theme';

		echo '<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="abio-typeface">'
			. esc_html__( 'Typeface', 'author-bio' ) . '</label></th><td>';

		echo '<select id="abio-typeface" name="' . esc_attr( self::OPTION . '[typeface]' ) . '">';
		foreach ( self::typefaces() as $key => $face ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $current, $key, false ),
				esc_html( $face['label'] )
			);
		}
		echo '</select>';

		echo '<p class="description">'
			. esc_html__( 'The author page inherits your theme\'s typeface by default, the same way it inherits your colors. Choose a stack here only when the theme\'s font does not suit a dense profile page. No webfont is ever loaded.', 'author-bio' )
			. '</p>';

		echo '</td></tr></tbody></table>';
	}

	/**
	 * The palette section. Task 5 adds the detection readout and re-detect button.
	 *
	 * @param array $values
	 */
	private static function palette_section( $values ) {
		$detected = ABIO_Palette::detected();

		$sources = array(
			'elementor' => __( 'Elementor global colors', 'author-bio' ),
			'bricks'    => __( 'Bricks color palette', 'author-bio' ),
			'default'   => __( 'plugin defaults (no page builder detected)', 'author-bio' ),
		);

		$source = isset( $sources[ $detected['source'] ] ) ? $sources[ $detected['source'] ] : $detected['source'];



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
				array( 'palette_ink', __( 'Ink (text and dark panels)', 'author-bio' ), 'hex', $detected['ink'] ),
				array( 'palette_paper', __( 'Paper (card background)', 'author-bio' ), 'hex', $detected['paper'] ),
				array( 'palette_accent', __( 'Accent (links and buttons)', 'author-bio' ), 'hex', $detected['accent'] ),
			),
			$values
		);

	}

	/**
	 * The ten colours mixed from the three seeds.
	 *
	 * @param array $values
	 */
	private static function derived_section( $values ) {
		$derived = ABIO_Palette::derived();

		$labels = array(
			'wash'        => __( 'Wash (page ground behind cards)', 'author-bio' ),
			'line'        => __( 'Line (hairline borders)', 'author-bio' ),
			'muted'       => __( 'Muted (labels and eyebrows)', 'author-bio' ),
			'dim'         => __( 'Dim (roles and secondary text)', 'author-bio' ),
			'soft'        => __( 'Soft (summary and body copy)', 'author-bio' ),
			'faint'       => __( 'Faint (image placeholders)', 'author-bio' ),
			'onink'       => __( 'On-Ink (text on dark panels)', 'author-bio' ),
			'onink-dim'   => __( 'On-Ink Dim (secondary on dark)', 'author-bio' ),
			'onink-line'  => __( 'On-Ink Line (borders on dark)', 'author-bio' ),
			'accent-soft' => __( 'Accent Soft (link hover)', 'author-bio' ),
		);

		// Open when something is already overridden, so a saved value is never
		// hidden behind a closed disclosure.
		$set = 0;

		foreach ( array_keys( $labels ) as $key ) {
			if ( '' !== (string) self::get( 'palette_' . str_replace( '-', '_', $key ), '' ) ) {
				$set++;
			}
		}

		printf(
			'<details class="abio-derived"%s><summary>%s</summary>',
			$set ? ' open' : '',
			esc_html(
				$set
					/* translators: %d: how many derived colors are overridden. */
					? sprintf( _n( 'Derived colors — %d overridden', 'Derived colors — %d overridden', $set, 'author-bio' ), $set )
					: sprintf( __( 'Override derived colors (%d)', 'author-bio' ), count( $labels ) )
			)
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'These are mixed from Ink, Paper and Accent, which is how the palette re-tones itself when a site changes its brand color. Each field shows the mixed value it will use; set one only to override that mix, and clear it to go back to deriving. Overriding several means a future brand change no longer carries through on its own.', 'author-bio' )
		);

		$fields = array();

		foreach ( $labels as $key => $label ) {
			$fields[] = array(
				'palette_' . str_replace( '-', '_', $key ),
				$label,
				'hex',
				isset( $derived[ $key ] ) ? $derived[ $key ] : '',
			);
		}

		self::section( '', $fields, $values );

		echo '</details>';
	}

	/**
	 * Corners, content width and density.
	 *
	 * @param array $values
	 */
	private static function shape_section( $values ) {
		echo '<h3>' . esc_html__( 'Shape and spacing', 'author-bio' ) . '</h3>';

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Width and density are proportional, so each template keeps its own character rather than all ten collapsing to one measure. Round portraits are unaffected by the corner setting — a circular portrait is one template\'s identity, not a corner radius.', 'author-bio' )
		);

		self::section(
			'',
			array(
				array( 'corners', __( 'Corners', 'author-bio' ), 'select', wp_list_pluck( self::corner_styles(), 'label' ) ),
				array( 'content_width', __( 'Content width', 'author-bio' ), 'select', wp_list_pluck( self::content_widths(), 'label' ) ),
				array( 'density', __( 'Density', 'author-bio' ), 'select', wp_list_pluck( self::densities(), 'label' ) ),
			),
			$values
		);
	}
}
