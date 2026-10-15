<?php
/**
 * White fact strip directly under a photo hero.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$items = array_values(
	array_filter(
		(array) ( $args['items'] ?? array() ),
		static function ( $item ) {
			return '' !== trim( (string) ( $item['value'] ?? '' ) );
		}
	)
);
if ( ! $items ) {
	return;
}
?>
<div class="mt-strip"<?php echo ! empty( $args['panel'] ) ? ' data-panel="journey"' : ''; ?>>
	<ul class="mt-strip__list">
		<?php foreach ( $items as $item ) : ?>
			<li>
				<?php mytaxi_icon( (string) ( $item['icon'] ?? 'check' ) ); ?>
				<span><strong><?php echo esc_html( (string) $item['value'] ); ?></strong> <?php echo esc_html( (string) ( $item['label'] ?? '' ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
