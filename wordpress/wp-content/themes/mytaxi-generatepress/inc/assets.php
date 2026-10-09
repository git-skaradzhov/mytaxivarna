<?php
/**
 * Front-end assets.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function () {
		$css = get_stylesheet_directory() . '/assets/css/main.css';
		$js  = get_stylesheet_directory() . '/assets/js/front.js';

		wp_enqueue_style(
			'mytaxi-main',
			get_stylesheet_directory_uri() . '/assets/css/main.css',
			array( 'generate-style' ),
			file_exists( $css ) ? (string) filemtime( $css ) : '1.0.0'
		);
		wp_enqueue_script(
			'mytaxi-front',
			get_stylesheet_directory_uri() . '/assets/js/front.js',
			array(),
			file_exists( $js ) ? (string) filemtime( $js ) : '1.0.0',
			true
		);
	},
	20
);
