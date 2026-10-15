<?php
/**
 * Contact Form 7 request forms.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the public request forms and sends them to one shared inbox.
 */
class ContactForm {

	const REVISION = '16';

	/**
	 * Context of the submission currently being mailed.
	 *
	 * @var array<string,string>
	 */
	private static $context = array();

	/**
	 * Route and area values written into fields after Contact Form 7 builds them.
	 *
	 * @var array<string,string>
	 */
	private static $fills = array();

	/**
	 * Hook Contact Form 7 when that plugin is available.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'ensure' ), 30 );
		add_action( 'wpcf7_swv_create_schema', array( __CLASS__, 'validation_messages' ), 5, 2 );
		add_filter( 'wpcf7_mail_components', array( __CLASS__, 'mail_components' ), 10, 2 );
		add_filter( 'wpcf7_special_mail_tags', array( __CLASS__, 'mail_tags' ), 10, 3 );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'before_send' ), 10, 3 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'remember_sent' ) );
		add_filter( 'wpcf7_skip_mail', array( __CLASS__, 'skip_honeypot' ), 10, 2 );
		add_filter( 'wpcf7_form_tag', array( __CLASS__, 'prefill' ), 10, 2 );
		add_filter( 'wpcf7_form_elements', array( __CLASS__, 'apply_prefill' ) );
		add_filter( 'wpcf7_validate_tel', array( __CLASS__, 'validate_phone' ), 20, 2 );
		add_filter( 'wpcf7_validate_tel*', array( __CLASS__, 'validate_phone' ), 20, 2 );
		add_filter( 'wpcf7_validate_text', array( __CLASS__, 'validate_time' ), 20, 2 );
		add_filter( 'wpcf7_validate_text*', array( __CLASS__, 'validate_time' ), 20, 2 );
		add_filter( 'wpcf7_validate_text', array( __CLASS__, 'validate_child_age' ), 20, 2 );
		add_filter( 'wpcf7_validate_date', array( __CLASS__, 'validate_date' ), 20, 2 );
		add_filter( 'wpcf7_validate_date*', array( __CLASS__, 'validate_date' ), 20, 2 );
		add_filter( 'wpcf7_validate', array( __CLASS__, 'validate_journey' ), 20, 2 );
	}

	/**
	 * Add specific messages before CF7's defaults, for both browser and server.
	 * Existing rules remain in place; the first failed rule supplies the message.
	 */
	public static function validation_messages( $schema, $contact_form ) {
		if ( ! self::is_ours( $contact_form ) || (int) $contact_form->id() !== (int) get_option( 'mytaxi_cf7_airport_id', 0 ) ) {
			return;
		}
		$messages = array(
			'mytaxi-name'  => __( 'Enter your full name.', 'mytaxi-core' ),
			'mytaxi-phone' => __( 'Enter a phone number including the country code.', 'mytaxi-core' ),
			'mytaxi-email' => __( 'Enter your email address so we can reply.', 'mytaxi-core' ),
			'mytaxi-hotel' => __( 'Enter your hotel name or drop-off address.', 'mytaxi-core' ),
		);
		foreach ( $messages as $field => $message ) {
			$schema->add_rule( wpcf7_swv_create_rule( 'required', array( 'field' => $field, 'error' => $message ) ) );
		}
		$schema->add_rule( wpcf7_swv_create_rule( 'enum', array(
			'field'  => 'mytaxi-passengers',
			'accept' => array_map( 'strval', range( 1, Booking::MAX_PASSENGERS ) ),
			'error'  => __( 'Choose 1–5 passengers. For larger groups, please contact us on WhatsApp or call.', 'mytaxi-core' ),
		) ) );
	}

	/**
	 * Print the form for one service or route.
	 *
	 * @param int $service_id Service ID.
	 * @param int $route_id Route ID.
	 */
	public static function render( $service_id, $route_id = 0 ) {
		unset( $route_id );
		if ( ! function_exists( 'wpcf7_contact_form' ) ) {
			echo '<p class="mt-notice">' . esc_html__( 'Call this service to request a car.', 'mytaxi-core' ) . '</p>';
			return;
		}

		$service = get_post( (int) $service_id );
		if ( ! $service || 'mytaxi_service' !== $service->post_type || 'publish' !== $service->post_status ) {
			return;
		}

		$type    = (string) get_post_meta( $service->ID, '_mytaxi_type', true );
		$option  = 'airport_transfer' === $type ? 'mytaxi_cf7_airport_id' : 'mytaxi_cf7_local_id';
		$form_id = (int) get_option( $option, 0 );
		$form    = wpcf7_contact_form( $form_id );
		if ( ! $form ) {
			self::ensure();
			$form_id = (int) get_option( $option, 0 );
			$form    = wpcf7_contact_form( $form_id );
		}
		if ( ! $form || ! self::can_send() ) {
			self::offer_direct_contact( $service->ID );
			return;
		}

		echo do_shortcode( sprintf( '[contact-form-7 id="%d" html_class="mt-form"]', $form_id ) );
	}

