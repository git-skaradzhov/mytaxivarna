<?php
/**
 * Vehicle cards with the price for the car.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$items = array_values( (array) ( $args['items'] ?? array() ) );
if ( ! $items ) {
	return;
}
$link = (string) ( $args['link'] ?? '#request' );
?>
<section class="mt-st-section mt-vehicles"<?php echo ! empty( $args['panel'] ) ? ' data-panel="journey"' : ''; ?>>
	<div class="mt-st-head">
		<h2><?php echo esc_html( (string) ( $args['heading'] ?? __( 'A vehicle for every journey', 'mytaxi-generatepress' ) ) ); ?></h2>
		<p><?php echo esc_html( (string) ( $args['text'] ?? __( 'Tell us your passengers and luggage. The team confirms a suitable car.', 'mytaxi-generatepress' ) ) ); ?></p>
	</div>
	<div class="mt-vehicles-row">
		<?php foreach ( $items as $item ) : ?>
			<a class="mt-vehicle-tile" href="<?php echo esc_url( $link ); ?>">
				<span class="mt-vehicle-tile__media"><?php mytaxi_image( (int) $item['id'], 'mytaxi-card', (string) $item['text'] ); ?></span>
				<span class="mt-vehicle-tile__title"><?php echo esc_html( (string) $item['title'] ); ?></span>
				<?php if ( ! empty( $item['price'] ) ) : ?>
					<span class="mt-vehicle-tile__price"><strong><?php echo esc_html( (string) $item['price'] ); ?></strong> <?php esc_html_e( 'for the car', 'mytaxi-generatepress' ); ?></span>
				<?php else : ?>
					<span class="mt-vehicle-tile__price"><?php esc_html_e( 'Price on request', 'mytaxi-generatepress' ); ?></span>
				<?php endif; ?>
				<span class="mt-vehicle-tile__text"><?php echo esc_html( (string) $item['text'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php if ( ! empty( $args['note'] ) ) : ?>
		<p class="mt-st-note"><?php echo esc_html( (string) $args['note'] ); ?></p>
	<?php endif; ?>
</section>
