<?php

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves the Author Profile edit screen from the field schema.
 */
class ABIO_Metaboxes {

	const NONCE = 'abio_profile_nonce';

	/**
	 * One metabox per schema group.
	 */
	public static function register() {
		foreach ( ABIO_Fields::groups() as $group ) {
			add_meta_box(
				'abio_' . $group['id'],
				$group['title'],
				array( __CLASS__, 'render' ),
				ABIO_Post_Type::SLUG,
				'normal',
				'default',
				array( 'group' => $group )
			);
		}
	}

	/**
	 * Render one group's fields.
	 *
	 * @param WP_Post $post
	 * @param array   $box
	 */
	public static function render( $post, $box ) {
		$group = $box['args']['group'];

		if ( 'identity' === $group['id'] ) {
			wp_nonce_field( self::NONCE, self::NONCE );
			self::duplicate_warning( $post );
		}

		echo '<div class="abio-admin">';

		foreach ( $group['fields'] as $field ) {
			$value = get_post_meta( $post->ID, ABIO_Fields::meta_key( $field['key'] ), true );
			self::render_field( $field, $value );
		}

		echo '</div>';
	}

	/**
	 * Warn when another profile already claims this profile's linked user.
	 *
	 * @param WP_Post $post
	 */
	private static function duplicate_warning( $post ) {
		$user_id = (int) get_post_meta( $post->ID, ABIO_Fields::meta_key( 'user' ), true );

		if ( ! $user_id ) {
			return;
		}

		$winner = ABIO_Post_Type::find_by_user( $user_id );

		if ( $winner && $winner !== $post->ID ) {
			printf(
				'<div class="notice notice-warning inline"><p>%s</p></div>',
				sprintf(
					/* translators: %s: link to the profile that wins. */
					esc_html__( 'Another profile is already linked to this user and takes precedence: %s', 'author-bio' ),
					'<a href="' . esc_url( get_edit_post_link( $winner ) ) . '">' . esc_html( get_the_title( $winner ) ) . '</a>'
				)
			);
		}
	}

