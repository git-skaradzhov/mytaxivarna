<?php
/**
 * Plugin Name: MyTaxi Core
 * Description: Services, routes, inquiries, and business settings for MyTaxi Varna.
 * Version: 1.0.0
 * Author: MyTaxi Varna
 * Text Domain: mytaxi-core
 * Requires at least: 6.5
 * Requires PHP: 8.1
 *
 * @package MyTaxi
 */

defined( 'ABSPATH' ) || exit;

define( 'MYTAXI_CORE_FILE', __FILE__ );
define( 'MYTAXI_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MYTAXI_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'MYTAXI_CORE_VERSION', '1.0.0' );

require_once MYTAXI_CORE_DIR . 'includes/class-fields.php';
require_once MYTAXI_CORE_DIR . 'includes/class-price.php';
require_once MYTAXI_CORE_DIR . 'includes/class-contacts.php';
require_once MYTAXI_CORE_DIR . 'includes/class-post-types.php';
require_once MYTAXI_CORE_DIR . 'includes/class-data.php';
require_once MYTAXI_CORE_DIR . 'includes/class-routing.php';
require_once MYTAXI_CORE_DIR . 'includes/class-meta.php';
require_once MYTAXI_CORE_DIR . 'includes/class-admin.php';
require_once MYTAXI_CORE_DIR . 'includes/class-forms.php';
require_once MYTAXI_CORE_DIR . 'includes/class-seo.php';
require_once MYTAXI_CORE_DIR . 'includes/class-setup.php';
require_once MYTAXI_CORE_DIR . 'includes/class-plugin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once MYTAXI_CORE_DIR . 'includes/class-cli.php';
}

register_activation_hook( MYTAXI_CORE_FILE, array( 'MyTaxi\\Core\\Plugin', 'activate' ) );
register_deactivation_hook( MYTAXI_CORE_FILE, array( 'MyTaxi\\Core\\Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'MyTaxi\\Core\\Plugin', 'boot' ) );
