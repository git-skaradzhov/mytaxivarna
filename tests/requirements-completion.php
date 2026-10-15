<?php
/** Run locally: docker compose run --rm -v "$PWD/tests:/tests:ro" wpcli wp eval-file /tests/requirements-completion.php */
use MyTaxi\Core\{Booking, ContactForm, Data, Price, Setup};
if ( 'local' !== wp_get_environment_type() ) { WP_CLI::error( 'Local only.' ); }
$failures = array();
$check = static function ( $name, $ok ) use ( &$failures ) {
	WP_CLI::log( ( $ok ? 'PASS ' : 'FAIL ' ) . $name );
	if ( ! $ok ) { $failures[] = $name; }
};
$snapshot = static function () {
	$items = array();
	foreach ( get_posts( array( 'post_type' => array( 'page', 'mytaxi_service', 'mytaxi_route' ), 'numberposts' => -1 ) ) as $post ) {
		$items[ $post->ID ] = array( $post->post_content, $post->post_excerpt, get_post_meta( $post->ID ) );
	}
	return hash( 'sha256', wp_json_encode( $items ) );
};
Setup::content();
$first = $snapshot();
$mail_options = array( get_option( 'mytaxi_mail_mode' ), get_option( 'mytaxi_test_email' ), get_option( 'mytaxi_request_email' ) );
Setup::content();
$check( 'content update is idempotent', $first === $snapshot() );
$check( 'content update preserves mail options', $mail_options === array( get_option( 'mytaxi_mail_mode' ), get_option( 'mytaxi_test_email' ), get_option( 'mytaxi_request_email' ) ) );
$sofia = get_page_by_path( 'sofia-airport-to-sofia', OBJECT, 'mytaxi_route' );
$check( 'Sofia keeps its own content', str_contains( $sofia->post_content, 'Sofia Airport' ) && ! str_contains( $sofia->post_content, 'Golden Sands' ) );
$original_body = $sofia->post_content;
$original_faq = get_post_meta( $sofia->ID, '_mytaxi_faq', true );
try {
	wp_update_post( array( 'ID' => $sofia->ID, 'post_content' => '<p>Client edited Sofia content.</p>' ) );
	$custom_faqs = MyTaxi\Core\Fields::decode_list( $original_faq );
	$custom_faqs[0]['answer'] = 'Client edited answer.';
	update_post_meta( $sofia->ID, '_mytaxi_faq', MyTaxi\Core\Fields::encode_list( $custom_faqs ) );
	Setup::content();
	$check( 'client body and FAQ edits survive content update', '<p>Client edited Sofia content.</p>' === get_post( $sofia->ID )->post_content && 'Client edited answer.' === MyTaxi\Core\Fields::decode_list( get_post_meta( $sofia->ID, '_mytaxi_faq', true ) )[0]['answer'] );
} finally {
	wp_update_post( array( 'ID' => $sofia->ID, 'post_content' => $original_body ) );
	update_post_meta( $sofia->ID, '_mytaxi_faq', $original_faq );
}
$questions = array_column( Data::route( $sofia->ID )['faqs'], 'question' );
$check( 'Sofia has useful FAQ without duplicates', count( $questions ) >= 10 && count( $questions ) === count( array_unique( $questions ) ) );
$price = Price::present( array( 'price' => '45', 'price_type' => 'fixed', 'price_basis' => 'per_vehicle', 'price_demo' => '1' ) );
$check( 'demo amount is visibly identified', str_contains( $price['text'], '(demo)' ) && $price['demo'] );
$check( 'demo return summary explains actual fare must be confirmed', str_contains( Booking::quote( $price, true )['note'], 'Demonstration' ) );
$saved_price_meta = array();
foreach ( array( '_mytaxi_price', '_mytaxi_price_sample', '_mytaxi_price_type', '_mytaxi_price_basis', '_mytaxi_currency', '_mytaxi_local_demo_price', '_mytaxi_staging_demo_price' ) as $key ) {
	$saved_price_meta[ $key ] = get_post_meta( $sofia->ID, $key, false );
}
try {
	update_post_meta( $sofia->ID, '_mytaxi_local_demo_price', '1' );
	MyTaxi\Core\Meta::store_price( $sofia->ID, '35', 'fixed', 'per_vehicle', false );
	$check( 'ordinary route save does not promote a demo fare', '1' === get_post_meta( $sofia->ID, '_mytaxi_local_demo_price', true ) );
	MyTaxi\Core\Meta::store_price( $sofia->ID, '35', 'fixed', 'per_vehicle', false, true );
	$check( 'demo fare needs explicit confirmation', '' === get_post_meta( $sofia->ID, '_mytaxi_local_demo_price', true ) );
} finally {
	foreach ( $saved_price_meta as $key => $values ) {
		delete_post_meta( $sofia->ID, $key );
		foreach ( $values as $value ) { add_post_meta( $sofia->ID, $key, $value ); }
	}
}
$schema_method = new ReflectionMethod( MyTaxi\Core\Seo::class, 'route_service' );
$data = Data::route( $sofia->ID );
$data['price'] = $price;
$schema = $schema_method->invoke( null, $data );
$check( 'demo price is excluded from Offer schema', ! isset( $schema['offers'] ) );
$schema_method = new ReflectionMethod( MyTaxi\Core\Seo::class, 'taxi_service' );
$service = $data['service'];
$service['contacts']['gbp_url'] = 'https://maps.google.com/?cid=1';
$schema = $schema_method->invoke( null, $service );
$check( 'service schema links its own Google profile', array( 'https://maps.google.com/?cid=1' ) === $schema['sameAs'] );
$form = wpcf7_contact_form( (int) get_option( 'mytaxi_cf7_local_id' ) );
$check( 'local success message does not mention a flight', ! str_contains( strtolower( $form->prop( 'messages' )['mail_sent_ok'] ), 'flight' ) );
$old_post = $_POST;
try {
	foreach ( $form->scan_form_tags() as $tag ) {
		if ( 'mytaxi-date' !== $tag->name ) { continue; }
		$_POST['mytaxi-date'] = ( new DateTimeImmutable( 'yesterday', new DateTimeZone( 'Europe/Sofia' ) ) )->format( 'Y-m-d' );
		$check( 'local yesterday is rejected', ! ContactForm::validate_date( new WPCF7_Validation(), $tag )->is_valid() );
		$_POST['mytaxi-date'] = Booking::today();
		$check( 'local today is accepted', ContactForm::validate_date( new WPCF7_Validation(), $tag )->is_valid() );
	}
} finally { $_POST = $old_post; }
if ( $failures ) { WP_CLI::error( count( $failures ) . ' checks failed.' ); }
WP_CLI::success( 'Requirements checks passed; no mail sent.' );
