<?php
/**
 * Airport route.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

the_post();
$route   = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::route( get_the_ID() ) : array();
$service = (array) ( $route['service'] ?? array() );
$related = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::related_routes( get_the_ID() ) : array();
$reviews = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::review_sources( (int) ( $service['id'] ?? 0 ) ) : array();
$price   = (array) ( $route['price'] ?? array() );
$origin  = (string) ( $route['origin'] ?? '' );
$dest    = (string) ( $route['destination'] ?? '' );
$fixed   = mytaxi_fixed_price( $price );

mytaxi_open( 'template-parts/route-hero', array( 'route' => $route ) );

get_template_part(
	'template-parts/strip',
	null,
	array(
		'panel' => true,
		'items' => array(
			array(
				'icon'  => 'users',
				'value' => (string) ( $route['capacity'] ?? '' ),
				'label' => '',
			),
			array(
				'icon'  => 'card',
				'value' => $fixed ? (string) $price['text'] : __( 'Price on request', 'mytaxi-generatepress' ),
				'label' => $fixed ? __( 'for the car', 'mytaxi-generatepress' ) : __( 'per car, not per person', 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'sign',
				'value' => __( 'Meet & greet', 'mytaxi-generatepress' ),
				'label' => __( 'in Arrivals', 'mytaxi-generatepress' ),
			),
		),
	)
);
?>
<div class="mt-route-body" data-panel="journey">
	<?php
	get_template_part( 'template-parts/route-about', null, array( 'route' => $route ) );

	get_template_part(
		'template-parts/photo-cards',
		null,
		array(
			'heading' => __( 'All set before you land', 'mytaxi-generatepress' ),
			'items'   => mytaxi_airport_cards( $service ),
		)
	);

	get_template_part(
		'template-parts/vehicles',
		null,
		array(
			'items'   => mytaxi_vehicle_items( $service, $fixed ? (string) $price['text'] : '' ),
			'heading' => __( 'Our cars', 'mytaxi-generatepress' ),
			'text'    => __( 'Private cars for up to 4 or up to 5 passengers. Tell us your passengers and luggage, and the team confirms the right car.', 'mytaxi-generatepress' ),
		)
	);
	?>
</div>
<?php
get_template_part(
	'template-parts/route-tiles',
	null,
	array(
		'routes'     => $related,
		'panel'      => true,
		'heading'    => __( 'Other popular routes', 'mytaxi-generatepress' ),
		'text'       => sprintf( __( 'From %s', 'mytaxi-generatepress' ), $origin ),
		'more_url'   => home_url( '/routes/' ),
		'more_label' => __( 'View all routes', 'mytaxi-generatepress' ),
	)
);
?>
<div class="mt-route-body" data-panel="journey">
	<?php
	get_template_part(
		'template-parts/steps',
		null,
		array(
			'image_id' => (int) ( $route['image_id'] ?? 0 ) ?: (int) ( $service['vehicle_id'] ?? 0 ),
			'heading'  => __( 'Request your transfer in a few steps', 'mytaxi-generatepress' ),
			'text'     => __( 'Send the request in a few minutes. The team replies to confirm everything with you.', 'mytaxi-generatepress' ),
			'steps'    => array(
				array(
					'title' => __( 'Enter your journey', 'mytaxi-generatepress' ),
					'text'  => __( 'Flight arrival, passengers, and a return if you need one.', 'mytaxi-generatepress' ),
				),
				array(
					'title' => __( 'Add your details', 'mytaxi-generatepress' ),
					'text'  => __( 'Name, phone, and hotel. The flight number can follow, and we need it to confirm.', 'mytaxi-generatepress' ),
				),
				array(
					'title' => __( 'We confirm', 'mytaxi-generatepress' ),
					'text'  => __( 'Your booking is confirmed after we agree the price and receive a passenger name and flight number.', 'mytaxi-generatepress' ),
				),
				array(
					'title' => __( 'Meet your driver', 'mytaxi-generatepress' ),
					'text'  => __( 'Your driver waits in Arrivals with a name sign.', 'mytaxi-generatepress' ),
				),
			),
		)
	);

	get_template_part( 'template-parts/travel-needs' );
	get_template_part( 'template-parts/review-sources', null, array( 'reviews' => $reviews ) );
	mytaxi_faqs( $route['faqs'] ?? array() );
	mytaxi_cta(
		sprintf( __( 'Arrange your %s transfer', 'mytaxi-generatepress' ), $dest ),
		sprintf( __( 'Send a request to the %s team, or message them on WhatsApp or Viber.', 'mytaxi-generatepress' ), (string) ( $service['title'] ?? '' ) ),
		'#request',
		__( 'Request your transfer', 'mytaxi-generatepress' )
	);
	?>
</div>
<?php
mytaxi_close();
