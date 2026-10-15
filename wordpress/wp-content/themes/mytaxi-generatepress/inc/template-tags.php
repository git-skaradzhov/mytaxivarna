<?php
/**
 * Template helpers.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Open the GeneratePress content wrappers.
 *
 * @param string              $hero Full-width template part printed before the page container.
 * @param array<string,mixed> $args Arguments for that template part.
 */
function mytaxi_open( $hero = '', array $args = array() ) {
	get_header();
	echo '<div ';
	generate_do_attr( 'content' );
	echo '><main ';
	generate_do_attr( 'main' );
	echo '>';
	if ( '' !== $hero ) {
		get_template_part( $hero, null, $args );
	}
	echo '<div class="mt-wrap">';
}

/**
 * Close the content wrappers.
 */
function mytaxi_close() {
	echo '</div></main></div>';
	get_footer();
}

/**
 * Service attached to the current view.
 *
 * @return array<string,mixed>
 */
function mytaxi_current_service() {
	if ( ! class_exists( '\MyTaxi\Core\Data' ) ) {
		return array();
	}
	if ( is_singular( 'mytaxi_service' ) ) {
		return \MyTaxi\Core\Data::service( get_the_ID() );
	}
	if ( is_singular( 'mytaxi_route' ) ) {
		$route = \MyTaxi\Core\Data::route( get_the_ID() );
		return $route['service'] ?? array();
	}
	return array();
}

/**
 * Services split by type.
 *
 * @return array{local:array<int,array<string,mixed>>,airport:array<int,array<string,mixed>>}
 */
function mytaxi_service_groups() {
	$groups = array(
		'local'   => array(),
		'airport' => array(),
	);
	if ( ! class_exists( '\MyTaxi\Core\Data' ) ) {
		return $groups;
	}
	foreach ( \MyTaxi\Core\Data::home_services() as $service ) {
		$key = 'airport_transfer' === ( $service['type'] ?? '' ) ? 'airport' : 'local';
		$groups[ $key ][] = $service;
	}
	return $groups;
}

/**
 * Airport route whose destination matches a local service.
 *
 * @param array<string,mixed> $service Local service.
 * @return array<string,mixed>
 */
function mytaxi_transfer_for_local( array $service ) {
	if ( 'local_taxi' !== ( $service['type'] ?? '' ) || ! class_exists( '\MyTaxi\Core\Data' ) ) {
		return array();
	}
	$haystack = strtolower( (string) $service['title'] . ' ' . implode( ' ', $service['areas'] ?? array() ) );
	foreach ( mytaxi_service_groups()['airport'] as $airport ) {
		foreach ( \MyTaxi\Core\Data::routes_for_service( (int) $airport['id'] ) as $route ) {
			$destination = strtolower( trim( (string) ( $route['destination'] ?? '' ) ) );
			if ( '' !== $destination && str_contains( $haystack, $destination ) ) {
				return $route;
			}
		}
	}
	return array();
}

/**
 * Theme photos used when a record has no featured image.
 *
 * @return array<string,array{file:string,alt:string}>
 */
function mytaxi_theme_photos() {
	return array(
		'home'                         => array(
			'file' => 'coast.jpg',
			'alt'  => __( 'Black Sea coast at Albena', 'mytaxi-generatepress' ),
		),
		'taxi-golden-sands'            => array(
			'file' => 'golden-sands.jpg',
			'alt'  => __( 'Golden Sands beach', 'mytaxi-generatepress' ),
		),
		'varna-airport-to-golden-sands' => array(
			'file' => 'golden-sands.jpg',
			'alt'  => __( 'Golden Sands beach', 'mytaxi-generatepress' ),
		),
		'taxi-albena'                  => array(
			'file' => 'albena.jpg',
			'alt'  => __( 'Albena beach', 'mytaxi-generatepress' ),
		),
		'varna-airport-to-albena'      => array(
			'file' => 'albena.jpg',
			'alt'  => __( 'Albena beach', 'mytaxi-generatepress' ),
		),
		'taxi-kranevo'                 => array(
			'file' => 'kranevo.jpg',
			'alt'  => __( 'Kranevo beach', 'mytaxi-generatepress' ),
		),
		'varna-airport-to-kranevo'     => array(
			'file' => 'kranevo.jpg',
			'alt'  => __( 'Kranevo beach', 'mytaxi-generatepress' ),
		),
		'varna-airport-to-balchik'     => array(
			'file' => 'balchik.jpg',
			'alt'  => __( 'Balchik coast', 'mytaxi-generatepress' ),
		),
		'varna-airport-transfers'      => array(
			'file' => 'varna-airport.jpg',
			'alt'  => __( 'Runway at Varna Airport', 'mytaxi-generatepress' ),
		),
		'sofia-airport-transfers'      => array(
			'file' => 'sofia-airport.jpg',
			'alt'  => __( 'Sofia Airport', 'mytaxi-generatepress' ),
		),
		'sofia-airport-to-sofia'       => array(
			'file' => 'sofia-airport.jpg',
			'alt'  => __( 'Sofia Airport', 'mytaxi-generatepress' ),
		),
	);
}

