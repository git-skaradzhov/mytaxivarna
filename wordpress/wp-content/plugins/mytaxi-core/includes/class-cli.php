<?php
/**
 * WP-CLI commands.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_CLI' ) ) {
	return;
}

\WP_CLI::add_command( 'mytaxi setup', array( Cli::class, 'setup' ) );
\WP_CLI::add_command( 'mytaxi verify', array( Cli::class, 'verify' ) );

/**
 * Setup and verification commands.
 */
class Cli {

	/**
	 * Create missing services, routes, and pages.
	 *
	 * @when after_wp_load
	 */
	public static function setup() {
		Setup::run();
		\WP_CLI::success( 'MyTaxi setup finished.' );
	}

	/**
	 * Check contacts, prices, forms, and redirects.
	 *
	 * @when after_wp_load
	 */
	public static function verify() {
		if ( ! defined( 'MYTAXI_VERIFY' ) ) {
			define( 'MYTAXI_VERIFY', true );
		}

		$failures = array();
		$check    = static function ( $label, $ok ) use ( &$failures ) {
			if ( $ok ) {
				\WP_CLI::log( 'OK  ' . $label );
				return;
			}
			$failures[] = $label;
			\WP_CLI::warning( 'FAIL ' . $label );
		};

		Setup::run();
		Setup::run();

		$services = get_posts(
			array(
				'post_type'      => 'mytaxi_service',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
			)
		);
		$routes = get_posts(
			array(
				'post_type'      => 'mytaxi_route',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
			)
		);
		$check( 'five services', 5 === count( $services ) );
		$check( 'five routes', 5 === count( $routes ) );

		$by_slug = array();
		foreach ( array_merge( $services, $routes ) as $post ) {
			$by_slug[ $post->post_name ] = $post;
		}
		$expected = array(
			'taxi-golden-sands',
			'taxi-albena',
			'taxi-kranevo',
			'varna-airport-transfers',
			'sofia-airport-transfers',
			'varna-airport-to-golden-sands',
			'varna-airport-to-albena',
			'varna-airport-to-kranevo',
			'varna-airport-to-balchik',
			'sofia-airport-to-sofia',
		);
		foreach ( $expected as $slug ) {
			$post = $by_slug[ $slug ] ?? null;
			$check(
				'permalink ' . $slug,
				$post && untrailingslashit( (string) get_permalink( $post ) ) === untrailingslashit( home_url( '/' . $slug . '/' ) )
			);
		}

		$albena_route = $by_slug['varna-airport-to-albena'] ?? null;
		$albena       = $by_slug['taxi-albena'] ?? null;
		$varna        = $by_slug['varna-airport-transfers'] ?? null;
		$sofia        = $by_slug['sofia-airport-transfers'] ?? null;
		$check( 'Albena route uses Varna Airport Transfers', $albena_route && $varna && (int) get_post_meta( $albena_route->ID, '_mytaxi_service_id', true ) === (int) $varna->ID );
		$route_phone  = Contacts::for_route( $albena_route ? $albena_route->ID : 0 );
		$local_phone  = Contacts::for_service( $albena ? $albena->ID : 0 );
		$check( 'route phone is the airport phone', '+359888850884' === $route_phone['phone'] );
		$check( 'route phone is not the local Albena phone', $route_phone['phone'] !== $local_phone['phone'] );
		$check( 'Sofia has no phone', $sofia && '' === (string) get_post_meta( $sofia->ID, '_mytaxi_phone', true ) );
		$check( 'no reviews seeded', array() === Data::home_extras()['reviews'] );

		$route_id = $albena_route ? (int) $albena_route->ID : 0;
		$before   = (string) get_post_meta( $route_id, '_mytaxi_price', true );
		$label    = Price::present(
			array(
				'price'       => '',
				'price_type'  => 'on_request',
				'price_basis' => '',
			)
		);
		$check( 'empty price is on request', 'Price on request' === $label['text'] && null === $label['amount'] && false === $label['offer'] );
		$zero = Price::present(
			array(
				'price'       => '0',
				'price_type'  => 'fixed',
				'price_basis' => 'per_vehicle',
			)
		);
		$check( 'zero price is not displayed as 0 EUR', false === $zero['offer'] && ! str_contains( (string) $zero['text'], '0' ) );
		update_post_meta( $route_id, '_mytaxi_price', '45.00' );
		update_post_meta( $route_id, '_mytaxi_price_type', 'fixed' );
		update_post_meta( $route_id, '_mytaxi_price_basis', 'per_vehicle' );
		$changed = Data::route( $route_id );
		$check( 'price change is the route label', isset( $changed['price']['text'] ) && str_contains( (string) $changed['price']['text'], '45' ) && true === $changed['price']['offer'] );
		update_post_meta( $route_id, '_mytaxi_price', $before );
		update_post_meta( $route_id, '_mytaxi_price_type', 'on_request' );
		update_post_meta( $route_id, '_mytaxi_price_basis', '' );

		$check( 'slug collision detects taxi-albena', Routing::conflicts( 'taxi-albena', 0 ) );
		$check( 'own slug is not a collision', $albena && ! Routing::conflicts( 'taxi-albena', (int) $albena->ID ) );

		foreach ( Routing::active_map() as $from => $to ) {
			$target = untrailingslashit( (string) wp_parse_url( $to, PHP_URL_PATH ) );
			$check( 'redirect ' . $from . ' has no loop', $from !== $target && ! isset( Routing::active_map()[ $target ] ) );
		}

		$rejected = Forms::inspect(
			self::payload( $albena ? (int) $albena->ID : 0, 0, array() )
		);
		$check( 'missing recipient is not success', empty( $rejected['ok'] ) && 'no_recipient' === $rejected['code'] );

		$injected = Forms::inspect(
			self::payload(
				$varna ? (int) $varna->ID : 0,
				0,
				array(
					'mytaxi_name' => "Ann\r\nBcc: bad@example.com",
				)
			)
		);
		$check( 'header injection is not a successful send', empty( $injected['ok'] ) );

		$original_email = $varna ? (string) get_post_meta( $varna->ID, '_mytaxi_email', true ) : '';
		if ( $varna ) {
			update_post_meta( $varna->ID, '_mytaxi_email', 'dispatch-test@example.com' );
		}
		$captured = array();
		add_filter(
			'pre_wp_mail',
			static function ( $short_circuit, $atts ) use ( &$captured ) {
				$captured[] = $atts;
				return true;
			},
			10,
			2
		);
		$payload = self::payload( $varna ? (int) $varna->ID : 0, $route_id, self::valid_fields() );
		$valid   = Forms::inspect( $payload );
		$check( 'valid request is accepted as unconfirmed', ! empty( $valid['ok'] ) && 'sent' === $valid['code'] );
		$mail    = $captured[0] ?? array();
		$headers = implode( "\n", (array) ( $mail['headers'] ?? array() ) );
		$check( 'mail recipient comes from the service', ( $mail['to'] ?? '' ) === 'dispatch-test@example.com' );
		$check( 'mail from is not the visitor', ! str_contains( $headers, 'From: Ana' ) && str_contains( $headers, 'Reply-To:' ) );
		$check( 'visitor header has no newline', ! str_contains( Fields::header_safe( "Ann\r\nBcc: bad@example.com" ), "\n" ) );
		$again = Forms::inspect( $payload );
		$check( 'repeat submit does not send a second email', ! empty( $again['ok'] ) && 1 === count( $captured ) );

		remove_all_filters( 'pre_wp_mail' );
		$live = wp_mail( 'dispatch-test@example.com', 'MyTaxi delivery check', 'Local Mailpit delivery check for Varna Airport Transfers.' );
		$check( 'wp_mail accepted a local message', (bool) $live );

		if ( $varna ) {
			update_post_meta( $varna->ID, '_mytaxi_email', $original_email );
		}

		if ( $failures ) {
			\WP_CLI::error( count( $failures ) . ' checks failed.' );
		}
		\WP_CLI::success( 'MyTaxi verification passed.' );
	}

