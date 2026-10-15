<?php
/**
 * Airport transfer booking rules shared by the forms, the browser, and the checks.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Dates follow the calendar day in Europe/Sofia, whatever the visitor's device says.
 */
class Booking {

	const TIMEZONE = 'Europe/Sofia';

	/**
	 * Largest group for one car. The confirmed cars take up to 4 or up to 5 passengers.
	 */
	const MAX_PASSENGERS = 5;

	/**
	 * How far ahead a request may be dated.
	 */
	const MAX_MONTHS = 18;

	/**
	 * Visitor-facing messages. The browser receives the same text.
	 *
	 * @return array<string,string>
	 */
	public static function messages() {
		return array(
			'date_required'        => __( 'Choose the arrival date.', 'mytaxi-core' ),
			'date_invalid'         => __( 'Enter the date as YYYY-MM-DD.', 'mytaxi-core' ),
			'date_today'           => __( 'For transfers today, please contact us on WhatsApp or call.', 'mytaxi-core' ),
			'date_past'            => __( 'This date has passed. Choose a date from tomorrow.', 'mytaxi-core' ),
			'date_far'             => __( 'Choose a date within the next 18 months.', 'mytaxi-core' ),
			'time_required'        => __( 'Choose the arrival time.', 'mytaxi-core' ),
			'time_invalid'         => __( 'Enter the time as HH:MM.', 'mytaxi-core' ),
			'passengers_required'  => __( 'Choose the number of passengers.', 'mytaxi-core' ),
			'passengers_many'      => __( 'For more than 5 passengers, please contact us on WhatsApp or call.', 'mytaxi-core' ),
			'return_date_required' => __( 'Choose the return date.', 'mytaxi-core' ),
			'return_time_required' => __( 'Choose the return pickup time.', 'mytaxi-core' ),
			'return_order'         => __( 'The return must be after the flight arrival.', 'mytaxi-core' ),
			'origin_required'      => __( 'Enter the pickup point.', 'mytaxi-core' ),
			'destination_required' => __( 'Enter the destination.', 'mytaxi-core' ),
			'destination_choose'   => __( 'Choose your destination.', 'mytaxi-core' ),
		);
	}

	/**
	 * Today's date in Sofia.
	 *
	 * @param \DateTimeImmutable|null $now Clock override for checks.
	 * @return string Y-m-d.
	 */
	public static function today( $now = null ) {
		return self::now( $now )->format( 'Y-m-d' );
	}

	/**
	 * Earliest date for a standard request: tomorrow in Sofia.
	 *
	 * @param \DateTimeImmutable|null $now Clock override for checks.
	 * @return string Y-m-d.
	 */
	public static function earliest( $now = null ) {
		return self::now( $now )->modify( '+1 day' )->format( 'Y-m-d' );
	}

	/**
	 * Latest date accepted.
	 *
	 * @param \DateTimeImmutable|null $now Clock override for checks.
	 * @return string Y-m-d.
	 */
	public static function latest( $now = null ) {
		return self::now( $now )->modify( '+' . self::MAX_MONTHS . ' months' )->format( 'Y-m-d' );
	}

	/**
	 * Check one journey. Keys: origin, destination, date, time, passengers, return (bool), return_date, return_time.
	 *
	 * @param array<string,mixed>     $journey Submitted values.
	 * @param \DateTimeImmutable|null $now Clock override for checks.
	 * @return array<string,string> Field key to error code. Empty when the journey is valid.
	 */
	public static function journey_errors( array $journey, $now = null ) {
		$value = static function ( $key ) use ( $journey ) {
			return trim( (string) ( $journey[ $key ] ?? '' ) );
		};
		$errors = array();

		if ( array_key_exists( 'origin', $journey ) && '' === $value( 'origin' ) ) {
			$errors['origin'] = 'origin_required';
		}
		if ( array_key_exists( 'destination', $journey ) && '' === $value( 'destination' ) ) {
			$errors['destination'] = 'destination_required';
		}

		$date_error = self::date_error( $value( 'date' ), 'date_required', $now );
		if ( $date_error ) {
			$errors['date'] = $date_error;
		}
		$time_error = self::time_error( $value( 'time' ), 'time_required' );
		if ( $time_error ) {
			$errors['time'] = $time_error;
		}

		$passengers = $value( 'passengers' );
		if ( '' === $passengers ) {
			$errors['passengers'] = 'passengers_required';
		} elseif ( ! ctype_digit( $passengers ) || (int) $passengers < 1 ) {
			$errors['passengers'] = 'passengers_required';
		} elseif ( (int) $passengers > self::MAX_PASSENGERS ) {
			$errors['passengers'] = 'passengers_many';
		}

		if ( ! empty( $journey['return'] ) ) {
			$back_date = $value( 'return_date' );
			$back_time = $value( 'return_time' );
			$back_date_error = self::date_error( $back_date, 'return_date_required', $now );
			if ( $back_date_error ) {
				$errors['return_date'] = $back_date_error;
			}
			$back_time_error = self::time_error( $back_time, 'return_time_required' );
			if ( $back_time_error ) {
				$errors['return_time'] = $back_time_error;
			}
			$out  = self::moment( $value( 'date' ), $value( 'time' ) );
			$back = self::moment( $back_date, $back_time );
			if ( ! isset( $errors['return_date'] ) && ! isset( $errors['return_time'] ) && $out && $back && $back <= $out ) {
				$errors[ $back_date === $value( 'date' ) ? 'return_time' : 'return_date' ] = 'return_order';
			}
		}

		return $errors;
	}

