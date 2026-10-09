<?php
/**
 * Theme setup.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		load_child_theme_textdomain( 'mytaxi-generatepress', get_stylesheet_directory() . '/languages' );
		add_image_size( 'mytaxi-hero', 1600, 900, false );
		add_image_size( 'mytaxi-card', 720, 480, false );
	}
);

add_filter(
	'generate_schema_type',
	static function () {
		return 'none';
	}
);

add_filter(
	'generate_sidebar_layout',
	static function () {
		return 'no-sidebar';
	}
);

add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'mytaxi';
		return $classes;
	}
);
