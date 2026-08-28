<?php

defined( 'ABSPATH' ) || exit;

/**
 * Front-end assets. The stylesheet is registered always and enqueued only by
 * the shortcode, so pages without an author bio load nothing.
 */
class ABIO_Assets {

	const HANDLE = 'abio';

	public static function register() {
		wp_register_style(
			self::HANDLE,
			ABIO_URL . 'assets/css/author-bio.css',
			array(),
			ABIO_VERSION
		);
	}

	/**
	 * Called from the shortcode. Running during the_content means
	 * wp_enqueue_scripts has already fired, so WordPress prints this in the
	 * footer; the shortcode also inlines the palette so first paint is correct.
	 */
	public static function enqueue() {
		wp_enqueue_style( self::HANDLE );
	}
}
