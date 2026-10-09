<?php
/**
 * Template helpers.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Open the GeneratePress content wrappers.
 */
function mytaxi_open() {
	get_header();
	echo '<div ';
	generate_do_attr( 'content' );
	echo '><main ';
	generate_do_attr( 'main' );
	echo '><div class="mt-wrap">';
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
 * Inner-page hero in the same composition as a service page.
 *
 * @param string                                    $kicker Short label above the title.
 * @param string                                    $photo  Theme photo key.
 * @param array<int,array{label:string,url:string,style?:string,icon?:string}> $actions Buttons.
 */
function mytaxi_page_hero( $kicker, $photo, array $actions = array() ) {
	echo '<header class="mt-hero">';
	echo '<div class="mt-hero__copy">';
	echo '<p class="mt-kicker">' . esc_html( $kicker ) . '</p>';
	echo '<h1>' . esc_html( get_the_title() ) . '</h1>';
	if ( get_the_content() ) {
		echo '<div class="mt-prose">';
		the_content();
		echo '</div>';
	}
	if ( $actions ) {
		echo '<div class="mt-actions">';
		foreach ( $actions as $action ) {
			$style = $action['style'] ?? 'ghost';
			echo '<a class="mt-btn mt-btn--' . esc_attr( $style ) . '" href="' . esc_url( $action['url'] ) . '">';
			if ( ! empty( $action['icon'] ) ) {
				mytaxi_icon( $action['icon'] );
			}
			echo '<span>' . esc_html( $action['label'] ) . '</span>';
			mytaxi_icon( 'arrow' );
			echo '</a>';
		}
		echo '</div>';
	}
	echo '</div>';
	echo '<div class="mt-hero__media">';
	mytaxi_picture( $photo, (int) get_post_thumbnail_id(), get_the_title(), true );
	echo '</div></header>';
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
	return array(
		array(
			'label'   => 'Albena coast',
			'author'  => 'karel291',
			'license' => 'CC BY 3.0',
			'url'     => 'https://commons.wikimedia.org/wiki/File:Albena,_Bulgaria_-_panoramio_(4).jpg',
		),
		array(
			'label'   => 'Albena beach',
			'author'  => 'bdmundo.com',
			'license' => 'CC BY-SA 2.0',
			'url'     => 'https://commons.wikimedia.org/wiki/File:Albena_Resort,_Bulgaria_(18552467973).jpg',
		),
		array(
			'label'   => 'Golden Sands',
			'author'  => 'Avishai Teicher',
			'license' => 'Public domain',
			'url'     => 'https://commons.wikimedia.org/wiki/File:Golden_sands_beach_Varna,_Bulgaria.jpg',
		),
		array(
			'label'   => 'Kranevo',
			'author'  => 'Dziadzik',
			'license' => 'CC BY-SA 3.0',
			'url'     => 'https://commons.wikimedia.org/wiki/File:Plazawkraniewo.JPG',
		),
		array(
			'label'   => 'Balchik',
			'author'  => 'Ali Burçin Titizel',
			'license' => 'CC BY 2.0',
			'url'     => 'https://commons.wikimedia.org/wiki/File:Balchik_coast,_Bulgaria.jpg',
		),
		array(
			'label'   => 'Varna Airport',
			'author'  => 'Meliha Kasacheva',
			'license' => 'CC BY-SA 3.0',
			'url'     => 'https://commons.wikimedia.org/wiki/File:VAR_RWY.jpg',
		),
		array(
			'label'   => 'Sofia Airport',
			'author'  => 'Oleg Yunakov',
			'license' => 'CC BY-SA 4.0',
			'url'     => 'https://commons.wikimedia.org/wiki/File:Sofia_Airport_(SOF)_in_Bulgaria.jpg',
		),
	);
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
		mytaxi_image( $attachment_id, 'mytaxi-card', $fallback_alt, $eager );
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
	printf(
		'<img class="mt-img" src="%1$s" alt="%2$s" width="1600" height="1067" decoding="async"%3$s>',
		esc_url( get_stylesheet_directory_uri() . '/assets/images/' . $photo['file'] ),
		esc_attr( $photo['alt'] ),
		$eager ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"'
	);
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
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return;
	}
	echo '<svg class="mt-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
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
	$text = mytaxi_strip_review_notes( $text );
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
	$alt  = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$attr = array(
		'class' => 'mt-img',
	);
	if ( '' === $alt ) {
		$attr['alt'] = $fallback_alt;
	}
	if ( $eager ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
	}
	echo wp_get_attachment_image( $attachment_id, $size, false, $attr );
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
