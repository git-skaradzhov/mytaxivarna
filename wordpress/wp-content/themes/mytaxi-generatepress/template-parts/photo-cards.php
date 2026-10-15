<?php
/**
 * Three photo cards with a short title and text.
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
$photos = true;
foreach ( $items as $item ) {
	if ( empty( $item['image_id'] ) && empty( $item['file'] ) ) {
		$photos = false;
	}
}
?>
<section class="mt-st-section"<?php echo ! empty( $args['id'] ) ? ' id="' . esc_attr( (string) $args['id'] ) . '"' : ''; ?>>
	<div class="mt-st-head">
		<h2><?php echo esc_html( (string) ( $args['heading'] ?? '' ) ); ?></h2>
		<?php if ( ! empty( $args['text'] ) ) : ?>
			<p><?php echo esc_html( (string) $args['text'] ); ?></p>
		<?php endif; ?>
	</div>
	<div class="mt-photo-cards">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$url = (string) ( $item['url'] ?? '' );
			$tag = $url ? 'a' : 'div';
			?>
			<article class="mt-photo-card">
				<<?php echo esc_html( $tag ); ?> class="mt-photo-card__inner"<?php echo $url ? ' href="' . esc_url( $url ) . '"' : ''; ?>>
					<?php if ( $photos ) : ?>
						<span class="mt-photo-card__media">
							<?php
							if ( ! empty( $item['image_id'] ) ) {
								mytaxi_image( (int) $item['image_id'], 'mytaxi-card', (string) $item['title'] );
							} else {
								mytaxi_theme_img( (string) $item['file'], (string) $item['title'] );
							}
							?>
						</span>
					<?php else : ?>
						<span class="mt-photo-card__icon"><?php mytaxi_icon( (string) ( $item['icon'] ?? 'check' ) ); ?></span>
					<?php endif; ?>
					<span class="mt-photo-card__title"><?php echo esc_html( (string) $item['title'] ); ?></span>
					<?php if ( ! empty( $item['text'] ) ) : ?>
						<span class="mt-photo-card__text"><?php echo esc_html( (string) $item['text'] ); ?></span>
					<?php endif; ?>
					<?php if ( $url && ! empty( $item['link'] ) ) : ?>
						<span class="mt-photo-card__link"><?php echo esc_html( (string) $item['link'] ); ?> →</span>
					<?php endif; ?>
				</<?php echo esc_html( $tag ); ?>>
			</article>
		<?php endforeach; ?>
	</div>
</section>
