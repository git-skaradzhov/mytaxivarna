<?php
/**
 * Compact contact card for one service.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

$service  = $args['service'] ?? array();
$contacts = $service['contacts'] ?? array();
if ( ! $service ) {
	return;
}
?>
<article class="mt-contact-card">
	<h3><a href="<?php echo esc_url( $service['url'] ?? '' ); ?>"><?php echo esc_html( $service['title'] ?? '' ); ?></a></h3>
	<?php if ( ! empty( $contacts['phone_href'] ) ) : ?>
		<p class="mt-contact-card__phone"><a href="<?php echo esc_url( $contacts['phone_href'] ); ?>"><?php echo esc_html( mytaxi_phone_text( $contacts['phone'] ) ); ?></a></p>
		<div class="mt-actions">
			<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( $contacts['phone_href'] ); ?>"><?php mytaxi_icon( 'phone' ); ?><span><?php esc_html_e( 'Call service', 'mytaxi-generatepress' ); ?></span></a>
			<?php if ( ! empty( $contacts['whatsapp_url'] ) ) : ?>
				<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( $contacts['whatsapp_url'] ); ?>">WhatsApp</a>
			<?php endif; ?>
			<?php if ( ! empty( $contacts['viber_url'] ) ) : ?>
				<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( $contacts['viber_url'] ); ?>">Viber</a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'Contact details to be supplied before launch.', 'mytaxi-generatepress' ); ?></p>
	<?php endif; ?>
	<p class="mt-contact-card__more"><a href="<?php echo esc_url( $service['url'] ?? '' ); ?>"><?php esc_html_e( 'View service', 'mytaxi-generatepress' ); ?> <?php mytaxi_icon( 'arrow' ); ?></a></p>
</article>