/**
 * Visible photo credits for the files shipped with the theme.
 *
 * @return array<int,array{label:string,author:string,license:string,url:string}>
 */
function mytaxi_photo_credits() {
	return array();
}

/**
 * Picture from the WordPress record, or the theme photo for that slug.
 *
 * @param string $slug Record slug or the home key.
 * @param int    $attachment_id Featured image.
 * @param string $fallback_alt Alt text for a media-library image.
 * @param bool   $eager Load immediately.
 */
function mytaxi_picture( $slug, $attachment_id, $fallback_alt, $eager = false ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id ) {
		mytaxi_image( $attachment_id, $eager ? 'mytaxi-hero' : 'mytaxi-card', $fallback_alt, $eager );
		return;
	}
	$photos = mytaxi_theme_photos();
	$photo  = $photos[ $slug ] ?? null;
	if ( ! $photo ) {
		return;
	}
	$path = get_stylesheet_directory() . '/assets/images/' . $photo['file'];
	if ( ! file_exists( $path ) ) {
		return;
	}
	mytaxi_theme_img( $photo['file'], $photo['alt'], $eager );
}

/**
 * URL for a route or service photo. Media Library wins over the theme file.
 *
 * @param string $slug Record slug.
 * @param int    $attachment_id Featured image.
 * @return string
 */
function mytaxi_photo_url( $slug, $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	$photos = mytaxi_theme_photos();
	$photo  = $photos[ $slug ] ?? null;
	if ( ! $photo ) {
		return '';
	}
	$path = get_stylesheet_directory() . '/assets/images/' . $photo['file'];
	if ( ! is_file( $path ) ) {
		return '';
	}
	return get_stylesheet_directory_uri() . '/assets/images/' . $photo['file'];
}

/**
 * Wide landscape behind a photo hero. Client photos are close-ups of the cars, so the hero uses scenery.
 *
 * @param string $slug Route slug or the home key.
 * @return string
 */
function mytaxi_hero_photo( $slug ) {
	$files = array(
		'home'                          => 'home.jpg',
		'varna-airport-to-golden-sands' => 'golden-sands.jpg',
		'varna-airport-to-albena'       => 'albena.jpg',
		'varna-airport-to-kranevo'      => 'kranevo.jpg',
		'varna-airport-to-balchik'      => 'balchik.jpg',
		'sofia-airport-to-sofia'        => 'sofia.jpg',
	);
	$file = $files[ (string) $slug ] ?? 'home.jpg';
	$webp = substr( $file, 0, -4 ) . '.webp';
	if ( is_file( get_stylesheet_directory() . '/assets/images/hero/' . $webp ) ) {
		$file = $webp;
	} elseif ( ! is_file( get_stylesheet_directory() . '/assets/images/hero/' . $file ) ) {
		return '';
	}
	return get_stylesheet_directory_uri() . '/assets/images/hero/' . $file;
}

/**
 * Wide landscape for a service or listing page.
 *
 * @param string $slug Service slug or page key.
 * @return string
 */
function mytaxi_page_photo( $slug ) {
	$map = array(
		'taxi-golden-sands'       => 'varna-airport-to-golden-sands',
		'taxi-albena'             => 'varna-airport-to-albena',
		'taxi-kranevo'            => 'varna-airport-to-kranevo',
		'sofia-airport-transfers' => 'sofia-airport-to-sofia',
		'local-taxi'              => 'varna-airport-to-albena',
	);
	return mytaxi_hero_photo( $map[ (string) $slug ] ?? 'home' );
}

