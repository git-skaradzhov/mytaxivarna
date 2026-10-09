<?php
/**
 * Plugin bootstrap.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Boots MyTaxi Core.
 */
class Plugin {

	/**
	 * Register hooks.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
		add_filter( 'determine_locale', array( __CLASS__, 'locale' ) );

		PostTypes::boot();
		Routing::boot();
		Meta::boot();
		Admin::boot();
		Forms::boot();
		Seo::boot();
	}

	/**
	 * Load the text domain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'mytaxi-core', false, dirname( plugin_basename( MYTAXI_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * Keep the public site in English and the admin screens in Bulgarian.
	 *
	 * @param string $locale Current locale.
	 * @return string
	 */
	public static function locale( $locale ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return $locale;
		}

		if ( is_admin() ) {
			return 'bg_BG';
		}

		return 'en_US';
	}

	/**
	 * Activation: post types exist before the next rewrite flush.
	 */
	public static function activate() {
		PostTypes::register();
		update_option( 'mytaxi_needs_flush', '1', false );
	}

	/**
	 * Deactivation removes rewrite rules and keeps content.
	 */
	public static function deactivate() {
		flush_rewrite_rules( false );
	}
}
