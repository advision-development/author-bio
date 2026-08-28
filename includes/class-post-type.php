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
						'key'   => '_abio_user',
						'value' => $user_id,
					),
				),
			)
		);

		return empty( $found ) ? 0 : (int) $found[0];
	}
}