	/**
	 * Build a form payload.
	 *
	 * @param int                  $service_id Service ID.
	 * @param int                  $route_id Route ID.
	 * @param array<string,string> $extra Extra fields.
	 * @param string               $token Existing token.
	 * @return array<string,mixed>
	 */
	private static function payload( $service_id, $route_id, array $extra, $token = '' ) {
		if ( '' === $token ) {
			$token = Forms::issue_token();
		}
		return array_merge(
			array(
				'mytaxi_token'       => $token,
				'mytaxi_started'     => (string) ( time() - 30 ),
				'mytaxi_nonce'       => wp_create_nonce( 'mytaxi_inquiry' ),
				'mytaxi_service_id'  => (string) $service_id,
				'mytaxi_route_id'    => (string) $route_id,
				'mytaxi_company'     => '',
				'mytaxi_return'      => (string) get_permalink( $route_id ? $route_id : $service_id ),
			),
			$extra
		);
	}

	/**
	 * A complete airport request.
	 *
	 * @return array<string,string>
	 */
	private static function valid_fields() {
		$when = ( new \DateTimeImmutable( '+2 days', new \DateTimeZone( 'Europe/Sofia' ) ) )->format( 'Y-m-d\TH:i' );
		return array(
			'mytaxi_name'        => 'Ana Petrova',
			'mytaxi_phone'       => '+447700900123',
			'mytaxi_email'       => 'ana@example.com',
			'mytaxi_origin'      => 'Varna Airport',
			'mytaxi_destination' => 'Albena',
			'mytaxi_datetime'    => $when,
			'mytaxi_passengers'  => '2',
			'mytaxi_flight'      => 'FB123',
			'mytaxi_luggage'     => '2 suitcases',
			'mytaxi_notes'       => 'Please confirm this request.',
		);
	}
}