	/**
	 * Production sends only to the shared inbox. Test mode is a separate, explicit switch.
	 *
	 * @return string
	 */
	public static function mail_mode() {
		return 'test' === (string) get_option( 'mytaxi_mail_mode', 'production' ) ? 'test' : 'production';
	}

	/**
	 * Real shared inbox. Empty until the client provides it.
	 *
	 * @return string
	 */
	public static function production_recipient() {
		return Fields::email( (string) get_option( 'mytaxi_request_email', '' ) );
	}

	/**
	 * Inbox used only while test mode is switched on.
	 *
	 * @return string
	 */
	public static function test_recipient() {
		return Fields::email( (string) get_option( 'mytaxi_test_email', '' ) );
	}

	/**
	 * Whether this environment may send a request email.
	 *
	 * @return bool
	 */
	public static function can_send() {
		if ( 'test' === self::mail_mode() ) {
			return '' !== self::test_recipient();
		}
		return '' !== self::production_recipient();
	}

	/**
	 * Recipient for the active mode. Production never falls back to the test address.
	 *
	 * @return string
	 */
	public static function recipient() {
		if ( ! self::can_send() ) {
			return '';
		}
		return 'test' === self::mail_mode() ? self::test_recipient() : self::production_recipient();
	}

	/**
	 * Create or refresh the two forms.
	 */
	public static function ensure() {
		if ( ! class_exists( '\WPCF7_ContactForm' ) || self::REVISION === (string) get_option( 'mytaxi_cf7_revision', '' ) ) {
			return;
		}

		$airport = self::save_form(
			(int) get_option( 'mytaxi_cf7_airport_id', 0 ),
			'MyTaxi airport transfer',
			self::airport_markup(),
			self::mail_body( true )
		);
		$local = self::save_form(
			(int) get_option( 'mytaxi_cf7_local_id', 0 ),
			'MyTaxi local taxi',
			self::local_markup(),
			self::mail_body( false )
		);
		if ( $airport && $local ) {
			update_option( 'mytaxi_cf7_airport_id', $airport, false );
			update_option( 'mytaxi_cf7_local_id', $local, false );
			update_option( 'mytaxi_cf7_revision', self::REVISION, false );
		}
	}

	/**
	 * Keep the active inbox as the only recipient and add the visitor as Reply-To.
	 *
	 * @param array<string,string> $components Mail components.
	 * @param \WPCF7_ContactForm   $contact_form Form.
	 * @return array<string,string>
	 */
	public static function mail_components( $components, $contact_form ) {
		if ( ! self::is_ours( $contact_form ) ) {
			return $components;
		}

		$components['recipient'] = self::recipient();
		$submission              = class_exists( '\WPCF7_Submission' ) ? \WPCF7_Submission::get_instance() : null;
		$posted                  = $submission ? $submission->get_posted_data() : array();
		$email                   = isset( $posted['mytaxi-email'] ) ? sanitize_email( (string) $posted['mytaxi-email'] ) : '';
		if ( is_email( $email ) ) {
			$components['additional_headers'] = trim( (string) $components['additional_headers'] . "\nReply-To: " . $email );
		}
		return $components;
	}

	/**
	 * Service and route names for the mail body.
	 *
	 * @param string $output Current output.
	 * @param string $name Tag name.
	 * @param bool   $html Whether HTML is expected.
	 * @return string
	 */
	public static function mail_tags( $output, $name, $html ) {
		unset( $html );
		$map = array(
			'_mytaxi_service'     => 'service',
			'_mytaxi_route'       => 'route',
			'_mytaxi_page'        => 'page',
			'_mytaxi_subject'     => 'subject',
			'_mytaxi_price'       => 'price',
			'_mytaxi_origin'      => 'origin',
			'_mytaxi_destination' => 'destination',
		);
		if ( ! isset( $map[ $name ] ) ) {
			return $output;
		}
		return (string) ( self::$context[ $map[ $name ] ] ?? '' );
	}

