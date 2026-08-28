<?php

defined( 'ABSPATH' ) || exit;

/**
 * Turns one Author Profile post, plus live site data, into the single array
 * every template renders from.
 */
class ABIO_Profile {

	/** @var int */
	private $post_id;

	/** @var int */
	private $user_id;

	/**
	 * @param int $post_id
	 */
	private function __construct( $post_id ) {
		$this->post_id = (int) $post_id;
		$this->user_id = (int) $this->meta( 'user' );
	}

	/**
	 * @param int $post_id
	 * @return ABIO_Profile|null
	 */
	public static function for_post( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ABIO_Post_Type::SLUG !== get_post_type( $post_id ) ) {
			return null;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return null;
		}

		return new self( $post_id );
	}

	/**
	 * @param int $user_id
	 * @return ABIO_Profile|null
	 */
	public static function for_user( $user_id ) {
		return self::for_post( ABIO_Post_Type::find_by_user( $user_id ) );
	}

	/**
	 * @return int
	 */
	public function user_id() {
		return $this->user_id;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	private function meta( $key ) {
		return get_post_meta( $this->post_id, ABIO_Fields::meta_key( $key ), true );
	}

	/**
	 * A repeater's rows, always an array.
	 *
	 * @param string $key
	 * @return array
	 */
	private function rows( $key ) {
		$value = $this->meta( $key );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Flatten a single-column repeater ('badges', 'credentials') to strings.
	 *
	 * @param string $key
	 * @return array
	 */
	private function strings( $key ) {
		$out = array();

		foreach ( $this->rows( $key ) as $row ) {
			if ( isset( $row['text'] ) && '' !== $row['text'] ) {
				$out[] = $row['text'];
			}
		}

		return $out;
	}

	/**
	 * Build the template data array.
	 *
	 * @param array $args count, post_type, others, hide
	 * @return array
	 */
	public function to_array( $args = array() ) {
		$defaults = array(
			'count'     => (int) ABIO_Settings::get( 'default_count', 6 ),
			'post_type' => ABIO_Articles::post_types(),
			'others'    => 2,
			'hide'      => array(),
		);

		$args = array_merge( $defaults, $args );
		$hide = (array) $args['hide'];

		$edits = in_array( 'edits', $hide, true )
			? array()
			: ABIO_Articles::for_user(
				$this->user_id,
				array(
					'count'     => $args['count'],
					'post_type' => $args['post_type'],
				)
			);

		$data = array(
			'site'        => $this->site(),
			'author'      => $this->author(),
			'stats'       => in_array( 'stats', $hide, true ) ? array() : ABIO_Stats::resolve( $this->rows( 'stats' ), $this->user_id ),
			'gallery'     => in_array( 'gallery', $hide, true ) ? $this->empty_gallery() : $this->gallery(),
			'focus'       => in_array( 'focus', $hide, true ) ? array() : $this->focus(),
			'edits'       => $edits,
			'experience'  => in_array( 'experience', $hide, true ) ? array() : $this->rows( 'experience' ),
			'credentials' => in_array( 'credentials', $hide, true ) ? array() : $this->strings( 'credentials' ),
			'follows'     => in_array( 'follows', $hide, true ) ? array() : $this->rows( 'follows' ),
			'others'      => in_array( 'others', $hide, true ) ? array() : $this->others( (int) $args['others'] ),
			'pitch'       => in_array( 'pitch', $hide, true ) ? array( 'title' => '', 'body' => '', 'cta' => '' ) : $this->pitch(),
		);

		$data['nav'] = $this->nav( $data );

		return $data;
	}

	/**
	 * @return array
	 */
	private function site() {
		return array(
			'name'        => ABIO_Settings::get( 'site_name', get_bloginfo( 'name' ) ),
			'editorialUrl' => ABIO_Settings::get( 'editorial_url', '' ),
			'contactUrl'  => ABIO_Settings::get( 'contact_url', '' ),
			'authorsUrl'  => ABIO_Settings::get( 'authors_url', '' ),
		);
	}

	/**
	 * @return array
	 */
	private function author() {
		$name = $this->meta( 'name' );

		if ( '' === $name && $this->user_id ) {
			$name = get_the_author_meta( 'display_name', $this->user_id );
		}

		$since = $this->meta( 'since' );

		if ( '' === $since && $this->user_id ) {
			$since = ABIO_Stats::first_year( $this->user_id );
		}

		$kicker = $this->meta( 'kicker' );

		return array(
			'kicker'   => '' === $kicker ? __( 'Author', 'author-bio' ) : $kicker,
			'name'     => $name,
			'role'     => $this->meta( 'role' ),
			'location' => $this->meta( 'location' ),
			'since'    => $since,
			'badges'   => $this->strings( 'badges' ),
			'bio'      => $this->meta( 'bio' ),
			'short'    => $this->meta( 'short' ),
			'portrait' => (int) $this->meta( 'portrait' ),
			'url'      => $this->user_id ? get_author_posts_url( $this->user_id ) : '',
		);
	}

	/**
	 * @return array
	 */
	private function empty_gallery() {
		return array(
			'heading' => '',
			'note'    => '',
			'items'   => array(),
		);
	}

	/**
	 * Gallery items carry a 1-based index; template 9 labels them "Exhibit N".
	 *
	 * @return array
	 */
	private function gallery() {
		$items = array();
		$n     = 1;

		foreach ( $this->rows( 'gallery_items' ) as $row ) {
			$row['image'] = isset( $row['image'] ) ? (int) $row['image'] : 0;
			$row['n']     = $n;
			$items[]      = $row;
			$n++;
		}

		return array(
			'heading' => $this->meta( 'gallery_heading' ),
			'note'    => $this->meta( 'gallery_note' ),
			'items'   => $items,
		);
	}

	/**
	 * Focus rows carry the derived indexes templates 7, 9 and 10 print:
	 * n = "01", sub = "1.1".
	 *
	 * @return array
	 */
	private function focus() {
		$out = array();
		$i   = 1;

		foreach ( $this->rows( 'focus' ) as $row ) {
			$row['n']   = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
			$row['sub'] = '1.' . $i;
			$out[]      = $row;
			$i++;
		}

		return $out;
	}

	/**
	 * Other published profiles, excluding this one.
	 *
	 * @param int $limit
	 * @return array
	 */
	private function others( $limit ) {
		$limit = (int) $limit;

		if ( $limit < 1 ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'        => ABIO_Post_Type::SLUG,
				'post_status'      => 'publish',
				'numberposts'      => $limit,
				'exclude'          => array( $this->post_id ),
				'orderby'          => 'title',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		$out = array();

		foreach ( $ids as $id ) {
			$profile = self::for_post( $id );

			if ( ! $profile ) {
				continue;
			}

			$author = $profile->author();

			$out[] = array(
				'name' => $author['name'],
				'role' => $author['role'],
				'url'  => $author['url'],
			);
		}

		return $out;
	}

	/**
	 * @return array
	 */
	private function pitch() {
		return array(
			'title' => ABIO_Settings::get( 'pitch_title', '' ),
			'body'  => ABIO_Settings::get( 'pitch_body', '' ),
			'cta'   => ABIO_Settings::get( 'pitch_cta', '' ),
		);
	}

	/**
	 * The "On this page" list, limited to sections that actually have content.
	 *
	 * @param array $data
	 * @return array
	 */
	private function nav( $data ) {
		$candidates = array(
			array( 'focus', __( 'Areas of focus', 'author-bio' ) ),
			array( 'edits', __( 'Latest edits', 'author-bio' ) ),
			array( 'experience', __( 'Experience', 'author-bio' ) ),
		);

		$nav = array();
		$n   = 1;

		foreach ( $candidates as $candidate ) {
			list( $key, $label ) = $candidate;

			if ( empty( $data[ $key ] ) ) {
				continue;
			}

			$nav[] = array(
				'num'   => str_pad( (string) $n, 2, '0', STR_PAD_LEFT ),
				'label' => $label,
				'href'  => '#abio-' . $key,
			);

			$n++;
		}

		return $nav;
	}
}
