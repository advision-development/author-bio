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
			return self::missing_notice( $atts );
		}

		$number = self::template_number( $atts['template'] );
		$file   = ABIO_PATH . 'templates/template-' . $number . '.php';

		if ( ! file_exists( $file ) ) {
			return '';
		}

		$hide = array_filter( array_map( 'sanitize_key', explode( ',', (string) $atts['hide'] ) ) );

		$count = '' === $atts['count'] ? (int) ABIO_Settings::get( 'default_count', 6 ) : absint( $atts['count'] );

		$d = $profile->to_array(
			array(
				// Mirrors the cap ABIO_Settings::sanitize() applies to the site-wide
				// default, so a shortcode attribute can't force an unbounded query.
				'count'     => min( 50, max( 1, $count ) ),
				'post_type' => ABIO_Articles::post_types( (string) $atts['post_type'] ),
				'others'    => absint( $atts['others'] ),
				'hide'      => $hide,
			)
		);

		ABIO_Assets::enqueue();

		ob_start();

		// The palette, plus the typeface when the site chose one. An empty stack
		// means "inherit the theme", which is the default, so nothing is emitted
		// and the CSS falls through to inherit.
		$vars  = ABIO_Palette::css_vars();
		$stack = ABIO_Settings::font_stack();

		if ( '' !== $stack ) {
			$vars .= ';--abio-font:' . $stack;
		}

		printf(
			'<div class="abio abio--t%d" style="%s">',
			$number,
			esc_attr( $vars )
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
	 * Editors get a diagnosable blank, naming whichever author was resolved
	 * (or saying plainly that none was) so the blank is diagnosable; everyone
	 * else gets nothing.
	 *
	 * @param array $atts
	 * @return string
	 */
	private static function missing_notice( $atts ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		$user_id = self::resolve_user( $atts );
		$user    = $user_id ? get_userdata( $user_id ) : false;

		if ( $user ) {
			$message = sprintf(
				/* translators: 1: user ID, 2: user display name. */
				__( 'Author Bio: no published author profile is linked to user #%1$d (%2$s).', 'author-bio' ),
				$user_id,
				$user->display_name
			);
		} else {
			$message = __( 'Author Bio: no author could be resolved for this page.', 'author-bio' );
		}

		return '<p class="abio-missing">' . esc_html( $message ) . '</p>';
	}
}