/**
 * Euro amount without trailing zero cents.
 *
 * @param float $amount Amount.
 * @return string
 */
function mytaxi_money( $amount ) {
	$amount = (float) $amount;
	return '€' . ( floor( $amount ) === $amount ? (string) (int) $amount : number_format( $amount, 2, '.', '' ) );
}

/**
 * Lowest published route price, as "From €X". Empty when no route has a price.
 *
 * @param array<int,array<string,mixed>> $routes Routes.
 * @return string
 */
function mytaxi_routes_from( array $routes ) {
	$amounts = array();
	$demo = false;
	foreach ( $routes as $route ) {
		$price = (array) ( $route['price'] ?? array() );
		if ( ! empty( $price['offer'] ) && null !== ( $price['amount'] ?? null ) ) {
			$amounts[] = (float) $price['amount'];
			$demo = $demo || ! empty( $price['demo'] );
		}
	}
	if ( ! $amounts ) {
		return '';
	}
	return sprintf( __( 'From %s', 'mytaxi-generatepress' ), mytaxi_money( min( $amounts ) ) ) . ( $demo ? ' (demo)' : '' );
}

/**
 * Vehicle cards of one airport service.
 *
 * @param array<string,mixed> $service Airport service.
 * @param string              $price   Price text shown on each card.
 * @param bool                $place   Add the airport name to the card text.
 * @return array<int,array<string,mixed>>
 */
function mytaxi_vehicle_items( array $service, $price, $place = false ) {
	$seats = array(
		'vehicle_4_id' => __( 'up to 4 passengers', 'mytaxi-generatepress' ),
		'vehicle_id'   => __( 'up to 5 passengers', 'mytaxi-generatepress' ),
	);
	$items = array();
	foreach ( $seats as $key => $label ) {
		if ( empty( $service[ $key ] ) ) {
			continue;
		}
		$items[] = array(
			'id'    => (int) $service[ $key ],
			'title' => 'vehicle_4_id' === $key ? __( 'Car for up to 4', 'mytaxi-generatepress' ) : __( 'Car for up to 5', 'mytaxi-generatepress' ),
			'price' => (string) $price,
			'text'  => $place
				? mytaxi_place_name( (string) ( $service['title'] ?? '' ) ) . ' · ' . $label
				: ( 'vehicle_4_id' === $key
					? __( 'Private car for up to 4 passengers with standard luggage.', 'mytaxi-generatepress' )
					: __( 'Private car for up to 5 passengers. Tell us about larger luggage.', 'mytaxi-generatepress' ) ),
		);
	}
	return $items;
}

/**
 * Steps of an airport transfer request.
 *
 * @return array<string,mixed> Arguments for the steps template part.
 */
function mytaxi_transfer_steps() {
	return array(
		'heading' => __( 'Request your transfer in a few steps', 'mytaxi-generatepress' ),
		'text'    => __( 'Arrange the transfer before you fly. The team replies to confirm everything with you.', 'mytaxi-generatepress' ),
		'steps'   => array(
			array(
				'title' => __( 'Choose your route', 'mytaxi-generatepress' ),
				'text'  => __( 'Pick the airport and your destination.', 'mytaxi-generatepress' ),
			),
			array(
				'title' => __( 'Send your request', 'mytaxi-generatepress' ),
				'text'  => __( 'Flight arrival, passengers, and your hotel.', 'mytaxi-generatepress' ),
			),
			array(
				'title' => __( 'We confirm', 'mytaxi-generatepress' ),
				'text'  => __( 'The team confirms the price, the car, and the pickup with you.', 'mytaxi-generatepress' ),
			),
			array(
				'title' => __( 'Meet your driver', 'mytaxi-generatepress' ),
				'text'  => __( 'Your driver waits in Arrivals with a name sign.', 'mytaxi-generatepress' ),
			),
		),
	);
}

/**
 * "All set before you land" cards for one airport service. Varna theme photos are not reused for other airports.
 *
 * @param array<string,mixed> $service Airport service.
 * @return array<int,array<string,mixed>>
 */
