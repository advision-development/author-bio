<?php

defined( 'ABSPATH' ) || exit;

class ABIO_Post_Type {

	const SLUG = 'author_profile';

	/**
	 * Register the Author Profile post type. Data-only: no permalink, no archive.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'Authors', 'author-bio' ),
			'singular_name'      => __( 'Author Profile', 'author-bio' ),
			'add_new'            => __( 'Add New', 'author-bio' ),
			'add_new_item'       => __( 'Add New Author Profile', 'author-bio' ),
			'edit_item'          => __( 'Edit Author Profile', 'author-bio' ),
			'new_item'           => __( 'New Author Profile', 'author-bio' ),
			'view_item'          => __( 'View Author Profile', 'author-bio' ),
			'search_items'       => __( 'Search Author Profiles', 'author-bio' ),
			'not_found'          => __( 'No author profiles found', 'author-bio' ),
			'not_found_in_trash' => __( 'No author profiles in trash', 'author-bio' ),
			'menu_name'          => __( 'Authors', 'author-bio' ),
		);

		register_post_type(
			self::SLUG,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'hierarchical'        => false,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-id-alt',
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
			)
		);
	}

	/**
	 * Find the profile linked to a WordPress user.
	 *
	 * Lowest post ID wins when more than one profile claims the same user, so
	 * rendering stays deterministic while the duplicate is being sorted out.
	 *
	 * @param int $user_id
	 * @return int Profile post ID, or 0 when there is none.
	 */
	public static function find_by_user( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return 0;
		}

		$found = get_posts(
			array(
				'post_type'        => self::SLUG,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array(
					array(
						'key'   => ABIO_Fields::meta_key( 'user' ),
						'value' => $user_id,
					),
				),
			)
		);

		return empty( $found ) ? 0 : (int) $found[0];
	}

	/**
	 * Add a Linked user column to the profile list table.
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;
		unset( $columns['date'] );

		$columns['abio_user'] = __( 'Linked user', 'author-bio' );

		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * Render the Linked user column.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public static function column( $column, $post_id ) {
		if ( 'abio_user' !== $column ) {
			return;
		}

		$user_id = (int) get_post_meta( $post_id, ABIO_Fields::meta_key( 'user' ), true );

		if ( ! $user_id ) {
			echo '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No linked user', 'author-bio' ) . '</span>';
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			esc_html_e( 'Missing user', 'author-bio' );
			return;
		}

		printf(
			'<a href="%s">%s</a>',
			esc_url( get_author_posts_url( $user_id ) ),
			esc_html( $user->display_name )
		);
	}
}
