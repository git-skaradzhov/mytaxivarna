<?php
/** Standalone PHP check; deliberately does not bootstrap WordPress or any mail transport. */
namespace {
	define( 'ABSPATH', '/' );
	$environment = 'production';
	function wp_get_environment_type() { return $GLOBALS['environment']; }
	function __( $text, $domain = '' ) { return $text; }
}
namespace MyTaxi\Core {
	class Fields { public static function price( $value ) { return number_format( (float) $value, 2, '.', '' ); } }
	require ( $argv[1] ?? __DIR__ . '/../wordpress/wp-content/plugins/mytaxi-core/includes/class-price.php' );
	$meta = array( 'price' => '45', 'price_type' => 'fixed', 'price_basis' => 'per_vehicle', 'price_demo' => '1' );
	$price = Price::present( $meta );
	if ( null !== $price['amount'] || $price['offer'] || 'Price on request' !== $price['text'] ) { throw new \RuntimeException( 'Production exposed a demonstration fare' ); }
	$GLOBALS['environment'] = 'staging';
	$price = Price::present( $meta );
	if ( 45.0 !== $price['amount'] || str_contains( $price['text'], '(demo)' ) || ! $price['demo'] ) { throw new \RuntimeException( 'Staging price presentation or internal marker is incorrect' ); }
	echo "PASS production hides demonstration amount; staging shows amount without demo label\n";
}