function mytaxi_airport_cards( array $service ) {
	$varna   = 'varna-airport-transfers' === (string) ( $service['slug'] ?? '' );
	$gallery = array_values( array_diff( (array) ( $service['gallery'] ?? array() ), array( (int) ( $service['vehicle_id'] ?? 0 ), (int) ( $service['vehicle_4_id'] ?? 0 ) ) ) );
	return array(
		array(
			'image_id' => (int) ( $gallery[1] ?? ( $service['vehicle_4_id'] ?? 0 ) ),
			'file'     => $varna ? 'varna-vehicle.jpg' : '',
			'icon'     => 'card',
			'title'    => __( 'One price for the car', 'mytaxi-generatepress' ),
			'text'     => __( 'The price is per car, not per person. No prepayment: pay the driver in cash or by card.', 'mytaxi-generatepress' ),
		),
		array(
			'image_id' => (int) ( $service['image_id'] ?? 0 ),
			'file'     => $varna ? 'varna-airport.jpg' : '',
			'icon'     => 'sign',
			'title'    => __( 'Met in Arrivals', 'mytaxi-generatepress' ),
			'text'     => __( 'Your driver waits with a name sign. No extra charge if your flight is delayed.', 'mytaxi-generatepress' ),
		),
		array(
			'image_id' => (int) ( $gallery[0] ?? 0 ),
			'file'     => $varna ? 'varna-interior.jpg' : '',
			'icon'     => 'seat',
			'title'    => __( 'Whatever your trip needs', 'mytaxi-generatepress' ),
			'text'     => __( 'A free child seat when you ask for it in advance. Free cancellation.', 'mytaxi-generatepress' ),
		),
	);
}

/**
 * Navy call-to-action band.
 *
 * @param string $heading Heading.
 * @param string $text    Text.
 * @param string $url     Button URL.
 * @param string $label   Button label.
 * @param bool   $panel   Hide in the route request step.
 */
function mytaxi_cta( $heading, $text, $url, $label, $panel = false ) {
	echo '<section class="mt-st-cta"' . ( $panel ? ' data-panel="journey"' : '' ) . '><div>';
	echo '<h2>' . esc_html( $heading ) . '</h2>';
	if ( '' !== $text ) {
		echo '<p>' . esc_html( $text ) . '</p>';
	}
	echo '</div><a class="mt-btn mt-btn--yellow" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></section>';
}

/**
 * Review-source cards of several services, one card per profile URL.
 *
 * @param array<int,array<string,mixed>> $services Services.
 * @return array<int,array<string,string>>
 */
function mytaxi_review_sources( array $services ) {
	if ( ! class_exists( '\MyTaxi\Core\Data' ) ) {
		return array();
	}
	$out = array();
	foreach ( $services as $service ) {
		foreach ( \MyTaxi\Core\Data::review_sources( (int) ( $service['id'] ?? 0 ) ) as $review ) {
			$out[ $review['url'] ] = $review;
		}
	}
	return array_values( $out );
}

/**
 * Theme image used when a record has no Media Library photo.
 *
 * @param string $file File name inside assets/images.
 * @param string $alt Alt text.
 * @param bool   $eager Load immediately.
 */
function mytaxi_theme_img( $file, $alt, $eager = false ) {
	$file = ltrim( (string) $file, '/' );
	$path = get_stylesheet_directory() . '/assets/images/' . $file;
	if ( ! is_file( $path ) ) {
		return;
	}
	$size   = getimagesize( $path );
	$wide   = (int) ( $size[0] ?? 1600 );
	$high   = (int) ( $size[1] ?? 1067 );
	$dir    = get_stylesheet_directory_uri() . '/assets/images/';
	$base   = preg_replace( '/\.(jpe?g|png)$/i', '', $file );
	$src    = $dir . $file;
	$srcset = '';
	if ( is_file( get_stylesheet_directory() . '/assets/images/' . $base . '.webp' ) ) {
		$src = $dir . $base . '.webp';
		if ( is_file( get_stylesheet_directory() . '/assets/images/' . $base . '-800.webp' ) ) {
			$src    = $dir . $base . '-800.webp';
			$srcset = $dir . $base . '-800.webp 800w, ' . $dir . $base . '.webp ' . $wide . 'w';
		}
	}
	printf(
		'<img class="mt-img" src="%1$s"%2$s alt="%3$s" width="%4$d" height="%5$d" decoding="async"%6$s>',
		esc_url( $src ),
		$srcset ? ' srcset="' . esc_attr( $srcset ) . '" sizes="' . ( $eager ? '100vw' : '(min-width: 1100px) 520px, (min-width: 700px) 50vw, 100vw' ) . '"' : '',
		esc_attr( $alt ),
		$wide,
		$high,
		$eager ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"'
	);
}

