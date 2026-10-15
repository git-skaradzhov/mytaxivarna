<?php
/**
 * Route listing.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

the_post();
$groups   = mytaxi_service_groups();
$all      = array();
$route_by = array();
foreach ( $groups['airport'] as $airport ) {
	$airport_routes = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::routes_for_service( (int) $airport['id'] ) : array();
	$all            = array_merge( $all, $airport_routes );
	$route_by[]     = array(
		'label'  => sprintf( __( 'From %s', 'mytaxi-generatepress' ), mytaxi_place_name( (string) $airport['title'] ) ),
		'routes' => $airport_routes,
	);
}
$first = $groups['airport'][0] ?? array();

mytaxi_open(
	'template-parts/home-hero',
	array(
		'routes' => $all,
		'photo'  => mytaxi_hero_photo( 'varna-airport-to-balchik' ),
		'title'  => get_the_title(),
		'lede'   => wp_strip_all_tags( get_the_content() ),
		'crumbs' => true,
		'local'  => false,
	)
);

get_template_part(
	'template-parts/route-tiles',
	null,
	array(
		'id'      => 'routes',
		'groups'  => $route_by,
		'heading' => mytaxi_line( 'routes_heading' ),
		'text'    => __( 'Prices are for the car, not per person. Where no price is shown, the team quotes it on request.', 'mytaxi-generatepress' ),
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
		'url'   => '#routes',
		'label' => __( 'Choose your route', 'mytaxi-generatepress' ),
	)
);

mytaxi_cta(
	mytaxi_line( 'routes_cta_heading' ),
	mytaxi_line( 'routes_cta_text' ),
	home_url( '/contact/' ),
	__( 'Contact us', 'mytaxi-generatepress' )
);

mytaxi_close();
