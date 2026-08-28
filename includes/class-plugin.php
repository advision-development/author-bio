<?php

defined( 'ABSPATH' ) || exit;

class ABIO_Plugin {

	/**
	 * Wire everything up. Called once, from the plugin bootstrap.
	 */
	public static function init() {
		$files = array(
			'includes/class-fields.php',
			'includes/class-post-type.php',
			'includes/class-metaboxes.php',
			'includes/class-palette.php',
			'includes/class-settings.php',
			'includes/class-articles.php',
		);

		foreach ( $files as $file ) {
			require_once ABIO_PATH . $file;
		}

		add_action( 'init', array( 'ABIO_Post_Type', 'register' ) );
		add_action( 'add_meta_boxes', array( 'ABIO_Metaboxes', 'register' ) );
		add_action( 'save_post', array( 'ABIO_Metaboxes', 'save' ) );
		add_action( 'admin_enqueue_scripts', array( 'ABIO_Metaboxes', 'admin_assets' ) );
		add_action( 'admin_menu', array( 'ABIO_Settings', 'menu' ) );
		add_action( 'admin_init', array( 'ABIO_Settings', 'register' ) );
		add_action( 'admin_post_abio_redetect', array( 'ABIO_Palette', 'handle_redetect' ) );
		register_activation_hook( ABIO_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Activation: register the post type once so its rewrite state is clean.
	 */
	public static function activate() {
		ABIO_Post_Type::register();
		ABIO_Palette::store_detection();
	}
}