/**
 * Inline style for a photo hero: the full photo, plus a smaller one for phones when it exists.
 *
 * @param string $url Photo URL from mytaxi_hero_photo().
 * @return string
 */
function mytaxi_hero_style( $url ) {
	$url = (string) $url;
	if ( '' === $url ) {
		return '';
	}
	$style = '--mt-hero:url(' . esc_url( $url ) . ')';
	$small = preg_replace( '/\.webp$/', '-768.webp', $url );
	if ( $small !== $url ) {
		$path = str_replace( get_stylesheet_directory_uri(), get_stylesheet_directory(), $small );
		if ( is_file( $path ) ) {
			$style .= ';--mt-hero-sm:url(' . esc_url( $small ) . ')';
		}
	}
	return ' style="' . esc_attr( $style ) . '"';
}

/**
 * Inline icon.
 *
 * @param string $name Icon name.
 */
function mytaxi_icon( $name ) {
	$icons = array(
		'car'      => '<path d="M3 13.5 4.6 8.8A2 2 0 0 1 6.5 7.4h11a2 2 0 0 1 1.9 1.4L21 13.5"/><path d="M5 16.5h14"/><circle cx="7.5" cy="16.5" r="1.4"/><circle cx="16.5" cy="16.5" r="1.4"/><path d="M4 13.5h16"/>',
		'plane'    => '<path d="M2.5 12.5 21 4.5l-4.2 14.2-4.3-4.4-4.5 2.2 1.2-4.6z"/><path d="M12.5 14.3 10.2 21"/>',
		'phone'    => '<path d="M8 3.5h2.2l1.1 3.2-1.6 1a12 12 0 0 0 5.6 5.6l1-1.6 3.2 1.1V15a2 2 0 0 1-2.2 2A14.5 14.5 0 0 1 6 5.7 2 2 0 0 1 8 3.5z"/>',
		'pin'      => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.1"/>',
		'chevron'  => '<path d="m9 6 6 6-6 6"/>',
		'arrow'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
		'check'    => '<circle cx="12" cy="12" r="9"/><path d="m8 12.2 2.6 2.6L16 9.4"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'card'     => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/>',
		'sign'     => '<rect x="3.5" y="5" width="17" height="10" rx="1.5"/><path d="M8 9.5h8"/><path d="M12 15v4"/><path d="M8.5 19h7"/>',
		'seat'     => '<path d="M8 4.5h4a3 3 0 0 1 3 3V13H8z"/><path d="M6 13h11l1.5 4.5H7.5z"/><path d="M9 17.5 8 20.5"/><path d="M16 17.5l1 3"/>',
		'bag'      => '<rect x="5" y="7.5" width="14" height="12" rx="2"/><path d="M9 7.5V5.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M9 11v5"/><path d="M15 11v5"/>',
		'note'     => '<path d="M6 3.5h9l3 3v14H6z"/><path d="M9 10h6"/><path d="M9 13.5h6"/><path d="M9 17h4"/>',
		'users'    => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M15.5 6a3 3 0 0 1 0 5.6"/><path d="M17 14.2A5.5 5.5 0 0 1 20.5 19"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return;
	}
	echo '<svg class="mt-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
}

/**
 * Guarantees under the hero search. Each line is one of the airport services' own benefits.
 *
 * @param string $extra Extra class.
 */
function mytaxi_trust_row( $extra = '' ) {
	$items = array(
		'check' => __( 'Free cancellation', 'mytaxi-generatepress' ),
		'clock' => __( 'No charge for flight delays', 'mytaxi-generatepress' ),
		'card'  => __( 'No prepayment', 'mytaxi-generatepress' ),
		'phone' => __( 'Open 24/7, all year', 'mytaxi-generatepress' ),
	);
	echo '<ul class="mt-trust' . ( $extra ? ' ' . esc_attr( $extra ) : '' ) . '">';
	foreach ( $items as $icon => $label ) {
		echo '<li>';
		mytaxi_icon( $icon );
		echo '<span>' . esc_html( $label ) . '</span></li>';
	}
	echo '</ul>';
}

