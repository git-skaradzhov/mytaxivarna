<?php
/** wp eval-file /tests/local-taxi-form.php today|yesterday; local only, mail in memory. */
if ( 'local' !== wp_get_environment_type() ) { WP_CLI::error( 'Local only.' ); }
define( 'MYTAXI_VERIFY', true );
$case = $args[0] ?? 'today';
if ( ! in_array( $case, array( 'today', 'yesterday' ), true ) ) { WP_CLI::error( 'Invalid case.' ); }
$service = get_page_by_path( 'taxi-golden-sands', OBJECT, 'mytaxi_service' );
$captured = array();
add_filter( 'pre_wp_mail', static function ( $value, $mail ) use ( &$captured ) { $captured[] = $mail; return true; }, PHP_INT_MAX, 2 );
$_POST = array(
	'_wpcf7_container_post' => (string) $service->ID,
	'_wpcf7_unit_tag' => 'wpcf7-f' . get_option( 'mytaxi_cf7_local_id' ) . '-p' . $service->ID . '-o1',
	'mytaxi-name' => 'Local regression ' . wp_generate_uuid4(),
	'mytaxi-phone' => '+447700900123', 'mytaxi-email' => '',
	'mytaxi-origin' => 'Test Hotel', 'mytaxi-destination' => 'Test address',
	'mytaxi-date' => ( new DateTimeImmutable( $case, new DateTimeZone( 'Europe/Sofia' ) ) )->format( 'Y-m-d' ),
	'mytaxi-time' => '18:30', 'privacy' => '1',
);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$form = wpcf7_contact_form( (int) get_option( 'mytaxi_cf7_local_id' ) );
$result = $form->submit();
if ( 'yesterday' === $case ) {
	$errors = WPCF7_Submission::get_instance()->get_invalid_fields();
	if ( 'validation_failed' !== $result['status'] || empty( $errors['mytaxi-date'] ) || $captured ) { WP_CLI::error( 'Past date was not refused before mail.' ); }
} elseif ( 'mail_sent' !== $result['status'] || 1 !== count( $captured ) || str_contains( strtolower( $result['message'] ), 'flight' ) ) {
	WP_CLI::error( 'Local request or success message is incorrect: ' . wp_json_encode( $result ) );
}
WP_CLI::success( 'Local ' . $case . ' passed; no transport used.' );
