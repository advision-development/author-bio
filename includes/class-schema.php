<?php

defined( 'ABSPATH' ) || exit;

/**
 * schema.org JSON-LD for the pages this plugin renders.
 *
 * Two shapes. A single profile emits a ProfilePage whose mainEntity is the
 * Person, which is the markup Google documents for author and profile pages.
 * An index emits an ItemList of Person nodes, each carrying the same `@id` its
 * own page will claim, so the two graphs describe one person rather than two.
 *
 * Everything here is derived from fields an editor filled in. Nothing is
 * inferred, defaulted or padded: a key whose value does not resolve is dropped
 * rather than emitted empty, for the same reason the templates render less page
 * instead of a placeholder. Credentials in particular are claims the site
 * stands behind, so they are passed through verbatim or not at all.
 *
 * None of it is emitted unless Authors → Settings switches it on, and the
 * default is off. Yoast and Rank Math both already describe authors on an
 * author archive, and two Person graphs for one person is worse than none from
 * us — so this opts in rather than out. `abio_schema_enabled` still has the
 * last word in both directions, and receives the context, for a site that wants
 * the profile graph but not the index one.
 */
class ABIO_Schema {

	/**
	 * ProfilePage + Person for one profile.
	 *
	 * @param array        $d       The template data array.
	 * @param ABIO_Profile $profile Source profile, for its post dates.
	 * @return string A <script> element, or '' when suppressed or too thin.
	 */
	public static function profile_page( $d, $profile ) {
		if ( ! self::enabled( 'profile' ) ) {
			return '';
		}

		$person = self::person(
			isset( $d['author'] ) ? (array) $d['author'] : array(),
			isset( $d['site'] ) ? (array) $d['site'] : array(),
			array(
				'knowsAbout'    => self::focus_terms( isset( $d['focus'] ) ? (array) $d['focus'] : array() ),
				'hasCredential' => self::credentials( isset( $d['credentials'] ) ? (array) $d['credentials'] : array() ),
				'sameAs'        => self::same_as( isset( $d['follows'] ) ? (array) $d['follows'] : array() ),
			)
		);

		// A Person with nothing but a name is not worth a graph of its own.
		if ( empty( $person['name'] ) || count( $person ) < 3 ) {
			return '';
		}

		$page = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'ProfilePage',
			'mainEntity' => $person,
		);

		$post_id = ( $profile instanceof ABIO_Profile ) ? $profile->post_id() : 0;

		if ( $post_id ) {
			// Only where there is a post to date. A virtual profile built from a
			// WordPress user alone has no authored record behind it, and a
			// fabricated timestamp is still a fabrication.
			$page['dateCreated']  = get_post_time( DATE_W3C, true, $post_id );
			$page['dateModified'] = get_post_modified_time( DATE_W3C, true, $post_id );
		}

		/**
		 * The finished ProfilePage graph.
		 *
		 * @param array $page
		 * @param array $d
		 */
		$page = apply_filters( 'abio_schema_profile_page', $page, $d );

