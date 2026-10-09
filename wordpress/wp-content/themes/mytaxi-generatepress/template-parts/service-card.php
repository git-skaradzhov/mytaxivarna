<?php
/**
 * Service card.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$service = $args['service'] ?? array();
$variant = $args['variant'] ?? 'place';
if ( ! $service ) {
	return;
}
$line  = mytaxi_card_text( $service['intro'] ?? '' );
$more  = 'airport' === $variant ? __( 'Explore transfers', 'mytaxi-generatepress' ) : __( 'View service', 'mytaxi-generatepress' );
?>
<article class="mt-place mt-place--<?php echo esc_attr( $variant ); ?>">
	<a href="<?php echo esc_url( $service['url'] ); ?>">
		<span class="mt-place__media">
			<?php mytaxi_picture( $service['slug'], (int) ( $service['image_id'] ?? 0 ), $service['title'] ); ?>
			<?php if ( 'airport' === $variant ) : ?>
				<span class="mt-badge"><?php mytaxi_icon( 'plane' ); ?></span>
			<?php endif; ?>
		</span>
		<span class="mt-place__body">
			<span class="mt-place__title"><?php echo esc_html( mytaxi_place_name( $service['title'] ) ); ?></span>
			<?php if ( $line ) : ?>
				<span class="mt-clamp"><?php echo esc_html( $line ); ?></span>
			<?php endif; ?>
			<span class="mt-more"><?php echo esc_html( $more ); ?> <?php mytaxi_icon( 'arrow' ); ?></span>
		</span>
		<?php mytaxi_icon( 'chevron' ); ?>
	</a>
</article>
