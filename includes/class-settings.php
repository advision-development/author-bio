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
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( self::scalar( $input[ $key ] ) ) : '';
		}

		foreach ( array( 'editorial_url', 'contact_url', 'authors_url' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( self::scalar( $input[ $key ] ) ) : '';
		}

		$clean['pitch_body'] = isset( $input['pitch_body'] ) ? wp_kses_post( self::scalar( $input['pitch_body'] ) ) : '';

		$template                    = isset( $input['default_template'] ) ? absint( $input['default_template'] ) : 1;
		$clean['default_template']   = (string) min( 10, max( 1, $template ) );

		$count                  = isset( $input['default_count'] ) ? absint( $input['default_count'] ) : 6;
		$clean['default_count'] = min( 50, max( 1, $count ) );

		foreach ( array( 'palette_ink', 'palette_paper', 'palette_accent' ) as $key ) {
			$raw           = isset( $input[ $key ] ) ? trim( self::scalar( $input[ $key ] ) ) : '';
			$clean[ $key ] = self::sanitize_hex( $raw );
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
}
