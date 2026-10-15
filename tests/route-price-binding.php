<?php
/**
 * Run each case in a fresh local WP-CLI process (CF7 has a submission singleton):
 * docker compose run --rm -v "$PWD/tests:/tests:ro" wpcli wp eval-file /tests/route-price-binding.php destination
 * Cases: destination, origin, valid, service. See tests/README.md.
 * All mail is short-circuited in memory. Temporary price/options are restored.
 */

use MyTaxi\Core\Booking;
use MyTaxi\Core\ContactForm;
use MyTaxi\Core\Data;

if ( 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Run only in local; no mail transport is used.' );
}
$case = $args[0] ?? 'destination';
if ( ! in_array( $case, array( 'destination', 'origin', 'valid', 'service' ), true ) ) {
	WP_CLI::error( 'Unknown case.' );
}
$route = get_page_by_path( 'varna-airport-to-golden-sands', OBJECT, 'mytaxi_route' );
if ( ! $route || 'publish' !== $route->post_status ) {
	WP_CLI::error( 'Published Golden Sands route required.' );
}
$snapshot = array();
foreach ( array( '_mytaxi_price', '_mytaxi_price_sample', '_mytaxi_price_type', '_mytaxi_price_basis', '_mytaxi_currency' ) as $key ) {
	$snapshot[ $key ] = get_post_meta( $route->ID, $key, false );
}
$options = array();
foreach ( array( 'mytaxi_mail_mode', 'mytaxi_test_email' ) as $key ) {
	$options[ $key ] = get_option( $key, null );
}
$captured = array();
$capture = static function ( $result, $atts ) use ( &$captured ) {
	$captured[] = $atts;
	return true;
};
add_filter( 'pre_wp_mail', $capture, PHP_INT_MAX, 2 );
$failure = null;
try {
	update_post_meta( $route->ID, '_mytaxi_price', '45' );
	update_post_meta( $route->ID, '_mytaxi_price_sample', '0' );
	update_post_meta( $route->ID, '_mytaxi_price_type', 'fixed' );
	update_post_meta( $route->ID, '_mytaxi_price_basis', 'per_vehicle' );
	update_post_meta( $route->ID, '_mytaxi_currency', 'EUR' );
	update_option( 'mytaxi_mail_mode', 'test' );
	update_option( 'mytaxi_test_email', 'binding@example.test' );
	$data = Data::route( $route->ID );
	if ( ! $data['price']['offer'] || 45.0 !== (float) $data['price']['amount'] ) {
		throw new RuntimeException( 'Confirmed fare fixture is invalid.' );
	}
	$service = 'service' === $case;
	$_POST = array(
		'_wpcf7_container_post' => (string) ( $service ? $data['service_id'] : $route->ID ),
		'_wpcf7_unit_tag' => 'wpcf7-f' . get_option( 'mytaxi_cf7_airport_id' ) . '-p' . $route->ID . '-o1',
		'mytaxi-origin' => 'origin' === $case ? 'Sofia Airport' : $data['origin'],
		'mytaxi-destination' => in_array( $case, array( 'destination', 'service' ), true ) ? 'Albena' : $data['destination'],
		'mytaxi-date' => Booking::earliest(), 'mytaxi-time' => '14:37', 'mytaxi-passengers' => '2',
		'mytaxi-name' => 'Binding Test ' . wp_generate_uuid4(),
		'mytaxi-phone' => '+447700900123', 'mytaxi-email' => 'binding@example.test',
		'mytaxi-hotel' => 'Test hotel', 'privacy' => '1',
	);
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	$form = wpcf7_contact_form( (int) get_option( 'mytaxi_cf7_airport_id' ) );
	$result = $form->submit();
	if ( in_array( $case, array( 'destination', 'origin' ), true ) ) {
		$field = 'mytaxi-' . $case;
		$errors = WPCF7_Submission::get_instance()->get_invalid_fields();
		if ( 'validation_failed' !== $result['status'] || empty( $errors[ $field ] ) || $captured ) {
			throw new RuntimeException( 'Tampered ' . $case . ' was not rejected before mail: ' . wp_json_encode( $result ) );
		}
	} else {
		$body = $captured[0]['message'] ?? '';
		if ( 'mail_sent' !== $result['status'] || 1 !== count( $captured ) ) {
			throw new RuntimeException( 'Expected one in-memory message: ' . wp_json_encode( $result ) );
		}
		if ( $service ) {
			if ( ! str_contains( $body, 'To: Albena' ) || str_contains( $body, '€45' ) ) {
				throw new RuntimeException( 'Service request acquired a route fare.' );
			}
		} else {
			if ( ! str_contains( $body, 'To: Golden Sands' ) || ! str_contains( $body, 'From: Varna Airport' ) || ! str_contains( $body, '€45' ) || str_contains( $body, 'Albena' ) ) {
				throw new RuntimeException( 'Mail route and price disagree.' );
			}
		}
	}
} catch ( Throwable $error ) {
	$failure = $error->getMessage();
} finally {
	foreach ( $snapshot as $key => $values ) {
		delete_post_meta( $route->ID, $key );
		foreach ( $values as $value ) {
			add_post_meta( $route->ID, $key, $value );
		}
	}
	foreach ( $options as $key => $value ) {
		if ( null === $value ) {
			delete_option( $key );
		} else {
			update_option( $key, $value );
		}
	}
	remove_filter( 'pre_wp_mail', $capture, PHP_INT_MAX );
}
if ( $failure ) {
	WP_CLI::error( $failure );
}
WP_CLI::success( $case . ': route/fare binding passed; no transport used; fixtures restored.' );
