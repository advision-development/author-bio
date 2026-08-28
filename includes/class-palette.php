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
