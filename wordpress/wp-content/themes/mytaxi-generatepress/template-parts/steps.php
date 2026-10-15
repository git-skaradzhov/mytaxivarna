<?php
/**
 * Photo beside numbered steps.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$steps = array_values( (array) ( $args['steps'] ?? array() ) );
if ( ! $steps ) {
	return;
}
$photo = ! empty( $args['image_id'] ) || ! empty( $args['file'] );
?>
<section class="mt-st-section mt-steps-split<?php echo $photo ? '' : ' mt-steps-split--text'; ?>"<?php echo ! empty( $args['panel'] ) ? ' data-panel="journey"' : ''; ?>>
	<?php if ( $photo ) : ?>
		<div class="mt-steps-split__media">
			<?php
			if ( ! empty( $args['image_id'] ) ) {
				mytaxi_image( (int) $args['image_id'], 'mytaxi-card', (string) ( $args['heading'] ?? '' ) );
			} else {
				mytaxi_theme_img( (string) $args['file'], (string) ( $args['heading'] ?? '' ) );
			}
			?>
		</div>
	<?php endif; ?>
	<div class="mt-steps-split__copy">
		<h2><?php echo esc_html( (string) ( $args['heading'] ?? '' ) ); ?></h2>
		<?php if ( ! empty( $args['text'] ) ) : ?>
			<p class="mt-steps-split__lede"><?php echo esc_html( (string) $args['text'] ); ?></p>
		<?php endif; ?>
		<ol class="mt-steps-list">
			<?php foreach ( $steps as $step ) : ?>
				<li>
					<h3><?php echo esc_html( (string) $step['title'] ); ?></h3>
					<p><?php echo esc_html( (string) $step['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
