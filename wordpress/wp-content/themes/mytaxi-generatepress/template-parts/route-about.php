<?php
/**
 * Route copy and the facts entered for it. Empty facts are left out.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$route = (array) ( $args['route'] ?? array() );
$facts = array(
	__( 'Distance', 'mytaxi-generatepress' )          => (string) ( $route['distance'] ?? '' ),
	__( 'Journey time', 'mytaxi-generatepress' )      => (string) ( $route['duration'] ?? '' ),
	__( 'Passengers', 'mytaxi-generatepress' )        => (string) ( $route['capacity'] ?? '' ),
	__( 'Luggage', 'mytaxi-generatepress' )           => (string) ( $route['luggage'] ?? '' ),
	__( 'Meeting point', 'mytaxi-generatepress' )     => (string) ( $route['meeting'] ?? '' ),
	__( 'Flight delays', 'mytaxi-generatepress' )     => (string) ( $route['delay'] ?? '' ),
	__( 'Included', 'mytaxi-generatepress' )          => implode( ', ', (array) ( $route['included'] ?? array() ) ),
	__( 'Before you travel', 'mytaxi-generatepress' ) => (string) ( $route['requirements'] ?? '' ),
);
$facts   = array_filter( array_map( 'trim', $facts ) );
$content = trim( (string) ( $route['content'] ?? '' ) );

if ( ! $facts && '' === $content ) {
	return;
}
?>
<section class="mt-st-section mt-route-about">
	<div class="mt-st-head">
		<h2><?php echo esc_html( sprintf( __( '%1$s to %2$s: what to know', 'mytaxi-generatepress' ), (string) ( $route['origin'] ?? '' ), (string) ( $route['destination'] ?? '' ) ) ); ?></h2>
	</div>
	<div class="mt-route-about__grid">
		<?php if ( '' !== $content ) : ?>
			<div class="mt-prose"><?php echo wp_kses_post( apply_filters( 'the_content', $content ) ); ?></div>
		<?php endif; ?>
		<?php if ( $facts ) : ?>
			<dl class="mt-route-facts">
				<?php foreach ( $facts as $label => $value ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</section>
