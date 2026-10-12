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
\WP_CLI::add_command( 'mytaxi mailtest', array( Cli::class, 'mailtest' ) );

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
		$check( 'zero price is not displayed as an amount', false === $zero['offer'] && null === $zero['amount'] );
		$sample_price = Price::present(
			array(
				'price'       => '45.00',
				'price_type'  => 'fixed',
				'price_basis' => 'per_vehicle',
			)
		);
		$check( 'confirmed price stays an offer', true === $sample_price['offer'] && str_contains( (string) $sample_price['text'], '45' ) );

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

		$wrong = Forms::inspect( self::payload( $albena ? (int) $albena->ID : 0, $route_id, self::valid_fields() ) );
		$check( 'route from another service is rejected', empty( $wrong['ok'] ) && 'route_invalid' === $wrong['code'] );
		$expired = Forms::inspect( self::payload( $varna ? (int) $varna->ID : 0, $route_id, self::valid_fields(), 'expired-token' ) );
		$check( 'expired token is rejected', empty( $expired['ok'] ) && 'token' === $expired['code'] );
		$check( 'visitor header has no newline', ! str_contains( Fields::header_safe( "Ann\r\nBcc: bad@example.com" ), "\n" ) );

		$original_email = $varna ? (string) get_post_meta( $varna->ID, '_mytaxi_email', true ) : '';
		$mail_probe     = $varna && '' === $original_email;
		try {
			if ( $mail_probe ) {
				update_post_meta( $varna->ID, '_mytaxi_email', 'dispatch-test@example.com' );
			}
			$recipient = $varna ? (string) get_post_meta( $varna->ID, '_mytaxi_email', true ) : '';
			$failed    = static function () {
				return false;
			};
			add_filter( 'pre_wp_mail', $failed, 10, 2 );
			$mail_down = Forms::inspect( self::payload( $varna ? (int) $varna->ID : 0, $route_id, self::valid_fields() ) );
			remove_filter( 'pre_wp_mail', $failed, 10 );
			$check( 'mail failure is not success', '' !== $recipient && empty( $mail_down['ok'] ) && 'mail_failed' === $mail_down['code'] );

			$captured = array();
			$capture  = static function ( $short_circuit, $atts ) use ( &$captured ) {
				unset( $short_circuit );
				$captured[] = $atts;
				return true;
			};
			add_filter( 'pre_wp_mail', $capture, 10, 2 );
			$payload = self::payload( $varna ? (int) $varna->ID : 0, $route_id, self::valid_fields() );
			$valid   = Forms::inspect( $payload );
			$again   = Forms::inspect( $payload );
			remove_filter( 'pre_wp_mail', $capture, 10 );
			$mail    = $captured[0] ?? array();
			$headers = implode( "\n", (array) ( $mail['headers'] ?? array() ) );
			$check( 'valid request is accepted as unconfirmed', ! empty( $valid['ok'] ) && 'sent' === $valid['code'] );
			$check( 'mail recipient comes from the service', '' !== $recipient && ( $mail['to'] ?? '' ) === $recipient );
			$check( 'mail from is not the visitor', ! str_contains( $headers, 'From: Ana' ) && str_contains( $headers, 'Reply-To:' ) );
			$check( 'repeat submit does not send a second email', ! empty( $again['ok'] ) && 1 === count( $captured ) );
		} finally {
			if ( $mail_probe && $varna ) {
				update_post_meta( $varna->ID, '_mytaxi_email', $original_email );
			}
		}
		$restored = $varna ? (string) get_post_meta( $varna->ID, '_mytaxi_email', true ) : '';
		$check( 'verify restored the service email', $restored === $original_email );

		if ( $failures ) {
			\WP_CLI::error( count( $failures ) . ' checks failed.' );
		}
		\WP_CLI::success( 'MyTaxi verification passed.' );
	}

	/**
	 * Send one message through the local test mailbox.
	 *
	 * ## OPTIONS
	 *
	 * [--to=<email>]
	 * : Example mailbox that receives the test.
	 *
	 * @param array<int,string>    $args Positional args.
	 * @param array<string,string> $assoc Associative args.
	 * @when after_wp_load
	 */
	public static function mailtest( $args, $assoc ) {
		unset( $args );
		if ( 'local' !== wp_get_environment_type() ) {
			\WP_CLI::error( 'Test mail runs only when WP_ENVIRONMENT_TYPE is local.' );
		}
		$to = isset( $assoc['to'] ) ? sanitize_email( (string) $assoc['to'] ) : 'dispatch-test@example.com';
		if ( ! is_email( $to ) || ! preg_match( '/@(example\.com|example\.test)$/i', $to ) ) {
			\WP_CLI::error( 'The test mailbox must end in example.com or example.test.' );
		}
		$sent = wp_mail( $to, 'MyTaxi delivery check', 'Local mailbox check.' );
		if ( ! $sent ) {
			\WP_CLI::error( 'Local mail transport did not accept a message.' );
		}
		\WP_CLI::success( 'Test message accepted for ' . $to );
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
