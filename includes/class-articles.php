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
				'summary'  => self::summary( $post ),
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

			// Publicly viewable, not merely registered — otherwise
			// [author_bio post_type="..."] can be pointed at a private or
			// internal-use CPT to list titles and permalinks from it.
			if ( $slug && is_post_type_viewable( $slug ) ) {
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
	 * The teaser shown under an article title.
	 *
	 * A hand-written excerpt is used as-is. Otherwise the summary is built from
	 * the raw post content rather than from get_the_excerpt(), because
	 * generating an excerpt runs the_content filters — and page builders hook
	 * that filter to inject their widget CSS, which then arrives in the excerpt
	 * as prose. Elementor's stylesheet turning up mid-sentence on a live site
	 * is what prompted this.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	private static function summary( $post ) {
		$manual = trim( (string) $post->post_excerpt );

		if ( '' !== $manual ) {
			return wptexturize( wp_strip_all_tags( $manual ) );
		}

		$content = strip_shortcodes( (string) $post->post_content );

		if ( function_exists( 'excerpt_remove_blocks' ) ) {
			$content = excerpt_remove_blocks( $content );
		}

		// Style and script blocks, contents included.
		$content = preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', ' ', $content );
		$content = wp_strip_all_tags( $content );

		// Builder CSS can survive tag stripping as literal text, so clear
		// comment blocks and anything still shaped like a rule.
		$content = preg_replace( '#/\*.*?\*/#s', ' ', $content );
		$content = preg_replace( '#[.\#@][-\w][^{}]{0,120}\{[^{}]*\}#s', ' ', $content );

		$content = preg_replace( '#\s+#u', ' ', (string) $content );

		/** This filter is documented in wp-includes/formatting.php */
		$words = (int) apply_filters( 'excerpt_length', 55 );

		// Skipping the_content also skips wptexturize, which is the one part of
		// that pipeline a teaser actually wants: straight quotes and hyphens
		// would otherwise read as a downgrade beside the rest of the page.
		return wptexturize( wp_trim_words( trim( (string) $content ), $words, '…' ) );
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
