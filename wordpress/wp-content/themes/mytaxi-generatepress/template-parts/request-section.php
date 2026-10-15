<?php
/**
 * Request form beside a short text and the service's direct contacts.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$service = (array) ( $args['service'] ?? array() );
if ( ! $service ) {
	return;
}
$contacts = (array) ( $service['contacts'] ?? array() );
?>
<section class="mt-st-section mt-request-split" id="request">
	<div class="mt-request-split__copy">
		<h2><?php echo esc_html( (string) ( $args['heading'] ?? '' ) ); ?></h2>
		<?php if ( ! empty( $args['text'] ) ) : ?>
			<p><?php echo esc_html( (string) $args['text'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $contacts['phone_href'] ) ) : ?>
			<div class="mt-request-split__direct">
				<p><?php esc_html_e( 'Prefer to talk? Call or message the team directly.', 'mytaxi-generatepress' ); ?></p>
				<p class="mt-request-split__phone"><a href="<?php echo esc_url( (string) $contacts['phone_href'] ); ?>"><?php mytaxi_icon( 'phone' ); ?><span><?php echo esc_html( mytaxi_phone_text( (string) $contacts['phone'] ) ); ?></span></a></p>
				<div class="mt-actions"><?php mytaxi_contact_buttons( $service ); ?></div>
			</div>
		<?php endif; ?>
	</div>
	<div class="mt-request-split__form">
		<?php get_template_part( 'template-parts/inquiry-form', null, array( 'service_id' => (int) $service['id'], 'route_id' => 0 ) ); ?>
	</div>
</section>
