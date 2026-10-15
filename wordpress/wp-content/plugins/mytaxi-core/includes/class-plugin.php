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
		add_filter(
			'kses_allowed_protocols',
			static function ( $protocols ) {
				$protocols[] = 'viber';
				return $protocols;
			}
		);

		add_action( 'init', array( 'MyTaxi\\Core\\Setup', 'seed_sample_prices' ), 20 );
		PostTypes::boot();
		Routing::boot();
		Meta::boot();
		Admin::boot();
		Forms::boot();
		ContactForm::boot();
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