/**
 * Short place name used on cards.
 *
 * @param string $title Record title.
 * @return string
 */
function mytaxi_place_name( $title ) {
	$title = trim( (string) $title );
	$title = (string) preg_replace( '/^Taxi\s+/i', '', $title );
	$title = (string) preg_replace( '/\s+Transfers$/i', '', $title );
	return $title;
}

/**
 * First sentence for a card, without the sample-review marker.
 *
 * @param string $text Source text.
 * @return string
 */
function mytaxi_card_text( $text ) {
	$text = trim( mytaxi_strip_review_notes( $text ) );
	if ( strlen( $text ) <= 120 ) {
		return $text;
	}
	$parts = preg_split( '/(?<=[.!?])\s+/', $text );
	$line  = trim( (string) ( $parts[0] ?? '' ) );
	if ( strlen( $line ) > 120 ) {
		$line = (string) preg_replace( '/\s+\S*$/', '', substr( $line, 0, 117 ) ) . '…';
	}
	return $line;
}

/**
 * Readable phone grouping. The link still uses the stored number.
 *
 * @param string $phone Stored phone.
 * @return string
 */
function mytaxi_phone_text( $phone ) {
	$phone  = trim( (string) $phone );
	$digits = preg_replace( '/\D+/', '', $phone );
	if ( str_starts_with( $phone, '+359' ) && 12 === strlen( (string) $digits ) ) {
		return '+359 ' . substr( $digits, 3, 3 ) . ' ' . substr( $digits, 6, 3 ) . ' ' . substr( $digits, 9, 3 );
	}
	return $phone;
}

/**
 * Breadcrumbs.
 */
