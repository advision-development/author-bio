<?php

defined( 'ABSPATH' ) || exit;

/**
 * Listing several profiles at once.
 *
 * ABIO_Profile answers questions about one author. This answers them about all
 * of them, and is the single query path behind both the author index and the
 * "Other authors" block every template carries.
 */
class ABIO_Directory {

	/**
	 * Published Author Profiles, as rows a listing can render directly.
	 *
	 * Rows carry only what a listing needs, and deliberately do not run the
	 * full profile build: that resolves "contributing since" with a query per
	 * author, which is cheap for one page and needless for a directory.
	 *
	 * @param array $args exclude (post IDs), limit (0 for all),
	 *                    orderby ('name'|'posts'|'recent'), order ('asc'|'desc'),
	 *                    stats (include the published count and first year),
	 *                    users (extra WordPress user IDs to list even though
	 *                    they have no Author Profile),
	 *                    profiles (false to list only the given users).
	 * @return array Rows of name, kicker, role, short, url, portrait, posts, since.
	 */
	public static function authors( $args = array() ) {
		$args = array_merge(
			array(
				'exclude' => array(),
				'limit'   => 0,
				'orderby' => 'name',
				'order'   => 'asc',
				'stats'   => true,
				'users'   => array(),
				'profiles' => true,
			),
			$args
		);

		$ids = empty( $args['profiles'] ) ? array() : get_posts(
			array(
				'post_type'        => ABIO_Post_Type::SLUG,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'exclude'          => array_map( 'absint', (array) $args['exclude'] ),
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		$rows = array();

		// Ordering by volume needs the figures even when they are not shown.
		$want_stats = ! empty( $args['stats'] ) || 'posts' === $args['orderby'];

		foreach ( $ids as $id ) {
			$user_id = (int) get_post_meta( $id, ABIO_Fields::meta_key( 'user' ), true );
			$name    = (string) get_post_meta( $id, ABIO_Fields::meta_key( 'name' ), true );

			if ( '' === $name && $user_id ) {
				$name = (string) get_the_author_meta( 'display_name', $user_id );
			}

			if ( '' === trim( $name ) ) {
				// Nothing to label the row with, and a nameless entry in an
				// index is worse than a shorter index.
				continue;
			}

			$portrait = (int) get_post_meta( $id, ABIO_Fields::meta_key( 'portrait' ), true );

			// The catch-all's reasoning applies here too: an author with no
			// uploaded portrait can still be shown by their avatar, where the
			// site has avatars switched on.
			if ( ! $portrait && $user_id && get_option( 'show_avatars' ) ) {
				$avatar = get_avatar_url( $user_id, array( 'size' => 300 ) );

				if ( $avatar ) {
					$portrait = $avatar;
				}
			}

			$kicker = (string) get_post_meta( $id, ABIO_Fields::meta_key( 'kicker' ), true );

			$stats = $want_stats && $user_id
				? ABIO_Stats::byline_summary( $user_id, ABIO_Articles::post_types() )
				: array( 'posts' => 0, 'since' => '' );

			$rows[] = array(
				'id'       => $id,
				'user'     => $user_id,
				'name'     => $name,
				// Same default as a single profile page, so a person's label
				// does not change between the index and their own page.
				'kicker'   => '' === trim( $kicker ) ? __( 'Author', 'author-bio' ) : $kicker,
				'role'     => (string) get_post_meta( $id, ABIO_Fields::meta_key( 'role' ), true ),
				'short'    => (string) get_post_meta( $id, ABIO_Fields::meta_key( 'short' ), true ),
				'url'      => $user_id ? get_author_posts_url( $user_id ) : '',
				'portrait' => $portrait,
				'posts'    => $stats['posts'],
				'since'    => $stats['since'],
			);
		}

		// Authors named explicitly who have no profile of their own. Anyone
		// already covered by a profile above is skipped: that record is the
		// richer one, and listing a person twice is worse than either.
		$listed = wp_list_pluck( $rows, 'user' );

		foreach ( (array) $args['users'] as $user_id ) {
			$user_id = absint( $user_id );

			if ( ! $user_id || in_array( $user_id, $listed, true ) ) {
				continue;
			}

			$row = self::user_row( $user_id, $want_stats );

			if ( $row ) {
				$rows[]   = $row;
				$listed[] = $user_id;
			}
		}

		$rows = self::sort( $rows, $args['orderby'], $args['order'] );
		$limit = absint( $args['limit'] );

		return $limit ? array_slice( $rows, 0, $limit ) : $rows;
	}

	/**
	 * A row for a WordPress user with no Author Profile.
	 *
	 * The same reasoning as the catch-all page: only what WordPress already
	 * holds. There is no role or summary because WordPress has no such field,
	 * so those cells stay empty rather than being filled with a guess.
	 *
	 * @param int  $user_id
	 * @param bool $want_stats
	 * @return array|false
	 */
	private static function user_row( $user_id, $want_stats ) {
		$user = get_userdata( $user_id );

		if ( ! $user || '' === trim( (string) $user->display_name ) ) {
			return false;
		}

		$portrait = 0;

		if ( get_option( 'show_avatars' ) ) {
			$avatar = get_avatar_url( $user_id, array( 'size' => 300 ) );

			if ( $avatar ) {
				$portrait = $avatar;
			}
		}

		$stats = $want_stats
			? ABIO_Stats::byline_summary( $user_id, ABIO_Articles::post_types() )
			: array( 'posts' => 0, 'since' => '' );

		return array(
			'id'       => 0,
			'user'     => $user_id,
			'name'     => $user->display_name,
			'kicker'   => __( 'Author', 'author-bio' ),
			'role'     => '',
			'short'    => '',
			'url'      => get_author_posts_url( $user_id ),
			'portrait' => $portrait,
			'posts'    => $stats['posts'],
			'since'    => $stats['since'],
		);
	}

	/**
	 * Order the rows.
	 *
	 * Sorted in PHP rather than SQL because the values that matter — the
	 * resolved display name, which may come from the linked user, and the
	 * article count, which is counted per author — are not columns to sort on.
	 *
	 * @param array  $rows
	 * @param string $orderby
	 * @param string $order
	 * @return array
	 */
	private static function sort( $rows, $orderby, $order ) {
		switch ( $orderby ) {
			case 'posts':
				usort(
					$rows,
					static function ( $a, $b ) {
						// Most published first on a tie-free comparison, then
						// by name so the order is stable rather than arbitrary.
						if ( $a['posts'] === $b['posts'] ) {
							return strcasecmp( $a['name'], $b['name'] );
						}

						return $b['posts'] < $a['posts'] ? -1 : 1;
					}
				);
				break;

			case 'recent':
				usort(
					$rows,
					static function ( $a, $b ) {
						// Users listed without a profile have no profile date to
						// sort on, so they sit after the ones that do rather
						// than sorting as if they were the oldest.
						if ( ! $a['id'] || ! $b['id'] ) {
							if ( $a['id'] === $b['id'] ) {
								return strcasecmp( $a['name'], $b['name'] );
							}

							return $a['id'] ? -1 : 1;
						}

						return $b['id'] < $a['id'] ? -1 : 1;
					}
				);
				break;

			case 'name':
			default:
				usort(
					$rows,
					static function ( $a, $b ) {
						return strcasecmp( $a['name'], $b['name'] );
					}
				);
				break;
		}

		return 'desc' === strtolower( (string) $order ) ? array_reverse( $rows ) : $rows;
	}
}