		return self::script( $page );
	}

	/**
	 * ItemList of Person nodes for an index.
	 *
	 * @param array  $rows    ABIO_Directory rows.
	 * @param string $heading Optional list name.
	 * @return string
	 */
	public static function item_list( $rows, $heading = '' ) {
		if ( ! self::enabled( 'list' ) ) {
			return '';
		}

		$items    = array();
		$position = 0;

		foreach ( (array) $rows as $row ) {
			$person = self::person(
				array(
					'name'     => isset( $row['name'] ) ? $row['name'] : '',
					'role'     => isset( $row['role'] ) ? $row['role'] : '',
					'short'    => isset( $row['short'] ) ? $row['short'] : '',
					'portrait' => isset( $row['portrait'] ) ? $row['portrait'] : 0,
					'url'      => isset( $row['url'] ) ? $row['url'] : '',
				),
				array()
			);

			if ( empty( $person['name'] ) ) {
				continue;
			}

			$position++;

			$item = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'item'     => $person,
			);

			if ( ! empty( $person['url'] ) ) {
				$item['url'] = $person['url'];
			}

			$items[] = $item;
		}

		if ( ! $items ) {
			return '';
		}

		$list = self::prune(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'ItemList',
				'name'            => self::text( $heading ),
				'numberOfItems'   => count( $items ),
				'itemListOrder'   => 'https://schema.org/ItemListUnordered',
				'itemListElement' => $items,
			)
		);

		/**
		 * The finished ItemList graph.
		 *
		 * @param array $list
		 * @param array $rows
		 */
		$list = apply_filters( 'abio_schema_item_list', $list, $rows );

		return self::script( $list );
	}

	/**
	 * One Person node.
	 *
	 * @param array $author name, role, location, bio, short, portrait, url
	 * @param array $site   name
	 * @param array $extra  Pre-built knowsAbout / hasCredential / sameAs.
	 * @return array
	 */
	private static function person( $author, $site, $extra = array() ) {
		$url = isset( $author['url'] ) ? esc_url_raw( (string) $author['url'] ) : '';

		$description = '';

		if ( ! empty( $author['bio'] ) ) {
			$description = self::text( $author['bio'] );
		}

		if ( '' === $description && ! empty( $author['short'] ) ) {
			$description = self::text( $author['short'] );
		}

		$person = array(
			'@type'       => 'Person',
			// Anchored to the author archive so the index's node and the profile
			// page's node are the same subject.
			'@id'         => '' !== $url ? $url . '#person' : '',
			'name'        => self::text( isset( $author['name'] ) ? $author['name'] : '' ),
			'url'         => $url,
			'jobTitle'    => self::text( isset( $author['role'] ) ? $author['role'] : '' ),
			'description' => $description,
			'image'       => self::image_url( isset( $author['portrait'] ) ? $author['portrait'] : 0 ),
		);

		if ( ! empty( $author['location'] ) ) {
			$person['homeLocation'] = array(
				'@type' => 'Place',
				'name'  => self::text( $author['location'] ),
			);
		}

		if ( ! empty( $site['name'] ) ) {
			$person['worksFor'] = array(
				'@type' => 'Organization',
				'name'  => self::text( $site['name'] ),
				'url'   => home_url( '/' ),
			);
		}

		foreach ( array( 'knowsAbout', 'hasCredential', 'sameAs' ) as $key ) {
			if ( ! empty( $extra[ $key ] ) ) {
				$person[ $key ] = $extra[ $key ];
			}
		}

		/**
		 * One Person node, before empty keys are pruned.
		 *
		 * @param array $person
		 * @param array $author
		 */
		$person = apply_filters( 'abio_schema_person', $person, $author );

		return self::prune( $person );
	}

	/**
	 * Areas of focus become knowsAbout: the subjects this author covers.
	 *
	 * @param array $focus
	 * @return array
	 */
	private static function focus_terms( $focus ) {
		$out = array();

		foreach ( $focus as $row ) {
			$title = isset( $row['title'] ) ? self::text( $row['title'] ) : '';

			if ( '' !== $title ) {
				$out[] = $title;
			}
		}

		return $out;
	}

	/**
	 * Credentials pass through as named credentials and nothing more.
	 *
	 * No issuer, no date, no level: the field holds a line of text an editor
	 * typed, and inventing the rest of the object would be inventing the claim.
	 *
	 * @param array $credentials
	 * @return array
	 */
	private static function credentials( $credentials ) {
		$out = array();

		foreach ( $credentials as $text ) {
			$text = self::text( $text );

			if ( '' !== $text ) {
				$out[] = array(
					'@type' => 'EducationalOccupationalCredential',
					'name'  => $text,
				);
			}
		}

		return $out;
	}

	/**
	 * Follow links become sameAs. Handles without a URL are dropped: sameAs is
	 * a set of URLs identifying the same person, not a list of usernames.
	 *
	 * @param array $follows
	 * @return array
	 */
	private static function same_as( $follows ) {
		$out = array();

		foreach ( $follows as $row ) {
			$url = isset( $row['url'] ) ? esc_url_raw( (string) $row['url'] ) : '';

			if ( '' !== $url ) {
				$out[] = $url;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * An absolute image URL from either an attachment ID or a URL.
	 *
	 * Mirrors ABIO_View::media()'s handling: the fallback profile carries an
	 * avatar URL where a configured one carries an attachment ID, and absint()
	 * would flatten the URL to 0.
	 *
	 * @param int|string $source
	 * @return string
	 */
	private static function image_url( $source ) {
		if ( is_string( $source ) && preg_match( '#^https?://#i', $source ) ) {
			return esc_url_raw( $source );
		}

		$id = absint( $source );

		if ( $id && wp_attachment_is_image( $id ) ) {
			$url = wp_get_attachment_image_url( $id, 'full' );

			if ( $url ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}

	/**
	 * Plain text for a JSON string: markup out, entities decoded, whitespace
	 * collapsed. The bio is stored as post-kses HTML and would otherwise carry
	 * tags and &#8217; sequences into the graph.
	 *
	 * @param string $value
	 * @return string
	 */
	private static function text( $value ) {
		$value = wp_strip_all_tags( (string) $value, true );
		$value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );

		return trim( preg_replace( '/\s+/u', ' ', $value ) );
	}

	/**
	 * Drop keys that did not resolve, at every depth.
	 *
	 * @param array $data
	 * @return array
	 */
	private static function prune( $data ) {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value        = self::prune( $value );
				$data[ $key ] = $value;
			}

			// 0 is never a meaningful value in these graphs, but an empty string
			// and an empty array both mean "nothing to say".
			if ( '' === $value || array() === $value || null === $value ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * @param string $context 'profile' or 'list'
	 * @return bool
	 */
	private static function enabled( $context ) {
		// The setting is the base answer and the filter still has the last word,
		// in both directions: a site can force this on where the setting is off,
		// or off where it is on, and can decide per context.
		$enabled = (bool) ABIO_Settings::get( 'show_schema', 0 );

		/**
		 * Whether to emit structured data at all.
		 *
		 * @param bool   $enabled Whether Authors → Settings has it switched on.
		 * @param string $context 'profile' or 'list'.
		 */
		return (bool) apply_filters( 'abio_schema_enabled', $enabled, $context );
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private static function script( $data ) {
		// JSON_HEX_TAG is what makes this safe to drop inside <script>: every
		// < and > becomes \u003C / \u003E, so no value can close the element.
		$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

		if ( ! $json ) {
			return '';
		}

		return '<script type="application/ld+json">' . $json . '</script>';
	}
}
