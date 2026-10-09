<?php
/**
 * Read models for templates.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Loads services, routes, and homepage sections.
 */
class Data {

	/**
	 * Services marked for the homepage.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function home_services() {
		$posts = get_posts(
			array(
				'post_type'      => 'mytaxi_service',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'meta_key'       => '_mytaxi_order',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_mytaxi_show_home',
						'value' => '1',
					),
				),
				'no_found_rows'  => true,
			)
		);

		return array_map( array( __CLASS__, 'service' ), $posts );
	}

	/**
	 * Routes marked for the homepage.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function home_routes() {
		$posts = get_posts(
			array(
				'post_type'      => 'mytaxi_route',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'meta_key'       => '_mytaxi_order',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_mytaxi_popular',
						'value' => '1',
					),
				),
				'no_found_rows'  => true,
			)
		);

		return array_map( array( __CLASS__, 'route' ), $posts );
	}

	/**
	 * Published routes for one service.
	 *
	 * @param int $service_id Service ID.
	 * @return array<int,array<string,mixed>>
	 */
	public static function routes_for_service( $service_id ) {
		$posts = get_posts(
			array(
				'post_type'      => 'mytaxi_route',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'meta_key'       => '_mytaxi_order',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_mytaxi_service_id',
						'value' => (string) (int) $service_id,
					),
				),
				'no_found_rows'  => true,
			)
		);

		return array_map( array( __CLASS__, 'route' ), $posts );
	}

	/**
	 * Other routes of the same service.
	 *
	 * @param int $route_id Route ID.
	 * @return array<int,array<string,mixed>>
	 */
	public static function related_routes( $route_id ) {
		$route_id   = (int) $route_id;
		$service_id = (int) get_post_meta( $route_id, '_mytaxi_service_id', true );
		$routes     = self::routes_for_service( $service_id );

		return array_values(
			array_filter(
				$routes,
				static function ( $route ) use ( $route_id ) {
					return (int) $route['id'] !== $route_id;
				}
			)
		);
	}

	/**
	 * Normalize a service post.
	 *
	 * @param \WP_Post|int $post Post or ID.
	 * @return array<string,mixed>
	 */
	public static function service( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return array();
		}

		$id    = (int) $post->ID;
		$type  = (string) get_post_meta( $id, '_mytaxi_type', true );
		$type  = in_array( $type, array( 'local_taxi', 'airport_transfer' ), true ) ? $type : 'local_taxi';
		$areas = Fields::decode_list( get_post_meta( $id, '_mytaxi_areas', true ) );
		$benefits = Fields::decode_list( get_post_meta( $id, '_mytaxi_benefits', true ) );

		return array(
			'id'          => $id,
			'title'       => get_the_title( $post ),
			'url'         => get_permalink( $post ),
			'slug'        => $post->post_name,
			'type'        => $type,
			'type_label'  => 'airport_transfer' === $type ? __( 'Airport transfer', 'mytaxi-core' ) : __( 'Local taxi', 'mytaxi-core' ),
			'intro'       => (string) get_post_meta( $id, '_mytaxi_intro', true ),
			'cta'         => (string) get_post_meta( $id, '_mytaxi_cta', true ),
			'areas'       => array_values( array_filter( array_map( 'strval', $areas ) ) ),
			'terms'       => (string) get_post_meta( $id, '_mytaxi_terms', true ),
			'benefits'    => array_values( array_filter( array_map( 'strval', $benefits ) ) ),
			'faqs'        => self::faqs( get_post_meta( $id, '_mytaxi_faq', true ) ),
			'gallery'     => array_values( array_filter( array_map( 'intval', Fields::decode_list( get_post_meta( $id, '_mytaxi_gallery', true ) ) ) ) ),
			'content'     => $post->post_content,
			'image_id'    => (int) get_post_thumbnail_id( $post ),
			'meta_title'  => (string) get_post_meta( $id, '_mytaxi_meta_title', true ),
			'meta_desc'   => (string) get_post_meta( $id, '_mytaxi_meta_description', true ),
			'sample'      => '1' === (string) get_post_meta( $id, '_mytaxi_sample', true ),
			'contacts'    => Contacts::for_service( $id ),
		);
	}

	/**
	 * Normalize a route post.
	 *
	 * @param \WP_Post|int $post Post or ID.
	 * @return array<string,mixed>
	 */
	public static function route( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return array();
		}

		$id         = (int) $post->ID;
		$service_id = (int) get_post_meta( $id, '_mytaxi_service_id', true );
		$meta       = array(
			'price'       => (string) get_post_meta( $id, '_mytaxi_price', true ),
			'price_type'  => (string) get_post_meta( $id, '_mytaxi_price_type', true ),
			'price_basis' => (string) get_post_meta( $id, '_mytaxi_price_basis', true ),
		);

		return array(
			'id'           => $id,
			'title'        => get_the_title( $post ),
			'url'          => get_permalink( $post ),
			'slug'         => $post->post_name,
			'service_id'   => $service_id,
			'service'      => $service_id ? self::service( $service_id ) : array(),
			'origin'       => (string) get_post_meta( $id, '_mytaxi_origin', true ),
			'destination'  => (string) get_post_meta( $id, '_mytaxi_destination', true ),
			'summary'      => (string) get_post_meta( $id, '_mytaxi_summary', true ),
			'price'        => Price::present( $meta ),
			'distance'     => (string) get_post_meta( $id, '_mytaxi_distance', true ),
			'duration'     => (string) get_post_meta( $id, '_mytaxi_duration', true ),
			'capacity'     => (string) get_post_meta( $id, '_mytaxi_capacity', true ),
			'luggage'      => (string) get_post_meta( $id, '_mytaxi_luggage', true ),
			'included'     => array_values( array_filter( array_map( 'strval', Fields::decode_list( get_post_meta( $id, '_mytaxi_included', true ) ) ) ) ),
			'meeting'      => (string) get_post_meta( $id, '_mytaxi_meeting', true ),
			'delay'        => (string) get_post_meta( $id, '_mytaxi_delay', true ),
			'requirements' => (string) get_post_meta( $id, '_mytaxi_requirements', true ),
			'faqs'         => self::faqs( get_post_meta( $id, '_mytaxi_faq', true ) ),
			'content'      => $post->post_content,
			'image_id'     => (int) get_post_thumbnail_id( $post ),
			'meta_title'   => (string) get_post_meta( $id, '_mytaxi_meta_title', true ),
			'meta_desc'    => (string) get_post_meta( $id, '_mytaxi_meta_description', true ),
			'contacts'     => Contacts::for_service( $service_id ),
		);
	}

	/**
	 * Visible FAQ pairs.
	 *
	 * @param mixed $stored Stored meta.
	 * @return array<int,array{question:string,answer:string}>
	 */
	public static function faqs( $stored ) {
		$rows = Fields::decode_list( $stored );
		$faqs = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$question = trim( (string) ( $row['question'] ?? '' ) );
			$answer   = trim( (string) ( $row['answer'] ?? '' ) );
			if ( '' === $question || '' === $answer ) {
				continue;
			}
			$faqs[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}
		return $faqs;
	}

	/**
	 * Homepage sections stored in options.
	 *
	 * @return array<string,mixed>
	 */
	public static function home_extras() {
		return array(
			'fleet'    => self::object_list( get_option( 'mytaxi_home_fleet', '[]' ), array( 'title', 'text' ) ),
			'benefits' => array_values( array_filter( array_map( 'strval', Fields::decode_list( get_option( 'mytaxi_home_benefits', '[]' ) ) ) ) ),
			'reviews'  => self::object_list( get_option( 'mytaxi_home_reviews', '[]' ), array( 'quote', 'name', 'detail' ) ),
			'faqs'     => self::faqs( get_option( 'mytaxi_home_faq', '[]' ) ),
		);
	}

	/**
	 * Confirmed company details. Empty values stay empty.
	 *
	 * @return array<string,string>
	 */
	public static function company() {
		$stored = get_option( 'mytaxi_company', array() );
		$stored = is_array( $stored ) ? $stored : array();
		$keys   = array( 'legal_name', 'company_id', 'vat', 'address' );
		$out    = array();
		foreach ( $keys as $key ) {
			$out[ $key ] = sanitize_text_field( (string) ( $stored[ $key ] ?? '' ) );
		}
		return $out;
	}

	/**
	 * Social URLs that were actually saved.
	 *
	 * @return array<string,string>
	 */
	public static function social() {
		$stored = get_option( 'mytaxi_social', array() );
		$stored = is_array( $stored ) ? $stored : array();
		$out    = array();
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok' ) as $key ) {
			$url = Fields::url( (string) ( $stored[ $key ] ?? '' ) );
			if ( $url ) {
				$out[ $key ] = $url;
			}
		}
		return $out;
	}

	/**
	 * Breadcrumb trail for the current request.
	 *
	 * @return array<int,array{name:string,url:string}>
	 */
	public static function breadcrumbs() {
		$trail = array(
			array(
				'name' => __( 'Home', 'mytaxi-core' ),
				'url'  => home_url( '/' ),
			),
		);

		if ( is_singular( 'mytaxi_service' ) ) {
			$trail[] = array(
				'name' => get_the_title(),
				'url'  => get_permalink(),
			);
		} elseif ( is_singular( 'mytaxi_route' ) ) {
			$service_id = (int) get_post_meta( get_the_ID(), '_mytaxi_service_id', true );
			if ( $service_id && 'publish' === get_post_status( $service_id ) ) {
				$trail[] = array(
					'name' => get_the_title( $service_id ),
					'url'  => get_permalink( $service_id ),
				);
			}
			$trail[] = array(
				'name' => get_the_title(),
				'url'  => get_permalink(),
			);
		} elseif ( is_page() ) {
			$trail[] = array(
				'name' => get_the_title(),
				'url'  => get_permalink(),
			);
		}

		return $trail;
	}

	/**
	 * Sample content is still public.
	 *
	 * @return bool
	 */
	public static function is_sample() {
		return '1' === (string) get_option( 'mytaxi_sample_content', '1' );
	}

	/**
	 * Keep only complete rows.
	 *
	 * @param mixed             $stored Stored JSON or array.
	 * @param array<int,string> $keys Required keys that may be empty except the first.
	 * @return array<int,array<string,string>>
	 */
	private static function object_list( $stored, array $keys ) {
		$rows = Fields::decode_list( $stored );
		$out  = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$item = array();
			foreach ( $keys as $key ) {
				$item[ $key ] = sanitize_textarea_field( (string) ( $row[ $key ] ?? '' ) );
			}
			$primary = (string) reset( $item );
			if ( '' === $primary ) {
				continue;
			}
			$out[] = $item;
		}
		return $out;
	}
}
