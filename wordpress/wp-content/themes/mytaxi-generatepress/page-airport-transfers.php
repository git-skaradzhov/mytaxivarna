<?php
/**
 * Airport transfer listing.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

the_post();
$groups   = mytaxi_service_groups();
$all      = array();
$route_by = array();
$vehicles = array();
$cards    = array();
foreach ( $groups['airport'] as $airport ) {
	$airport_routes = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::routes_for_service( (int) $airport['id'] ) : array();
	$all            = array_merge( $all, $airport_routes );
	$vehicles       = array_merge( $vehicles, mytaxi_vehicle_items( $airport, mytaxi_routes_from( $airport_routes ), true ) );
	$route_by[]     = array(
		'label'  => sprintf( __( 'From %s', 'mytaxi-generatepress' ), mytaxi_place_name( (string) $airport['title'] ) ),
		'routes' => $airport_routes,
	);
	$photos  = mytaxi_theme_photos();
	$cards[] = array(
		'image_id' => (int) ( $airport['image_id'] ?? 0 ) ?: (int) ( $airport['vehicle_id'] ?? 0 ),
		'file'     => $photos[ (string) $airport['slug'] ]['file'] ?? '',
		'title'    => (string) $airport['title'],
		'text'     => trim( mytaxi_card_text( (string) ( $airport['intro'] ?? '' ) ) . ' ' . mytaxi_routes_from( $airport_routes ) ),
		'url'      => (string) $airport['url'],
		'link'     => __( 'View destinations', 'mytaxi-generatepress' ),
	);
}
$first = $groups['airport'][0] ?? array();

mytaxi_open(
	'template-parts/home-hero',
	array(
		'routes' => $all,
		'photo'  => mytaxi_hero_photo( 'home' ),
		'title'  => get_the_title(),
		'lede'   => wp_strip_all_tags( get_the_content() ),
		'crumbs' => true,
		'local'  => false,
	)
);

get_template_part(
	'template-parts/strip',
	null,
	array(
		'items' => array(
			array(
				'icon'  => 'card',
				'value' => __( 'Fixed price', 'mytaxi-generatepress' ),
				'label' => __( 'for the car, not per person', 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'sign',
				'value' => __( 'Meet & greet', 'mytaxi-generatepress' ),
				'label' => __( 'in Arrivals', 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'phone',
				'value' => __( '24/7', 'mytaxi-generatepress' ),
				'label' => __( 'call, WhatsApp or Viber', 'mytaxi-generatepress' ),
			),
		),
	)
);

get_template_part(
	'template-parts/photo-cards',
	null,
	array(
		'id'      => 'services',
		'heading' => mytaxi_line( 'airport_heading' ),
		'text'    => __( 'Choose the airport you land at.', 'mytaxi-generatepress' ),
		'items'   => $cards,
	)
);

get_template_part(
	'template-parts/route-tiles',
	null,
	array(
		'groups'  => $route_by,
		'heading' => __( 'Popular airport transfers', 'mytaxi-generatepress' ),
	)
);

get_template_part(
	'template-parts/vehicles',
	null,
	array(
		'items' => $vehicles,
		'link'  => '#services',
		'text'  => __( 'Private cars for up to 4 or 5 passengers. The price is for the car, not per person.', 'mytaxi-generatepress' ),
	)
);

get_template_part(
	'template-parts/steps',
	null,
	array_merge(
		mytaxi_transfer_steps(),
		array(
			'image_id' => (int) ( $first['image_id'] ?? 0 ),
			'file'     => 'varna-airport.jpg',
		)
	)
);

get_template_part(
	'template-parts/travel-needs',
	null,
	array(
		'url'   => '#services',
		'label' => __( 'Choose your airport', 'mytaxi-generatepress' ),
	)
);

get_template_part( 'template-parts/review-sources', null, array( 'reviews' => mytaxi_review_sources( $groups['airport'] ) ) );

mytaxi_cta(
	mytaxi_line( 'airport_cta_heading' ),
	mytaxi_line( 'airport_cta_text' ),
	home_url( '/contact/' ),
	__( 'Contact us', 'mytaxi-generatepress' )
);

mytaxi_close();
