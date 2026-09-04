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
	 * Minimum contrast ratio a detected ink must clear against the resolved
	 * paper. Ink doubles as body text and the fill of the dark panels, so the
	 * bar is the stricter WCAG AAA threshold rather than the AA 4.5:1 used for
	 * normal text.
	 */
	const MIN_INK_CONTRAST = 7.0;

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

		$ink_raw    = isset( $by_id['text'] ) ? $by_id['text'] : '';
		$accent_raw = isset( $by_id['primary'] ) ? $by_id['primary'] : '';
		$paper_raw  = self::elementor_background( $settings );

		if ( ! $ink_raw && ! $accent_raw && ! $paper_raw ) {
			return false;
		}

		$paper = self::hex( $paper_raw, self::DEFAULT_PAPER );

		return array(
			'source' => 'elementor',
			'ink'    => self::guarded_ink( $ink_raw, $paper, self::DEFAULT_INK ),
			'paper'  => $paper,
			'accent' => self::hex( $accent_raw, self::DEFAULT_ACCENT ),
		);
	}

	/**
	 * Elementor's kit background color lives outside system_colors, under the
	 * kit's own background settings rather than the named color swatches.
	 *
	 * @param array $settings
	 * @return string Raw value, possibly invalid or empty.
	 */
	private static function elementor_background( $settings ) {
		if ( isset( $settings['background_color'] ) && is_string( $settings['background_color'] ) ) {
			return $settings['background_color'];
		}

		if ( isset( $settings['background']['color'] ) && is_string( $settings['background']['color'] ) ) {
			return $settings['background']['color'];
		}

		return '';
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

		$colors = array();
		$values = array();

		foreach ( $first['colors'] as $color ) {
			if ( isset( $color['hex'] ) && $color['hex'] ) {
				$colors[] = $color;
				$values[] = $color['hex'];
			}
		}

		if ( empty( $values ) ) {
			return false;
		}

		$paper      = self::DEFAULT_PAPER;
		$ink_raw    = self::bricks_named( $colors, array( 'text', 'dark', 'black', 'ink', 'body', 'foreground' ) );
		$accent_raw = isset( $values[1] ) ? $values[1] : ( isset( $values[0] ) ? $values[0] : '' );

		return array(
			'source' => 'bricks',
			'ink'    => self::guarded_ink( $ink_raw, $paper, self::DEFAULT_INK ),
			'paper'  => $paper,
			'accent' => self::hex( $accent_raw, self::DEFAULT_ACCENT ),
		);
	}

	/**
	 * Find a Bricks palette color identified by name or id, rather than
	 * indexing positionally into an arbitrary list.
	 *
	 * Matches on whole words, not raw substrings — a plain strpos() for
	 * "ink" would false-positive inside an unrelated name like "Brand Pink".
	 *
	 * @param array $colors   Bricks color entries, each with a 'hex' and
	 *                        usually a 'name' and/or 'id'.
	 * @param array $keywords Lower-case words to match against.
	 * @return string Hex value, or '' when nothing matched.
	 */
	private static function bricks_named( $colors, $keywords ) {
		foreach ( $colors as $color ) {
			$haystack = strtolower(
				( isset( $color['name'] ) && is_string( $color['name'] ) ? $color['name'] : '' ) . ' ' .
				( isset( $color['id'] ) && is_string( $color['id'] ) ? $color['id'] : '' )
			);

			if ( '' === trim( $haystack ) ) {
				continue;
			}

			foreach ( $keywords as $keyword ) {
				if ( preg_match( '/\b' . preg_quote( $keyword, '/' ) . '\b/', $haystack ) ) {
					return isset( $color['hex'] ) ? $color['hex'] : '';
				}
			}
		}

		return '';
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
	 * Accept a detected ink only when it is a valid color that contrasts
	 * enough against the resolved paper to work as both body text and the
	 * fill of the dark panels. Falls back to the default ink rather than
	 * discarding the whole detection: a site can legitimately yield a good
	 * paper and a bad ink.
	 *
	 * @param string $raw    Raw detected value, possibly invalid or empty.
	 * @param string $paper  Resolved paper color, already validated.
	 * @param string $fallback
	 * @return string
	 */
	private static function guarded_ink( $raw, $paper, $fallback ) {
		$candidate = self::hex( $raw, '' );

		if ( '' === $candidate ) {
			return $fallback;
		}

		return self::contrast_ratio( $candidate, $paper ) >= self::MIN_INK_CONTRAST ? $candidate : $fallback;
	}

	/**
	 * WCAG relative luminance of a validated hex color.
	 *
	 * @param string $hex
	 * @return float
	 */
	private static function luminance( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) ) {
			return 0.0;
		}

		$channels = array_map(
			static function ( $channel ) {
				$value = hexdec( $channel ) / 255;

				return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
			},
			str_split( $hex, 2 )
		);

		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	/**
	 * WCAG contrast ratio between two hex colors.
	 *
	 * @param string $hex_a
	 * @param string $hex_b
	 * @return float
	 */
	private static function contrast_ratio( $hex_a, $hex_b ) {
		$l1 = self::luminance( $hex_a );
		$l2 = self::luminance( $hex_b );

		return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
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

		$parts = array(
			'--abio-ink:' . $palette['ink'],
			'--abio-paper:' . $palette['paper'],
			'--abio-accent:' . $palette['accent'],
		);

		// A derived colour is only emitted when someone typed one in. Left
		// blank it stays absent from the inline style, so the stylesheet's
		// color-mix() keeps deriving it and the palette keeps re-toning itself
		// from the three seeds. That is the whole point of the three-seed rule;
		// these fields are an escape hatch, not a replacement for it.
		foreach ( array_keys( self::MIXES ) as $key ) {
			$override = ABIO_Settings::get( 'palette_' . str_replace( '-', '_', $key ), '' );

			if ( $override ) {
				$parts[] = '--abio-' . $key . ':' . $override;
			}
		}

		foreach ( ABIO_Settings::shape_vars() as $prop => $value ) {
			$parts[] = $prop . ':' . $value;
		}

		return implode( ';', $parts );
	}

	/**
	 * How each non-seed colour is mixed, mirroring the @supports block in
	 * author-bio.css. Kept here so the admin can show an editor the value a
	 * blank field will actually produce, rather than an empty box.
	 *
	 * Each entry is array( base, percentage, other ) for
	 * `color-mix( in srgb, base pct%, other )`.
	 */
	const MIXES = array(
		'wash'        => array( 'paper', 97, 'ink' ),
		'line'        => array( 'paper', 89, 'ink' ),
		'muted'       => array( 'ink', 50, 'paper' ),
		'dim'         => array( 'ink', 71, 'paper' ),
		'soft'        => array( 'ink', 61, 'paper' ),
		'faint'       => array( 'ink', 33, 'paper' ),
		'onink'       => array( 'paper', 77, 'ink' ),
		'onink-dim'   => array( 'paper', 64, 'ink' ),
		'onink-line'  => array( 'paper', 15, 'ink' ),
		'accent-soft' => array( 'accent', 61, 'paper' ),
	);

	/**
	 * The ten derived colours, computed from the resolved seeds.
	 *
	 * `color-mix( in srgb, … )` interpolates in gamma-encoded sRGB, so this is
	 * a per-channel weighted average. Eight of the ten land on exactly the hex
	 * the browser paints; two sit on a half step where the browser's float
	 * order rounds the other way, so treat these as accurate to one level out
	 * of 255. They are shown to tell an editor what they are about to
	 * override, and a difference that small is not visible.
	 *
	 * @return array key => hex
	 */
	public static function derived() {
		$palette = self::resolve();
		$out     = array();

		foreach ( self::MIXES as $key => $mix ) {
			list( $base, $pct, $other ) = $mix;

			$out[ $key ] = self::mix(
				isset( $palette[ $base ] ) ? $palette[ $base ] : self::DEFAULT_INK,
				$pct,
				isset( $palette[ $other ] ) ? $palette[ $other ] : self::DEFAULT_PAPER
			);
		}

		return $out;
	}

	/**
	 * @param string $base
	 * @param int    $pct  Weight of $base, 0-100.
	 * @param string $other
	 * @return string Six-digit hex.
	 */
	private static function mix( $base, $pct, $other ) {
		$a   = self::channels( $base );
		$b   = self::channels( $other );
		$pct = max( 0, min( 100, (int) $pct ) );

		// Both weights come from the integer percentage. Deriving the second as
		// `1 - $w` introduces float error large enough to move a channel by one
		// step: 1 - 0.33 is 0.6699999999999999, not 0.67.
		$wa = $pct / 100;
		$wb = ( 100 - $pct ) / 100;

		$out = '#';

		foreach ( array( 0, 1, 2 ) as $i ) {
			// Mixed in 0-1 space and scaled back, which is the order the
			// browser uses and lands on its value more often than mixing the
			// 8-bit values directly. Checked by sampling painted pixels — the
			// serialised color(srgb ...) string is only six decimals wide and
			// cannot settle a one-level difference either way.
			$mixed = ( $a[ $i ] / 255 ) * $wa + ( $b[ $i ] / 255 ) * $wb;

			$out .= str_pad( dechex( (int) round( $mixed * 255 ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}

	/**
	 * @param string $hex
	 * @return array r, g, b as 0-255
	 */
	private static function channels( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return array( 0, 0, 0 );
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
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
