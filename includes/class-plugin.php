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
			'includes/class-stats.php',
			'includes/class-directory.php',
			'includes/class-profile.php',
			'includes/class-view.php',
			'includes/class-schema.php',
			'includes/class-assets.php',
			'includes/class-shortcode.php',
			'includes/class-list-shortcode.php',
			'includes/class-updater.php',
		);

		foreach ( $files as $file ) {
			require_once ABIO_PATH . $file;
		}

		add_action( 'init', array( 'ABIO_Post_Type', 'register' ) );
		add_action( 'add_meta_boxes', array( 'ABIO_Metaboxes', 'register' ) );
		add_action( 'save_post', array( 'ABIO_Metaboxes', 'save' ) );
		add_action( 'admin_enqueue_scripts', array( 'ABIO_Metaboxes', 'admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( 'ABIO_Settings', 'assets' ) );
		add_action( 'admin_menu', array( 'ABIO_Settings', 'menu' ) );
		add_action( 'admin_init', array( 'ABIO_Settings', 'register' ) );
		add_action( 'admin_post_abio_redetect', array( 'ABIO_Palette', 'handle_redetect' ) );

		// Update URI in the plugin header points at github.com, so core offers
		// this host the chance to describe an update during its own check.
		add_filter( 'update_plugins_github.com', array( 'ABIO_Updater', 'check' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( 'ABIO_Updater', 'rename_source' ), 10, 4 );
		add_filter( 'plugins_api', array( 'ABIO_Updater', 'details' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( 'ABIO_Updater', 'row_action' ), 10, 2 );
		add_action( 'admin_post_abio_check_update', array( 'ABIO_Updater', 'handle_check' ) );
		add_action( 'admin_notices', array( 'ABIO_Updater', 'check_notice' ) );
		add_action( 'upgrader_process_complete', array( 'ABIO_Updater', 'flush' ) );
		add_action( 'init', array( 'ABIO_Shortcode', 'register' ) );
		add_action( 'init', array( 'ABIO_Shortcode_List', 'register' ) );
		add_action( 'init', array( 'ABIO_Assets', 'register' ) );
		add_filter( 'manage_' . ABIO_Post_Type::SLUG . '_posts_columns', array( 'ABIO_Post_Type', 'columns' ) );
		add_action( 'manage_' . ABIO_Post_Type::SLUG . '_posts_custom_column', array( 'ABIO_Post_Type', 'column' ), 10, 2 );
		register_activation_hook( ABIO_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Activation: register the post type once so its rewrite state is clean.
	 */
	public static function activate() {
		ABIO_Post_Type::register();
		ABIO_Post_Type::add_capabilities();
		ABIO_Palette::store_detection();
	}
}
