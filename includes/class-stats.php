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
	/**
	 * Both derived byline figures in one query.
	 *
	 * An index needs the published count and the first year for every author it
	 * lists, and asking separately is two queries per person. Ordering ascending
	 * by date and taking one row answers both at once: found_posts is the total,
	 * and the single row returned is the earliest post.
	 *
	 * @param int          $user_id
	 * @param array|string $post_types
	 * @return array posts (int), since (four-digit year or '')
	 */
	public static function byline_summary( $user_id, $post_types ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array( 'posts' => 0, 'since' => '' );
		}

		$query = new WP_Query(
			array(
				'author'                 => $user_id,
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => 'date',
				'order'                  => 'ASC',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return array(
			'posts' => (int) $query->found_posts,
			'since' => empty( $query->posts ) ? '' : get_the_date( 'Y', $query->posts[0] ),
		);
	}

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
