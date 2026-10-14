<?php
/**
 * Confirmed transfer vehicles. Shown only when that service has one.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$service  = $args['service'] ?? array();
$vehicles = array(
	array(
		'id'    => (int) ( $service['vehicle_4_id'] ?? 0 ),
		'title' => __( 'Up to 4 passengers', 'mytaxi-generatepress' ),
		'text'  => __( 'This transfer is for up to 4 passengers.', 'mytaxi-generatepress' ),
	),
	array(
		'id'    => (int) ( $service['vehicle_id'] ?? 0 ),
		'title' => __( 'Up to 5 passengers', 'mytaxi-generatepress' ),
		'text'  => __( 'This transfer is for up to 5 passengers.', 'mytaxi-generatepress' ),
	),
);
$vehicles = array_values(
	array_filter(
		$vehicles,
		static function ( $vehicle ) {
			return ! empty( $vehicle['id'] );
		}
	)
);
if ( ! $vehicles ) {
	return;
}
?>
<section class="mt-section mt-vehicles<?php echo count( $vehicles ) > 1 ? ' mt-vehicles--pair' : ''; ?>">
	<?php foreach ( $vehicles as $vehicle ) : ?>
		<article class="mt-vehicle">
			<div class="mt-section__head">
				<h2><?php echo esc_html( $vehicle['title'] ); ?></h2>
				<p><?php echo esc_html( $vehicle['text'] ); ?></p>
			</div>
			<div class="mt-vehicle__media">
				<?php mytaxi_image( (int) $vehicle['id'], 'mytaxi-hero', $vehicle['title'] ); ?>
			</div>
		</article>
	<?php endforeach; ?>
</section>
