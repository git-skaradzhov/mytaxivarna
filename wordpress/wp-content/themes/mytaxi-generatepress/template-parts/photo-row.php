<?php
/**
 * Row of client photos.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$ids = array_values( array_filter( array_map( 'intval', (array) ( $args['ids'] ?? array() ) ) ) );
if ( ! $ids ) {
	return;
}
?>
<section class="mt-st-section">
	<div class="mt-st-head">
		<h2><?php echo esc_html( (string) ( $args['heading'] ?? '' ) ); ?></h2>
		<?php if ( ! empty( $args['text'] ) ) : ?>
			<p><?php echo esc_html( (string) $args['text'] ); ?></p>
		<?php endif; ?>
	</div>
	<div class="mt-photo-row">
		<?php foreach ( array_slice( $ids, 0, 4 ) as $image_id ) : ?>
			<figure class="mt-photo-row__item"><?php mytaxi_image( $image_id, 'mytaxi-card', (string) ( $args['alt'] ?? '' ) ); ?></figure>
		<?php endforeach; ?>
	</div>
</section>
