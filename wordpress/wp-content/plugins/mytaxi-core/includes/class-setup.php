<?php
/**
 * Idempotent initial content.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the contracted services, routes, and pages once.
 */
class Setup {

	/**
	 * Create missing records without overwriting client edits.
	 *
	 * @return array<string,int>
	 */
	public static function run() {
		if ( ! defined( 'MYTAXI_SETUP_RUNNING' ) ) {
			define( 'MYTAXI_SETUP_RUNNING', true );
		}

		$services = array();
		foreach ( self::services() as $row ) {
			$services[ $row['slug'] ] = self::ensure_service( $row );
		}
		foreach ( self::routes() as $row ) {
			$row['service_id'] = $services[ $row['service'] ] ?? 0;
			self::ensure_route( $row );
		}

		$home = self::ensure_page(
			'home',
			'MyTaxi Varna',
			"MyTaxi Varna brings the local taxi services and the airport transfers onto one site.\n\nEach service keeps its own name, phone, and request recipient. A local taxi page is for someone already in the resort. An airport transfer page is for a trip planned around Varna Airport or Sofia Airport.\n\nSample text for review. Replace this presentation before the public launch."
		);
		self::ensure_page(
			'contact',
			'Contact',
			"Choose the service you need and use the phone or request form on that page.\n\nAn online request is sent to that service only. The team confirms it with you. Sending the form does not create a booking.\n\nSample text for review."
		);

		if ( ! (int) get_option( 'page_on_front' ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home );
		}
		if ( '' === (string) get_option( 'timezone_string' ) ) {
			update_option( 'timezone_string', 'Europe/Sofia' );
		}
		if ( false === get_option( 'mytaxi_sample_content', false ) ) {
			update_option( 'mytaxi_sample_content', '1', false );
		}
		if ( '' === (string) get_option( 'mytaxi_mail_from', '' ) ) {
			update_option( 'mytaxi_mail_from', 'requests@mytaxivarna.com', false );
			update_option( 'mytaxi_mail_from_name', 'MyTaxi Varna', false );
		}
		if ( '' === (string) get_option( 'blogdescription' ) ) {
			update_option( 'blogdescription', 'Local taxi services and airport transfers' );
		}

		self::retire_default_content();
		self::ensure_menu( $services );
		self::flush();

		return $services;
	}

