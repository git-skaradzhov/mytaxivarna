<?php
/**
 * Shared header, footer, and mobile actions.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp',
	static function () {
		remove_action( 'generate_header', 'generate_construct_header' );
		remove_action( 'generate_after_header', 'generate_featured_page_header', 10 );
		remove_action( 'generate_before_content', 'generate_featured_page_header_inside_single', 10 );
		remove_action( 'generate_after_header', 'generate_add_navigation_after_header', 5 );
		remove_action( 'generate_before_header', 'generate_add_navigation_before_header', 5 );
		remove_action( 'generate_after_header_content', 'generate_add_navigation_float_right', 5 );
		remove_action( 'generate_footer', 'generate_construct_footer_widgets', 5 );
		remove_action( 'generate_footer', 'generate_construct_footer' );
	}
);

add_action( 'generate_header', 'mytaxi_header' );
add_action( 'generate_footer', 'mytaxi_footer' );
add_action( 'wp_footer', 'mytaxi_mobile_bar' );

/**
 * Site header used on every public page.
 */
function mytaxi_header() {
	$items = mytaxi_nav_items();
	echo '<header class="mt-header"><div class="mt-wrap mt-header__bar">';
	mytaxi_brand();
	echo '<button class="mt-nav-toggle" type="button" aria-expanded="false" aria-controls="mt-nav" data-nav-toggle>';
	echo '<span class="mt-nav-toggle__bars" aria-hidden="true"></span>';
	echo '<span class="screen-reader-text">' . esc_html__( 'Menu', 'mytaxi-generatepress' ) . '</span>';
	echo '</button>';
	echo '<nav id="mt-nav" class="mt-nav" data-nav aria-label="' . esc_attr__( 'Primary', 'mytaxi-generatepress' ) . '">';
	foreach ( $items as $item ) {
		echo '<a class="mt-nav__link' . ( $item['current'] ? ' is-current' : '' ) . '" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
	}
	echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact us', 'mytaxi-generatepress' ) . '</a>';
	echo '</nav></div></header>';
}

/**
 * Primary links. Each item is its own page, including the records under it.
 *
 * @return array<string,array{label:string,url:string,current:bool}>
 */
function mytaxi_nav_items() {
	$service = mytaxi_current_service();
	$type    = (string) ( $service['type'] ?? '' );
	return array(
		'local'   => array(
			'label'   => __( 'Local taxi', 'mytaxi-generatepress' ),
			'url'     => home_url( '/local-taxi/' ),
			'current' => is_page( 'local-taxi' ) || 'local_taxi' === $type,
		),
		'airport' => array(
			'label'   => __( 'Airport transfers', 'mytaxi-generatepress' ),
			'url'     => home_url( '/airport-transfers/' ),
			'current' => is_page( 'airport-transfers' ) || ( 'airport_transfer' === $type && ! is_singular( 'mytaxi_route' ) ),
		),
		'routes'  => array(
			'label'   => __( 'Routes', 'mytaxi-generatepress' ),
			'url'     => home_url( '/routes/' ),
			'current' => is_page( 'routes' ) || is_singular( 'mytaxi_route' ),
		),
		'contact' => array(
			'label'   => __( 'Contact', 'mytaxi-generatepress' ),
			'url'     => home_url( '/contact/' ),
			'current' => is_page( 'contact' ),
		),
	);
}

/**
 * Logo used in the header and footer.
 */
function mytaxi_brand() {
	$src = get_stylesheet_directory_uri() . '/assets/images/logo.png';
	echo '<a class="mt-brand" href="' . esc_url( home_url( '/' ) ) . '">';
	echo '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="896" height="256">';
	echo '</a>';
}

/**
 * Header action for the current page.
 *
 * @return array{url:string,label:string,class:string}
 */
function mytaxi_header_cta() {
	if ( is_singular( array( 'mytaxi_service', 'mytaxi_route' ) ) ) {
		return array(
			'url'   => '#request',
			'label' => __( 'Request', 'mytaxi-generatepress' ),
			'class' => 'mt-btn--yellow',
		);
	}
	return array(
		'url'   => home_url( '/contact/' ),
		'label' => __( 'Contact us', 'mytaxi-generatepress' ),
		'class' => is_front_page() ? 'mt-btn--ghost' : 'mt-btn--yellow',
	);
}

/**
 * Dropdown of services.
 *
 * @param string                           $label Group label.
 * @param array<int,array<string,mixed>>   $services Services.
 * @param bool                             $current Whether this group is the current page.
 */
