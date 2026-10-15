<?php
/**
 * Full-width photo hero without the search: title, short text, and contact buttons.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$photo   = (string) ( $args['photo'] ?? '' );
$service = (array) ( $args['service'] ?? array() );
$actions = (array) ( $args['actions'] ?? array() );
$phone   = (string) ( $service['contacts']['phone'] ?? '' );
?>
<section class="mt-photo-hero mt-photo-hero--page<?php echo ! empty( $args['compact'] ) ? ' mt-photo-hero--compact' : ''; ?>"<?php echo mytaxi_hero_style( $photo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="mt-photo-hero__inner">
		<?php mytaxi_breadcrumbs(); ?>
		<?php if ( ! empty( $args['kicker'] ) ) : ?>
			<p class="mt-photo-hero__kicker"><?php echo esc_html( (string) $args['kicker'] ); ?></p>
		<?php endif; ?>
		<h1><?php echo esc_html( (string) ( $args['title'] ?? get_the_title() ) ); ?></h1>
		<?php if ( ! empty( $args['lede'] ) ) : ?>
			<p class="mt-photo-hero__lede"><?php echo esc_html( (string) $args['lede'] ); ?></p>
		<?php endif; ?>
		<?php if ( $phone ) : ?>
			<p class="mt-photo-hero__phone"><a href="<?php echo esc_url( (string) $service['contacts']['phone_href'] ); ?>"><?php mytaxi_icon( 'phone' ); ?><span><?php echo esc_html( mytaxi_phone_text( $phone ) ); ?></span></a></p>
		<?php endif; ?>
		<?php if ( $service || $actions ) : ?>
			<div class="mt-actions mt-photo-hero__actions">
				<?php
				if ( $service ) {
					mytaxi_contact_buttons( $service );
				}
				foreach ( $actions as $action ) {
					echo '<a class="mt-btn mt-btn--' . esc_attr( (string) ( $action['style'] ?? 'yellow' ) ) . '" href="' . esc_url( (string) $action['url'] ) . '">' . esc_html( (string) $action['label'] ) . '</a>';
				}
				?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $args['trust'] ) ) : ?>
			<?php mytaxi_trust_row(); ?>
		<?php endif; ?>
	</div>
</section>
