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
			'Local taxis in Golden Sands, Albena, and Kranevo, plus transfers from Varna Airport and Sofia Airport.'
		);
		self::ensure_page(
			'contact',
			'Contact your taxi service',
			'Choose your destination and contact the team directly.'
		);
		self::ensure_page(
			'local-taxi',
			'Local taxi services',
			'Choose Golden Sands, Albena or Kranevo for a local taxi.'
		);
		self::ensure_page(
			'airport-transfers',
			'Airport transfers',
			'Select your airport to view destinations and contact details.'
		);
		self::ensure_page(
			'routes',
			'Airport transfer routes',
			'Choose your destination and request a quote for your journey.'
		);
		self::ensure_page(
			'privacy-policy',
			'Privacy policy',
			'We use the name, phone, email and journey details you submit so the team can reply about that request. The form is not a mailing list.'
		);
		self::ensure_page(
			'terms',
			'Terms',
			'Sending a request does not confirm the journey. The team contacts you to confirm availability and the quote before the trip.'
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
		self::refresh_editorial();
		self::apply_design_copy();
		self::apply_confirmed_details();
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
				'meta_desc'  => 'Local taxi for Golden Sands. Call to request a car.',
				'intro' => 'Local taxi journeys from Golden Sands.',
				'body'  => "Call the number on this page when you need a taxi in Golden Sands.\n\nFor a ride from Varna Airport, open the airport transfer to Golden Sands.",
				'terms' => 'We’ll confirm availability and your quote before your journey.',
				'faqs'  => array(
					array(
						'question' => 'How do I ask for a taxi in Golden Sands?',
						'answer'   => 'Call the number on this page. The team confirms the trip with you before the journey.',
					),
					array(
						'question' => 'Can I book a pickup from Varna Airport here?',
						'answer'   => 'A pickup from Varna Airport is arranged with Varna Airport Transfers. Open the route from Varna Airport to Golden Sands.',
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
				'meta_desc'  => 'Local taxi for Albena. Call to request a car.',
				'intro' => 'Contact Taxi Albena for your journey.',
				'body'  => "Call Taxi Albena when you are in the resort and need a car.\n\nFor a ride from Varna Airport, open the airport transfer to Albena.",
				'terms' => 'We’ll confirm availability and your quote before your journey.',
				'faqs'  => array(
					array(
						'question' => 'Which trips is this page for?',
						'answer'   => 'Taxi trips while you are in Albena. Call the number on this page.',
					),
					array(
						'question' => 'I am flying into Varna. Where do I start?',
						'answer'   => 'Open the route from Varna Airport to Albena. That transfer is handled by Varna Airport Transfers.',
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
				'meta_desc'  => 'Local taxi for Kranevo. Call to request a car.',
				'intro' => 'Local taxi journeys from Kranevo.',
				'body'  => "Call Taxi Kranevo when you are in Kranevo and need a car.\n\nFor a ride from Varna Airport, open the airport transfer to Kranevo.",
				'terms' => 'We’ll confirm availability and your quote before your journey.',
				'faqs'  => array(
					array(
						'question' => 'Is Taxi Kranevo the same as Taxi Albena?',
						'answer'   => 'Taxi Kranevo is the service for Kranevo. Use the number on this page.',
					),
					array(
						'question' => 'I need a car from Varna Airport. Where do I start?',
						'answer'   => 'Open the route from Varna Airport to Kranevo.',
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
				'meta_desc'  => 'Transfers to and from Varna Airport, including Golden Sands, Albena, Kranevo, and Balchik.',
				'intro' => 'Transfers between Varna Airport and coastal destinations.',
				'body'  => "Choose a route or send your flight details.\n\nWe’ll confirm availability and your quote before your journey.",
				'terms' => 'We’ll confirm availability and your quote before your journey.',
				'faqs'  => array(
					array(
						'question' => 'How do I request a transfer?',
						'answer'   => 'Call the number on this page or send the form with your date, time, and flight number. We’ll confirm availability and your quote before your journey.',
					),
					array(
						'question' => 'Which places can I travel to?',
						'answer'   => 'The published routes cover Golden Sands, Albena, Kranevo, and Balchik.',
					),
				),
			),
			array(
				'slug'  => 'sofia-airport-transfers',
				'title' => 'Sofia Airport Transfers',
				'type'  => 'airport_transfer',
				'phone' => '+359886330192',
				'order' => 50,
				'areas' => array( 'Sofia Airport' ),
				'cta'   => 'Sofia Airport transfer details',
				'meta_title' => 'Sofia Airport Transfers',
				'meta_desc'  => 'Transfers between Sofia Airport and Sofia.',
				'intro' => 'Transfers between Sofia Airport and Sofia.',
				'body'  => "The route from Sofia Airport to Sofia is listed with this service.\n\nWe’ll confirm availability and your quote before your journey.",
				'terms' => 'We’ll confirm availability and your quote before your journey.',
				'faqs'  => array(
					array(
						'question' => 'How do I request a Sofia Airport transfer?',
						'answer'   => 'Use the contact options on this page. We’ll confirm availability and your quote before your journey.',
					),
					array(
						'question' => 'Which route is listed?',
						'answer'   => 'Sofia Airport to Sofia.',
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
			array( 'varna-airport-to-golden-sands', 'Varna Airport → Golden Sands', 'varna-airport-transfers', 'Varna Airport', 'Golden Sands', 'Transfer from Varna Airport to Golden Sands.' ),
			array( 'varna-airport-to-albena', 'Varna Airport → Albena', 'varna-airport-transfers', 'Varna Airport', 'Albena', 'Transfer from Varna Airport to Albena.' ),
			array( 'varna-airport-to-kranevo', 'Varna Airport → Kranevo', 'varna-airport-transfers', 'Varna Airport', 'Kranevo', 'Transfer from Varna Airport to Kranevo.' ),
			array( 'varna-airport-to-balchik', 'Varna Airport → Balchik', 'varna-airport-transfers', 'Varna Airport', 'Balchik', 'Transfer from Varna Airport to Balchik.' ),
			array( 'sofia-airport-to-sofia', 'Sofia Airport → Sofia', 'sofia-airport-transfers', 'Sofia Airport', 'Sofia', 'Transfer from Sofia Airport to Sofia.' ),
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
				'body'        => 'Tell us your pickup time. We’ll confirm availability and your quote before the journey.',
				'faqs'        => array(
					array(
						'question' => 'How do I request ' . $row[3] . ' to ' . $row[4] . '?',
						'answer'   => 'Use the form on this page. The pickup and destination are already filled in. We’ll confirm availability and your quote before your journey.',
					),
					array(
						'question' => 'When is the price confirmed?',
						'answer'   => 'If a fare is published, it is shown with the route. Otherwise the price is confirmed with you before the journey.',
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
		update_post_meta( $id, '_mytaxi_whatsapp', Fields::phone( (string) ( $row['whatsapp'] ?? $row['phone'] ) ) );
		update_post_meta( $id, '_mytaxi_viber', Fields::phone( (string) ( $row['viber'] ?? $row['phone'] ) ) );
		update_post_meta( $id, '_mytaxi_hours', 'Open every day, 24 hours.' );
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
				'post_excerpt' => '',
			),
			true
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Write the contacts and booking terms the client confirmed.
	 */
	private static function apply_confirmed_details() {
		$stored_inbox = Fields::email( (string) get_option( 'mytaxi_request_email', '' ) );
		if ( 'info@skaradzhov.com' === $stored_inbox ) {
			if ( '' === Fields::email( (string) get_option( 'mytaxi_test_email', '' ) ) ) {
				update_option( 'mytaxi_test_email', $stored_inbox, false );
			}
			update_option( 'mytaxi_request_email', '', false );
		}
		if ( ! in_array( (string) get_option( 'mytaxi_mail_mode', '' ), array( 'test', 'production' ), true ) ) {
			update_option( 'mytaxi_mail_mode', 'production', false );
		}

		$phones = array(
			'taxi-golden-sands'       => '+359884811045',
			'taxi-albena'             => '+359896817384',
			'taxi-kranevo'            => '+359896816334',
			'varna-airport-transfers' => '+359888850884',
			'sofia-airport-transfers' => '+359886330192',
		);
		foreach ( array( 'varna-airport-transfers', 'sofia-airport-transfers' ) as $transfer_slug ) {
			$transfer = self::find( 'mytaxi_service', $transfer_slug );
			if ( ! $transfer ) {
				continue;
			}
			$transfer_routes = get_posts(
				array(
					'post_type'      => 'mytaxi_route',
					'post_status'    => 'publish',
					'posts_per_page' => 20,
					'meta_key'       => '_mytaxi_service_id',
					'meta_value'     => (string) $transfer->ID,
					'no_found_rows'  => true,
				)
			);
			foreach ( $transfer_routes as $transfer_route ) {
				if ( '' === (string) get_post_meta( $transfer_route->ID, '_mytaxi_capacity', true ) ) {
					update_post_meta( $transfer_route->ID, '_mytaxi_capacity', 'Up to 5 passengers' );
				}
			}
		}
		$local_terms = 'Open every day, 24 hours. A child seat is free when you ask for it before the journey.';
		$hours_only  = 'Open every day, 24 hours.';
		$transfer_terms = 'The fare is a fixed price for the car, not for each person. It covers a one-way transfer, and the return is the same price for that route. A delayed flight does not add a waiting charge. Please book at least one day before the flight. The request is confirmed after the price is agreed and you give one passenger’s name and the flight number. The driver meets you in the arrivals hall with a name sign. You do not pay in advance. You pay the driver in cash or by card when the transfer is done. You can cancel at any time with no charge. A child seat is free when you ask for it with the booking. We confirm whether a seat is available and do not promise a particular seat. The service is available every day, 24 hours.';
		$previous_transfer_terms = 'The fare is a fixed price for the car, not for each person. It covers a one-way transfer, and the return is the same price for that route. A delayed flight does not add a waiting charge. Please book at least one day before the flight. The request is confirmed after the price is agreed and you give one passenger’s name and the flight number. The driver meets you in the arrivals hall with a name sign. You do not pay in advance. You pay the driver in cash or by card when the transfer is done. You can cancel at any time with no charge. A child seat is free when you ask for it with the booking. The service is available every day, 24 hours.';
		$local_benefits = array(
			'Open every day, 24 hours',
			'Free child seat when requested in advance',
		);
		$transfer_benefits = array(
			'Fixed price for the car, not per person',
			'One-way fare; the return is the same price',
			'No extra charge for a delayed flight',
			'Book at least one day before the flight',
			'Confirmed after the price, one passenger name, and the flight number',
			'The driver meets you in arrivals with a name sign',
			'Pay the driver in cash or by card, with no prepayment',
			'Cancel at any time with no charge',
			'Free child seat when requested with the booking',
			'Open every day, 24 hours',
		);
		$old_terms = 'We’ll confirm availability and your quote before your journey.';

		foreach ( $phones as $slug => $phone ) {
			$post = self::find( 'mytaxi_service', $slug );
			if ( ! $post ) {
				continue;
			}
			$phone = Fields::phone( $phone );
			update_post_meta( $post->ID, '_mytaxi_phone', $phone );
			if ( '' === (string) get_post_meta( $post->ID, '_mytaxi_whatsapp', true ) ) {
				update_post_meta( $post->ID, '_mytaxi_whatsapp', $phone );
			}
			if ( '' === (string) get_post_meta( $post->ID, '_mytaxi_viber', true ) ) {
				update_post_meta( $post->ID, '_mytaxi_viber', $phone );
			}
			if ( '' === (string) get_post_meta( $post->ID, '_mytaxi_hours', true ) ) {
				update_post_meta( $post->ID, '_mytaxi_hours', 'Open every day, 24 hours.' );
			}
			$airport = 'airport_transfer' === (string) get_post_meta( $post->ID, '_mytaxi_type', true );
			$terms   = (string) get_post_meta( $post->ID, '_mytaxi_terms', true );
			if ( $airport && ( '' === $terms || $old_terms === $terms || $previous_transfer_terms === $terms ) ) {
				update_post_meta( $post->ID, '_mytaxi_terms', $transfer_terms );
			} elseif ( ! $airport && ( '' === $terms || $old_terms === $terms || $local_terms === $terms ) ) {
				update_post_meta( $post->ID, '_mytaxi_terms', $hours_only );
			}
			$benefits = array_values( array_filter( array_map( 'strval', Fields::decode_list( get_post_meta( $post->ID, '_mytaxi_benefits', true ) ) ) ) );
			if ( $airport && ( ! $benefits || $benefits === $transfer_benefits ) ) {
				$next = $transfer_benefits;
				$next[8] = 'Free child seat when requested with the booking; availability is confirmed with you';
				update_post_meta( $post->ID, '_mytaxi_benefits', Fields::encode_list( $next ) );
			} elseif ( ! $airport && ( ! $benefits || $benefits === $local_benefits ) ) {
				update_post_meta( $post->ID, '_mytaxi_benefits', '[]' );
			}
			if ( str_contains( $terms, 'recipient email is saved' ) ) {
				update_post_meta( $post->ID, '_mytaxi_terms', $transfer_terms );
			}
			self::replace_faq_answers(
				$post->ID,
				array(
					'Call the number on this page, or send the form when it is available. We’ll confirm availability and your quote before your journey.' => 'Call the number on this page. The team confirms the trip with you before the journey.',
				)
			);
			if ( $airport ) {
				self::replace_faq_answers(
					$post->ID,
					array(
						'Call the number on this page or send the form with your date, time, and flight number. We’ll confirm availability and your quote before your journey.' => 'Call or send the form with your date and time. You can ask for a price without a flight number. The transfer is confirmed after the price is agreed and you give one passenger’s name and the flight number. Please book at least one day before the flight. The driver meets you in the arrivals hall with a name sign.',
						'Use the contact options on this page. We’ll confirm availability and your quote before your journey.' => 'Use the contact options on this page. You can ask for a price without a flight number. The transfer is confirmed after the price is agreed and you give one passenger’s name and the flight number. The flight number is needed before the driver meets you.',
					)
				);
			}
		}

		$routes = get_posts(
			array(
				'post_type'      => 'mytaxi_route',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
			)
		);
		foreach ( $routes as $route ) {
			self::replace_faq_answers(
				$route->ID,
				array(
					'Use the form on this page. The pickup and destination are already filled in. We’ll confirm availability and your quote before your journey.' => 'Use the form on this page. The pickup and destination are already filled in. Sending it asks for a quote and does not confirm the transfer. Confirmation needs an agreed price, one passenger’s name, and the flight number. The flight number can follow a price enquiry, and it is needed before the airport pickup.',
					'If a fare is published, it is shown with the route. Otherwise the price is confirmed with you before the journey.' => 'The fare is agreed as a fixed price for the car, for a one-way transfer. The return on the same route is the same price. There is no extra charge for waiting on a delayed flight, and you can cancel at any time with no charge. You pay the driver in cash or by card. No payment is taken in advance.',
				)
			);
		}

		$terms_page = get_page_by_path( 'terms' );
		$old_page   = 'Sending a request does not confirm the journey. The team contacts you to confirm availability and the quote before the trip.';
		$terms_html = '<p>These conditions apply to airport transfers.</p><p>' . $transfer_terms . '</p><p>Sending a request does not confirm the journey. The team agrees the price with you first.</p>';
		if ( $terms_page && ( $old_page === trim( wp_strip_all_tags( $terms_page->post_content ) ) || str_contains( (string) $terms_page->post_content, $previous_transfer_terms ) ) ) {
			wp_update_post(
				array(
					'ID'           => $terms_page->ID,
					'post_content' => $terms_html,
				)
			);
		}

		$privacy_note = ' The site owner still needs to confirm the final privacy wording before launch.';
		$privacy_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
			)
		);
		foreach ( $privacy_pages as $privacy ) {
			if ( ! str_contains( (string) $privacy->post_content, $privacy_note ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'           => $privacy->ID,
					'post_content' => str_replace( $privacy_note, '', (string) $privacy->post_content ),
				)
			);
		}
	}

	/**
	 * Replace a FAQ answer only while it is still the seeded sentence.
	 *
	 * @param int               $post_id Post ID.
	 * @param array<string,string> $map Old answer to new answer.
	 */
	private static function replace_faq_answers( $post_id, array $map ) {
		$faqs    = Fields::decode_list( get_post_meta( $post_id, '_mytaxi_faq', true ) );
		$changed = false;
		foreach ( $faqs as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$answer = (string) ( $row['answer'] ?? '' );
			if ( isset( $map[ $answer ] ) ) {
				$faqs[ $index ]['answer'] = $map[ $answer ];
				$changed = true;
			}
		}
		if ( $changed ) {
			update_post_meta( $post_id, '_mytaxi_faq', Fields::encode_list( $faqs ) );
		}
	}

	/**
	 * Align seeded copy with the design handoff when it is still the previous sample.
	 */
	private static function apply_design_copy() {
		$intros = array(
			'taxi-golden-sands'       => array(
				'Taxi for guests staying in Golden Sands.' => 'Local taxi journeys from Golden Sands.',
			),
			'taxi-albena'             => array(
				'Taxi for guests staying in Albena.' => 'Contact Taxi Albena for your journey.',
			),
			'taxi-kranevo'            => array(
				'Taxi for guests staying in Kranevo.' => 'Local taxi journeys from Kranevo.',
			),
			'varna-airport-transfers' => array(
				'Transfers to and from Varna Airport, including Golden Sands, Albena, Kranevo, and Balchik.' => 'Transfers between Varna Airport and coastal destinations.',
			),
			'sofia-airport-transfers' => array(
				'Separate service. Contacts to be supplied before launch.' => 'Transfers between Sofia Airport and Sofia.',
			),
		);
		foreach ( $intros as $slug => $map ) {
			$post = self::find( 'mytaxi_service', $slug );
			if ( ! $post ) {
				continue;
			}
			$current = (string) get_post_meta( $post->ID, '_mytaxi_intro', true );
			if ( isset( $map[ $current ] ) ) {
				update_post_meta( $post->ID, '_mytaxi_intro', $map[ $current ] );
			}
		}

		$pages = array(
			'home'               => array(
				'titles'  => array(),
				'bodies'  => array(
					'Local taxis in Golden Sands, Albena, and Kranevo, plus transfers from Varna Airport and Sofia Airport.' => '',
				),
			),
			'local-taxi'         => array(
				'titles' => array( 'Local taxi' => 'Local taxi services' ),
				'bodies' => array(
					'Taxis for guests already in Golden Sands, Albena, and Kranevo.' => 'Choose Golden Sands, Albena or Kranevo for a local taxi.',
				),
			),
			'airport-transfers'  => array(
				'titles' => array(),
				'bodies' => array(
					'Transfers to and from Varna Airport and Sofia Airport.' => 'Select your airport to view destinations and contact details.',
				),
			),
			'routes'             => array(
				'titles' => array( 'Routes' => 'Airport transfer routes' ),
				'bodies' => array(
					'Choose a route and request a transfer.' => 'Choose your destination and request a quote for your journey.',
				),
			),
			'contact'            => array(
				'titles' => array( 'Contact' => 'Contact your taxi service' ),
				'bodies' => array(
					'Call the service for your destination, or open its page to send a request.' => 'Choose your destination and contact the team directly.',
				),
			),
		);
		foreach ( $pages as $slug => $map ) {
			$post = get_page_by_path( $slug );
			if ( ! $post ) {
				continue;
			}
			$update = array( 'ID' => $post->ID );
			$title  = (string) $post->post_title;
			$body   = trim( wp_strip_all_tags( $post->post_content ) );
			if ( isset( $map['titles'][ $title ] ) ) {
				$update['post_title'] = $map['titles'][ $title ];
			}
			if ( isset( $map['bodies'][ $body ] ) ) {
				$update['post_content'] = '' === $map['bodies'][ $body ] ? '' : '<p>' . $map['bodies'][ $body ] . '</p>';
			}
			if ( count( $update ) > 1 ) {
				wp_update_post( $update );
			}
		}

		$privacy = get_page_by_path( 'privacy-policy' );
		if ( $privacy && str_contains( $privacy->post_content, 'Suggested text' ) ) {
			wp_update_post(
				array(
					'ID'           => $privacy->ID,
					'post_title'   => 'Privacy policy',
					'post_content' => '<p>We use the name, phone, email and journey details you submit so the team can reply about that request. The form is not a mailing list.</p>',
				)
			);
		}
	}

	/**
	 * Replace sample notes that explain the implementation.
	 */
	private static function refresh_editorial() {
		foreach ( self::services() as $row ) {
			$post = self::find( 'mytaxi_service', $row['slug'] );
			if ( ! $post ) {
				continue;
			}
			self::replace_if_stale( $post->ID, '_mytaxi_intro', $row['intro'] );
			self::replace_if_stale( $post->ID, '_mytaxi_meta_description', $row['meta_desc'] );
			self::replace_if_stale( $post->ID, '_mytaxi_meta_title', $row['meta_title'] );
			self::replace_if_stale( $post->ID, '_mytaxi_terms', $row['terms'] );
			if ( self::stale( (string) get_post_meta( $post->ID, '_mytaxi_faq', true ) ) ) {
				update_post_meta( $post->ID, '_mytaxi_faq', Fields::encode_list( $row['faqs'] ) );
			}
			if ( self::stale( $post->post_content ) ) {
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => self::paragraphs( $row['body'] ),
					)
				);
			}
		}

		foreach ( self::routes() as $row ) {
			$post = self::find( 'mytaxi_route', $row['slug'] );
			if ( ! $post ) {
				continue;
			}
			self::replace_if_stale( $post->ID, '_mytaxi_summary', $row['summary'] );
			self::replace_if_stale( $post->ID, '_mytaxi_meta_description', $row['meta_desc'] );
			self::replace_if_stale( $post->ID, '_mytaxi_meta_title', $row['meta_title'] );
			if ( self::stale( (string) get_post_meta( $post->ID, '_mytaxi_faq', true ) ) ) {
				update_post_meta( $post->ID, '_mytaxi_faq', Fields::encode_list( $row['faqs'] ) );
			}
			if ( self::stale( $post->post_content ) ) {
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => self::paragraphs( $row['body'] ),
					)
				);
			}
		}

		$pages = array(
			'home'               => 'Local taxis in Golden Sands, Albena, and Kranevo, plus transfers from Varna Airport and Sofia Airport.',
			'contact'            => 'Call the service for your destination, or open its page to send a request.',
			'local-taxi'         => 'Taxis for guests already in Golden Sands, Albena, and Kranevo.',
			'airport-transfers'  => 'Transfers to and from Varna Airport and Sofia Airport.',
			'routes'             => 'Choose a route and request a transfer.',
		);
		foreach ( $pages as $slug => $body ) {
			$post = self::find( 'page', $slug );
			if ( ! $post || ( ! self::stale( $post->post_content ) && ! self::stale( $post->post_excerpt ) ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => self::paragraphs( $body ),
					'post_excerpt' => '',
				)
			);
		}
	}

	/**
	 * Update one meta value when it still explains the implementation.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param string $value Replacement.
	 */
	private static function replace_if_stale( $post_id, $key, $value ) {
		if ( self::stale( (string) get_post_meta( $post_id, $key, true ) ) ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Whether public copy still describes internal settings.
	 *
	 * @param string $text Text.
	 * @return bool
	 */
	private static function stale( $text ) {
		foreach ( array(
			'Sample text for review',
			'request recipient',
			'0 EUR',
			'Contact buttons stay hidden',
			'not shown as 0',
			'keeps its own phone',
			'does not create a booking',
			'has not been confirmed',
			'No phone, WhatsApp',
			'does not publish',
			'not a booking',
			'own phone',
			'shown only when it is confirmed',
			'request email',
			'automatic booking',
			'published on that service',
		) as $needle ) {
			if ( false !== stripos( (string) $text, $needle ) ) {
				return true;
			}
		}
		return false;
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
