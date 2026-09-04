<?php

defined( 'ABSPATH' ) || exit;

/**
 * [author_bio_list] — every saved Author Profile as a vertical index.
 *
 * Each of the ten templates has its own index view in templates/list-N.php,
 * mirroring the composition of templates/template-N.php. Tokens alone were not
 * enough: with one shared layout, several templates differed only in a corner
 * radius and read as identical, so changing `template` looked like it did
 * nothing. The trade is ten files to keep in step; the row contract they all
 * render — portrait, name, kicker, role, short line — is fixed by
 * ABIO_Directory and is the thing to hold constant when editing them.

 * Renders inside the same root element as a single profile page, under that
 * template's modifier class, so palette, typeface and label treatment still
 * come from the tokens rather than from a second set of styles.
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
				'heading'  => '',
				'users'    => '',
				'profiles' => '',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		$orderby = in_array( $atts['orderby'], array( 'name', 'posts', 'recent' ), true )
			? $atts['orderby']
			: 'name';

		// Authors → Settings → Author index supplies the defaults; a shortcode
		// attribute overrides them for one placement.
		$profiles = '' === (string) $atts['profiles']
			? (bool) ABIO_Settings::get( 'index_all_profiles', 1 )
			: '0' !== (string) $atts['profiles'];

		if ( '' === (string) $atts['users'] ) {
			$users = (array) ABIO_Settings::get( 'index_users', array() );
		} else {
			$users = array();

			foreach ( explode( ',', (string) $atts['users'] ) as $token ) {
				$id = ABIO_Shortcode::user_from_token( trim( $token ) );

				if ( $id ) {
					$users[] = $id;
				}
			}
		}

		$authors = ABIO_Directory::authors(
			array(
				'limit'    => absint( $atts['count'] ),
				'orderby'  => $orderby,
				'order'    => 'desc' === strtolower( (string) $atts['order'] ) ? 'desc' : 'asc',
				// Counted only when the order depends on it. No index view shows
				// the figure, and counting is a query per author.
				'counts'   => 'posts' === $orderby,
				'users'    => $users,
				'profiles' => $profiles,
			)
		);

		if ( ! $authors ) {
			return self::empty_notice();
		}

		$number = ABIO_Shortcode::template_number( $atts['template'] );
		$file   = ABIO_PATH . 'templates/list-' . $number . '.php';

		if ( ! file_exists( $file ) ) {
			return '';
		}

		// The same shape a single profile gets: every list view reads these
		// three keys and nothing else.
		$d = array(
			'authors' => $authors,
			'heading' => (string) $atts['heading'],
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

		echo ABIO_Schema::item_list( $authors, (string) $atts['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput

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
			. esc_html__( 'Author Bio: nothing to list. Either no Author Profiles are published, or Authors → Settings → Author index is set to list only selected authors and none are selected.', 'author-bio' )
			. '</p>';
	}
}