	/**
	 * Service definitions.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function services() {
		return array(
			array(
				'slug'  => 'taxi-golden-sands',
				'title' => 'Taxi Golden Sands',
				'type'  => 'local_taxi',
				'phone' => '+359884811045',
				'order' => 10,
				'areas' => array( 'Golden Sands' ),
				'cta'   => 'Call Taxi Golden Sands',
				'meta_title' => 'Taxi Golden Sands | Local taxi',
				'meta_desc'  => 'Local taxi for guests already in Golden Sands. Airport transfers use a different service and phone. Sample text for review.',
				'intro' => 'Taxi Golden Sands is the local taxi entry for guests who are already in Golden Sands. A pickup from Varna Airport is handled by Varna Airport Transfers, with that service’s own phone. Sample text for review.',
				'body'  => "Use this page to call Taxi Golden Sands or, once a request email is added, to send a local taxi request.\n\nThe page does not publish a price list, fleet, or working hours because those details have not been confirmed.\n\nSample text for review.",
				'terms' => 'A call or a form request is not a confirmed booking. The Taxi Golden Sands team confirms the trip with you.',
				'faqs'  => array(
					array(
						'question' => 'How do I ask for a taxi in Golden Sands?',
						'answer'   => 'Call the Taxi Golden Sands number on this page. An online form appears here only after a request email is saved for this service. The team confirms the trip; the request is not an automatic booking.',
					),
					array(
						'question' => 'Does this page cover a pickup from Varna Airport?',
						'answer'   => 'No. A transfer from Varna Airport uses Varna Airport Transfers and the phone published on that service, even when the destination is Golden Sands.',
					),
				),
			),
			array(
				'slug'  => 'taxi-albena',
				'title' => 'Taxi Albena',
				'type'  => 'local_taxi',
				'phone' => '+359896817384',
				'order' => 20,
				'areas' => array( 'Albena' ),
				'cta'   => 'Call Taxi Albena',
				'meta_title' => 'Taxi Albena | Local taxi',
				'meta_desc'  => 'Local taxi for guests already in Albena. This is not the Varna Airport transfer number. Sample text for review.',
				'intro' => 'Taxi Albena is for a taxi while you are in Albena. It is a separate service from Taxi Kranevo and from the airport transfer desk. Sample text for review.',
				'body'  => "Call the Taxi Albena number shown at the top of this page when you need a car in the resort.\n\nPickup points, payment methods, and vehicle types are not stated here until they are confirmed.\n\nSample text for review.",
				'terms' => 'Calling or writing to Taxi Albena starts a request. The team confirms whether the car is available.',
				'faqs'  => array(
					array(
						'question' => 'Which trips belong on the Taxi Albena page?',
						'answer'   => 'Trips for people who are already in Albena and want the local taxi service. The page is the direct entry for that team.',
					),
					array(
						'question' => 'Why is the airport number different?',
						'answer'   => 'Varna Airport to Albena is a transfer arranged by Varna Airport Transfers. That route shows the airport service phone, not the Taxi Albena phone.',
					),
				),
			),
			array(
				'slug'  => 'taxi-kranevo',
				'title' => 'Taxi Kranevo',
				'type'  => 'local_taxi',
				'phone' => '+359896816334',
				'order' => 30,
				'areas' => array( 'Kranevo' ),
				'cta'   => 'Call Taxi Kranevo',
				'meta_title' => 'Taxi Kranevo | Local taxi',
				'meta_desc'  => 'Local taxi for guests already in Kranevo, with its own phone. Sample text for review.',
				'intro' => 'Taxi Kranevo is the local taxi entry for Kranevo. The phone on this page belongs to this service only and is not the Golden Sands or Albena number. Sample text for review.',
				'body'  => "If you are in Kranevo and need a taxi, start here. The resort pages are separate so a visitor is not sent to another service’s phone.\n\nNo shared fleet, price, or response-time claim is published.\n\nSample text for review.",
				'terms' => 'A request to Taxi Kranevo waits for confirmation from that team.',
				'faqs'  => array(
					array(
						'question' => 'Is Taxi Kranevo the same service as Taxi Albena?',
						'answer'   => 'No. Taxi Kranevo has its own page, phone, and request recipient. Do not use the Albena or Golden Sands number for a Kranevo taxi.',
					),
					array(
						'question' => 'What if I need a car from Varna Airport to Kranevo?',
						'answer'   => 'Open the Varna Airport to Kranevo route. That page uses the Varna Airport Transfers contacts.',
					),
				),
			),
			array(
				'slug'  => 'varna-airport-transfers',
				'title' => 'Varna Airport Transfers',
				'type'  => 'airport_transfer',
				'phone' => '+359888850884',
				'order' => 40,
				'areas' => array( 'Varna Airport' ),
				'cta'   => 'Request a Varna Airport transfer',
				'meta_title' => 'Varna Airport Transfers',
				'meta_desc'  => 'Pre-arranged transfers to and from Varna Airport, with this service’s own phone. Sample text for review.',
				'intro' => 'Varna Airport Transfers is for a planned trip to or from Varna Airport. Routes to Golden Sands, Albena, Kranevo, and Balchik use this phone, not the local taxi numbers. Sample text for review.',
				'body'  => "Choose a route to see the published price, or send a request if the price is still on request. Meeting instructions and delayed-flight terms appear only after they are confirmed.\n\nThe form asks for the flight number when you have one. That does not mean the service monitors the flight automatically.\n\nSample text for review.",
				'terms' => 'An airport request is reviewed by the Varna Airport Transfers team. Nothing on this page confirms a car until the team replies.',
				'faqs'  => array(
					array(
						'question' => 'How is an airport transfer requested?',
						'answer'   => 'Use the phone on this page or the request form once a request email is saved. Include the date, time, and flight number when you know them. The team confirms the arrangement.',
					),
					array(
						'question' => 'What happens if the flight is late?',
						'answer'   => 'Write the flight number in the request. The team confirms how a delay is handled. This page does not promise a waiting time.',
					),
				),
			),
			array(
				'slug'  => 'sofia-airport-transfers',
				'title' => 'Sofia Airport Transfers',
				'type'  => 'airport_transfer',
				'phone' => '',
				'order' => 50,
				'areas' => array( 'Sofia Airport' ),
				'cta'   => 'Sofia Airport transfer details',
				'meta_title' => 'Sofia Airport Transfers',
				'meta_desc'  => 'Sofia Airport transfer entry. No phone is published until one is confirmed for this service. Sample text for review.',
				'intro' => 'Sofia Airport Transfers is a separate direction from the Varna services. No phone, WhatsApp number, or request email has been confirmed, so none is borrowed from another service. Sample text for review.',
				'body'  => "The Sofia Airport to Sofia route can be prepared here. Contact buttons stay hidden until this service has its own confirmed details.\n\nSample text for review.",
				'terms' => 'A request can be sent only after a recipient email is saved for Sofia Airport Transfers. Until then, the page does not offer another company’s phone.',
				'faqs'  => array(
					array(
						'question' => 'Why is there no phone on this page?',
						'answer'   => 'A phone number for Sofia Airport Transfers has not been confirmed. The site will not show a Varna or resort number in its place.',
					),
					array(
						'question' => 'Is a request a confirmed car?',
						'answer'   => 'No. When online requests open, the team still has to confirm the trip.',
					),
				),
			),
		);
	}

	/**
	 * Route definitions.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function routes() {
		$rows = array(
			array( 'varna-airport-to-golden-sands', 'Varna Airport → Golden Sands', 'varna-airport-transfers', 'Varna Airport', 'Golden Sands', 'Transfer from Varna Airport to Golden Sands, using the Varna Airport Transfers contacts rather than the local Golden Sands taxi phone. Sample text for review.' ),
			array( 'varna-airport-to-albena', 'Varna Airport → Albena', 'varna-airport-transfers', 'Varna Airport', 'Albena', 'Transfer from Varna Airport to Albena. The local Taxi Albena number is for trips inside the resort, not for this airport route. Sample text for review.' ),
			array( 'varna-airport-to-kranevo', 'Varna Airport → Kranevo', 'varna-airport-transfers', 'Varna Airport', 'Kranevo', 'Transfer from Varna Airport to Kranevo through Varna Airport Transfers. Taxi Kranevo remains the local resort service. Sample text for review.' ),
			array( 'varna-airport-to-balchik', 'Varna Airport → Balchik', 'varna-airport-transfers', 'Varna Airport', 'Balchik', 'Transfer from Varna Airport to Balchik. This route does not turn the old general Balchik address into an airport page. Sample text for review.' ),
			array( 'sofia-airport-to-sofia', 'Sofia Airport → Sofia', 'sofia-airport-transfers', 'Sofia Airport', 'Sofia', 'Transfer from Sofia Airport into Sofia. It belongs to Sofia Airport Transfers and does not use a Varna phone. Sample text for review.' ),
		);
		$out = array();
		$order = 10;
		foreach ( $rows as $row ) {
			$out[] = array(
				'slug'        => $row[0],
				'title'       => $row[1],
				'service'     => $row[2],
				'origin'      => $row[3],
				'destination' => $row[4],
				'summary'     => $row[5],
				'order'       => $order,
				'meta_title'  => $row[3] . ' to ' . $row[4] . ' transfer',
				'meta_desc'   => $row[5],
				'body'        => "The price is shown only after it is confirmed. Until then the page says the price is on request and does not show 0 EUR.\n\nDistance, journey time, passenger capacity, luggage allowance, meeting point, and payment method are omitted until they are confirmed.\n\nSample text for review.",
				'faqs'        => array(
					array(
						'question' => 'How do I request ' . $row[3] . ' to ' . $row[4] . '?',
						'answer'   => 'Use the contacts of the linked service. The pickup and drop-off on the form start filled with ' . $row[3] . ' and ' . $row[4] . '. The team confirms the request; it is not a booking.',
					),
					array(
						'question' => 'Why does the page say price on request?',
						'answer'   => 'No confirmed fare has been entered for this route. A missing price is never displayed as 0 EUR.',
					),
				),
			);
			$order += 10;
		}
		return $out;
	}

	/**
	 * Create a service if the slug is free.
	 *
	 * @param array<string,mixed> $row Definition.
	 * @return int
	 */
	private static function ensure_service( array $row ) {
		$existing = self::find( 'mytaxi_service', $row['slug'] );
		if ( $existing ) {
			return (int) $existing->ID;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'mytaxi_service',
				'post_status'  => 'publish',
				'post_title'   => $row['title'],
				'post_name'    => $row['slug'],
				'post_content' => self::paragraphs( $row['body'] ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}

		update_post_meta( $id, '_mytaxi_type', $row['type'] );
		update_post_meta( $id, '_mytaxi_intro', $row['intro'] );
		update_post_meta( $id, '_mytaxi_cta', $row['cta'] );
		update_post_meta( $id, '_mytaxi_areas', Fields::encode_list( $row['areas'] ) );
		update_post_meta( $id, '_mytaxi_phone', $row['phone'] );
		update_post_meta( $id, '_mytaxi_whatsapp', '' );
		update_post_meta( $id, '_mytaxi_viber', '' );
		update_post_meta( $id, '_mytaxi_email', '' );
		update_post_meta( $id, '_mytaxi_gbp_url', '' );
		update_post_meta( $id, '_mytaxi_gallery', '[]' );
		update_post_meta( $id, '_mytaxi_terms', $row['terms'] );
		update_post_meta( $id, '_mytaxi_benefits', '[]' );
		update_post_meta( $id, '_mytaxi_faq', Fields::encode_list( $row['faqs'] ) );
		update_post_meta( $id, '_mytaxi_show_home', '1' );
		update_post_meta( $id, '_mytaxi_order', (string) $row['order'] );
		update_post_meta( $id, '_mytaxi_meta_title', $row['meta_title'] );
		update_post_meta( $id, '_mytaxi_meta_description', $row['meta_desc'] );
		update_post_meta( $id, '_mytaxi_sample', '1' );
		return (int) $id;
	}

	/**
	 * Create a route if the slug is free.
	 *
	 * @param array<string,mixed> $row Definition.
	 */
	private static function ensure_route( array $row ) {
		$existing = self::find( 'mytaxi_route', $row['slug'] );
		if ( $existing ) {
			$linked = (int) get_post_meta( $existing->ID, '_mytaxi_service_id', true );
			if ( ! $linked && ! empty( $row['service_id'] ) ) {
				update_post_meta( $existing->ID, '_mytaxi_service_id', (string) $row['service_id'] );
			}
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'mytaxi_route',
				'post_status'  => 'publish',
				'post_title'   => $row['title'],
				'post_name'    => $row['slug'],
				'post_content' => self::paragraphs( $row['body'] ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return;
		}

		update_post_meta( $id, '_mytaxi_service_id', (string) $row['service_id'] );
		update_post_meta( $id, '_mytaxi_origin', $row['origin'] );
		update_post_meta( $id, '_mytaxi_destination', $row['destination'] );
		update_post_meta( $id, '_mytaxi_summary', $row['summary'] );
		update_post_meta( $id, '_mytaxi_price', '' );
		update_post_meta( $id, '_mytaxi_currency', 'EUR' );
		update_post_meta( $id, '_mytaxi_price_type', 'on_request' );
		update_post_meta( $id, '_mytaxi_price_basis', '' );
		update_post_meta( $id, '_mytaxi_distance', '' );
		update_post_meta( $id, '_mytaxi_duration', '' );
		update_post_meta( $id, '_mytaxi_capacity', '' );
		update_post_meta( $id, '_mytaxi_luggage', '' );
		update_post_meta( $id, '_mytaxi_included', '[]' );
		update_post_meta( $id, '_mytaxi_meeting', '' );
		update_post_meta( $id, '_mytaxi_delay', '' );
		update_post_meta( $id, '_mytaxi_requirements', '' );
		update_post_meta( $id, '_mytaxi_faq', Fields::encode_list( $row['faqs'] ) );
		update_post_meta( $id, '_mytaxi_popular', '1' );
		update_post_meta( $id, '_mytaxi_order', (string) $row['order'] );
		update_post_meta( $id, '_mytaxi_meta_title', $row['meta_title'] );
		update_post_meta( $id, '_mytaxi_meta_description', $row['meta_desc'] );
		update_post_meta( $id, '_mytaxi_sample', '1' );
	}

	/**
	 * Create a page if the slug is free.
	 *
	 * @param string $slug Slug.
	 * @param string $title Title.
	 * @param string $body Plain paragraphs.
	 * @return int
	 */
	private static function ensure_page( $slug, $title, $body ) {
		$existing = self::find( 'page', $slug );
		if ( $existing ) {
			return (int) $existing->ID;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => self::paragraphs( $body ),
				'post_excerpt' => 'home' === $slug ? 'Local taxi in the resorts, and airport transfers from Varna and Sofia. Sample text for review.' : '',
			),
			true
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Draft the untouched WordPress sample content.
	 */
	private static function retire_default_content() {
		foreach ( array( 'post' => 'hello-world', 'page' => 'sample-page' ) as $type => $slug ) {
			$post = self::find( $type, $slug );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}
			if ( str_contains( $post->post_content, 'Welcome to WordPress' ) || str_contains( $post->post_content, 'This is an example page' ) ) {
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => 'draft',
					)
				);
			}
		}
	}

