<?php
/**
 * Plugin Name: MyTaxi Local Mail
 * Description: Sends local WordPress mail to Mailpit. Does nothing outside the local environment.
 *
 * @package MyTaxi
 */

if ( ! function_exists( 'wp_get_environment_type' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_filter(
	'wp_mail_from',
	static function ( $email ) {
		if ( ! is_email( $email ) || str_ends_with( strtolower( (string) $email ), '@localhost' ) ) {
			return 'requests@mytaxivarna.com';
		}
		return $email;
	}
);

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		if ( 'local' !== wp_get_environment_type() ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host        = 'mailpit';
		$phpmailer->Port        = 1025;
		$phpmailer->SMTPAuth    = false;
		$phpmailer->SMTPAutoTLS = false;
		$phpmailer->SMTPSecure  = '';

		$from = (string) $phpmailer->From;
		if ( '' === $from || ! is_email( $from ) || str_ends_with( strtolower( $from ), '@localhost' ) ) {
			$phpmailer->setFrom( 'requests@mytaxivarna.com', 'MyTaxi Varna', false );
		}
	}
);
