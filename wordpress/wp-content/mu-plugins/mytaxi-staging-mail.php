<?php
/**
 * Plugin Name: MyTaxi Staging Mail Capture
 * Description: Captures staging mail in private temporary files, without a mail transport.
 *
 * @package MyTaxi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Private capture directory outside the public WordPress tree.
 *
 * @return string
 */
function mytaxi_staging_mail_directory() {
	return rtrim( sys_get_temp_dir(), '/\\' ) . '/mytaxi-staging-mail-' . substr( hash( 'sha256', ABSPATH ), 0, 16 );
}

add_filter(
	'pre_wp_mail',
	static function ( $result, $atts ) {
		if ( 'staging' !== wp_get_environment_type() ) {
			return $result;
		}

		// Fail closed: a storage failure must never fall through to a real transport.
		$directory = mytaxi_staging_mail_directory();
		if ( ! is_dir( $directory ) && ! mkdir( $directory, 0700, true ) ) {
			return false;
		}
		if ( ! chmod( $directory, 0700 ) || ! is_writable( $directory ) ) {
			return false;
		}
		$payload = wp_json_encode( array( 'captured_at' => gmdate( 'c' ), 'mail' => $atts ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		if ( false === $payload ) {
			return false;
		}
		// tempnam creates a new file with mode 0600. Captures are not served over HTTP.
		$file = tempnam( $directory, 'mail-' );
		if ( false === $file ) {
			return false;
		}
		return false !== file_put_contents( $file, $payload, LOCK_EX );
	},
	PHP_INT_MAX,
	2
);
