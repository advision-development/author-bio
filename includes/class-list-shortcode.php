<?php

defined( 'ABSPATH' ) || exit;

/**
 * [author_bio_list] — every saved Author Profile as a vertical index.
 *
 * Renders inside the same root element as a single profile page, under the
 * selected template's modifier class, so it inherits that template's palette,
 * typeface, corner language and label treatment from the tokens rather than
 * from a second set of styles.
 */
class ABIO_Shortcode_List {

	const TAG = 'author_bio_list';

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
				'template' => ABIO_Settings::get( 'default_template', '1' ),
				'count'    => '0',
				'orderby'  => 'name',
				'order'    => 'asc',
				'counts'   => '1',
				'heading'  => '',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		$orderby = in_array( $atts['orderby'], array( 'name', 'posts', 'recent' ), true )
			? $atts['orderby']
			: 'name';

		$authors = ABIO_Directory::authors(
			array(
				'limit'   => absint( $atts['count'] ),
				'orderby' => $orderby,
				'order'   => 'desc' === strtolower( (string) $atts['order'] ) ? 'desc' : 'asc',
				'counts'  => '0' !== (string) $atts['counts'],
			)
		);

		if ( ! $authors ) {
			return self::empty_notice();
		}

		$number = ABIO_Shortcode::template_number( $atts['template'] );
		$file   = ABIO_PATH . 'templates/list.php';

		if ( ! file_exists( $file ) ) {
			return '';
		}

		// The same shape a single profile gets, so the list is styled by the
		// tokens already on the root rather than by anything of its own.
		$d = array(
			'authors' => $authors,
			'heading' => (string) $atts['heading'],
			'counts'  => '0' !== (string) $atts['counts'],
			'site'    => array(
				'name'       => ABIO_Settings::get( 'site_name', get_bloginfo( 'name' ) ),
				'authorsUrl' => ABIO_Settings::get( 'authors_url', '' ),
			),
		);

		ABIO_Assets::enqueue();

		ob_start();

		$vars  = ABIO_Palette::css_vars();
		$stack = ABIO_Settings::font_stack();

		if ( '' !== $stack ) {
			$vars .= ';--abio-font:' . $stack;
		}

		printf(
			'<div class="abio abio--t%d abio-list-root" style="%s">',
			$number,
			esc_attr( $vars )
		);

		include $file;

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Nothing to list. Silent for visitors; diagnosable for editors.
	 *
	 * @return string
	 */
	private static function empty_notice() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return '<p class="abio-missing">'
			. esc_html__( 'Author Bio: no published author profiles to list yet.', 'author-bio' )
			. '</p>';
	}
}
