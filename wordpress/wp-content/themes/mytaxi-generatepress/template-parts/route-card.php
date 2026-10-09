<?php
/**
 * Route card.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$route   = $args['route'] ?? array();
$variant = $args['variant'] ?? 'row';
if ( ! $route ) {
	return;
}
$origin = (string) ( $route['origin'] ?? '' );
$dest   = (string) ( $route['destination'] ?? '' );
$label  = trim( $origin . ' → ' . $dest );
$price  = $route['price']['text'] ?? '';
?>
<?php if ( 'photo' === $variant ) : ?>
	<article class="mt-place mt-place--photo">
		<a href="<?php echo esc_url( $route['url'] ); ?>">
			<span class="mt-place__media"><?php mytaxi_picture( $route['slug'], (int) ( $route['image_id'] ?? 0 ), $label ); ?></span>
			<span class="mt-place__body">
				<span class="mt-place__title"><?php echo esc_html( $label ); ?></span>
				<span class="mt-clamp"><?php echo esc_html( $price ); ?></span>
			</span>
			<?php mytaxi_icon( 'chevron' ); ?>
		</a>
	</article>
<?php else : ?>
	<article class="mt-route-row">
		<a href="<?php echo esc_url( $route['url'] ); ?>">
			<span class="mt-route-row__media"><?php mytaxi_picture( $route['slug'], (int) ( $route['image_id'] ?? 0 ), $label ); ?></span>
			<span class="mt-route-row__path">
				<span class="mt-badge"><?php mytaxi_icon( 'plane' ); ?></span>
				<span><?php echo esc_html( $origin ); ?></span>
				<span class="mt-route-row__arrow" aria-hidden="true">→</span>
				<span><?php echo esc_html( $dest ); ?></span>
			</span>
			<span class="mt-route-row__price"><?php echo esc_html( $price ); ?></span>
			<span class="mt-route-row__go"><?php esc_html_e( 'View route', 'mytaxi-generatepress' ); ?> <?php mytaxi_icon( 'arrow' ); ?></span>
		</a>
	</article>
<?php endif; ?>