	/**
	 * Accept a request only from a published service or its published route.
	 *
	 * @param \WPCF7_ContactForm $contact_form Form.
	 * @param bool               $abort Whether to abort.
	 * @param \WPCF7_Submission  $submission Submission.
	 */
	public static function before_send( $contact_form, &$abort = false, $submission = null ) {
		if ( ! self::is_ours( $contact_form ) ) {
			return;
		}
		if ( ! $submission && class_exists( '\WPCF7_Submission' ) ) {
			$submission = \WPCF7_Submission::get_instance();
		}
		$context = $submission ? self::context_from_submission( $submission ) : array();
		if ( ! $context ) {
			$abort = true;
			if ( $submission ) {
				$submission->set_response( __( 'This page cannot send a request.', 'mytaxi-core' ) );
				$submission->set_status( 'aborted' );
			}
			return;
		}
		if ( $reason = self::journey_error( $contact_form ) ) {
			$abort = true;
			if ( $submission ) {
				$submission->set_response( $reason );
				$submission->set_status( 'aborted' );
			}
			return;
		}
		if ( ! self::can_send() ) {
			$abort = true;
			if ( $submission ) {
				$submission->set_response( __( 'This request was not sent. Please use WhatsApp or call this service.', 'mytaxi-core' ) );
				$submission->set_status( 'aborted' );
			}
			return;
		}
		$duplicate = self::duplicate_key( $contact_form, $submission );
		if ( '' !== $duplicate && get_transient( $duplicate ) ) {
			$abort = true;
			$submission->set_response( __( 'This request was already sent. Use WhatsApp or call this service if you need to change it.', 'mytaxi-core' ) );
			$submission->set_status( 'aborted' );
			return;
		}
		if ( ! self::allow_rate() ) {
			$abort = true;
			$submission->set_response( __( 'Too many requests. Please call the service or try again later.', 'mytaxi-core' ) );
			$submission->set_status( 'aborted' );
			return;
		}
		self::$context = $context;
	}

	/**
	 * Drop honeypot submissions without sending mail.
	 *
	 * @param bool               $skip Whether to skip mail.
	 * @param \WPCF7_ContactForm $contact_form Form.
	 * @return bool
	 */
	public static function skip_honeypot( $skip, $contact_form ) {
		if ( $skip || ! self::is_ours( $contact_form ) || ! class_exists( '\WPCF7_Submission' ) ) {
			return $skip;
		}
		$submission = \WPCF7_Submission::get_instance();
		$posted     = $submission ? $submission->get_posted_data() : array();
		return ! empty( $posted['mytaxi-company'] );
	}

	/**
	 * Fill the route's pickup and destination.
	 *
	 * @param mixed $tag Form tag.
	 * @param bool  $replace Whether the tag is being replaced.
	 * @return mixed
	 */
	public static function prefill( $tag, $replace = true ) {
		if ( ! $replace ) {
			return $tag;
		}
		$name  = is_object( $tag ) ? (string) $tag->name : (string) ( $tag['name'] ?? '' );
		$value = '';
		if ( is_singular( 'mytaxi_route' ) && in_array( $name, array( 'mytaxi-origin', 'mytaxi-destination' ), true ) ) {
			$route = Data::route( get_queried_object_id() );
			$value = 'mytaxi-origin' === $name ? (string) ( $route['origin'] ?? '' ) : (string) ( $route['destination'] ?? '' );
		} elseif ( is_singular( 'mytaxi_service' ) && 'mytaxi-origin' === $name ) {
			$service = Data::service( get_queried_object_id() );
			if ( ! empty( $service['areas'][0] ) ) {
				$value = (string) $service['areas'][0];
			}
		}
		if ( '' !== $value ) {
			self::$fills[ $name ] = $value;
		}
		return $tag;
	}

	/**
	 * Put the known pickup and destination into the fields, leaving the placeholders in place.
	 *
	 * @param string $html Form HTML.
	 * @return string
	 */
	public static function apply_prefill( $html ) {
		foreach ( self::$fills as $name => $value ) {
			$safe    = esc_attr( $value );
			$pattern = '/<input\b[^>]*\bname="' . preg_quote( $name, '/' ) . '"[^>]*>/';
			$html    = preg_replace_callback(
				$pattern,
				static function ( $matches ) use ( $safe ) {
					$tag = $matches[0];
					if ( is_singular( 'mytaxi_route' ) ) {
						$tag = substr( $tag, 0, -1 ) . ' readonly>';
					}
					if ( preg_match( '/\bvalue="/', $tag ) ) {
						return preg_replace_callback(
							'/\bvalue="[^"]*"/',
							static function () use ( $safe ) {
								return 'value="' . $safe . '"';
							},
							$tag,
							1
						);
					}
					return substr( $tag, 0, -1 ) . ' value="' . $safe . '">';
				},
				$html,
				1
			);
		}
		self::$fills = array();
		return $html;
	}

	/**
	 * Require a usable phone number.
	 *
	 * @param \WPCF7_Validation $result Result.
	 * @param \WPCF7_FormTag    $tag Tag.
	 * @return \WPCF7_Validation
	 */
	public static function validate_phone( $result, $tag ) {
		$name = is_object( $tag ) ? (string) $tag->name : '';
		if ( 'mytaxi-phone' !== $name ) {
			return $result;
		}
		$value  = isset( $_POST['mytaxi-phone'] ) ? sanitize_text_field( wp_unslash( $_POST['mytaxi-phone'] ) ) : '';
		$digits = preg_replace( '/\D+/', '', $value );
		if ( ! is_string( $digits ) || strlen( $digits ) < 8 || strlen( $digits ) > 15 ) {
			$result->invalidate( $tag, __( 'Enter a phone number we can call.', 'mytaxi-core' ) );
		}
		return $result;
	}