	/**
	 * Message for an error code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function message( $code ) {
		$messages = self::messages();
		return $messages[ $code ] ?? '';
	}

	/**
	 * Price lines for the request summary and the email. The browser never supplies the amount.
	 *
	 * @param array<string,mixed> $price Output of Price::present().
	 * @param bool                $return Whether a return was requested.
	 * @return array{confirmed:bool,lines:array<int,array{label:string,value:string}>,note:string}
	 */
	public static function quote( array $price, $return ) {
		$confirmed = ! empty( $price['offer'] ) && 'fixed' === ( $price['type'] ?? '' ) && null !== ( $price['amount'] ?? null );
		if ( ! $confirmed ) {
			$lines = array(
				array(
					'label' => __( 'Price', 'mytaxi-core' ),
					'value' => __( 'Price on request', 'mytaxi-core' ),
				),
			);
			if ( $return ) {
				$lines[] = array(
					'label' => __( 'Return', 'mytaxi-core' ),
					'value' => __( 'Return requested', 'mytaxi-core' ),
				);
			}
			return array(
				'confirmed' => false,
				'lines'     => $lines,
				'note'      => $return
					? __( 'The team will confirm the total price for both journeys.', 'mytaxi-core' )
					: __( 'The team will confirm the price for the car.', 'mytaxi-core' ),
			);
		}

		$one = (string) $price['text'];
		if ( ! $return ) {
			return array(
				'confirmed' => true,
				'lines'     => array(
					array(
						'label' => __( 'Price for the car', 'mytaxi-core' ),
						'value' => $one,
					),
				),
				'note'      => '',
			);
		}
		return array(
			'confirmed' => true,
			'lines'     => array(
				array(
					'label' => __( 'Outbound transfer', 'mytaxi-core' ),
					'value' => $one,
				),
				array(
					'label' => __( 'Return transfer', 'mytaxi-core' ),
					'value' => $one,
				),
				array(
					'label' => __( 'Total for both journeys', 'mytaxi-core' ),
					'value' => self::euro( 2 * (float) $price['amount'] ),
				),
			),
			'note'      => '',
		);
	}

	/**
	 * Rules and messages handed to the browser.
	 *
	 * @return array<string,mixed>
	 */
	public static function client_rules() {
		return array(
			'timezone'      => self::TIMEZONE,
			'maxPassengers' => self::MAX_PASSENGERS,
			'maxMonths'     => self::MAX_MONTHS,
			'messages'      => self::messages(),
			'labels'        => array(
				'price'          => __( 'Price', 'mytaxi-core' ),
				'priceOnRequest' => __( 'Price on request', 'mytaxi-core' ),
				'priceForCar'    => __( 'Price for the car', 'mytaxi-core' ),
				'outbound'       => __( 'Outbound transfer', 'mytaxi-core' ),
				'return'         => __( 'Return transfer', 'mytaxi-core' ),
				'returnShort'    => __( 'Return', 'mytaxi-core' ),
				'returnAsked'    => __( 'Return requested', 'mytaxi-core' ),
				'total'          => __( 'Total for both journeys', 'mytaxi-core' ),
				'noteBoth'       => __( 'The team will confirm the total price for both journeys.', 'mytaxi-core' ),
				'noteOne'        => __( 'The team will confirm the price for the car.', 'mytaxi-core' ),
				'notRequested'   => __( 'Not requested', 'mytaxi-core' ),
				'localTime'      => __( 'local time', 'mytaxi-core' ),
			),
		);
	}

	/**
	 * Euro amount without zero cents.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function euro( $amount ) {
		$amount = round( (float) $amount, 2 );
		return '€' . ( floor( $amount ) === $amount ? number_format( $amount, 0, '.', '' ) : number_format( $amount, 2, '.', '' ) );
	}

	/**
	 * Date error code, or empty string.
	 *
	 * @param string                  $date Y-m-d.
	 * @param string                  $required Code when empty.
	 * @param \DateTimeImmutable|null $now Clock override.
	 * @return string
	 */
	private static function date_error( $date, $required, $now ) {
		if ( '' === $date ) {
			return $required;
		}
		$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, new \DateTimeZone( self::TIMEZONE ) );
		if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
			return 'date_invalid';
		}
		$today = self::today( $now );
		if ( $date < $today ) {
			return 'date_past';
		}
		if ( $date === $today ) {
			return 'date_today';
		}
		if ( $date > self::latest( $now ) ) {
			return 'date_far';
		}
		return '';
	}

	/**
	 * Time error code, or empty string.
	 *
	 * @param string $time HH:MM.
	 * @param string $required Code when empty.
	 * @return string
	 */
	private static function time_error( $time, $required ) {
		if ( '' === $time ) {
			return $required;
		}
		return preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time ) ? '' : 'time_invalid';
	}

	/**
	 * Sofia moment from a date and time.
	 *
	 * @param string $date Y-m-d.
	 * @param string $time HH:MM.
	 * @return \DateTimeImmutable|null
	 */
	private static function moment( $date, $time ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d/', $time ) ) {
			return null;
		}
		$moment = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . substr( $time, 0, 5 ), new \DateTimeZone( self::TIMEZONE ) );
		return $moment ?: null;
	}

	/**
	 * Current Sofia time.
	 *
	 * @param \DateTimeImmutable|null $now Override.
	 * @return \DateTimeImmutable
	 */
	private static function now( $now ) {
		$zone = new \DateTimeZone( self::TIMEZONE );
		return $now instanceof \DateTimeImmutable ? $now->setTimezone( $zone ) : new \DateTimeImmutable( 'now', $zone );
	}
}
