<?php
/**
 * Price presentation. One source for every public surface.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Turns route price meta into a public label and schema payload.
 */
class Price {

	/**
	 * Present a route price.
	 *
	 * @param array<string,mixed> $meta Route meta.
	 * @return array<string,mixed>
	 */
	public static function present( array $meta ) {
		$type   = isset( $meta['price_type'] ) ? (string) $meta['price_type'] : 'on_request';
		$basis  = isset( $meta['price_basis'] ) ? (string) $meta['price_basis'] : '';
		$amount = Fields::price( isset( $meta['price'] ) ? (string) $meta['price'] : '' );

		if ( ! in_array( $type, array( 'fixed', 'from', 'on_request' ), true ) ) {
			$type = 'on_request';
		}
		if ( ! in_array( $basis, array( 'per_vehicle', 'per_passenger' ), true ) ) {
			$basis = '';
		}

		$basis_label = '';
		if ( 'per_passenger' === $basis ) {
			$basis_label = __( 'Per passenger', 'mytaxi-core' );
		} elseif ( 'per_vehicle' === $basis ) {
			$basis_label = __( 'Per vehicle', 'mytaxi-core' );
		}

		if ( 'on_request' === $type || '' === $amount ) {
			return array(
				'text'        => __( 'Price on request', 'mytaxi-core' ),
				'amount'      => null,
				'currency'    => 'EUR',
				'type'        => 'on_request',
				'basis'       => $basis,
				'basis_label' => $basis_label,
				'offer'       => false,
			);
		}

		$formatted = '€' . self::format_amount( $amount );
		$text      = ( 'from' === $type )
			? sprintf(
				/* translators: %s: formatted euro amount */
				__( 'From %s', 'mytaxi-core' ),
				$formatted
			)
			: $formatted;

		return array(
			'text'        => $text,
			'amount'      => (float) $amount,
			'currency'    => 'EUR',
			'type'        => $type,
			'basis'       => $basis,
			'basis_label' => $basis_label,
			'offer'       => true,
		);
	}

	/**
	 * Format a stored decimal without trailing zeros when they are unnecessary.
	 *
	 * @param string $amount Normalized amount.
	 * @return string
	 */
	private static function format_amount( $amount ) {
		if ( str_ends_with( $amount, '.00' ) ) {
			return substr( $amount, 0, -3 );
		}
		return rtrim( rtrim( $amount, '0' ), '.' );
	}
}
