<?php

defined( 'ABSPATH' ) || exit;

/**
 * Update checks against this plugin's GitHub releases.
 *
 * Uses the Update URI mechanism WordPress 5.8 added for exactly this case: the
 * plugin header names a host, and core hands that host's filter the chance to
 * describe an update. No library, no cron of our own, and no calls on ordinary
 * page loads — core only runs this during its own update check.
 *
 * Once this returns an update, everything else is core's: the admin notice, the
 * Plugins-screen row, the View details modal, and automatic updates when the
 * site has them switched on for this plugin.
 */
class ABIO_Updater {

	/** Owner/repo this plugin is released from. */
	const REPO = 'advision-development/author-bio';

	/** Cached release payload. */
	const CACHE = 'abio_latest_release';

	/** How long a successful lookup is trusted. */
	const TTL = 6 * HOUR_IN_SECONDS;

	/** How long a failed lookup is remembered, so an outage is not retried hard. */
	const TTL_FAIL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Core calls this once per update check, for this plugin only.
	 *
	 * @param array|false $update      Existing update payload, or false.
	 * @param array       $plugin_data Headers from this plugin's main file.
	 * @param string      $plugin_file Plugin basename, e.g. author-bio/author-bio.php.
	 * @return array|false
	 */
	public static function check( $update, $plugin_data, $plugin_file ) {
		$release = self::latest_release();

		if ( ! $release ) {
			return $update;
		}

		$current = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : ABIO_VERSION;

		if ( ! version_compare( $release['version'], $current, '>' ) ) {
			return $update;
		}

		return array(
			'id'           => 'github.com/' . self::REPO,
			'slug'         => dirname( $plugin_file ),
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'tested'       => $release['tested'],
			'requires_php' => isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '',
		);
	}

	/**
	 * The latest published release, or false.
	 *
	 * A failed lookup caches a sentinel rather than nothing, so a rate limit or
	 * an offline site does not re-request on every check.
	 *
	 * @param bool $force Skip the cache.
	 * @return array|false
	 */
	public static function latest_release( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE );

			if ( 'none' === $cached ) {
				return false;
			}

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'author-bio/' . ABIO_VERSION . '; ' . home_url( '/' ),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::CACHE, 'none', self::TTL_FAIL );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['tag_name'] ) || ! empty( $body['draft'] ) ) {
			set_site_transient( self::CACHE, 'none', self::TTL_FAIL );
			return false;
		}

		$release = array(
			// Tags are conventionally v1.2.3; the header version is not.
			'version'   => ltrim( (string) $body['tag_name'], 'vV' ),
			'url'       => isset( $body['html_url'] ) ? $body['html_url'] : 'https://github.com/' . self::REPO,
			'package'   => self::package_url( $body ),
			'notes'     => isset( $body['body'] ) ? (string) $body['body'] : '',
			'published' => isset( $body['published_at'] ) ? (string) $body['published_at'] : '',
			'tested'    => self::tested_header(),
		);

		if ( '' === $release['package'] ) {
			set_site_transient( self::CACHE, 'none', self::TTL_FAIL );
			return false;
		}

		set_site_transient( self::CACHE, $release, self::TTL );

		return $release;
	}

	/**
	 * Where to download the release from.
	 *
	 * A zip attached to the release wins, because it can be built with the
	 * plugin folder at its root and without the repo's development files. The
	 * generated source archive is the fallback so a release still installs when
	 * nobody attached one — ABIO_Updater::rename_source() fixes its folder name
	 * on the way in.
	 *
	 * @param array $body
	 * @return string
	 */
	private static function package_url( $body ) {
		foreach ( (array) ( isset( $body['assets'] ) ? $body['assets'] : array() ) as $asset ) {
			$name = isset( $asset['name'] ) ? $asset['name'] : '';

			if ( '.zip' === substr( $name, -4 ) && ! empty( $asset['browser_download_url'] ) ) {
				return $asset['browser_download_url'];
			}
		}

		return isset( $body['zipball_url'] ) ? (string) $body['zipball_url'] : '';
	}

	/**
	 * GitHub's generated source zip unpacks to owner-repo-<sha>/, which would
	 * install as a second, differently named plugin instead of replacing this
	 * one. Rename the extracted folder to match the plugin being updated.
	 *
	 * @param string      $source
	 * @param string      $remote_source
	 * @param WP_Upgrader $upgrader
	 * @param array       $args
	 * @return string|WP_Error
	 */
	public static function rename_source( $source, $remote_source, $upgrader, $args = array() ) {
		if ( empty( $args['plugin'] ) || dirname( $args['plugin'] ) !== basename( ABIO_PATH ) ) {
			return $source;
		}

		$wanted = trailingslashit( $remote_source ) . basename( ABIO_PATH );

		if ( untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
			return $source;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem || ! $wp_filesystem->move( $source, $wanted ) ) {
			return new WP_Error(
				'abio_rename_failed',
				__( 'Author Bio could not prepare the downloaded package for installation.', 'author-bio' )
			);
		}

		return trailingslashit( $wanted );
	}

	/**
	 * Fill the "View details" modal, which core otherwise leaves empty for a
	 * plugin that does not come from the plugin directory.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object|array
	 */
	public static function details( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || basename( ABIO_PATH ) !== $args->slug ) {
			return $result;
		}

		$release = self::latest_release();

		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Author Bio',
			'slug'          => $args->slug,
			'version'       => $release['version'],
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'tested'        => $release['tested'],
			'last_updated'  => $release['published'],
			'homepage'      => 'https://github.com/' . self::REPO,
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => wpautop( esc_html__( 'Renders a full author page from an [author_bio] shortcode, backed by a dedicated Author Profile admin screen linked to a WordPress user.', 'author-bio' ) ),
				'changelog'   => self::render_notes( $release ),
			),
		);
	}

	/**
	 * Release notes as safe HTML. GitHub returns Markdown; rather than pull in
	 * a parser for it, keep the text intact and linkify the release itself.
	 *
	 * @param array $release
	 * @return string
	 */
	private static function render_notes( $release ) {
		$notes = trim( $release['notes'] );

		$html = '' === $notes
			? wpautop( esc_html__( 'No release notes were published for this version.', 'author-bio' ) )
			: wpautop( esc_html( $notes ) );

		return $html . sprintf(
			'<p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
			esc_url( $release['url'] ),
			esc_html__( 'View this release on GitHub', 'author-bio' )
		);
	}

	/**
	 * "Tested up to" from the plugin header, so core does not warn about an
	 * untested-compatibility state it has no data for.
	 *
	 * @return string
	 */
	private static function tested_header() {
		// "Tested up to" is not one of the headers core parses into plugin data,
		// so ask for it explicitly rather than reading a key that is never set.
		$data = get_file_data( ABIO_FILE, array( 'tested' => 'Tested up to' ) );

		return isset( $data['tested'] ) ? trim( $data['tested'] ) : '';
	}

	/**
	 * A "Check for updates" link on the Plugins screen, because a six-hour
	 * cache is otherwise the only way to find out.
	 *
	 * @param array  $links
	 * @param string $plugin_file
	 * @return array
	 */
	public static function row_action( $links, $plugin_file ) {
		if ( plugin_basename( ABIO_FILE ) !== $plugin_file || ! current_user_can( 'update_plugins' ) ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=abio_check_update' ), 'abio_check_update' ) ),
			esc_html__( 'Check for updates', 'author-bio' )
		);

		return $links;
	}

	/**
	 * Handle that link: drop the cache, force a lookup, and let core re-check.
	 */
	public static function handle_check() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You are not allowed to check for plugin updates.', 'author-bio' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'abio_check_update' );

		delete_site_transient( self::CACHE );
		$release = self::latest_release( true );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();

		$status = 'error';

		if ( $release ) {
			$status = version_compare( $release['version'], ABIO_VERSION, '>' ) ? 'update' : 'current';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'abio_checked' => $status,
					'abio_latest'  => $release ? rawurlencode( $release['version'] ) : '',
				),
				admin_url( 'plugins.php' )
			)
		);

		exit;
	}

	/**
	 * Report the outcome of a manual check.
	 */
	public static function check_notice() {
		if ( empty( $_GET['abio_checked'] ) || ! current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['abio_checked'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$latest = isset( $_GET['abio_latest'] ) ? sanitize_text_field( wp_unslash( $_GET['abio_latest'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'update' === $status ) {
			$class = 'notice-success';
			/* translators: %s: version number. */
			$text = sprintf( __( 'Author Bio %s is available.', 'author-bio' ), $latest );
		} elseif ( 'current' === $status ) {
			$class = 'notice-success';
			/* translators: %s: version number. */
			$text = sprintf( __( 'Author Bio is up to date (%s).', 'author-bio' ), ABIO_VERSION );
		} else {
			$class = 'notice-warning';
			$text  = __( 'Author Bio could not reach GitHub to check for updates. It will try again shortly.', 'author-bio' );
		}

		printf(
			'<div class="notice %s is-dismissible"><p>%s</p></div>',
			esc_attr( $class ),
			esc_html( $text )
		);
	}

	/**
	 * Forget the cached release when the plugin is updated or the site asks core
	 * to re-check, so the Plugins screen never reports a stale version.
	 */
	public static function flush() {
		delete_site_transient( self::CACHE );
	}
}