	/**
	 * Render one field, dispatching on type.
	 *
	 * @param array $field
	 * @param mixed $value
	 */
	private static function render_field( $field, $value ) {
		$name = 'abio[' . $field['key'] . ']';
		$id   = 'abio-' . $field['key'];

		echo '<div class="abio-field abio-field--' . esc_attr( $field['type'] ) . '">';
		echo '<label class="abio-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';

		// Repeaters and the stat grid put their control first and their rows
		// after it, so trailing help would sit below an Add button and be read
		// only after the field had been filled. Guidance belongs before it.
		$help_first = isset( $field['help'] ) && in_array( $field['type'], array( 'repeater', 'stats' ), true );

		if ( $help_first ) {
			echo '<p class="description abio-field__help">' . esc_html( $field['help'] ) . '</p>';
		}

		switch ( $field['type'] ) {
			case 'user':
				if ( current_user_can( 'list_users' ) ) {
					wp_dropdown_users(
						array(
							'name'              => $name,
							'id'                => $id,
							'selected'          => (int) $value,
							'show_option_none'  => __( '— none —', 'author-bio' ),
							'option_none_value' => 0,
							'capability'        => array( 'edit_posts' ),
						)
					);
				} else {
					self::render_restricted_user_field( $name, $id, (int) $value );
				}
				break;

			case 'media':
				self::render_media( $name, $id, (int) $value );
				break;

			case 'textarea':
				$rows = isset( $field['rows'] ) ? (int) $field['rows'] : 4;
				echo '<textarea class="large-text" rows="' . esc_attr( $rows ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">' . esc_textarea( $value ) . '</textarea>';
				break;

			case 'repeater':
				self::render_repeater( $field, is_array( $value ) ? $value : array() );
				break;

			case 'stats':
				self::render_stats( is_array( $value ) ? $value : array() );
				break;

			case 'url':
			case 'text':
			default:
				$type        = 'url' === $field['type'] ? 'url' : 'text';
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
				echo '<input type="' . esc_attr( $type ) . '" class="large-text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
				break;
		}

		if ( isset( $field['help'] ) && ! $help_first ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * A restricted stand-in for the linked-user dropdown, shown to anyone who
	 * cannot list_users. Enumerating every account on the site is itself a
	 * capability, independent of whether the CPT lets them save a profile, so
	 * this offers only the current user plus whatever is already saved
	 * (unchanged, so simply re-saving the form never silently clears a
	 * validly-set link).
	 *
	 * @param string $name
	 * @param string $id
	 * @param int    $value
	 */
	private static function render_restricted_user_field( $name, $id, $value ) {
		$current = wp_get_current_user();
		$choices = array( 0 => __( '— none —', 'author-bio' ) );

		if ( $current && $current->ID ) {
			$choices[ $current->ID ] = $current->display_name;
		}

		if ( $value && ! isset( $choices[ $value ] ) ) {
			$existing = get_userdata( $value );

			if ( $existing ) {
				$choices[ $value ] = $existing->display_name;
			}
		}

		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '">';

		foreach ( $choices as $choice_id => $label ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $choice_id,
				selected( $value, $choice_id, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
		echo '<p class="description">' . esc_html__( 'You do not have permission to browse every user on this site, so only your own account can be linked here.', 'author-bio' ) . '</p>';
	}

	/**
	 * A media picker: hidden attachment ID, thumbnail, choose/remove buttons.
	 *
	 * @param string $name
	 * @param string $id
	 * @param int    $attachment_id
	 */
	private static function render_media( $name, $id, $attachment_id ) {
		$thumb = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';

		echo '<div class="abio-media" data-abio-media>';
		echo '<div class="abio-media__preview" data-abio-media-preview>' . $thumb . '</div>';
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $attachment_id ) . '" data-abio-media-input />';
		echo '<button type="button" class="button" data-abio-media-choose>' . esc_html__( 'Choose image', 'author-bio' ) . '</button> ';
		echo '<button type="button" class="button-link abio-media__remove" data-abio-media-remove>' . esc_html__( 'Remove', 'author-bio' ) . '</button>';
		echo '</div>';
	}

	/**
	 * A repeater: existing rows, a hidden <template> row, and an Add button.
	 *
	 * @param array $field
	 * @param array $rows
	 */
	private static function render_repeater( $field, $rows ) {
		echo '<div class="abio-repeater" data-abio-repeater data-key="' . esc_attr( $field['key'] ) . '">';
		echo '<div class="abio-repeater__rows" data-abio-repeater-rows>';

		$index = 0;

		foreach ( $rows as $row ) {
			self::render_repeater_row( $field, $row, (string) $index );
			$index++;
		}

		echo '</div>';

		echo '<template data-abio-repeater-template>';
		self::render_repeater_row( $field, array(), '__i__' );
		echo '</template>';

		echo '<button type="button" class="button" data-abio-repeater-add>' . esc_html__( 'Add row', 'author-bio' ) . '</button>';
		echo '</div>';
	}

	/**
	 * One repeater row.
	 *
	 * @param array  $field
	 * @param array  $row
	 * @param string $index Numeric index, or the literal __i__ placeholder.
	 */
	private static function render_repeater_row( $field, $row, $index ) {
		echo '<div class="abio-row" data-abio-repeater-row>';
		echo '<span class="abio-row__handle dashicons dashicons-menu" data-abio-repeater-handle></span>';
		echo '<div class="abio-row__fields">';

		foreach ( $field['subfields'] as $sub_key => $sub ) {
			$name  = 'abio[' . $field['key'] . '][' . $index . '][' . $sub_key . ']';
			$value = isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : '';

			echo '<div class="abio-row__field">';
			echo '<span class="abio-row__label">' . esc_html( $sub['label'] ) . '</span>';

			if ( 'textarea' === $sub['type'] ) {
				echo '<textarea class="large-text" rows="3" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			} elseif ( 'media' === $sub['type'] ) {
				self::render_media( $name, '', (int) $value );
			} else {
				$type = 'url' === $sub['type'] ? 'url' : 'text';
				echo '<input type="' . esc_attr( $type ) . '" class="large-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
			}

			echo '</div>';
		}

		echo '</div>';
		echo '<button type="button" class="button-link abio-row__remove" data-abio-repeater-remove>' . esc_html__( 'Remove', 'author-bio' ) . '</button>';
		echo '</div>';
	}

	/**
	 * The four stat tiles.
	 *
	 * @param array $tiles
	 */
	private static function render_stats( $tiles ) {
		$modes = array(
			'off'             => __( '— not shown —', 'author-bio' ),
			'auto_bylines'    => __( 'Automatic: pieces bylined', 'author-bio' ),
			'auto_since'      => __( 'Automatic: contributing since', 'author-bio' ),
			'auto_type_count' => __( 'Automatic: count in one post type', 'author-bio' ),
			'manual'          => __( 'Manual value', 'author-bio' ),
		);

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		echo '<div class="abio-stats">';

		for ( $i = 0; $i < 4; $i++ ) {
			$tile = isset( $tiles[ $i ] ) ? $tiles[ $i ] : array();
			$mode = isset( $tile['mode'] ) ? $tile['mode'] : 'off';
			$base = 'abio[stats][' . $i . ']';

			echo '<div class="abio-stat" data-abio-stat>';
			printf( '<span class="abio-stat__num">%d</span>', $i + 1 );

			echo '<select name="' . esc_attr( $base . '[mode]' ) . '" data-abio-stat-mode>';
			foreach ( $modes as $key => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $key ),
					selected( $mode, $key, false ),
					esc_html( $label )
				);
			}
			echo '</select>';

			echo '<select name="' . esc_attr( $base . '[post_type]' ) . '" data-abio-stat-post-type>';
			foreach ( $post_types as $pt ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $pt->name ),
					selected( isset( $tile['post_type'] ) ? $tile['post_type'] : '', $pt->name, false ),
					esc_html( $pt->labels->singular_name )
				);
			}
			echo '</select>';

			printf(
				'<input type="text" name="%s" value="%s" placeholder="%s" data-abio-stat-value />',
				esc_attr( $base . '[value]' ),
				esc_attr( isset( $tile['value'] ) ? $tile['value'] : '' ),
				esc_attr__( 'Value', 'author-bio' )
			);

			printf(
				'<input type="text" name="%s" value="%s" placeholder="%s" />',
				esc_attr( $base . '[label]' ),
				esc_attr( isset( $tile['label'] ) ? $tile['label'] : '' ),
				esc_attr__( 'Label', 'author-bio' )
			);

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Save every schema field.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ABIO_Post_Type::SLUG !== get_post_type( $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each value is sanitized by ABIO_Fields::sanitize().
		$submitted = isset( $_POST['abio'] ) && is_array( $_POST['abio'] ) ? $_POST['abio'] : array();

		foreach ( ABIO_Fields::fields() as $key => $field ) {
			$raw   = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
			$clean = ABIO_Fields::sanitize( $field, $raw );

			// Defense in depth: the dropdown already restricts *choices* for
			// users who can't list_users, but the save path must not trust
			// the form to have been rendered honestly — a crafted request
			// could submit any user ID directly.
			if ( 'user' === $field['type'] && ! self::may_assign_user( $clean ) ) {
				continue;
			}

			update_post_meta( $post_id, ABIO_Fields::meta_key( $key ), $clean );
		}
	}

	/**
	 * Whether the current user is allowed to link a profile to $user_id.
	 * Users who can list_users may assign anyone eligible for the dropdown
	 * (matching its 'capability' filter); everyone else may only assign
	 * themselves. Clearing the link (0) is always allowed.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	private static function may_assign_user( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return true;
		}

		if ( current_user_can( 'list_users' ) ) {
			return user_can( $user_id, 'edit_posts' );
		}

		return get_current_user_id() === $user_id;
	}

	/**
	 * Admin CSS and JS, on the profile edit screen only.
	 *
	 * @param string $hook
	 */
	public static function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ABIO_Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style( 'abio-admin', ABIO_URL . 'assets/admin/admin.css', array(), ABIO_VERSION );
		wp_enqueue_script( 'abio-admin', ABIO_URL . 'assets/admin/admin.js', array(), ABIO_VERSION, true );
	}
}
