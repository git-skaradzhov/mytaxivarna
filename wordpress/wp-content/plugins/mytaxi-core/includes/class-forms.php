<?php
/**
 * Inquiry forms.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Validates and sends service inquiries.
 */
class Forms {

	/**
	 * Hook handlers.
	 */
	public static function boot() {
		add_action( 'admin_post_nopriv_mytaxi_inquiry', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_mytaxi_inquiry', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Issue a one-time form token.
	 *
	 * @return string
	 */
	public static function issue_token() {
		$token = wp_generate_password( 20, false, false );
		set_transient(
			'mytaxi_tok_' . $token,
			array(
				'status' => 'open',
				'at'     => time(),
			),
			2 * HOUR_IN_SECONDS
		);
		return $token;
	}

	/**
	 * Values and errors for the current form view.
	 *
	 * @param int $service_id Service ID.
	 * @param int $route_id Route ID.
	 * @return array<string,mixed>
	 */
	public static function view( $service_id, $route_id = 0 ) {
		$service_id = (int) $service_id;
		$route_id   = (int) $route_id;
		$service    = Data::service( $service_id );
		$route      = $route_id ? Data::route( $route_id ) : array();
		$result     = array(
			'ok'     => false,
			'code'   => '',
			'errors' => array(),
			'old'    => array(),
		);
		$ref = isset( $_GET['mytaxi_inquiry'] ) ? sanitize_key( wp_unslash( $_GET['mytaxi_inquiry'] ) ) : '';
		if ( $ref ) {
			$stored = get_transient( 'mytaxi_result_' . $ref );
			if ( is_array( $stored ) ) {
				$result = array_merge( $result, $stored );
			}
		}

		$old = is_array( $result['old'] ) ? $result['old'] : array();
		$origin = (string) ( $old['origin'] ?? ( $route['origin'] ?? '' ) );
		$destination = (string) ( $old['destination'] ?? ( $route['destination'] ?? '' ) );

		return array(
			'service'      => $service,
			'route'        => $route,
			'type'         => (string) ( $service['type'] ?? 'local_taxi' ),
			'can_send'     => ! empty( $service['contacts']['email'] ),
			'token'        => self::issue_token(),
			'started'      => time(),
			'ok'           => ! empty( $result['ok'] ),
			'code'         => (string) $result['code'],
			'errors'       => is_array( $result['errors'] ) ? $result['errors'] : array(),
			'old'          => array(
				'name'        => (string) ( $old['name'] ?? '' ),
				'phone'       => (string) ( $old['phone'] ?? '' ),
				'email'       => (string) ( $old['email'] ?? '' ),
				'origin'      => $origin,
				'destination' => $destination,
				'datetime'    => (string) ( $old['datetime'] ?? '' ),
				'passengers'  => (string) ( $old['passengers'] ?? '' ),
				'luggage'     => (string) ( $old['luggage'] ?? '' ),
				'flight'      => (string) ( $old['flight'] ?? '' ),
				'notes'       => (string) ( $old['notes'] ?? '' ),
			),
			'return_url'   => $route_id ? (string) ( $route['url'] ?? '' ) : (string) ( $service['url'] ?? '' ),
		);
	}

	/**
	 * Human error text.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	public static function message( $code ) {
		$messages = array(
			'name_required'       => __( 'Enter your name.', 'mytaxi-core' ),
			'phone_required'      => __( 'Enter a phone number.', 'mytaxi-core' ),
			'phone_invalid'       => __( 'Enter a phone number with at least 8 digits.', 'mytaxi-core' ),
			'email_required'      => __( 'Enter your email address.', 'mytaxi-core' ),
			'email_invalid'       => __( 'Enter a valid email address.', 'mytaxi-core' ),
			'origin_required'     => __( 'Enter the pickup point.', 'mytaxi-core' ),
			'destination_required'=> __( 'Enter the destination.', 'mytaxi-core' ),
			'datetime_required'   => __( 'Enter the date and time.', 'mytaxi-core' ),
			'datetime_invalid'    => __( 'Enter a valid date and time in the Europe/Sofia timezone.', 'mytaxi-core' ),
			'passengers_required' => __( 'Enter the number of passengers.', 'mytaxi-core' ),
			'passengers_invalid'  => __( 'Enter a passenger count from 1 to 16.', 'mytaxi-core' ),
			'no_recipient'        => __( 'Online requests are not available for this service because no request email is set.', 'mytaxi-core' ),
			'service_invalid'     => __( 'This service is not available.', 'mytaxi-core' ),
			'route_invalid'       => __( 'This route is not available.', 'mytaxi-core' ),
			'rate_limited'        => __( 'Too many requests were sent. Please wait and try again, or call the service.', 'mytaxi-core' ),
			'rejected'            => __( 'The request could not be sent. Please try again.', 'mytaxi-core' ),
			'token'               => __( 'The form expired. Please submit it again.', 'mytaxi-core' ),
			'mail_failed'         => __( 'The request was not sent. Please call the service or try again later.', 'mytaxi-core' ),
			'mail_from'           => __( 'The request was not sent because the site mail sender is not configured.', 'mytaxi-core' ),
			'sent'                => __( 'Your request has been sent to the team. They still need to confirm it with you. This is not a confirmed booking.', 'mytaxi-core' ),
		);
		return $messages[ $code ] ?? $messages['rejected'];
	}

	/**
	 * Handle a browser submission and redirect back.
	 */
	public static function handle() {
		$input  = wp_unslash( $_POST );
		$result = self::inspect( is_array( $input ) ? $input : array() );
		$ref    = wp_generate_password( 12, false, false );
		set_transient(
			'mytaxi_result_' . $ref,
			array(
				'ok'     => $result['ok'],
				'code'   => $result['code'],
				'errors' => $result['errors'],
				'old'    => $result['ok'] ? array() : $result['old'],
			),
			15 * MINUTE_IN_SECONDS
		);
		wp_safe_redirect( add_query_arg( 'mytaxi_inquiry', $ref, $result['return_url'] ) );
		exit;
	}

	/**
	 * Validate and maybe send. Does not redirect.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>
	 */
	public static function inspect( array $input ) {
		$old = self::old_input( $input );
		$return = self::return_url( $input );
		$fail = static function ( $code, array $errors = array() ) use ( $old, $return, $input ) {
			self::reopen_token( (string) ( $input['mytaxi_token'] ?? '' ) );
			return array(
				'ok'         => false,
				'code'       => $code,
				'errors'     => $errors,
				'old'        => $old,
				'return_url' => $return,
			);
		};

		$token = sanitize_text_field( (string) ( $input['mytaxi_token'] ?? '' ) );
		$state = get_transient( 'mytaxi_tok_' . $token );
		if ( ! is_array( $state ) || empty( $state['status'] ) ) {
			return $fail( 'token' );
		}
		if ( 'sent' === $state['status'] ) {
			return array(
				'ok'         => true,
				'code'       => 'sent',
				'errors'     => array(),
				'old'        => array(),
				'return_url' => $return,
			);
		}

		if ( ! self::allow_rate() ) {
			return $fail( 'rate_limited' );
		}
		if ( ! empty( $input['mytaxi_company'] ) ) {
			return $fail( 'rejected' );
		}
		$started = isset( $input['mytaxi_started'] ) ? (int) $input['mytaxi_started'] : 0;
		if ( $started < 1 || ( time() - $started ) < 2 || ( time() - $started ) > 2 * DAY_IN_SECONDS ) {
			return $fail( 'rejected' );
		}

		$nonce = (string) ( $input['mytaxi_nonce'] ?? '' );
		if ( ! wp_verify_nonce( $nonce, 'mytaxi_inquiry' ) ) {
			return $fail( 'token' );
		}

		$service_id = absint( $input['mytaxi_service_id'] ?? 0 );
		$route_id   = absint( $input['mytaxi_route_id'] ?? 0 );
		$service    = get_post( $service_id );
		if ( ! $service || 'mytaxi_service' !== $service->post_type || 'publish' !== $service->post_status ) {
			return $fail( 'service_invalid' );
		}

		$route = null;
		if ( $route_id ) {
			$route = get_post( $route_id );
			$owner = (int) get_post_meta( $route_id, '_mytaxi_service_id', true );
			if ( ! $route || 'mytaxi_route' !== $route->post_type || 'publish' !== $route->post_status || $owner !== $service_id ) {
				return $fail( 'route_invalid' );
			}
		}

		$recipient = Fields::email( (string) get_post_meta( $service_id, '_mytaxi_email', true ) );
		if ( '' === $recipient ) {
			return $fail( 'no_recipient' );
		}

		$type   = (string) get_post_meta( $service_id, '_mytaxi_type', true );
		$errors = self::validate( $old, $type );
		if ( $errors ) {
			return $fail( $errors[0], $errors );
		}

		$from = Fields::email( (string) get_option( 'mytaxi_mail_from', '' ) );
		if ( '' === $from ) {
			return $fail( 'mail_from' );
		}

		$sent = self::send( $service, $route, $old, $recipient, $from, $type );
		if ( ! $sent ) {
			return $fail( 'mail_failed' );
		}

		set_transient( 'mytaxi_tok_' . $token, array( 'status' => 'sent', 'at' => time() ), 2 * HOUR_IN_SECONDS );
		return array(
			'ok'         => true,
			'code'       => 'sent',
			'errors'     => array(),
			'old'        => array(),
			'return_url' => $return,
		);
	}

	/**
	 * Keep a failed token reusable.
	 *
	 * @param string $token Token.
	 */
	private static function reopen_token( $token ) {
		if ( '' === $token ) {
			return;
		}
		$state = get_transient( 'mytaxi_tok_' . $token );
		if ( is_array( $state ) && 'sent' !== ( $state['status'] ?? '' ) ) {
			$state['status'] = 'open';
			set_transient( 'mytaxi_tok_' . $token, $state, 2 * HOUR_IN_SECONDS );
		}
	}

	/**
	 * Rate limit by remote address.
	 *
	 * @return bool
	 */
	private static function allow_rate() {
		if ( defined( 'MYTAXI_VERIFY' ) && MYTAXI_VERIFY ) {
			return true;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '0';
		$key = 'mytaxi_rl_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= 8 ) {
			return false;
		}
		set_transient( $key, $hits + 1, 15 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Sanitized submitted values.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,string>
	 */
	private static function old_input( array $input ) {
		return array(
			'name'        => Fields::header_safe( (string) ( $input['mytaxi_name'] ?? '' ) ),
			'phone'       => sanitize_text_field( (string) ( $input['mytaxi_phone'] ?? '' ) ),
			'email'       => Fields::header_safe( (string) ( $input['mytaxi_email'] ?? '' ) ),
			'origin'      => sanitize_text_field( (string) ( $input['mytaxi_origin'] ?? '' ) ),
			'destination' => sanitize_text_field( (string) ( $input['mytaxi_destination'] ?? '' ) ),
			'datetime'    => sanitize_text_field( (string) ( $input['mytaxi_datetime'] ?? '' ) ),
			'passengers'  => sanitize_text_field( (string) ( $input['mytaxi_passengers'] ?? '' ) ),
			'luggage'     => sanitize_text_field( (string) ( $input['mytaxi_luggage'] ?? '' ) ),
			'flight'      => Fields::header_safe( (string) ( $input['mytaxi_flight'] ?? '' ) ),
			'notes'       => sanitize_textarea_field( (string) ( $input['mytaxi_notes'] ?? '' ) ),
		);
	}

	/**
	 * Field validation for the service type stored on the server.
	 *
	 * @param array<string,string> $old Values.
	 * @param string               $type Service type.
	 * @return array<int,string>
	 */
	private static function validate( array $old, $type ) {
		$errors = array();
		$airport = ( 'airport_transfer' === $type );

		if ( strlen( $old['name'] ) < 2 || strlen( $old['name'] ) > 80 ) {
			$errors[] = 'name_required';
		}
		$digits = preg_replace( '/\D+/', '', $old['phone'] );
		if ( '' === $old['phone'] ) {
			$errors[] = 'phone_required';
		} elseif ( ! is_string( $digits ) || strlen( $digits ) < 8 || strlen( $digits ) > 15 ) {
			$errors[] = 'phone_invalid';
		}
		if ( $airport && '' === $old['email'] ) {
			$errors[] = 'email_required';
		} elseif ( '' !== $old['email'] && ! is_email( $old['email'] ) ) {
			$errors[] = 'email_invalid';
		}
		if ( '' === $old['origin'] || strlen( $old['origin'] ) > 160 ) {
			$errors[] = 'origin_required';
		}
		if ( '' === $old['destination'] || strlen( $old['destination'] ) > 160 ) {
			$errors[] = 'destination_required';
		}
		if ( $airport && '' === $old['datetime'] ) {
			$errors[] = 'datetime_required';
		} elseif ( '' !== $old['datetime'] && ! self::parse_datetime( $old['datetime'] ) ) {
			$errors[] = 'datetime_invalid';
		}
		if ( '' === $old['passengers'] ) {
			$errors[] = 'passengers_required';
		} elseif ( ! ctype_digit( $old['passengers'] ) || (int) $old['passengers'] < 1 || (int) $old['passengers'] > 16 ) {
			$errors[] = 'passengers_invalid';
		}
		if ( strlen( $old['notes'] ) > 2000 || strlen( $old['luggage'] ) > 200 || strlen( $old['flight'] ) > 20 ) {
			$errors[] = 'rejected';
		}
		return $errors;
	}

	/**
	 * Parse a datetime-local value in Europe/Sofia.
	 *
	 * @param string $value Raw value.
	 * @return \DateTimeImmutable|null
	 */
	private static function parse_datetime( $value ) {
		$zone = new \DateTimeZone( 'Europe/Sofia' );
		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, $zone );
		if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $value ) {
			return null;
		}
		$now = new \DateTimeImmutable( 'now', $zone );
		if ( $date < $now->modify( '-1 day' ) || $date > $now->modify( '+18 months' ) ) {
			return null;
		}
		return $date;
	}

	/**
	 * A return URL that belongs to the submitted service or route.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return string
	 */
	private static function return_url( array $input ) {
		$service_id = absint( $input['mytaxi_service_id'] ?? 0 );
		$route_id   = absint( $input['mytaxi_route_id'] ?? 0 );
		$fallback   = $route_id ? (string) get_permalink( $route_id ) : (string) get_permalink( $service_id );
		if ( ! $fallback ) {
			$fallback = home_url( '/' );
		}
		$candidate = isset( $input['mytaxi_return'] ) ? esc_url_raw( (string) $input['mytaxi_return'] ) : '';
		$target_id = $candidate ? url_to_postid( $candidate ) : 0;
		if ( $target_id && ( $target_id === $service_id || $target_id === $route_id ) ) {
			return $candidate;
		}
		return $fallback;
	}

	/**
	 * Send the inquiry.
	 *
	 * @param \WP_Post      $service Service.
	 * @param \WP_Post|null $route Route.
	 * @param array<string,string> $old Values.
	 * @param string        $recipient Recipient.
	 * @param string        $from From email.
	 * @param string        $type Service type.
	 * @return bool
	 */
	private static function send( $service, $route, array $old, $recipient, $from, $type ) {
		$from_name = Fields::header_safe( (string) get_option( 'mytaxi_mail_from_name', get_bloginfo( 'name' ) ) );
		if ( '' === $from_name ) {
			$from_name = 'MyTaxi Varna';
		}
		$subject = Fields::header_safe( 'Request: ' . ( $route ? get_the_title( $route ) : get_the_title( $service ) ) );
		$page    = $route ? get_permalink( $route ) : get_permalink( $service );
		$when    = '';
		if ( '' !== $old['datetime'] ) {
			$parsed = self::parse_datetime( $old['datetime'] );
			$when   = $parsed ? $parsed->format( 'Y-m-d H:i T' ) : $old['datetime'];
		}

		$lines = array(
			'Service: ' . get_the_title( $service ),
			'Route: ' . ( $route ? get_the_title( $route ) : 'Not a specific route' ),
			'Page: ' . $page,
			'Service type: ' . ( 'airport_transfer' === $type ? 'Airport transfer' : 'Local taxi' ),
			'',
			'Name: ' . $old['name'],
			'Phone: ' . $old['phone'],
			'Email: ' . ( $old['email'] ? $old['email'] : 'Not provided' ),
			'Pickup: ' . $old['origin'],
			'Destination: ' . $old['destination'],
			'Date and time (Europe/Sofia): ' . ( $when ? $when : 'Not provided' ),
			'Passengers: ' . $old['passengers'],
			'Flight number: ' . ( $old['flight'] ? $old['flight'] : 'Not provided' ),
			'Luggage: ' . ( $old['luggage'] ? $old['luggage'] : 'Not provided' ),
			'',
			'Additional requirements:',
			$old['notes'] ? $old['notes'] : 'None',
			'',
			'This message is a request. It is not a confirmed booking.',
		);

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from . '>',
		);
		if ( is_email( $old['email'] ) ) {
			$headers[] = 'Reply-To: ' . $old['name'] . ' <' . $old['email'] . '>';
		}

		return (bool) wp_mail( $recipient, $subject, implode( "\n", $lines ), $headers );
	}
}