function mytaxi_nav_group( $label, array $services, $current ) {
	if ( ! $services ) {
		return;
	}
	echo '<details class="mt-drop">';
	echo '<summary class="' . ( $current ? 'is-current' : '' ) . '">' . esc_html( $label ) . '</summary><ul>';
	foreach ( $services as $service ) {
		$here = is_singular( 'mytaxi_service' ) && (int) get_the_ID() === (int) $service['id'];
		echo '<li><a href="' . esc_url( $service['url'] ) . '"' . ( $here ? ' aria-current="page"' : '' ) . '>' . esc_html( $service['title'] ) . '</a></li>';
	}
	echo '</ul></details>';
}

/**
 * Dropdown of homepage routes.
 *
 * @param array<int,array<string,mixed>> $routes Routes.
 */
function mytaxi_nav_routes( array $routes ) {
	if ( ! $routes ) {
		return;
	}
	$current = is_singular( 'mytaxi_route' );
	echo '<details class="mt-drop">';
	echo '<summary class="' . ( $current ? 'is-current' : '' ) . '">' . esc_html__( 'Routes', 'mytaxi-generatepress' ) . '</summary><ul>';
	foreach ( $routes as $route ) {
		$here  = $current && (int) get_the_ID() === (int) $route['id'];
		$label = trim( (string) $route['origin'] . ' → ' . (string) $route['destination'] );
		echo '<li><a href="' . esc_url( $route['url'] ) . '"' . ( $here ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul></details>';
}

/**
 * Footer built from the same service and route records.
 */
function mytaxi_footer() {
	$groups  = mytaxi_service_groups();
	$company = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::company() : array();
	echo '<div class="mt-footer"><div class="mt-wrap">';
	echo '<div class="mt-footer__bar">';
	mytaxi_brand();
	echo '<nav class="mt-footer__nav" aria-label="' . esc_attr__( 'Footer', 'mytaxi-generatepress' ) . '">';
	foreach ( mytaxi_nav_items() as $item ) {
		echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
	}
	echo '</nav></div>';
	if ( ! empty( $company['legal_name'] ) || ! empty( $company['address'] ) ) {
		echo '<p class="mt-footer__note">';
		echo esc_html( trim( ( $company['legal_name'] ?? '' ) . ( ! empty( $company['address'] ) ? ' · ' . $company['address'] : '' ) ) );
		echo '</p>';
	}
	echo '<ul class="mt-footer__phones">';
	foreach ( array_merge( $groups['local'], $groups['airport'] ) as $service ) {
		echo '<li><a href="' . esc_url( $service['url'] ) . '">' . esc_html( mytaxi_place_name( $service['title'] ) ) . '</a>';
		if ( ! empty( $service['contacts']['phone_href'] ) ) {
			echo ' <a href="' . esc_url( $service['contacts']['phone_href'] ) . '">' . esc_html( mytaxi_phone_text( $service['contacts']['phone'] ) ) . '</a>';
		}
		echo '</li>';
	}
	echo '</ul>';
	echo '<p class="mt-credits">';
	$credits = array();
	foreach ( mytaxi_photo_credits() as $credit ) {
		$credits[] = '<a href="' . esc_url( $credit['url'] ) . '">' . esc_html( $credit['label'] . ', ' . $credit['author'] . ', ' . $credit['license'] ) . '</a>';
	}
	echo wp_kses_post( implode( ' · ', $credits ) );
	echo '</p></div></div>';
}

/**
 * Fixed mobile actions. The homepage does not borrow a service phone.
 */
function mytaxi_mobile_bar() {
	if ( is_admin() ) {
		return;
	}
	$service = mytaxi_current_service();
	echo '<div class="mt-mobile-bar" data-mobile-bar>';
	if ( $service && ! empty( $service['contacts']['phone_href'] ) ) {
		echo '<a class="mt-btn mt-btn--yellow" href="' . esc_url( $service['contacts']['phone_href'] ) . '">';
		mytaxi_icon( 'phone' );
		echo '<span>' . esc_html__( 'Call', 'mytaxi-generatepress' ) . '</span></a>';
	} elseif ( ! $service ) {
		echo '<a class="mt-btn mt-btn--yellow" href="' . esc_url( home_url( '/local-taxi/' ) ) . '">';
		mytaxi_icon( 'car' );
		echo '<span>' . esc_html__( 'Choose service', 'mytaxi-generatepress' ) . '</span></a>';
	}
	if ( $service && ! empty( $service['contacts']['whatsapp_url'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $service['contacts']['whatsapp_url'] ) . '">WhatsApp</a>';
	}
	if ( $service ) {
		echo '<a class="mt-btn mt-btn--ghost" href="#request">';
		mytaxi_icon( 'plane' );
		echo '<span>' . esc_html__( 'Request', 'mytaxi-generatepress' ) . '</span></a>';
	} else {
		echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( home_url( '/airport-transfers/' ) ) . '">';
		mytaxi_icon( 'plane' );
		echo '<span>' . esc_html__( 'Transfer request', 'mytaxi-generatepress' ) . '</span></a>';
	}
	echo '</div>';
}
