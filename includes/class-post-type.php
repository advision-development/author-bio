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
				'capability_type'     => array( 'author_profile', 'author_profiles' ),
				'map_meta_cap'        => true,
				'capabilities'        => self::capabilities(),
			)
		);
	}

	/**
	 * A dedicated capability set, distinct from the generic 'post' caps the
	 * Author role already has. Publishing a blog post must not be enough to
	 * publish an author profile that can point at another user.
	 *
	 * @return array
	 */
	public static function capabilities() {
		return array(
			'edit_post'              => 'edit_author_profile',
			'read_post'              => 'read_author_profile',
			'delete_post'            => 'delete_author_profile',
			'edit_posts'             => 'edit_author_profiles',
			'edit_others_posts'      => 'edit_others_author_profiles',
			'publish_posts'          => 'publish_author_profiles',
			'read_private_posts'     => 'read_private_author_profiles',
			'delete_posts'           => 'delete_author_profiles',
			'delete_private_posts'   => 'delete_private_author_profiles',
			'delete_published_posts' => 'delete_published_author_profiles',
			'delete_others_posts'    => 'delete_others_author_profiles',
			'edit_private_posts'     => 'edit_private_author_profiles',
			'edit_published_posts'   => 'edit_published_author_profiles',
			'create_posts'           => 'edit_author_profiles',
		);
	}

	/**
	 * Grant the profile capabilities to the roles allowed to manage profiles.
	 * A custom capability_type is not inherited from anywhere, so without
	 * this, map_meta_cap => true would leave every role — including
	 * Administrator — unable to touch the post type. Authors are deliberately
	 * left out: they can publish posts, but not author profiles, which can
	 * link to and speak for another user.
	 *
	 * Safe to call repeatedly — add_cap() on a role that already has the
	 * capability is a no-op.
	 */
	public static function add_capabilities() {
		$caps = array_unique( array_values( self::capabilities() ) );

		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
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
