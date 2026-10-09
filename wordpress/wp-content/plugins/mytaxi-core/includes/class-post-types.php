<?php
/**
 * Content types.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers services and routes.
 */
class PostTypes {

	/**
	 * Hook registration.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'page_excerpt' ) );
	}

	/**
	 * Allow the front-page excerpt to be edited.
	 */
	public static function page_excerpt() {
		add_post_type_support( 'page', 'excerpt' );
	}

	/**
	 * Register both post types.
	 */
	public static function register() {
		register_post_type(
			'mytaxi_service',
			array(
				'labels'              => self::labels(
					'Услуги',
					'Услуга',
					'Добави услуга',
					'Редактирай услугата'
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => true,
				'exclude_from_search' => false,
				'menu_icon'           => 'dashicons-location-alt',
				'menu_position'       => 26,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);

		register_post_type(
			'mytaxi_route',
			array(
				'labels'              => self::labels(
					'Маршрути',
					'Маршрут',
					'Добави маршрут',
					'Редактирай маршрута'
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => true,
				'exclude_from_search' => false,
				'menu_icon'           => 'dashicons-car',
				'menu_position'       => 27,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Bulgarian labels.
	 *
	 * @param string $plural   Plural.
	 * @param string $single   Singular.
	 * @param string $add      Add label.
	 * @param string $edit     Edit label.
	 * @return array<string,string>
	 */
	private static function labels( $plural, $single, $add, $edit ) {
		return array(
			'name'                  => $plural,
			'singular_name'         => $single,
			'menu_name'             => $plural,
			'add_new'               => 'Добави',
			'add_new_item'          => $add,
			'edit_item'             => $edit,
			'new_item'              => $single,
			'view_item'             => 'Преглед',
			'search_items'          => 'Търсене',
			'not_found'             => 'Няма записи',
			'not_found_in_trash'    => 'Няма записи в кошчето',
			'all_items'             => 'Всички',
			'featured_image'        => 'Основна снимка',
			'set_featured_image'    => 'Задай основна снимка',
			'remove_featured_image' => 'Премахни основната снимка',
			'use_featured_image'    => 'Използвай като основна снимка',
		);
	}
}
