<?php
/**
 * Local taxi listing.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

the_post();
$groups = mytaxi_service_groups();
$routes = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::home_routes() : array();

$cards = array();
foreach ( $groups['local'] as $service ) {
	$cards[] = array(
		'image_id' => (int) ( $service['image_id'] ?? 0 ),
		'title'    => mytaxi_place_name( (string) $service['title'] ),
		'text'     => mytaxi_card_text( (string) ( $service['intro'] ?? '' ) ),
		'url'      => (string) $service['url'],
		'link'     => __( 'View service', 'mytaxi-generatepress' ),
	);
}
$first = $groups['local'][0] ?? array();

mytaxi_open(
	'template-parts/page-hero',
	array(
		'title'   => get_the_title(),
		'lede'    => wp_strip_all_tags( get_the_content() ),
		'photo'   => mytaxi_page_photo( 'local-taxi' ),
		'actions' => array(
			array(
				'label' => __( 'Choose your resort', 'mytaxi-generatepress' ),
				'url'   => '#services',
			),
		),
	)
);

get_template_part(
	'template-parts/strip',
	null,
	array(
		'items' => array(
			array(
				'icon'  => 'pin',
				'value' => (string) count( $cards ),
				'label' => _n( 'resort with a local taxi', 'resorts with a local taxi', count( $cards ), 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'clock',
				'value' => __( '24/7', 'mytaxi-generatepress' ),
				'label' => __( 'every day', 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'phone',
				'value' => __( 'Call', 'mytaxi-generatepress' ),
				'label' => __( 'WhatsApp or Viber', 'mytaxi-generatepress' ),
			),
		),
	)
);

get_template_part(
	'template-parts/photo-cards',
	null,
	array(
		'id'      => 'services',
		'heading' => mytaxi_line( 'local_heading' ),
		'text'    => __( 'Each resort has its own team and phone number.', 'mytaxi-generatepress' ),
		'items'   => $cards,
	)
);

get_template_part(
	'template-parts/steps',
	null,
	array(
		'image_id' => (int) ( $first['image_id'] ?? 0 ),
		'heading'  => __( 'Get a local taxi in a few steps', 'mytaxi-generatepress' ),
		'steps'    => array(
			array(
				'title' => __( 'Choose your resort', 'mytaxi-generatepress' ),
				'text'  => __( 'Golden Sands, Albena, or Kranevo.', 'mytaxi-generatepress' ),
			),
			array(
				'title' => __( 'Call, message, or send a request', 'mytaxi-generatepress' ),
				'text'  => __( 'Tell the team your pickup, destination, and time.', 'mytaxi-generatepress' ),
			),
			array(
				'title' => __( 'We confirm the car', 'mytaxi-generatepress' ),
				'text'  => __( 'The team confirms the car and the pickup with you.', 'mytaxi-generatepress' ),
			),
		),
	)
);

get_template_part(
	'template-parts/route-tiles',
	null,
	array(
		'routes'     => $routes,
		'heading'    => __( 'Arriving by plane?', 'mytaxi-generatepress' ),
		'text'       => __( 'Arrange an airport transfer to your resort before you fly.', 'mytaxi-generatepress' ),
		'more_url'   => home_url( '/airport-transfers/' ),
		'more_label' => __( 'All airport transfers', 'mytaxi-generatepress' ),
	)
);

mytaxi_cta(
	mytaxi_line( 'local_cta_heading' ),
	mytaxi_line( 'local_cta_text' ),
	home_url( '/contact/' ),
	__( 'Contact us', 'mytaxi-generatepress' )
);

mytaxi_close();
