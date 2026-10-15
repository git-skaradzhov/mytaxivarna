<?php
/**
 * Needs the visitor can describe in the request. Only the child seat is a stated benefit; the rest is confirmed by the team.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$tiles = array(
	'child-seat.jpg' => __( 'Free child seat on request', 'mytaxi-generatepress' ),
	'luggage.jpg'    => __( 'Tell us about your luggage', 'mytaxi-generatepress' ),
	'message.jpg'    => __( 'Any other needs', 'mytaxi-generatepress' ),
);
?>
<section class="mt-st-section mt-needs-split"<?php echo ! empty( $args['panel'] ) ? ' data-panel="journey"' : ''; ?>>
	<div class="mt-needs-split__copy">
		<h2><?php esc_html_e( 'Anything we should plan around?', 'mytaxi-generatepress' ); ?></h2>
		<p><?php esc_html_e( 'Add it to your request. The team confirms what is possible.', 'mytaxi-generatepress' ); ?></p>
		<a class="mt-btn mt-btn--navy" href="<?php echo esc_url( (string) ( $args['url'] ?? '#request' ) ); ?>"><?php echo esc_html( (string) ( $args['label'] ?? __( 'Request your transfer', 'mytaxi-generatepress' ) ) ); ?></a>
	</div>
	<ul class="mt-needs-tiles">
		<?php foreach ( $tiles as $file => $label ) : ?>
			<li>
				<span class="mt-needs-tiles__media"><?php mytaxi_theme_img( 'needs/' . $file, '' ); ?></span>
				<span><?php echo esc_html( $label ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
