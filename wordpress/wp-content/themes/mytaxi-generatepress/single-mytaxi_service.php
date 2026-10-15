<?php
/**
 * Service page.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

the_post();
$service  = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::service( get_the_ID() ) : array();
$routes   = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::routes_for_service( get_the_ID() ) : array();
$reviews  = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::review_sources( get_the_ID() ) : array();
$airport  = 'airport_transfer' === ( $service['type'] ?? '' );
$place    = mytaxi_place_name( get_the_title() );
$from     = mytaxi_routes_from( $routes );
$phone    = (string) ( $service['contacts']['phone'] ?? '' );
$contacts = (array) ( $service['contacts'] ?? array() );

if ( $airport ) {
	mytaxi_open(
		'template-parts/home-hero',
		array(
			'routes' => $routes,
			'photo'  => mytaxi_page_photo( (string) ( $service['slug'] ?? '' ) ),
			'title'  => get_the_title(),
			'lede'   => (string) ( $service['intro'] ?? '' ),
			'crumbs' => true,
			'local'  => false,
		)
	);
} else {
	mytaxi_open(
		'template-parts/page-hero',
		array(
			'kicker'  => (string) ( $service['type_label'] ?? '' ),
			'title'   => get_the_title(),
			'lede'    => (string) ( $service['intro'] ?? '' ),
			'photo'   => mytaxi_page_photo( (string) ( $service['slug'] ?? '' ) ),
			'service' => $service,
		)
	);
}

get_template_part(
	'template-parts/strip',
	null,
	array(
		'items' => array(
			array(
				'icon'  => 'pin',
				'value' => implode( ', ', (array) ( $service['areas'] ?? array() ) ),
				'label' => $airport ? __( 'pickup in Arrivals', 'mytaxi-generatepress' ) : __( 'pickup area', 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'card',
				'value' => $airport ? $from : '',
				'label' => __( 'for the car, not per person', 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'clock',
				'value' => ! empty( $service['hours'] ) ? __( '24/7', 'mytaxi-generatepress' ) : '',
				'label' => (string) ( $service['hours'] ?? '' ),
			),
			array(
				'icon'  => 'phone',
				'value' => $airport ? '' : mytaxi_phone_text( $phone ),
				'label' => __( 'call, WhatsApp or Viber', 'mytaxi-generatepress' ),
			),
		),
	)
);

if ( $airport ) {
	get_template_part(
		'template-parts/route-tiles',
		null,
		array(
			'id'      => 'destinations',
			'routes'  => $routes,
			'heading' => sprintf( __( 'Destinations from %s', 'mytaxi-generatepress' ), $place ),
			'text'    => __( 'Prices are for the car, not per person. Where no price is shown, the team quotes it on request.', 'mytaxi-generatepress' ),
		)
	);

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
			'items' => mytaxi_vehicle_items( $service, $from ),
			'link'  => '#destinations',
		)
	);

	get_template_part(
		'template-parts/steps',
		null,
		array_merge(
			mytaxi_transfer_steps(),
			array( 'image_id' => (int) ( $service['vehicle_id'] ?? 0 ) )
		)
	);

	get_template_part( 'template-parts/travel-needs', null, array( 'url' => '#destinations', 'label' => __( 'Choose your destination', 'mytaxi-generatepress' ) ) );

	get_template_part(
		'template-parts/request-section',
		null,
		array(
			'service' => $service,
			'heading' => __( 'Going somewhere else?', 'mytaxi-generatepress' ),
			'text'    => sprintf( __( 'Send a request for any pickup or drop-off from %s. The team replies with a price and confirms the details with you.', 'mytaxi-generatepress' ), $place ),
		)
	);
} else {
	get_template_part(
		'template-parts/photo-row',
		null,
		array(
			'ids'     => (array) ( $service['gallery'] ?? array() ),
			'heading' => __( 'Our cars', 'mytaxi-generatepress' ),
			'alt'     => get_the_title(),
		)
	);

	get_template_part(
		'template-parts/request-section',
		null,
		array(
			'service' => $service,
			'heading' => __( 'Request a local taxi', 'mytaxi-generatepress' ),
			'text'    => __( 'Tell us your pickup, destination, and time. This sends a request; the team confirms the car with you.', 'mytaxi-generatepress' ),
		)
	);

	get_template_part( 'template-parts/travel-needs', null, array( 'label' => __( 'Request a taxi', 'mytaxi-generatepress' ) ) );

	$transfer = mytaxi_transfer_for_local( $service );
	if ( $transfer ) {
		get_template_part(
			'template-parts/route-tiles',
			null,
			array(
				'routes'     => array( $transfer ),
				'heading'    => sprintf( __( 'Arriving at %s?', 'mytaxi-generatepress' ), (string) $transfer['origin'] ),
				'text'       => sprintf( __( 'Arrange your transfer to %s before you fly.', 'mytaxi-generatepress' ), (string) $transfer['destination'] ),
				'more_url'   => home_url( '/airport-transfers/' ),
				'more_label' => __( 'All airport transfers', 'mytaxi-generatepress' ),
			)
		);
	}
}

if ( $airport ) {
	get_template_part(
		'template-parts/photo-row',
		null,
		array(
			'ids'     => (array) ( $service['gallery'] ?? array() ),
			'heading' => __( 'Photos', 'mytaxi-generatepress' ),
			'alt'     => get_the_title(),
		)
	);
}

if ( get_the_content() ) {
	echo '<section class="mt-st-section mt-prose">';
	the_content();
	echo '</section>';
}

if ( ! empty( $contacts['gbp_url'] ) ) {
	echo '<p class="mt-st-section"><a href="' . esc_url( $contacts['gbp_url'] ) . '">' . esc_html__( 'View this service on Google', 'mytaxi-generatepress' ) . '</a></p>';
}

get_template_part( 'template-parts/review-sources', null, array( 'reviews' => $reviews ) );
mytaxi_faqs( $service['faqs'] ?? array() );

if ( $airport ) {
	mytaxi_cta(
		(string) ( $service['cta'] ?? '' ) ?: get_the_title(),
		__( 'Choose your destination, or send a request for any other address.', 'mytaxi-generatepress' ),
		'#request',
		__( 'Request a quote', 'mytaxi-generatepress' )
	);
} elseif ( ! empty( $contacts['phone_href'] ) ) {
	mytaxi_cta(
		(string) ( $service['cta'] ?? '' ) ?: get_the_title(),
		trim( (string) ( $service['hours'] ?? '' ) . ' ' . mytaxi_phone_text( $phone ) ),
		(string) $contacts['phone_href'],
		__( 'Call now', 'mytaxi-generatepress' )
	);
}

mytaxi_close();