	/**
	 * Create the primary menu once.
	 *
	 * @param array<string,int> $services Service IDs by slug.
	 */
	private static function ensure_menu( array $services ) {
		$menu = wp_get_nav_menu_object( 'Main' );
		if ( $menu ) {
			return;
		}
		$menu_id = wp_create_nav_menu( 'Main' );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
		$home = self::find( 'page', 'home' );
		if ( $home ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'Home',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $home->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		foreach ( $services as $service_id ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object'    => 'mytaxi_service',
					'menu-item-object-id' => $service_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$contact = self::find( 'page', 'contact' );
		if ( $contact ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $contact->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( empty( $locations['primary'] ) ) {
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	/**
	 * Flush permalinks after the records exist.
	 */
	private static function flush() {
		global $wp_rewrite;
		if ( '/%postname%/' !== $wp_rewrite->permalink_structure ) {
			$wp_rewrite->set_permalink_structure( '/%postname%/' );
		}
		update_option( 'mytaxi_needs_flush', '1', false );
		Routing::maybe_flush();
	}

	/**
	 * Find a post by slug.
	 *
	 * @param string $type Post type.
	 * @param string $slug Slug.
	 * @return \WP_Post|null
	 */
	private static function find( $type, $slug ) {
		$posts = get_posts(
			array(
				'post_type'      => $type,
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		return $posts ? $posts[0] : null;
	}

	/**
	 * Plain text paragraphs to HTML.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function paragraphs( $text ) {
		$parts = preg_split( "/\n\s*\n/", trim( $text ) );
		$html  = '';
		foreach ( (array) $parts as $part ) {
			$html .= '<p>' . esc_html( trim( $part ) ) . '</p>';
		}
		return $html;
	}
}
