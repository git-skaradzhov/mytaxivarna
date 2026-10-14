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
\WP_CLI::add_command( 'mytaxi photos', array( Cli::class, 'photos' ) );

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
		$check( 'Sofia phone is the confirmed number', $sofia && '+359886330192' === (string) get_post_meta( $sofia->ID, '_mytaxi_phone', true ) );
		$mail_mode = (string) get_option( 'mytaxi_mail_mode', 'production' );
		$mail_to   = (string) get_option( 'mytaxi_request_email', '' );
		$mail_test = (string) get_option( 'mytaxi_test_email', '' );
		update_option( 'mytaxi_mail_mode', 'production', false );
		update_option( 'mytaxi_request_email', '', false );
		update_option( 'mytaxi_test_email', 'dispatch-test@example.com', false );
		$check( 'missing production recipient does not send', ! ContactForm::can_send() && '' === ContactForm::recipient() );
		update_option( 'mytaxi_mail_mode', 'test', false );
		$check( 'test mode uses only the test recipient', ContactForm::can_send() && 'dispatch-test@example.com' === ContactForm::recipient() );
		update_option( 'mytaxi_mail_mode', $mail_mode, false );
		update_option( 'mytaxi_request_email', $mail_to, false );
		update_option( 'mytaxi_test_email', $mail_test, false );
		$check( 'verify restored the mail settings', (string) get_option( 'mytaxi_mail_mode', 'production' ) === $mail_mode && (string) get_option( 'mytaxi_request_email', '' ) === $mail_to );
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
	/**
	 * Import the prepared client photos without duplicating attachments.
	 *
	 * @when after_wp_load
	 */
	public static function photos() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$dir      = WP_CONTENT_DIR . '/mytaxi-photo-import';
		$manifest = json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true );
		if ( ! is_array( $manifest ) ) {
			\WP_CLI::error( 'Photo manifest is missing.' );
		}

		$galleries = array();
		foreach ( $manifest as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id = self::import_photo( $dir, $item );
			if ( ! $id ) {
				continue;
			}
			foreach ( (array) ( $item['featured'] ?? array() ) as $target ) {
				self::assign_featured( (string) $target, $id );
			}
			foreach ( (array) ( $item['vehicle'] ?? array() ) as $slug ) {
				self::assign_vehicle( (string) $slug, $id, '_mytaxi_vehicle_id' );
			}
			foreach ( (array) ( $item['vehicle_4'] ?? array() ) as $slug ) {
				self::assign_vehicle( (string) $slug, $id, '_mytaxi_vehicle_4_id' );
			}
			foreach ( (array) ( $item['gallery'] ?? array() ) as $slug => $order ) {
				$galleries[ (string) $slug ][ (int) $order ] = $id;
			}
			\WP_CLI::log( (string) $item['file'] . ' -> ' . $id );
		}

		foreach ( $galleries as $slug => $ordered ) {
			ksort( $ordered );
			self::assign_gallery( $slug, array_values( $ordered ) );
		}
		\WP_CLI::success( 'Client photos imported.' );
	}

	/**
	 * Create or reuse one attachment.
	 *
	 * @param string              $dir Import directory.
	 * @param array<string,mixed> $item Manifest row.
	 * @return int
	 */
	private static function import_photo( $dir, array $item ) {
		$hash = (string) ( $item['sha256'] ?? '' );
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'meta_key'       => '_mytaxi_source',
				'meta_value'     => $hash,
				'fields'         => 'ids',
			)
		);
		$id = (int) ( $found[0] ?? 0 );
		$revision = (string) ( $item['revision'] ?? '' );
		if ( $id && '' !== $revision && (string) get_post_meta( $id, '_mytaxi_revision', true ) !== $revision ) {
			self::replace_derivative( $dir, $id, $item );
		}
		if ( ! $id ) {
			$path = $dir . '/' . basename( (string) $item['file'] );
			$tmp  = wp_tempnam( basename( $path ) );
			if ( ! $tmp || ! copy( $path, $tmp ) ) {
				return 0;
			}
			$file = array(
				'name'     => basename( $path ),
				'tmp_name' => $tmp,
			);
			$id = media_handle_sideload( $file, 0, (string) ( $item['caption'] ?? '' ) );
			if ( is_wp_error( $id ) ) {
				\WP_CLI::warning( $id->get_error_message() );
				return 0;
			}
			update_post_meta( (int) $id, '_mytaxi_source', $hash );
			update_post_meta( (int) $id, '_mytaxi_revision', (string) ( $item['revision'] ?? '' ) );
		}

		$alt  = (string) ( $item['alt'] ?? '' );
		$seed = (string) get_post_meta( $id, '_mytaxi_alt_seed', true );
		$now  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		if ( '' === $now || $now === $seed ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			update_post_meta( $id, '_mytaxi_alt_seed', $alt );
		}
		if ( ! empty( $item['review'] ) ) {
			update_post_meta( $id, '_mytaxi_review', sanitize_key( (string) $item['review'] ) );
		}
		self::copy_webp( $dir, $id, (string) ( $item['webp'] ?? '' ), '_mytaxi_webp', (int) ( $item['width'] ?? 0 ) );
		self::copy_webp( $dir, $id, (string) ( $item['card_webp'] ?? '' ), '_mytaxi_webp_card', (int) ( $item['card_width'] ?? 0 ) );
		return (int) $id;
	}

	/**
	 * Replace an imported file when the crop revision changes.
	 *
	 * @param string              $dir Import directory.
	 * @param int                 $id Attachment ID.
	 * @param array<string,mixed> $item Manifest row.
	 */
	private static function replace_derivative( $dir, $id, array $item ) {
		$attached = get_attached_file( $id );
		$source   = $dir . '/' . basename( (string) ( $item['file'] ?? '' ) );
		if ( ! $attached || ! is_file( $source ) ) {
			return;
		}
		$meta = wp_get_attachment_metadata( $id );
		$folder = dirname( $attached );
		if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size ) {
				$file = $folder . '/' . (string) ( $size['file'] ?? '' );
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
		copy( $source, $attached );
		$generated = wp_generate_attachment_metadata( $id, $attached );
		if ( is_array( $generated ) ) {
			wp_update_attachment_metadata( $id, $generated );
		}
		update_post_meta( $id, '_mytaxi_revision', (string) ( $item['revision'] ?? '' ) );
	}

	/**
	 * Keep a WebP sibling beside the imported JPEG.
	 *
	 * @param string $dir Import directory.
	 * @param int    $id Attachment ID.
	 * @param string $filename Prepared file name.
	 * @param string $meta Meta key.
	 * @param int    $width Pixel width.
	 */
	private static function copy_webp( $dir, $id, $filename, $meta, $width ) {
		if ( '' === $filename ) {
			return;
		}
		$attached = get_attached_file( $id );
		if ( ! $attached ) {
			return;
		}
		$target = dirname( $attached ) . '/' . basename( $filename );
		copy( $dir . '/' . basename( $filename ), $target );
		update_post_meta( $id, $meta, _wp_relative_upload_path( $target ) );
		update_post_meta( $id, $meta . '_width', $width );
	}

	/**
	 * Set a featured image when the current one is empty or itself imported.
	 *
	 * @param string $target front, a record slug, or page:slug.
	 * @param int    $attachment_id Attachment ID.
	 */
	private static function assign_featured( $target, $attachment_id ) {
		$post_id = 0;
		if ( 'front' === $target ) {
			$post_id = (int) get_option( 'page_on_front' );
		} elseif ( str_starts_with( $target, 'page:' ) ) {
			$page = get_page_by_path( substr( $target, 5 ) );
			$post_id = $page ? (int) $page->ID : 0;
		} else {
			$post = self::find_record( $target );
			$post_id = $post ? (int) $post->ID : 0;
		}
		if ( ! $post_id ) {
			\WP_CLI::warning( 'Missing target ' . $target );
			return;
		}
		$current = (int) get_post_thumbnail_id( $post_id );
		if ( $current && ! get_post_meta( $current, '_mytaxi_source', true ) ) {
			return;
		}
		set_post_thumbnail( $post_id, $attachment_id );
	}

	/**
	 * Attach the confirmed transfer vehicle without replacing the place photo.
	 *
	 * @param string $slug Service slug.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $meta_key Vehicle meta key.
	 */
	private static function assign_vehicle( $slug, $attachment_id, $meta_key ) {
		$post = self::find_record( $slug );
		if ( ! $post || 'mytaxi_service' !== $post->post_type ) {
			\WP_CLI::warning( 'Missing vehicle target ' . $slug );
			return;
		}
		$current = (int) get_post_meta( $post->ID, $meta_key, true );
		if ( $current && ! get_post_meta( $current, '_mytaxi_source', true ) ) {
			return;
		}
		update_post_meta( $post->ID, $meta_key, (string) $attachment_id );
	}

	/**
	 * Replace a gallery that does not contain hand-picked outside photos.
	 *
	 * @param string     $slug Service slug.
	 * @param array<int> $ids Attachment IDs.
	 */
	private static function assign_gallery( $slug, array $ids ) {
		$post = self::find_record( $slug );
		if ( ! $post || 'mytaxi_service' !== $post->post_type ) {
			return;
		}
		$current = array_map( 'intval', Fields::decode_list( get_post_meta( $post->ID, '_mytaxi_gallery', true ) ) );
		foreach ( $current as $id ) {
			if ( $id && ! get_post_meta( $id, '_mytaxi_source', true ) ) {
				return;
			}
		}
		update_post_meta( $post->ID, '_mytaxi_gallery', Fields::encode_list( array_map( 'strval', $ids ) ) );
	}

	/**
	 * Find a service or route by slug.
	 *
	 * @param string $slug Slug.
	 * @return \WP_Post|null
	 */
	private static function find_record( $slug ) {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => array( 'mytaxi_service', 'mytaxi_route' ),
				'post_status'    => 'any',
				'posts_per_page' => 1,
			)
		);
		return $posts[0] ?? null;
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