function mytaxi_breadcrumbs() {
	if ( ! class_exists( '\MyTaxi\Core\Data' ) || is_front_page() ) {
		return;
	}
	$crumbs = \MyTaxi\Core\Data::breadcrumbs();
	if ( count( $crumbs ) < 2 ) {
		return;
	}
	echo '<nav class="mt-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'mytaxi-generatepress' ) . '"><ol>';
	$last = count( $crumbs ) - 1;
	foreach ( $crumbs as $index => $crumb ) {
		echo '<li>';
		if ( $index === $last ) {
			echo '<span aria-current="page">' . esc_html( $crumb['name'] ) . '</span>';
		} else {
			echo '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['name'] ) . '</a>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Call, WhatsApp, and Viber for one service. Empty channels stay hidden.
 *
 * @param array<string,mixed> $service Service.
 * @param string              $call_label Button label when a phone exists.
 */
function mytaxi_contact_actions( array $service, $call_label = '' ) {
	$contacts = $service['contacts'] ?? array();
	if ( ! $contacts ) {
		return;
	}
	echo '<div class="mt-actions">';
	if ( ! empty( $contacts['phone_href'] ) ) {
		$label = $call_label ? $call_label : __( 'Call', 'mytaxi-generatepress' ) . ' ' . mytaxi_phone_text( $contacts['phone'] );
		echo '<a class="mt-btn mt-btn--yellow" href="' . esc_url( $contacts['phone_href'] ) . '">';
		mytaxi_icon( 'phone' );
		echo '<span>' . esc_html( $label ) . '</span></a>';
	}
	if ( ! empty( $contacts['whatsapp_url'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $contacts['whatsapp_url'] ) . '">WhatsApp</a>';
	}
	if ( ! empty( $contacts['viber_url'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $contacts['viber_url'] ) . '">Viber</a>';
	}
	echo '</div>';
}

/**
 * WhatsApp, call, and Viber for one service. WhatsApp is the primary button.
 *
 * @param array<string,mixed> $service Service.
 */
function mytaxi_contact_buttons( array $service ) {
	$contacts = $service['contacts'] ?? array();
	if ( ! empty( $contacts['whatsapp_url'] ) ) {
		echo '<a class="mt-btn mt-btn--yellow mt-btn--channel" href="' . esc_url( $contacts['whatsapp_url'] ) . '">WhatsApp</a>';
	}
	if ( ! empty( $contacts['phone_href'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost mt-btn--channel" href="' . esc_url( $contacts['phone_href'] ) . '">';
		mytaxi_icon( 'phone' );
		echo '<span>' . esc_html__( 'Call', 'mytaxi-generatepress' ) . '</span></a>';
	}
	if ( ! empty( $contacts['viber_url'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost mt-btn--channel" href="' . esc_url( $contacts['viber_url'] ) . '">Viber</a>';
	}
	mytaxi_location_button( $service );
}

/**
 * WhatsApp first, then Viber and the phone number, as compact links.
 *
 * @param array<string,mixed> $service Service.
 */
function mytaxi_channel_links( array $service ) {
	$contacts = (array) ( $service['contacts'] ?? array() );
	echo '<span class="mt-channels">';
	if ( ! empty( $contacts['whatsapp_url'] ) ) {
		echo '<a class="mt-btn mt-btn--yellow mt-channels__main" href="' . esc_url( $contacts['whatsapp_url'] ) . '">WhatsApp</a>';
	}
	if ( ! empty( $contacts['viber_url'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $contacts['viber_url'] ) . '">Viber</a>';
	}
	if ( ! empty( $contacts['phone_href'] ) ) {
		echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $contacts['phone_href'] ) . '">';
		mytaxi_icon( 'phone' );
		echo '<span>' . esc_html( mytaxi_phone_text( (string) $contacts['phone'] ) ) . '</span></a>';
	}
	echo '</span>';
}

/**
 * Direct contact line next to a request form. Hidden when the form itself already offers only direct contact.
 *
 * @param array<string,mixed> $service Service.
 * @param string              $lead Lead text.
 * @param string              $panel Route step the line belongs to.
 */
function mytaxi_direct_line( array $service, $lead, $panel = '' ) {
	if ( empty( $service['contacts'] ) || ( class_exists( '\MyTaxi\Core\ContactForm' ) && ! \MyTaxi\Core\ContactForm::can_send() ) ) {
		return;
	}
	echo '<div class="mt-direct" data-direct' . ( $panel ? ' data-panel="' . esc_attr( $panel ) . '"' : '' ) . '>';
	echo '<p>' . esc_html( $lead ) . '</p>';
	mytaxi_channel_links( $service );
	echo '</div>';
}

/**
 * Whether a route price is a confirmed fixed amount that may be shown as a price.
 *
 * @param array<string,mixed> $price Output of Price::present().
 * @return bool
 */
function mytaxi_fixed_price( array $price ) {
	return ! empty( $price['offer'] ) && 'fixed' === ( $price['type'] ?? '' ) && null !== ( $price['amount'] ?? null );
}

/**
 * Price lines for the request summary. The browser redraws them from the same rules.
 *
 * @param array<string,mixed> $price Output of Price::present().
 * @param bool                $return Return requested.
 */
function mytaxi_quote( array $price, $return ) {
	if ( ! class_exists( '\MyTaxi\Core\Booking' ) ) {
		return;
	}
	$quote = \MyTaxi\Core\Booking::quote( $price, (bool) $return );
	echo $quote['confirmed'] ? '<dl data-confirmed>' : '<dl>';
	foreach ( $quote['lines'] as $line ) {
		echo '<div><dt>' . esc_html( $line['label'] ) . '</dt><dd>' . esc_html( $line['value'] ) . '</dd></div>';
	}
	echo '</dl>';
	if ( '' !== $quote['note'] ) {
		echo '<p>' . esc_html( $quote['note'] ) . '</p>';
	}
}

/**
 * Kranevo sends the visitor location to that service's own WhatsApp number.
 *
 * @param array<string,mixed> $service Service.
 */
function mytaxi_location_button( array $service ) {
	if ( 'taxi-kranevo' !== (string) ( $service['slug'] ?? '' ) ) {
		return;
	}
	$number = ltrim( (string) ( $service['contacts']['whatsapp'] ?? '' ), '+' );
	if ( '' === $number ) {
		return;
	}
	echo '<button type="button" class="mt-btn mt-btn--ghost" data-send-location data-whatsapp="' . esc_attr( $number ) . '">';
	mytaxi_icon( 'pin' );
	echo '<span>' . esc_html__( 'Send location', 'mytaxi-generatepress' ) . '</span></button>';
}

/**
 * Responsive image with a stable frame.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size Image size.
 * @param string $fallback_alt Alt text when the media item has none.
 * @param bool   $eager Load immediately.
 */
function mytaxi_image( $attachment_id, $size, $fallback_alt, $eager = false ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id ) {
		return;
	}
	$meta = wp_get_attachment_metadata( $attachment_id );
	$wide = (int) ( $meta['width'] ?? 0 );
	$high = (int) ( $meta['height'] ?? 0 );
	$alt  = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$sizes = $eager
		? '(min-width: 1024px) 576px, calc(100vw - 48px)'
		: '(min-width: 1024px) 384px, calc(100vw - 48px)';
	$attr = array(
		'class' => 'mt-img' . ( $high > $wide ? ' mt-img--tall' : '' ),
		'sizes' => $sizes,
	);
	if ( '' === $alt ) {
		$attr['alt'] = $fallback_alt;
	}
	if ( $eager ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
	} else {
		$attr['loading'] = 'lazy';
	}
	$html = wp_get_attachment_image( $attachment_id, $size, false, $attr );
	$webp = (string) get_post_meta( $attachment_id, '_mytaxi_webp', true );
	if ( '' === $webp ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	$uploads = wp_get_upload_dir();
	$rev     = rawurlencode( (string) get_post_meta( $attachment_id, '_mytaxi_revision', true ) );
	$srcset  = esc_url( $uploads['baseurl'] . '/' . $webp . ( '' !== $rev ? '?rev=' . $rev : '' ) ) . ' ' . max( 1, (int) get_post_meta( $attachment_id, '_mytaxi_webp_width', true ) ) . 'w';
	$card    = (string) get_post_meta( $attachment_id, '_mytaxi_webp_card', true );
	if ( '' !== $card ) {
		$srcset .= ', ' . esc_url( $uploads['baseurl'] . '/' . $card . ( '' !== $rev ? '?rev=' . $rev : '' ) ) . ' ' . max( 1, (int) get_post_meta( $attachment_id, '_mytaxi_webp_card_width', true ) ) . 'w';
	}
	echo '<picture><source type="image/webp" srcset="' . $srcset . '" sizes="' . esc_attr( $sizes ) . '">' . $html . '</picture>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Editable public line.
 *
 * @param string $key Line key.
 * @return string
 */
function mytaxi_line( $key ) {
	if ( ! class_exists( '\MyTaxi\Core\Data' ) ) {
		return '';
	}
	return \MyTaxi\Core\Data::line( $key );
}

/**
 * Plain text as paragraphs.
 *
 * @param string $text Text.
 */
function mytaxi_text( $text ) {
	$text = mytaxi_strip_review_notes( $text );
	if ( '' === $text ) {
		return;
	}
	echo wp_kses_post( wpautop( esc_html( $text ) ) );
}

/**
 * Drop the review-only sentences from public copy.
 *
 * @param string $text Source text.
 * @return string
 */
function mytaxi_strip_review_notes( $text ) {
	$text = (string) preg_replace( '/\s*Sample text for review\.(?:\s+[^.]+?\.)?/iu', '', (string) $text );
	$text = (string) preg_replace( '/<p>(?:\s|&nbsp;)*<\/p>/i', '', $text );
	return trim( $text );
}

add_filter( 'the_content', 'mytaxi_strip_review_notes' );

/**
 * FAQ list.
 *
 * @param array<int,array<string,string>> $faqs FAQs.
 */
function mytaxi_faqs( array $faqs ) {
	if ( ! $faqs ) {
		return;
	}
	echo '<section class="mt-section" id="questions"><h2>' . esc_html__( 'Frequently asked questions', 'mytaxi-generatepress' ) . '</h2><div class="mt-faqs">';
	foreach ( $faqs as $faq ) {
		echo '<details class="mt-faq"><summary>' . esc_html( $faq['question'] ) . '</summary>';
		mytaxi_text( $faq['answer'] );
		echo '</details>';
	}
	echo '</div></section>';
}