	/**
	 * Local taxi form: require HH:MM when a time is entered. The airport form uses Booking.
	 *
	 * @param \WPCF7_Validation $result Result.
	 * @param \WPCF7_FormTag    $tag Tag.
	 * @return \WPCF7_Validation
	 */
	public static function validate_time( $result, $tag ) {
		$name = is_object( $tag ) ? (string) $tag->name : '';
		if ( 'mytaxi-time' !== $name || self::is_airport_submission() ) {
			return $result;
		}
		$value = self::posted_scalar( $name );
		if ( '' !== $value && ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value ) ) {
			$result->invalidate( $tag, __( 'Enter the time as HH:MM.', 'mytaxi-core' ) );
		}
		return $result;
	}

	/**
	 * Local taxi form: keep the date inside the booking window. Today is allowed for a local taxi.
	 *
	 * @param \WPCF7_Validation $result Result.
	 * @param \WPCF7_FormTag    $tag Tag.
	 * @return \WPCF7_Validation
	 */
	public static function validate_date( $result, $tag ) {
		$name = is_object( $tag ) ? (string) $tag->name : '';
		if ( 'mytaxi-date' !== $name || self::is_airport_submission() ) {
			return $result;
		}
		$value = self::posted_scalar( $name );
		if ( '' === $value ) {
			return $result;
		}
		$zone = new \DateTimeZone( 'Europe/Sofia' );
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $zone );
		$now  = new \DateTimeImmutable( 'today', $zone );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $value || $date < $now || $date > $now->modify( '+18 months' ) ) {
			$result->invalidate( $tag, __( 'Enter a date for the journey.', 'mytaxi-core' ) );
		}
		return $result;
	}

	/**
	 * Airport journey rules from Booking: the same rules and messages the browser uses.
	 *
	 * @param \WPCF7_Validation        $result Result.
	 * @param array<int,\WPCF7_FormTag> $tags Form tags.
	 * @return \WPCF7_Validation
	 */
	public static function validate_journey( $result, $tags ) {
		if ( ! self::is_airport_submission() ) {
			return $result;
		}
		$names  = self::journey_fields();
		$errors = array_merge( Booking::journey_errors( self::posted_journey() ), self::route_errors() );
		foreach ( (array) $tags as $tag ) {
			$name = is_object( $tag ) ? (string) $tag->name : '';
			$key  = array_search( $name, $names, true );
			if ( false !== $key && isset( $errors[ $key ] ) ) {
				$result->invalidate( $tag, Booking::message( $errors[ $key ] ) );
			}
		}
		return $result;
	}

	/**
	 * Resolve the submitted CF7 page, never a browser-provided price or route name.
	 */
	private static function route_errors() {
		$submission = \WPCF7_Submission::get_instance();
		$page_id    = $submission ? (int) $submission->get_meta( 'container_post_id' ) : 0;
		if ( 'mytaxi_route' !== get_post_type( $page_id ) ) {
			return array();
		}
		return Booking::route_errors( $page_id, self::posted_scalar( 'mytaxi-origin' ), self::posted_scalar( 'mytaxi-destination' ) );
	}

	/**
	 * Booking keys and the airport form fields that carry them.
	 *
	 * @return array<string,string>
	 */
	private static function journey_fields() {
		return array(
			'origin'      => 'mytaxi-origin',
			'destination' => 'mytaxi-destination',
			'date'        => 'mytaxi-date',
			'time'        => 'mytaxi-time',
			'passengers'  => 'mytaxi-passengers',
			'return_date' => 'mytaxi-return-date',
			'return_time' => 'mytaxi-return-time',
		);
	}

	/**
	 * The submitted journey in Booking's shape.
	 *
	 * @return array<string,mixed>
	 */
	private static function posted_journey() {
		$journey = array( 'return' => self::return_requested() );
		foreach ( self::journey_fields() as $key => $name ) {
			$journey[ $key ] = self::posted_scalar( $name );
		}
		return $journey;
	}

	/**
	 * Whether Contact Form 7 is processing the airport form.
	 *
	 * @return bool
	 */
	private static function is_airport_submission() {
		$form = class_exists( '\WPCF7_ContactForm' ) ? \WPCF7_ContactForm::get_current() : null;
		return $form && (int) $form->id() === (int) get_option( 'mytaxi_cf7_airport_id', 0 );
	}

	/**
	 * Child age is required only when a seat is requested.
	 *
	 * @param \WPCF7_Validation $result Result.
	 * @param \WPCF7_FormTag    $tag Tag.
	 * @return \WPCF7_Validation
	 */
	public static function validate_child_age( $result, $tag ) {
		$name = is_object( $tag ) ? (string) $tag->name : '';
		if ( 'mytaxi-child-age' !== $name || 'Yes' !== self::posted_scalar( 'mytaxi-child-seat' ) ) {
			return $result;
		}
		$value = self::posted_scalar( 'mytaxi-child-age' );
		if ( ! preg_match( '/^(?:1[0-7]|[0-9])$/', $value ) ) {
			$result->invalidate( $tag, __( 'Enter the child’s age in years.', 'mytaxi-core' ) );
		}
		return $result;
	}

	/**
	 * One posted scalar. Arrays from checkboxes use the first value.
	 *
	 * @param string $name Field name.
	 * @return string
	 */
	private static function posted_scalar( $name ) {
		if ( ! isset( $_POST[ $name ] ) ) {
			return '';
		}
		$value = wp_unslash( $_POST[ $name ] );
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Whether the visitor asked for a return on the same route.
	 *
	 * @return bool
	 */
	private static function return_requested() {
		if ( ! isset( $_POST['mytaxi-return'] ) ) {
			return false;
		}
		$value = wp_unslash( $_POST['mytaxi-return'] );
		if ( is_array( $value ) ) {
			return (bool) array_filter( array_map( 'strval', $value ) );
		}
		return '' !== sanitize_text_field( (string) $value );
	}

	/**
	 * Server-side journey rules for the airport form.
	 *
	 * @param \WPCF7_ContactForm $contact_form Form.
	 * @return string
	 */
	private static function journey_error( $contact_form ) {
		if ( (int) $contact_form->id() !== (int) get_option( 'mytaxi_cf7_airport_id', 0 ) ) {
			return '';
		}
		$errors = Booking::journey_errors( self::posted_journey() );
		if ( $errors ) {
			return Booking::message( (string) reset( $errors ) );
		}
		if ( '' === self::posted_scalar( 'mytaxi-hotel' ) ) {
			return __( 'The request was not sent. Enter the hotel or drop-off address.', 'mytaxi-core' );
		}
		if ( 'Yes' === self::posted_scalar( 'mytaxi-child-seat' ) && ! preg_match( '/^(?:1[0-7]|[0-9])$/', self::posted_scalar( 'mytaxi-child-age' ) ) ) {
			return __( 'The request was not sent. Enter the child’s age.', 'mytaxi-core' );
		}
		return '';
	}

	/**
	 * Persist one form.
	 *
	 * @param int    $form_id Existing form ID.
	 * @param string $title Title.
	 * @param string $markup Form markup.
	 * @param string $body Mail body.
	 * @return int
	 */
	private static function save_form( $form_id, $title, $markup, $body ) {
		$contact_form = $form_id ? wpcf7_contact_form( $form_id ) : null;
		if ( ! $contact_form ) {
			$contact_form = \WPCF7_ContactForm::get_template();
		}
		$properties = $contact_form->get_properties();
		$from       = Fields::email( (string) get_option( 'mytaxi_mail_from', '' ) );
		if ( '' === $from ) {
			$from = 'requests@mytaxivarna.com';
		}
		$from_name = sanitize_text_field( (string) get_option( 'mytaxi_mail_from_name', 'MyTaxi Varna' ) );
		if ( '' === $from_name ) {
			$from_name = 'MyTaxi Varna';
		}

		$properties['form']                 = $markup;
		$properties['additional_settings']  = 'acceptance_as_validation: on';
		$properties['mail']['active']       = true;
		$properties['mail']['recipient']    = self::recipient();
		$properties['mail']['sender']       = sprintf( '%s <%s>', $from_name, $from );
		$properties['mail']['subject']      = 'Request: [_mytaxi_subject]';
		$properties['mail']['body']         = $body;
		$properties['mail']['additional_headers'] = '';
		$properties['mail']['attachments']  = '';
		$properties['mail']['use_html']     = false;
		$properties['messages']['mail_sent_ok'] = str_contains( $markup, 'mt-continue' )
			? __( 'Thank you. Your request has been sent. Your booking is confirmed after we agree the price and receive a passenger name and flight number.', 'mytaxi-core' )
			: __( 'Thank you. Your taxi request has been sent. The local team will contact you to confirm availability, the price and pickup.', 'mytaxi-core' );
		$properties['messages']['mail_sent_ng']     = __( 'We couldn’t send your request. Please try again, or use WhatsApp or call this service.', 'mytaxi-core' );
		$properties['messages']['validation_error'] = __( 'The request was not sent. Correct the fields below.', 'mytaxi-core' );
		$properties['messages']['accept_terms']     = __( 'Please confirm that you have read the Privacy Policy.', 'mytaxi-core' );
		$properties['messages']['spam']             = __( 'Please refresh the form and try again.', 'mytaxi-core' );
		$contact_form->set_properties( $properties );
		$contact_form->set_title( $title );
		$contact_form->set_locale( 'en_US' );
		$saved = $contact_form->save();
		return (int) $saved;
	}

	/**
	 * Airport form markup.
	 *
	 * @return string
	 */
	private static function airport_markup() {
		return implode(
			"\n",
			array(
				'<p class="mt-hp" aria-hidden="true"><label> Company [text mytaxi-company autocomplete:off]</label></p>',
				'<div class="mt-step mt-step-1">',
				'<div class="mt-search mt-search--route">',
				'<p class="mt-search__field"><label><span class="mt-search__label">Arrival airport</span> [text mytaxi-origin autocomplete:off placeholder "Pickup"]</label></p>',
				'<p class="mt-search__field"><label><span class="mt-search__label">Going to</span> [text mytaxi-destination autocomplete:off placeholder "Destination"]</label></p>',
				'<div class="mt-search__field mt-search__field--when"><span class="mt-search__label">Flight arrival</span><span class="mt-search__pair">[date mytaxi-date class:mt-picker autocomplete:off] [text mytaxi-time class:mt-picker class:mt-picker--time autocomplete:off]</span></div>',
				'<div class="mt-search__field mt-search__field--return"><span class="mt-search__label">Return</span>[checkbox mytaxi-return use_label_element "Add a return"]</div>',
				'<p class="mt-search__field mt-search__field--small"><label><span class="mt-search__label">Passengers</span> [select mytaxi-passengers first_as_label "Choose" "1" "2" "3" "4" "5"]</label></p>',
				'<p class="mt-search__action"><button type="button" class="mt-btn mt-btn--navy mt-continue">Continue</button></p>',
				'</div>',
				'<div class="mt-return-when" hidden><span class="mt-search__label">Return pickup</span><span class="mt-search__pair">[date mytaxi-return-date class:mt-picker autocomplete:off] [text mytaxi-return-time class:mt-picker class:mt-picker--time autocomplete:off]</span></div>',
				'</div>',
				'<div class="mt-step mt-step-2">',
				'<h2>Contact details</h2>',
				'<div class="mt-pair">',
				'<p class="mt-field"><label><span class="mt-label">Full name *</span> [text* mytaxi-name autocomplete:name placeholder "Full name"]</label></p>',
				'<p class="mt-field"><label><span class="mt-label">Phone *</span> [tel* mytaxi-phone autocomplete:tel placeholder "Include country code"]</label></p>',
				'</div>',
				'<div class="mt-pair">',
				'<p class="mt-field"><label><span class="mt-label">Email *</span> [email* mytaxi-email autocomplete:email placeholder "name@example.com"]</label></p>',
				'<p class="mt-field"><label><span class="mt-label">Flight number</span> [text mytaxi-flight maxlength:20 placeholder "Needed to confirm the booking"]</label></p>',
				'</div>',
				'<p class="mt-field"><label><span class="mt-label">Hotel / drop-off address *</span> [text* mytaxi-hotel maxlength:160 placeholder "Hotel name or address"]</label></p>',
				'<h2>Travel requirements</h2>',
				'<div class="mt-pair">',
				'<p class="mt-field"><label><span class="mt-label">Luggage</span> [text mytaxi-luggage maxlength:160 placeholder "Number and size of bags"]</label></p>',
				'<p class="mt-field"><label><span class="mt-label">Child seat</span> [select mytaxi-child-seat first_as_label "No / Yes (free)" "No" "Yes"]</label></p>',
				'</div>',
				'<p class="mt-field mt-child-age" hidden><label><span class="mt-label">Child’s age *</span> [text mytaxi-child-age maxlength:2 placeholder "Age in years"]</label></p>',
				'<p class="mt-field"><label><span class="mt-label">Additional requirements</span> [textarea mytaxi-notes rows:3 maxlength:2000 placeholder "Anything else we should plan for"]</label></p>',
				'<div class="mt-field mt-accept"><label><span class="mt-accept__control">[acceptance privacy]</span> <span>I have read the Privacy Policy.</span></label> <a class="mt-privacy-link" href="/privacy-policy/">Privacy Policy</a></div>',
				'<p class="mt-field">[submit class:mt-btn class:mt-btn--yellow "Send transfer request"]</p>',
				'<p class="mt-form__note">This form sends a request. Your booking is confirmed after we agree the price and receive a passenger name and flight number. You can send the flight number later.</p>',
				'</div>',
			)
		);
	}

	/**
	 * Local taxi form markup.
	 *
	 * @return string
	 */
	private static function local_markup() {
		return implode(
			"\n",
			array(
				'<p class="mt-hp" aria-hidden="true"><label> Company [text mytaxi-company autocomplete:off]</label></p>',
				'<div class="mt-pair">',
				'<p class="mt-field"><label><span class="mt-sr">Full name</span> [text* mytaxi-name autocomplete:name placeholder "Full name"]</label></p>',
				'<p class="mt-field"><label><span class="mt-sr">Phone</span> [tel* mytaxi-phone autocomplete:tel placeholder "Phone"]</label></p>',
				'</div>',
				'<div class="mt-when">',
				'<p class="mt-field"><label><span class="mt-sr">Date</span> [date* mytaxi-date class:mt-picker min:today max:+18_months autocomplete:off placeholder "Date"]</label></p>',
				'<p class="mt-field"><label><span class="mt-sr">Time</span> [text* mytaxi-time class:mt-picker class:mt-picker--time autocomplete:off placeholder "Time"]</label></p>',
				'</div>',
				'<div class="mt-pair">',
				'<p class="mt-field"><label><span class="mt-sr">Pickup</span> [text* mytaxi-origin autocomplete:off placeholder "Pickup"]</label></p>',
				'<p class="mt-field"><label><span class="mt-sr">Destination</span> [text* mytaxi-destination autocomplete:off placeholder "Destination"]</label></p>',
				'</div>',
				'<p class="mt-field"><label><span class="mt-sr">Email</span> [email mytaxi-email autocomplete:email placeholder "Email, optional"]</label></p>',
				'<div class="mt-field mt-check">[checkbox mytaxi-child-seat use_label_element "Free child seat"]</div>',
				'<p class="mt-field mt-child-age" hidden><label><span class="mt-sr">Child age</span> [text mytaxi-child-age maxlength:80 placeholder "Child’s age"]</label></p>',
				'<p class="mt-field"><label><span class="mt-sr">Notes</span> [textarea mytaxi-notes rows:2 maxlength:2000 placeholder "Luggage or anything else"]</label></p>',
				'<div class="mt-field mt-accept"><label><span class="mt-accept__control">[acceptance privacy]</span> <span>I have read the Privacy Policy.</span></label> <a class="mt-privacy-link" href="/privacy-policy/">Privacy Policy</a></div>',
				'<p class="mt-field">[submit class:mt-btn class:mt-btn--yellow "Send request"]</p>',
				'<p class="mt-form__note">This sends a request. The team confirms the car with you.</p>',
			)
		);
	}

	/**
	 * Mail body.
	 *
	 * @param bool $airport Whether this is the airport form.
	 * @return string
	 */
	private static function mail_body( $airport ) {
		$lines = array(
			'Service: [_mytaxi_service]',
			'Route: [_mytaxi_route]',
			'Page: [_mytaxi_page]',
			'',
			'Name: [mytaxi-name]',
			'Phone: [mytaxi-phone]',
			'Email: [mytaxi-email]',
			'From: [_mytaxi_origin]',
			'To: [_mytaxi_destination]',
			'Date: [mytaxi-date]',
			'Time (Europe/Sofia): [mytaxi-time]',
		);
		if ( $airport ) {
			$lines[] = 'Passengers: [mytaxi-passengers]';
			$lines[] = 'Return requested: [mytaxi-return]';
			$lines[] = 'Return date (Europe/Sofia): [mytaxi-return-date]';
			$lines[] = 'Return time (Europe/Sofia): [mytaxi-return-time]';
			$lines[] = 'Flight number: [mytaxi-flight]';
			$lines[] = 'Hotel / drop-off: [mytaxi-hotel]';
			$lines[] = 'Luggage: [mytaxi-luggage]';
			$lines[] = 'Price shown on the site: [_mytaxi_price]';
		}
		$lines[] = 'Child seat requested: [mytaxi-child-seat]';
		$lines[] = 'Child age: [mytaxi-child-age]';
		$lines[] = '';
		$lines[] = 'Notes:';
		$lines[] = '[mytaxi-notes]';
		$lines[] = '';
		$lines[] = 'This message is a request, not a confirmed booking.';
		return implode( "\n", $lines );
	}

	/**
	 * Show this service's own WhatsApp, call, and Viber when mail cannot be sent.
	 *
	 * @param int $service_id Service ID.
	 */
	private static function offer_direct_contact( $service_id ) {
		$contacts = Contacts::for_service( (int) $service_id );
		echo '<p class="mt-form__note mt-form__note--direct">' . esc_html__( 'Online requests are not open yet. Send your transfer details by WhatsApp or Viber, or call us. We answer 24/7.', 'mytaxi-core' ) . '</p>';
		echo '<div class="mt-actions mt-actions--direct">';
		if ( ! empty( $contacts['whatsapp_url'] ) ) {
			echo '<a class="mt-btn mt-btn--yellow" href="' . esc_url( $contacts['whatsapp_url'] ) . '">WhatsApp</a>';
		}
		if ( ! empty( $contacts['phone_href'] ) ) {
			echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $contacts['phone_href'] ) . '">' . esc_html__( 'Call', 'mytaxi-core' ) . '</a>';
		}
		if ( ! empty( $contacts['viber_url'] ) ) {
			echo '<a class="mt-btn mt-btn--ghost" href="' . esc_url( $contacts['viber_url'] ) . '">Viber</a>';
		}
		echo '</div>';
	}

	/**
	 * Remember a successful send so the same request is not mailed again.
	 *
	 * @param \WPCF7_ContactForm $contact_form Form.
	 */
	public static function remember_sent( $contact_form ) {
		if ( ! self::is_ours( $contact_form ) || ! class_exists( '\WPCF7_Submission' ) ) {
			return;
		}
		$submission = \WPCF7_Submission::get_instance();
		$key        = $submission ? self::duplicate_key( $contact_form, $submission ) : '';
		if ( '' !== $key ) {
			set_transient( $key, 1, 30 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Key for one visitor request.
	 *
	 * @param \WPCF7_ContactForm $contact_form Form.
	 * @param \WPCF7_Submission  $submission Submission.
	 * @return string
	 */
	private static function duplicate_key( $contact_form, $submission ) {
		$posted = $submission->get_posted_data();
		$posted = is_array( $posted ) ? $posted : array();
		$parts  = array( (string) $contact_form->id() );
		foreach ( array( 'mytaxi-name', 'mytaxi-phone', 'mytaxi-email', 'mytaxi-origin', 'mytaxi-destination', 'mytaxi-date', 'mytaxi-time', 'mytaxi-return-date', 'mytaxi-return-time', 'mytaxi-passengers', 'mytaxi-flight', 'mytaxi-hotel', 'mytaxi-luggage', 'mytaxi-child-age' ) as $name ) {
			$value = $posted[ $name ] ?? '';
			$parts[] = is_array( $value ) ? implode( ',', $value ) : (string) $value;
		}
		$seat = $posted['mytaxi-child-seat'] ?? '';
		$parts[] = is_array( $seat ) ? implode( ',', $seat ) : (string) $seat;
		$return = $posted['mytaxi-return'] ?? '';
		$parts[] = is_array( $return ) ? implode( ',', $return ) : (string) $return;
		return 'mytaxi_cf7_once_' . md5( implode( '|', $parts ) );
	}

	/**
	 * Whether the form belongs to MyTaxi.
	 *
	 * @param \WPCF7_ContactForm $contact_form Form.
	 * @return bool
	 */
	private static function is_ours( $contact_form ) {
		if ( ! is_object( $contact_form ) || ! method_exists( $contact_form, 'id' ) ) {
			return false;
		}
		$id = (int) $contact_form->id();
		return $id && in_array( $id, array( (int) get_option( 'mytaxi_cf7_airport_id', 0 ), (int) get_option( 'mytaxi_cf7_local_id', 0 ) ), true );
	}

	/**
	 * Resolve the page that contains the form.
	 *
	 * @param \WPCF7_Submission $submission Submission.
	 * @return array<string,string>
	 */
	private static function context_from_submission( $submission ) {
		$post_id = (int) $submission->get_meta( 'container_post_id' );
		$post    = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return array();
		}

		if ( 'mytaxi_service' === $post->post_type ) {
			return array(
				'service' => get_the_title( $post ),
				'route'   => 'Not a specific route',
				'page'    => (string) get_permalink( $post ),
				'subject' => get_the_title( $post ),
				'price'   => __( 'Confirmed with the route', 'mytaxi-core' ),
				'origin'  => self::posted_scalar( 'mytaxi-origin' ),
				'destination' => self::posted_scalar( 'mytaxi-destination' ),
			);
		}

		if ( 'mytaxi_route' !== $post->post_type ) {
			return array();
		}

		$service_id = (int) get_post_meta( $post->ID, '_mytaxi_service_id', true );
		$service    = get_post( $service_id );
		if ( ! $service || 'mytaxi_service' !== $service->post_type || 'publish' !== $service->post_status ) {
			return array();
		}

		if ( Booking::route_errors( $post->ID, self::posted_scalar( 'mytaxi-origin' ), self::posted_scalar( 'mytaxi-destination' ) ) ) {
			return array();
		}
		$route = Data::route( $post->ID );
		$quote = Booking::quote( (array) ( $route['price'] ?? array() ), self::return_requested() );
		$price = array();
		foreach ( $quote['lines'] as $line ) {
			$price[] = $line['label'] . ': ' . $line['value'];
		}
		if ( '' !== $quote['note'] ) {
			$price[] = $quote['note'];
		}
		return array(
			'service' => get_the_title( $service ),
			'route'   => get_the_title( $post ),
			'page'    => (string) get_permalink( $post ),
			'subject' => get_the_title( $post ),
			'price'   => implode( '; ', $price ),
			'origin'  => (string) $route['origin'],
			'destination' => (string) $route['destination'],
		);
	}

	/**
	 * Limit repeated submissions from one address.
	 *
	 * @return bool
	 */
	private static function allow_rate() {
		if ( defined( 'MYTAXI_VERIFY' ) && MYTAXI_VERIFY ) {
			return true;
		}
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '0';
		$key  = 'mytaxi_cf7_rl_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= 8 ) {
			return false;
		}
		set_transient( $key, $hits + 1, 15 * MINUTE_IN_SECONDS );
		return true;
	}
}
