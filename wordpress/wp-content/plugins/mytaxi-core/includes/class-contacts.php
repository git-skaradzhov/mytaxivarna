<?php
/**
 * Service contact resolution.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Reads contact details from the service that owns them.
 */
class Contacts {

	/**
	 * Contacts for a published service.
	 *
	 * @param int $service_id Service ID.
	 * @return array<string,string>
	 */
	public static function for_service( $service_id ) {
		$empty = array(
			'phone'        => '',
			'phone_href'   => '',
			'whatsapp'     => '',
			'whatsapp_url' => '',
			'viber'        => '',
			'viber_url'    => '',
			'email'        => '',
			'gbp_url'      => '',
		);

		$service_id = (int) $service_id;
		$post       = get_post( $service_id );
		if ( ! $post || 'mytaxi_service' !== $post->post_type ) {
			return $empty;
		}

		$phone    = Fields::phone( (string) get_post_meta( $service_id, '_mytaxi_phone', true ) );
		$whatsapp = Fields::phone( (string) get_post_meta( $service_id, '_mytaxi_whatsapp', true ) );
		$viber    = Fields::phone( (string) get_post_meta( $service_id, '_mytaxi_viber', true ) );
		$email    = Fields::email( (string) get_post_meta( $service_id, '_mytaxi_email', true ) );
		$gbp      = Fields::url( (string) get_post_meta( $service_id, '_mytaxi_gbp_url', true ) );

		return array(
			'phone'        => $phone,
			'phone_href'   => $phone ? 'tel:' . $phone : '',
			'whatsapp'     => $whatsapp,
			'whatsapp_url' => $whatsapp ? 'https://wa.me/' . ltrim( $whatsapp, '+' ) : '',
			'viber'        => $viber,
			'viber_url'    => $viber ? 'viber://chat?number=%2B' . ltrim( $viber, '+' ) : '',
			'email'        => $email,
			'gbp_url'      => $gbp,
		);
	}

	/**
	 * Contacts inherited by a route from its service.
	 *
	 * @param int $route_id Route ID.
	 * @return array<string,string>
	 */
	public static function for_route( $route_id ) {
		$service_id = (int) get_post_meta( (int) $route_id, '_mytaxi_service_id', true );
		return self::for_service( $service_id );
	}
}
