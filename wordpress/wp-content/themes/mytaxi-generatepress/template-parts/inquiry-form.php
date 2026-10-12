<?php
/**
 * Inquiry form. Contact Form 7 sends the request.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\MyTaxi\Core\ContactForm' ) ) {
	return;
}

\MyTaxi\Core\ContactForm::render(
	(int) ( $args['service_id'] ?? 0 ),
	(int) ( $args['route_id'] ?? 0 )
);
