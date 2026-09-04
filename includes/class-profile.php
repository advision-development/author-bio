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
	 * True when there is no Author Profile post and the page is being built
	 * from the WordPress user alone.
	 *
	 * @var bool
	 */
	private $fallback = false;

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
	 * A profile backed by nothing but the WordPress user.
	 *
	 * Every group in to_array() that comes from post meta resolves to empty on
	 * post 0, and the parts that matter most on an author page — the name, the
	 * article list, the automatic stat tiles, the first-published year — are
	 * derived from the user anyway. So an unconfigured author still gets a real
	 * page: their name, their picture, and what they have actually published.
	 *
	 * Nothing is invented here. Only fields WordPress already holds are used,
	 * and a field WordPress does not know is left empty so the section
	 * disappears rather than filling with a guess.
	 *
	 * @param int $user_id
	 * @return ABIO_Profile|null
	 */
	public static function fallback_for_user( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return null;
		}

		$profile           = new self( 0 );
		$profile->user_id  = $user_id;
		$profile->fallback = true;

		return $profile;
	}

	/**
	 * Whether this page is being built from the user alone.
	 *
	 * @return bool
	 */
	public function is_fallback() {
		return $this->fallback;
	}

	/**
	 * @return int
	 */
	public function user_id() {
		return $this->user_id;
	}

	/**
	 * The Author Profile post behind this profile, or 0 for a virtual one built
	 * from a WordPress user alone. Structured data uses it for the record's
	 * dates, which a virtual profile has no honest answer for.
	 *
	 * @return int
	 */
	public function post_id() {
		return (int) $this->post_id;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	private function meta( $key ) {
		// A virtual profile has no post behind it, and get_post_meta( 0, … )
		// returns false rather than an empty string — which would defeat every
		// "'' === $value" fallback in author().
		if ( ! $this->post_id ) {
			return '';
		}

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
			'stats'       => in_array( 'stats', $hide, true ) ? array() : ABIO_Stats::resolve( $this->stat_config(), $this->user_id ),
			'gallery'     => in_array( 'gallery', $hide, true ) ? $this->empty_gallery() : $this->gallery(),
			'focus'       => in_array( 'focus', $hide, true ) ? array() : $this->focus(),
			'edits'       => $edits,
			'experience'  => in_array( 'experience', $hide, true ) ? array() : $this->rows( 'experience' ),
			'credentials' => in_array( 'credentials', $hide, true ) ? array() : $this->strings( 'credentials' ),
			'follows'     => in_array( 'follows', $hide, true ) ? array() : $this->rows( 'follows' ),
			'others'      => in_array( 'others', $hide, true ) ? array() : $this->others( (int) $args['others'] ),
			'pitch'       => in_array( 'pitch', $hide, true ) ? array( 'title' => '', 'body' => '', 'cta' => '' ) : $this->pitch(),
		);

		$data['breadcrumbs'] = in_array( 'breadcrumbs', $hide, true ) ? array() : $this->breadcrumbs();
		$data['nav']         = $this->nav( $data );

		return $data;
	}

	/**
	 * The stat tiles to resolve.
	 *
	 * A configured profile uses whatever the editor set. A virtual one has no
	 * configuration, so it falls back to the two figures WordPress can derive
	 * on its own: how much this person has published, and since when. Both
	 * values are counted from real posts — only the labels are ours — and both
	 * drop out on their own if the author has nothing published.
	 *
	 * @return array
	 */
	private function stat_config() {
		$configured = $this->rows( 'stats' );

		if ( ! $this->fallback || $configured ) {
			return $configured;
		}

		return array(
			array(
				'mode'      => 'auto_bylines',
				'post_type' => '',
				'value'     => '',
				'label'     => __( 'Articles published', 'author-bio' ),
			),
			array(
				'mode'      => 'auto_since',
				'post_type' => '',
				'value'     => '',
				'label'     => __( 'Writing since', 'author-bio' ),
			),
		);
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

		$bio      = $this->meta( 'bio' );
		$portrait = (int) $this->meta( 'portrait' );

		if ( $this->fallback ) {
			// WordPress's own Biographical Info field, when the user filled it in.
			if ( '' === $bio ) {
				$bio = (string) get_the_author_meta( 'description', $this->user_id );
			}

			// And their avatar, but only where the site has avatars switched on:
			// a site that turned them off has said it does not want a stand-in,
			// and Gravatar's default silhouette is exactly that.
			if ( ! $portrait && get_option( 'show_avatars' ) ) {
				$avatar = get_avatar_url( $this->user_id, array( 'size' => 600 ) );

				if ( $avatar ) {
					$portrait = $avatar;
				}
			}
		}

		return array(
			'kicker'   => '' === $kicker ? __( 'Author', 'author-bio' ) : $kicker,
			'name'     => $name,
			'role'     => $this->meta( 'role' ),
			'location' => $this->meta( 'location' ),
			'since'    => $since,
			'badges'   => $this->strings( 'badges' ),
			'bio'      => $bio,
			'short'    => $this->meta( 'short' ),
			'portrait' => $portrait,
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
	 * Other published profiles, excluding this one, ordered by the resolved
	 * author name — not the profile's post title, which the CPT documents as
	 * an internal label only. That name can fall back to the linked user's
	 * display name, so sorting has to happen after resolving each profile
	 * rather than in the query.
	 *
	 * @param int $limit
	 * @return array
	 */
	private function others( $limit ) {
		$limit = (int) $limit;

		if ( $limit < 1 ) {
			return array();
		}

		// Same query path as the author index, so the two can never disagree
		// about who counts as an author. Counts are skipped: the rail shows a
		// name and a role, and a byline count per author is a query each.
		$rows = ABIO_Directory::authors(
			array(
				'exclude' => array( $this->post_id ),
				'limit'   => $limit,
				'orderby' => 'name',
				'counts'  => false,
			)
		);

		$out = array();

		foreach ( $rows as $row ) {
			$out[] = array(
				'name'     => $row['name'],
				'role'     => $row['role'],
				'url'      => $row['url'],
				'portrait' => $row['portrait'],
			);
		}

		return $out;
	}

	/**
	 * @return array
	 */
	private function pitch() {
		$title = ABIO_Settings::get( 'pitch_title', '' );
		$body  = ABIO_Settings::get( 'pitch_body', '' );
		$cta   = ABIO_Settings::get( 'pitch_cta', '' );
		$url   = ABIO_Settings::get( 'contact_url', '' );

		// Switched off site-wide, or with nothing in it worth a box. A heading
		// on its own is not content: both pitch_title and pitch_cta ship with
		// defaults, so a site that never filled the pitch in would otherwise
		// render an empty bordered box on every template carrying one.
		// Templates guard on the title, so blanking the whole group here
		// suppresses the block everywhere without touching them.
		if ( ! ABIO_Settings::get( 'show_pitch', 1 ) || ( '' === $body && ! ( $cta && $url ) ) ) {
			return array(
				'title' => '',
				'body'  => '',
				'cta'   => '',
			);
		}

		return array(
			'title' => $title,
			'body'  => $body,
			'cta'   => $cta,
		);
	}

	/**
	 * The breadcrumb trail, as rows of label plus url. The last row carries no
	 * url because it is the current page.
	 *
	 * Built as data rather than as markup in the template so the site-wide
	 * toggle and hide="breadcrumbs" both collapse to the same empty array, and
	 * a template that wants a trail does not have to rebuild the logic.
	 *
	 * @return array
	 */
	private function breadcrumbs() {
		if ( ! ABIO_Settings::get( 'show_breadcrumbs', 1 ) ) {
			return array();
		}

		$author = $this->author();
		$crumbs = array(
			array(
				'label' => __( 'Home', 'author-bio' ),
				'url'   => home_url( '/' ),
			),
		);

		$authors_url = ABIO_Settings::get( 'authors_url', '' );

		if ( $authors_url ) {
			$crumbs[] = array(
				'label' => __( 'Authors', 'author-bio' ),
				'url'   => $authors_url,
			);
		}

		if ( '' !== $author['name'] ) {
			$crumbs[] = array(
				'label' => $author['name'],
				'url'   => '',
			);
		}

		return $crumbs;
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
