<?php

defined( 'ABSPATH' ) || exit;

/**
 * The single source of truth for profile fields.
 *
 * The admin UI, the save path, and the profile reader are all generated from
 * this schema. Adding a field means adding one entry here.
 */
class ABIO_Fields {

	const PREFIX = '_abio_';

	/**
	 * Metabox groups, in the order they appear on the edit screen.
	 *
	 * @return array
	 */
	public static function groups() {
		return array(
			array(
				'id'     => 'identity',
				'title'  => __( 'Identity', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'user',
						'label' => __( 'Linked WordPress user', 'author-bio' ),
						'type'  => 'user',
						'help'  => __( 'Required. Drives the articles list, the automatic stats, and which author archive this profile answers to.', 'author-bio' ),
					),
					array(
						'key'         => 'kicker',
						'label'       => __( 'Kicker', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'Author', 'author-bio' ),
						'help'        => __( 'The small label above the name. "Author", "Editor", "Analyst".', 'author-bio' ),
					),
					array(
						'key'   => 'name',
						'label' => __( 'Display name', 'author-bio' ),
						'type'  => 'text',
						'help'  => __( 'Leave blank to use the linked user\'s display name.', 'author-bio' ),
					),
					array(
						'key'         => 'role',
						'label'       => __( 'Role', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'Job title as it should appear under the name', 'author-bio' ),
						'help'        => __( 'The role this person actually holds. Not a description of what they write about — that is what Areas of focus is for.', 'author-bio' ),
					),
					array(
						'key'         => 'location',
						'label'       => __( 'Location', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'City, Country', 'author-bio' ),
					),
					array(
						'key'         => 'since',
						'label'       => __( 'Contributing since', 'author-bio' ),
						'type'        => 'text',
						'placeholder' => __( 'YYYY', 'author-bio' ),
						'help'        => __( 'Leave blank to use the year of the linked user\'s first published post.', 'author-bio' ),
					),
					array(
						'key'   => 'portrait',
						'label' => __( 'Portrait', 'author-bio' ),
						'type'  => 'media',
						'help'  => __( 'Square crop. Used by templates 1, 2, 4, 6, 7 and 9.', 'author-bio' ),
					),
				),
			),
			array(
				'id'     => 'bio',
				'title'  => __( 'Biography', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'short',
						'label' => __( 'Short line', 'author-bio' ),
						'type'  => 'text',
						'help'  => __( 'One sentence on what this person covers, in their own words where possible. Used by templates 8 and 10.', 'author-bio' ),
					),
					array(
						'key'   => 'bio',
						'label' => __( 'Biography', 'author-bio' ),
						'type'  => 'textarea',
						'rows'  => 8,
						'help'  => __( 'Two or three short paragraphs. Links and bold are allowed. Blank lines become paragraphs.', 'author-bio' ),
					),
					array(
						'key'       => 'badges',
						'label'     => __( 'Badges', 'author-bio' ),
						'type'      => 'repeater',
						'help'      => __( 'Short trust signals shown beside the name. A reader takes these as claims the site stands behind, so add one only if you could evidence it on request. Two or three is plenty.', 'author-bio' ),
						'subfields' => array(
							'text' => array(
								'label'       => __( 'Badge', 'author-bio' ),
								'type'        => 'text',
								'placeholder' => __( 'A claim you can substantiate', 'author-bio' ),
							),
						),
					),
					array(
						'key'       => 'credentials',
						'label'     => __( 'Credentials', 'author-bio' ),
						'type'      => 'repeater',
						'help'      => __( 'Qualifications, licences, memberships or track record you can substantiate. Leave this empty rather than approximating — the page reads better with three sections than with a credential nobody can stand behind.', 'author-bio' ),
						'subfields' => array(
							'text' => array(
								'label'       => __( 'Credential', 'author-bio' ),
								'type'        => 'text',
								'placeholder' => __( 'Qualification, licence, membership or verifiable track record', 'author-bio' ),
							),
						),
					),
				),
			),
			array(
				'id'     => 'stats',
				'title'  => __( 'Stat tiles', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'stats',
						'label' => __( 'Stat tiles', 'author-bio' ),
						'type'  => 'stats',
						'help'  => __( 'Four tiles. Automatic tiles recalculate on every page load; manual tiles show exactly what you type.', 'author-bio' ),
					),
				),
			),
			array(
				'id'     => 'gallery',
				'title'  => __( 'Gallery', 'author-bio' ),
				'fields' => array(
					array(
						'key'   => 'gallery_heading',
						'label' => __( 'Gallery heading', 'author-bio' ),
						'type'  => 'text',
					),
					array(
						'key'   => 'gallery_note',
						'label' => __( 'Gallery note', 'author-bio' ),
						'type'  => 'text',
					),
					array(
						'key'       => 'gallery_items',
						'label'     => __( 'Gallery items', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'image'   => array(
								'label' => __( 'Image', 'author-bio' ),
								'type'  => 'media',
							),
							'label'   => array(
								'label' => __( 'Label', 'author-bio' ),
								'type'  => 'text',
							),
							'caption' => array(
								'label' => __( 'Caption', 'author-bio' ),
								'type'  => 'text',
							),
							'short'   => array(
								'label' => __( 'Short label', 'author-bio' ),
								'type'  => 'text',
							),
						),
					),
				),
			),
			array(
				'id'     => 'focus',
				'title'  => __( 'Areas of focus', 'author-bio' ),
				'fields' => array(
					array(
						'key'       => 'focus',
						'label'     => __( 'Areas of focus', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'title' => array(
								'label' => __( 'Title', 'author-bio' ),
								'type'  => 'text',
							),
							'body'  => array(
								'label' => __( 'Body', 'author-bio' ),
								'type'  => 'textarea',
							),
						),
					),
				),
			),
			array(
				'id'     => 'experience',
				'title'  => __( 'Experience', 'author-bio' ),
				'fields' => array(
					array(
						'key'       => 'experience',
						'label'     => __( 'Experience', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'years' => array(
								'label' => __( 'Years', 'author-bio' ),
								'type'  => 'text',
							),
							'title' => array(
								'label' => __( 'Title', 'author-bio' ),
								'type'  => 'text',
							),
							'org'   => array(
								'label' => __( 'Organisation', 'author-bio' ),
								'type'  => 'text',
							),
							'body'  => array(
								'label' => __( 'Body', 'author-bio' ),
								'type'  => 'textarea',
							),
						),
					),
				),
			),
			array(
				'id'     => 'follows',
				'title'  => __( 'Follows', 'author-bio' ),
				'fields' => array(
					array(
						'key'       => 'follows',
						'label'     => __( 'Follows', 'author-bio' ),
						'type'      => 'repeater',
						'subfields' => array(
							'handle' => array(
								'label' => __( 'Handle', 'author-bio' ),
								'type'  => 'text',
							),
							'url'    => array(
								'label' => __( 'URL', 'author-bio' ),
								'type'  => 'url',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Every field, flattened and keyed by field key.
	 *
	 * @return array
	 */
	public static function fields() {
		$flat = array();

		foreach ( self::groups() as $group ) {
			foreach ( $group['fields'] as $field ) {
				$flat[ $field['key'] ] = $field;
			}
		}

		return $flat;
	}

	/**
	 * Meta key for a schema key.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function meta_key( $key ) {
		return self::PREFIX . $key;
	}

	/**
	 * Sanitize a submitted value according to its field type.
	 *
	 * @param array $field Field definition from the schema.
	 * @param mixed $value Raw submitted value.
	 * @return mixed
	 */
	public static function sanitize( $field, $value ) {
		switch ( $field['type'] ) {
			case 'user':
			case 'media':
				return absint( $value );

			case 'textarea':
				return wp_kses_post( self::scalar( wp_unslash( $value ) ) );

			case 'url':
				return esc_url_raw( self::scalar( wp_unslash( $value ) ) );

			case 'repeater':
				return self::sanitize_repeater( $field, $value );

			case 'stats':
				return self::sanitize_stats( $value );

			case 'text':
			default:
				return sanitize_text_field( self::scalar( wp_unslash( $value ) ) );
		}
	}

	/**
	 * WordPress hands sanitizers whatever was posted, which may be an array
	 * (e.g. a text input duplicated with `[]` in its name, or a tampered
	 * request). Every string sanitizer above would misbehave or fatal on one
	 * — esc_url_raw() throws, wp_kses_post() silently returns an array that
	 * then gets serialized into post meta — so non-scalar input collapses to
	 * an empty string first. Mirrors ABIO_Settings::scalar().
	 *
	 * @param mixed $value
	 * @return string
	 */
	private static function scalar( $value ) {
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Sanitize a repeater: an array of rows, each row keyed by subfield.
	 *
	 * Rows where every subfield is empty are dropped, so an untouched blank row
	 * never reaches the front end.
	 *
	 * @param array $field
	 * @param mixed $value
	 * @return array
	 */
	private static function sanitize_repeater( $field, $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$rows = array();

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			$empty = true;

			foreach ( $field['subfields'] as $sub_key => $sub ) {
				$raw           = isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : '';
				$clean[ $sub_key ] = self::sanitize( $sub, $raw );

				if ( '' !== $clean[ $sub_key ] && 0 !== $clean[ $sub_key ] ) {
					$empty = false;
				}
			}

			if ( ! $empty ) {
				$rows[] = $clean;
			}
		}

		return $rows;
	}

	/**
	 * Sanitize the four stat tiles.
	 *
	 * Each tile: mode (auto_bylines|auto_since|auto_type_count|manual),
	 * post_type (for auto_type_count), value (for manual), label.
	 *
	 * @param mixed $value
	 * @return array
	 */
	private static function sanitize_stats( $value ) {
		$modes = array( 'auto_bylines', 'auto_since', 'auto_type_count', 'manual', 'off' );
		$tiles = array();

		for ( $i = 0; $i < 4; $i++ ) {
			$row  = isset( $value[ $i ] ) && is_array( $value[ $i ] ) ? $value[ $i ] : array();
			$mode = isset( $row['mode'] ) ? sanitize_key( self::scalar( $row['mode'] ) ) : 'off';

			if ( ! in_array( $mode, $modes, true ) ) {
				$mode = 'off';
			}

			$tiles[] = array(
				'mode'      => $mode,
				'post_type' => isset( $row['post_type'] ) ? sanitize_key( self::scalar( $row['post_type'] ) ) : '',
				'value'     => isset( $row['value'] ) ? sanitize_text_field( self::scalar( wp_unslash( $row['value'] ) ) ) : '',
				'label'     => isset( $row['label'] ) ? sanitize_text_field( self::scalar( wp_unslash( $row['label'] ) ) ) : '',
			);
		}

		return $tiles;
	}
}
