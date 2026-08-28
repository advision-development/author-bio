<?php

defined( 'ABSPATH' ) || exit;

class ABIO_Plugin {

	/**
	 * Wire everything up. Called once, from the plugin bootstrap.
	 */
	public static function init() {
		$files = array(
			'includes/class-post-type.php',
		);

		foreach ( $files as $file ) {
			require_once ABIO_PATH . $file;
		}

		add_action( 'init', array( 'ABIO_Post_Type', 'register' ) );
		register_activation_hook( ABIO_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Activation: register the post type once so its rewrite state is clean.
	 */
	public static function activate() {
		ABIO_Post_Type::register();
	}
}
