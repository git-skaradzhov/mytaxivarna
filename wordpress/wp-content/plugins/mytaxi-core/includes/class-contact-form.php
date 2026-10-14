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

	const REVISION = '4';

	/**
	 * Context of the submission currently being mailed.
	 *
	 * @var array<string,string>
	 */
	private static $context = array();

	/**
	 * Hook Contact Form 7 when that plugin is available.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'ensure' ), 30 );
		add_filter( 'wpcf7_mail_components', array( __CLASS__, 'mail_components' ), 10, 2 );
		add_filter( 'wpcf7_special_mail_tags', array( __CLASS__, 'mail_tags' ), 10, 3 );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'before_send' ), 10, 3 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'remember_sent' ) );
		add_filter( 'wpcf7_skip_mail', array( __CLASS__, 'skip_honeypot' ), 10, 2 );
		add_filter( 'wpcf7_form_tag', array( __CLASS__, 'prefill' ), 10, 2 );
		add_filter( 'wpcf7_validate_tel', array( __CLASS__, 'validate_phone' ), 20, 2 );
		add_filter( 'wpcf7_validate_tel*', array( __CLASS__, 'validate_phone' ), 20, 2 );
		add_filter( 'wpcf7_validate_text', array( __CLASS__, 'validate_time' ), 20, 2 );
		add_filter( 'wpcf7_validate_text*', array( __CLASS__, 'validate_time' ), 20, 2 );
		add_filter( 'wpcf7_validate_date', array( __CLASS__, 'validate_date' ), 20, 2 );
		add_filter( 'wpcf7_validate_date*', array( __CLASS__, 'validate_date' ), 20, 2 );
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
			'_mytaxi_service' => 'service',
			'_mytaxi_route'   => 'route',
			'_mytaxi_page'    => 'page',
			'_mytaxi_subject' => 'subject',
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
		if ( ! $replace || ! is_singular( 'mytaxi_route' ) ) {
			return $tag;
		}
		$name = is_object( $tag ) ? (string) $tag->name : (string) ( $tag['name'] ?? '' );
		if ( ! in_array( $name, array( 'mytaxi-origin', 'mytaxi-destination' ), true ) ) {
			return $tag;
		}
		$route = Data::route( get_queried_object_id() );
		$value = 'mytaxi-origin' === $name ? (string) ( $route['origin'] ?? '' ) : (string) ( $route['destination'] ?? '' );
		if ( '' === $value ) {
			return $tag;
		}
		if ( is_object( $tag ) ) {
			$tag->values = array( $value );
			return $tag;
		}
		$tag['values'] = array( $value );
		return $tag;
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
	 * Require HH:MM when a time is entered.
	 *
	 * @param \WPCF7_Validation $result Result.
	 * @param \WPCF7_FormTag    $tag Tag.
	 * @return \WPCF7_Validation
	 */
	public static function validate_time( $result, $tag ) {
		$name = is_object( $tag ) ? (string) $tag->name : '';
		if ( 'mytaxi-time' !== $name ) {
			return $result;
		}
		$value = isset( $_POST['mytaxi-time'] ) ? sanitize_text_field( wp_unslash( $_POST['mytaxi-time'] ) ) : '';
		if ( '' !== $value && ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) ) {
			$result->invalidate( $tag, __( 'Enter the time as HH:MM.', 'mytaxi-core' ) );
		}
		return $result;
	}

	/**
	 * Keep the date inside the booking window.
	 *
	 * @param \WPCF7_Validation $result Result.
	 * @param \WPCF7_FormTag    $tag Tag.
	 * @return \WPCF7_Validation
	 */
	public static function validate_date( $result, $tag ) {
		$name = is_object( $tag ) ? (string) $tag->name : '';
		if ( 'mytaxi-date' !== $name ) {
			return $result;
		}
		$value = isset( $_POST['mytaxi-date'] ) ? sanitize_text_field( wp_unslash( $_POST['mytaxi-date'] ) ) : '';
		if ( '' === $value ) {
			return $result;
		}
		$zone = new \DateTimeZone( 'Europe/Sofia' );
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $zone );
		$now  = new \DateTimeImmutable( 'today', $zone );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $value || $date < $now->modify( '-1 day' ) || $date > $now->modify( '+18 months' ) ) {
			$result->invalidate( $tag, __( 'Enter a date for the journey.', 'mytaxi-core' ) );
		}
		return $result;
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
		$properties['additional_settings']  = '';
		$properties['mail']['active']       = true;
		$properties['mail']['recipient']    = self::recipient();
		$properties['mail']['sender']       = sprintf( '%s <%s>', $from_name, $from );
		$properties['mail']['subject']      = 'Request: [_mytaxi_subject]';
		$properties['mail']['body']         = $body;
		$properties['mail']['additional_headers'] = '';
		$properties['mail']['attachments']  = '';
		$properties['mail']['use_html']     = false;
		$properties['messages']['mail_sent_ok']     = __( 'Your request has been sent. It is not a confirmed booking. The team will contact you to agree the next step.', 'mytaxi-core' );
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
				'<p class="mt-field"><label> Full name * [text* mytaxi-name autocomplete:name]</label></p>',
				'<p class="mt-field"><label> Phone * [tel* mytaxi-phone autocomplete:tel placeholder "+359…"]</label></p>',
				'<p class="mt-field"><label> Email * [email* mytaxi-email autocomplete:email]</label></p>',
				'<p class="mt-field"><label> Flight number [text mytaxi-flight maxlength:20 placeholder "e.g. W6…"]</label></p>',
				'<p class="mt-hint">Optional for a price request. The flight number is needed before an airport pickup can be confirmed.</p>',
				'<div class="mt-when">',
				'<p class="mt-field"><label> Pickup date * [date* mytaxi-date]</label></p>',
				'<p class="mt-field"><label> Pickup time * [text* mytaxi-time autocomplete:off placeholder "HH:MM"]</label></p>',
				'</div>',
				'<p class="mt-field"><label> Pickup location * [text* mytaxi-origin autocomplete:off]</label></p>',
				'<p class="mt-field"><label> Destination * [text* mytaxi-destination autocomplete:off]</label></p>',
				'<div class="mt-pair">',
				'<p class="mt-field"><label> Passengers * [number* mytaxi-passengers min:1 max:16]</label></p>',
				'<p class="mt-field"><label> Luggage (optional) [text mytaxi-luggage maxlength:200]</label></p>',
				'</div>',
				'<div class="mt-field mt-check">[checkbox mytaxi-child-seat use_label_element "Request a free child seat"]</div>',
				'<p class="mt-hint">We confirm whether a seat is available. A particular seat is not promised.</p>',
				'<p class="mt-field"><label> Child’s age, if you request a seat [text mytaxi-child-age maxlength:80]</label></p>',
				'<p class="mt-field"><label> Additional details (optional) [textarea mytaxi-notes maxlength:2000]</label></p>',
				'<p class="mt-field mt-accept"><label><span class="mt-accept__control">[acceptance privacy]</span> <span>I have read the <a href="/privacy-policy/">Privacy Policy</a>.</span></label></p>',
				'<p class="mt-field">[submit class:mt-btn class:mt-btn--yellow "Request a quote"]</p>',
				'<p class="mt-form__note">Sending this form does not confirm the booking. Confirmation needs an agreed price, one passenger’s name, and the flight number.</p>',
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
				'<p class="mt-field"><label> Full name * [text* mytaxi-name autocomplete:name]</label></p>',
				'<p class="mt-field"><label> Phone * [tel* mytaxi-phone autocomplete:tel placeholder "+359…"]</label></p>',
				'<p class="mt-field"><label> Email (optional) [email mytaxi-email autocomplete:email]</label></p>',
				'<div class="mt-when">',
				'<p class="mt-field"><label> Pickup date * [date* mytaxi-date]</label></p>',
				'<p class="mt-field"><label> Pickup time * [text* mytaxi-time autocomplete:off placeholder "HH:MM"]</label></p>',
				'</div>',
				'<p class="mt-field"><label> Pickup location * [text* mytaxi-origin autocomplete:off]</label></p>',
				'<p class="mt-field"><label> Destination * [text* mytaxi-destination autocomplete:off]</label></p>',
				'<div class="mt-field mt-check">[checkbox mytaxi-child-seat use_label_element "Request a free child seat"]</div>',
				'<p class="mt-hint">We confirm whether a seat is available. A particular seat is not promised.</p>',
				'<p class="mt-field"><label> Child’s age, if you request a seat [text mytaxi-child-age maxlength:80]</label></p>',
				'<p class="mt-field"><label> Additional details (optional) [textarea mytaxi-notes maxlength:2000]</label></p>',
				'<p class="mt-field mt-accept"><label><span class="mt-accept__control">[acceptance privacy]</span> <span>I have read the <a href="/privacy-policy/">Privacy Policy</a>.</span></label></p>',
				'<p class="mt-field">[submit class:mt-btn class:mt-btn--yellow "Send request"]</p>',
				'<p class="mt-form__note">Sending this form does not confirm the booking. The team contacts you to agree the journey.</p>',
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
			'From: [mytaxi-origin]',
			'To: [mytaxi-destination]',
			'Date: [mytaxi-date]',
			'Time (Europe/Sofia): [mytaxi-time]',
		);
		if ( $airport ) {
			$lines[] = 'Passengers: [mytaxi-passengers]';
			$lines[] = 'Flight number: [mytaxi-flight]';
			$lines[] = 'Luggage: [mytaxi-luggage]';
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
		echo '<p class="mt-form__note">' . esc_html__( 'Send your request by WhatsApp or call this service.', 'mytaxi-core' ) . '</p>';
		echo '<div class="mt-actions">';
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
		foreach ( array( 'mytaxi-name', 'mytaxi-phone', 'mytaxi-email', 'mytaxi-origin', 'mytaxi-destination', 'mytaxi-date', 'mytaxi-time', 'mytaxi-flight', 'mytaxi-child-age' ) as $name ) {
			$value = $posted[ $name ] ?? '';
			$parts[] = is_array( $value ) ? implode( ',', $value ) : (string) $value;
		}
		$seat = $posted['mytaxi-child-seat'] ?? '';
		$parts[] = is_array( $seat ) ? implode( ',', $seat ) : (string) $seat;
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

		return array(
			'service' => get_the_title( $service ),
			'route'   => get_the_title( $post ),
			'page'    => (string) get_permalink( $post ),
			'subject' => get_the_title( $post ),
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
