<?php
/**
 * Plugin Name: Author Bio
 * Description: Renders a full author page from an [author_bio] shortcode, backed by a dedicated Author Profile admin.
 * Version:     1.2.1
 * Author:      Advision Development
 * Text Domain: author-bio
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Update URI: https://github.com/advision-development/author-bio
 */

defined( 'ABSPATH' ) || exit;

define( 'ABIO_VERSION', '1.2.1' );
define( 'ABIO_FILE', __FILE__ );
define( 'ABIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'ABIO_URL', plugin_dir_url( __FILE__ ) );

require_once ABIO_PATH . 'includes/class-plugin.php';

ABIO_Plugin::init();
