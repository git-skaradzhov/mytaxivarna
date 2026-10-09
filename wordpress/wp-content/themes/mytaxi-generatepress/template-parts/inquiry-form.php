<?php
/**
 * Inquiry form.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\MyTaxi\Core\Forms' ) ) {
	return;
}

$view    = \MyTaxi\Core\Forms::view( (int) ( $args['service_id'] ?? 0 ), (int) ( $args['route_id'] ?? 0 ) );
$errors  = $view['errors'];
$old     = $view['old'];
$airport = 'airport_transfer' === $view['type'];
$invalid = static function ( $codes ) use ( $errors ) {
	foreach ( (array) $codes as $code ) {
		if ( in_array( $code, $errors, true ) ) {
			return ' aria-invalid="true"';
		}
	}
	return '';
};

if ( ! empty( $view['ok'] ) ) {
	echo '<p class="mt-notice mt-notice--ok" role="status">' . esc_html( \MyTaxi\Core\Forms::message( 'sent' ) ) . '</p>';
}
if ( $errors ) {
	echo '<div class="mt-notice" role="alert"><p>' . esc_html__( 'The request was not sent. Correct the fields below.', 'mytaxi-generatepress' ) . '</p><ul>';
	foreach ( $errors as $code ) {
		echo '<li>' . esc_html( \MyTaxi\Core\Forms::message( $code ) ) . '</li>';
	}
	echo '</ul></div>';
}
if ( empty( $view['can_send'] ) ) {
	echo '<p class="mt-notice">' . esc_html( \MyTaxi\Core\Forms::message( 'no_recipient' ) ) . '</p>';
	return;
}
?>
<form class="mt-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
	<input type="hidden" name="action" value="mytaxi_inquiry">
	<input type="hidden" name="mytaxi_service_id" value="<?php echo esc_attr( (string) ( $view['service']['id'] ?? 0 ) ); ?>">
	<input type="hidden" name="mytaxi_route_id" value="<?php echo esc_attr( (string) ( $view['route']['id'] ?? 0 ) ); ?>">
	<input type="hidden" name="mytaxi_return" value="<?php echo esc_url( $view['return_url'] ); ?>">
	<input type="hidden" name="mytaxi_token" value="<?php echo esc_attr( $view['token'] ); ?>">
	<input type="hidden" name="mytaxi_started" value="<?php echo esc_attr( (string) $view['started'] ); ?>">
	<?php wp_nonce_field( 'mytaxi_inquiry', 'mytaxi_nonce' ); ?>
	<p class="mt-hp" aria-hidden="true">
		<label for="mytaxi-company"><?php esc_html_e( 'Company', 'mytaxi-generatepress' ); ?></label>
		<input id="mytaxi-company" type="text" name="mytaxi_company" tabindex="-1" autocomplete="off">
	</p>
	<p class="mt-field">
		<label for="mytaxi-name"><?php esc_html_e( 'Full name', 'mytaxi-generatepress' ); ?> <span aria-hidden="true">*</span></label>
		<input id="mytaxi-name" name="mytaxi_name" type="text" required autocomplete="name" value="<?php echo esc_attr( $old['name'] ); ?>"<?php echo $invalid( 'name_required' ); ?>>
	</p>
	<p class="mt-field">
		<label for="mytaxi-phone"><?php esc_html_e( 'Phone', 'mytaxi-generatepress' ); ?> <span aria-hidden="true">*</span></label>
		<input id="mytaxi-phone" name="mytaxi_phone" type="tel" required autocomplete="tel" value="<?php echo esc_attr( $old['phone'] ); ?>"<?php echo $invalid( array( 'phone_required', 'phone_invalid' ) ); ?>>
	</p>
	<p class="mt-field<?php echo $airport ? '' : ' mt-field--wide'; ?>">
		<label for="mytaxi-email"><?php echo esc_html( $airport ? __( 'Email', 'mytaxi-generatepress' ) : __( 'Email, if you want a reply by email', 'mytaxi-generatepress' ) ); ?><?php echo $airport ? ' <span aria-hidden="true">*</span>' : ''; ?></label>
		<input id="mytaxi-email" name="mytaxi_email" type="email" autocomplete="email" <?php echo $airport ? 'required' : ''; ?> value="<?php echo esc_attr( $old['email'] ); ?>"<?php echo $invalid( array( 'email_required', 'email_invalid' ) ); ?>>
	</p>
	<p class="mt-field">
		<label for="mytaxi-origin"><?php esc_html_e( 'From', 'mytaxi-generatepress' ); ?> <span aria-hidden="true">*</span></label>
		<input id="mytaxi-origin" name="mytaxi_origin" type="text" required value="<?php echo esc_attr( $old['origin'] ); ?>"<?php echo $invalid( 'origin_required' ); ?>>
	</p>
	<p class="mt-field">
		<label for="mytaxi-destination"><?php esc_html_e( 'To', 'mytaxi-generatepress' ); ?> <span aria-hidden="true">*</span></label>
		<input id="mytaxi-destination" name="mytaxi_destination" type="text" required value="<?php echo esc_attr( $old['destination'] ); ?>"<?php echo $invalid( 'destination_required' ); ?>>
	</p>
	<p class="mt-field mt-field--wide">
		<label for="mytaxi-datetime"><?php esc_html_e( 'Date and time (Europe/Sofia)', 'mytaxi-generatepress' ); ?><?php echo $airport ? ' <span aria-hidden="true">*</span>' : ''; ?></label>
		<input id="mytaxi-datetime" name="mytaxi_datetime" type="datetime-local" <?php echo $airport ? 'required' : ''; ?> value="<?php echo esc_attr( $old['datetime'] ); ?>"<?php echo $invalid( array( 'datetime_required', 'datetime_invalid' ) ); ?>>
	</p>
	<?php if ( $airport ) : ?>
		<p class="mt-field">
			<label for="mytaxi-flight"><?php esc_html_e( 'Flight number, when you have one', 'mytaxi-generatepress' ); ?></label>
			<input id="mytaxi-flight" name="mytaxi_flight" type="text" value="<?php echo esc_attr( $old['flight'] ); ?>">
		</p>
	<?php endif; ?>
	<p class="mt-field">
		<label for="mytaxi-passengers"><?php esc_html_e( 'Passengers', 'mytaxi-generatepress' ); ?> <span aria-hidden="true">*</span></label>
		<input id="mytaxi-passengers" name="mytaxi_passengers" type="number" min="1" max="16" required inputmode="numeric" value="<?php echo esc_attr( $old['passengers'] ); ?>"<?php echo $invalid( array( 'passengers_required', 'passengers_invalid' ) ); ?>>
	</p>
	<?php if ( $airport ) : ?>
		<p class="mt-field mt-field--wide">
			<label for="mytaxi-luggage"><?php esc_html_e( 'Luggage', 'mytaxi-generatepress' ); ?></label>
			<input id="mytaxi-luggage" name="mytaxi_luggage" type="text" value="<?php echo esc_attr( $old['luggage'] ); ?>">
		</p>
	<?php endif; ?>
	<p class="mt-field mt-field--wide">
		<label for="mytaxi-notes"><?php esc_html_e( 'Notes', 'mytaxi-generatepress' ); ?></label>
		<textarea id="mytaxi-notes" name="mytaxi_notes" rows="4"><?php echo esc_textarea( $old['notes'] ); ?></textarea>
	</p>
	<button class="mt-btn mt-btn--yellow mt-field--wide" type="submit"><?php echo esc_html( $airport ? __( 'Send transfer request', 'mytaxi-generatepress' ) : __( 'Send request', 'mytaxi-generatepress' ) ); ?></button>
	<p class="mt-form__note mt-field--wide"><?php esc_html_e( 'A request is not a confirmed booking.', 'mytaxi-generatepress' ); ?></p>
</form>
