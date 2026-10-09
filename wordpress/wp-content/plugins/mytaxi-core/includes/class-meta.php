<?php
/**
 * Structured fields for services and routes.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Meta boxes and saving.
 */
class Meta {

	/**
	 * Hook meta boxes.
	 */
	public static function boot() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_mytaxi_service', array( __CLASS__, 'save_service' ) );
		add_action( 'save_post_mytaxi_route', array( __CLASS__, 'save_route' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Register boxes.
	 */
	public static function boxes() {
		add_meta_box( 'mytaxi_service_fields', 'Данни за услугата', array( __CLASS__, 'render_service' ), 'mytaxi_service', 'normal', 'high' );
		add_meta_box( 'mytaxi_route_fields', 'Данни за маршрута', array( __CLASS__, 'render_route' ), 'mytaxi_route', 'normal', 'high' );

		$front_id = (int) get_option( 'page_on_front' );
		$post_id  = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		if ( $front_id && $post_id === $front_id ) {
			add_meta_box( 'mytaxi_front_help', 'Начална страница', array( __CLASS__, 'render_front_help' ), 'page', 'normal', 'high' );
		}
	}

	/**
	 * Admin assets on our edit screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'mytaxi_service', 'mytaxi_route' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'mytaxi-admin', MYTAXI_CORE_URL . 'assets/admin.css', array(), MYTAXI_CORE_VERSION );
		wp_enqueue_script( 'mytaxi-admin', MYTAXI_CORE_URL . 'assets/admin.js', array(), MYTAXI_CORE_VERSION, true );
	}

	/**
	 * Explain the front page fields.
	 */
	public static function render_front_help() {
		echo '<p>Заглавието на страницата е основният H1. Откъсът е краткото описание под него. Основната снимка е изображението в началото. Съдържанието е представянето на бизнеса.</p>';
		echo '<p>Услугите, маршрутите, автопаркът, предимствата, отзивите и общите въпроси се избират от <a href="' . esc_url( admin_url( 'admin.php?page=mytaxi-home' ) ) . '">MyTaxi → Начална страница</a>. Телефоните и цените не се копират ръчно тук.</p>';
	}

	/**
	 * Service fields.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_service( $post ) {
		wp_nonce_field( 'mytaxi_save_service_' . $post->ID, 'mytaxi_service_nonce' );
		$id = (int) $post->ID;

		$email = (string) get_post_meta( $id, '_mytaxi_email', true );
		$phone = (string) get_post_meta( $id, '_mytaxi_phone', true );
		if ( '' === $email ) {
			echo '<div class="notice notice-warning inline"><p>Няма имейл за заявки. Формата на сайта няма да приема запитвания за тази услуга, докато не добавите получател.</p></div>';
		}
		if ( '' === $phone ) {
			echo '<div class="notice notice-warning inline"><p>Няма телефон. Публичният бутон за обаждане остава скрит. Не се използва номер от друга услуга.</p></div>';
		}

		Fields::section( 'Основна информация', 'Типът определя коя форма и кое представяне вижда посетителят.' );
		Fields::select(
			'mytaxi_type',
			'Тип',
			(string) get_post_meta( $id, '_mytaxi_type', true ) ?: 'local_taxi',
			array(
				'local_taxi'        => 'Локално такси',
				'airport_transfer'  => 'Летищен трансфер',
			)
		);
		Fields::textarea( 'mytaxi_intro', 'Кратко въвеждащо описание', (string) get_post_meta( $id, '_mytaxi_intro', true ), 'Показва се под заглавието. Основният текст остава в редактора по-горе.' );
		Fields::input( 'mytaxi_cta', 'Заглавие на основния призив', (string) get_post_meta( $id, '_mytaxi_cta', true ) );
		Fields::lines( 'mytaxi_areas', 'Обслужвани райони', Fields::decode_list( get_post_meta( $id, '_mytaxi_areas', true ) ), 'По един район на ред.' );
		Fields::end_section();

		Fields::section( 'Контакти', 'Тези данни се използват на страницата на услугата и на свързаните маршрути. Празно поле скрива съответния бутон.' );
		Fields::input( 'mytaxi_phone', 'Телефон', $phone, 'Международен формат, например +359…' );
		Fields::input( 'mytaxi_whatsapp', 'WhatsApp номер', (string) get_post_meta( $id, '_mytaxi_whatsapp', true ), 'Оставете празно, ако няма отделен WhatsApp.' );
		Fields::input( 'mytaxi_viber', 'Viber номер', (string) get_post_meta( $id, '_mytaxi_viber', true ), 'Оставете празно, ако Viber не се използва.' );
		Fields::input( 'mytaxi_email', 'Имейл за получаване на заявки', $email, 'Формата изпраща само до този адрес. Адресът не се показва публично.', 'email' );
		Fields::input( 'mytaxi_gbp_url', 'Google Business URL', (string) get_post_meta( $id, '_mytaxi_gbp_url', true ), 'Поставете само проверен адрес на профила.', 'url' );
		Fields::end_section();

		Fields::section( 'Снимки', 'Основната снимка се задава в блока „Основна снимка“. Галерията е допълнителна.' );
		self::gallery( Fields::decode_list( get_post_meta( $id, '_mytaxi_gallery', true ) ) );
		Fields::end_section();

		Fields::section( 'Условия и съдържание' );
		Fields::textarea( 'mytaxi_terms', 'Условия за поръчване', (string) get_post_meta( $id, '_mytaxi_terms', true ), 'Пишете само потвърдени условия.', 6 );
		Fields::lines( 'mytaxi_benefits', 'Потвърдени предимства', Fields::decode_list( get_post_meta( $id, '_mytaxi_benefits', true ) ), 'По едно предимство на ред. Празният списък не се показва.' );
		echo '<p><strong>Въпроси и отговори</strong></p>';
		Fields::repeater(
			'mytaxi_faq',
			Fields::decode_list( get_post_meta( $id, '_mytaxi_faq', true ) ),
			array(
				'question' => 'Въпрос',
				'answer'   => 'Отговор',
			),
			'Добави въпрос'
		);
		Fields::end_section();

		Fields::section( 'Показване' );
		Fields::checkbox( 'mytaxi_show_home', 'Показване на началната страница', '1' === (string) get_post_meta( $id, '_mytaxi_show_home', true ) );
		Fields::input( 'mytaxi_order', 'Ред на показване', (string) get_post_meta( $id, '_mytaxi_order', true ), 'По-малко число се показва по-напред.', 'number' );
		Fields::end_section();

		Fields::section( 'Търсене', 'Ако оставите полетата празни, заглавието и описанието се съставят от името и въведението.' );
		Fields::input( 'mytaxi_meta_title', 'Meta title', (string) get_post_meta( $id, '_mytaxi_meta_title', true ) );
		Fields::textarea( 'mytaxi_meta_description', 'Meta description', (string) get_post_meta( $id, '_mytaxi_meta_description', true ) );
		Fields::end_section();
	}

	/**
	 * Route fields.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_route( $post ) {
		wp_nonce_field( 'mytaxi_save_route_' . $post->ID, 'mytaxi_route_nonce' );
		$id         = (int) $post->ID;
		$service_id = (string) get_post_meta( $id, '_mytaxi_service_id', true );

		echo '<p class="description">Телефонът, WhatsApp, Viber и получателят на заявките идват от избраната услуга. Не се пазят отделно в маршрута.</p>';

		Fields::section( 'Връзка' );
		self::service_select( $service_id );
		Fields::input( 'mytaxi_origin', 'Начална точка', (string) get_post_meta( $id, '_mytaxi_origin', true ) );
		Fields::input( 'mytaxi_destination', 'Крайна точка', (string) get_post_meta( $id, '_mytaxi_destination', true ) );
		Fields::textarea( 'mytaxi_summary', 'Кратко описание', (string) get_post_meta( $id, '_mytaxi_summary', true ), 'Основният текст се редактира в редактора по-горе.' );
		Fields::end_section();

		Fields::section( 'Цена', 'Празната цена се показва като Price on request. Нула не се публикува. „От“ и „за автомобил“ са отделни характеристики.' );
		Fields::input( 'mytaxi_price', 'Цена', (string) get_post_meta( $id, '_mytaxi_price', true ), 'Сума в EUR. Оставете празно, ако не е потвърдена.', 'text' );
		echo '<p class="description">Валутата е EUR и не се сменя.</p>';
		Fields::select(
			'mytaxi_price_type',
			'Тип цена',
			(string) get_post_meta( $id, '_mytaxi_price_type', true ) ?: 'on_request',
			array(
				'fixed'      => 'Фиксирана',
				'from'       => 'От',
				'on_request' => 'При запитване',
			)
		);
		Fields::select(
			'mytaxi_price_basis',
			'Основа на цената',
			(string) get_post_meta( $id, '_mytaxi_price_basis', true ),
			array(
				''               => 'Не е посочена',
				'per_vehicle'    => 'За автомобил',
				'per_passenger'  => 'За пътник',
			)
		);
		Fields::end_section();

		Fields::section( 'Пътуване', 'Показват се само попълнените редове.' );
		Fields::input( 'mytaxi_distance', 'Ориентировъчно разстояние', (string) get_post_meta( $id, '_mytaxi_distance', true ) );
		Fields::input( 'mytaxi_duration', 'Ориентировъчна продължителност', (string) get_post_meta( $id, '_mytaxi_duration', true ) );
		Fields::input( 'mytaxi_capacity', 'Капацитет', (string) get_post_meta( $id, '_mytaxi_capacity', true ), 'Само потвърден капацитет.' );
		Fields::textarea( 'mytaxi_luggage', 'Информация за багажа', (string) get_post_meta( $id, '_mytaxi_luggage', true ) );
		Fields::lines( 'mytaxi_included', 'Включени услуги', Fields::decode_list( get_post_meta( $id, '_mytaxi_included', true ) ), 'По една на ред. Празният списък не се показва.' );
		Fields::textarea( 'mytaxi_meeting', 'Условия за посрещане', (string) get_post_meta( $id, '_mytaxi_meeting', true ) );
		Fields::textarea( 'mytaxi_delay', 'Условия при закъснял полет', (string) get_post_meta( $id, '_mytaxi_delay', true ) );
		Fields::textarea( 'mytaxi_requirements', 'Допълнителни изисквания', (string) get_post_meta( $id, '_mytaxi_requirements', true ) );
		Fields::end_section();

		Fields::section( 'Въпроси и показване' );
		Fields::repeater(
			'mytaxi_faq',
			Fields::decode_list( get_post_meta( $id, '_mytaxi_faq', true ) ),
			array(
				'question' => 'Въпрос',
				'answer'   => 'Отговор',
			),
			'Добави въпрос'
		);
		Fields::checkbox( 'mytaxi_popular', 'Показване сред популярните маршрути', '1' === (string) get_post_meta( $id, '_mytaxi_popular', true ) );
		Fields::input( 'mytaxi_order', 'Ред', (string) get_post_meta( $id, '_mytaxi_order', true ), 'По-малко число се показва по-напред.', 'number' );
		Fields::end_section();

		Fields::section( 'Търсене' );
		Fields::input( 'mytaxi_meta_title', 'Meta title', (string) get_post_meta( $id, '_mytaxi_meta_title', true ) );
		Fields::textarea( 'mytaxi_meta_description', 'Meta description', (string) get_post_meta( $id, '_mytaxi_meta_description', true ) );
		Fields::end_section();
	}

	/**
	 * Save a service.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_service( $post_id ) {
		if ( ! self::authorized( $post_id, 'mytaxi_service_nonce', 'mytaxi_save_service_' . $post_id ) ) {
			return;
		}

		$type = isset( $_POST['mytaxi_type'] ) ? sanitize_text_field( wp_unslash( $_POST['mytaxi_type'] ) ) : 'local_taxi';
		if ( ! in_array( $type, array( 'local_taxi', 'airport_transfer' ), true ) ) {
			$type = 'local_taxi';
		}

		$price_was = '';
		update_post_meta( $post_id, '_mytaxi_type', $type );
		update_post_meta( $post_id, '_mytaxi_intro', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_intro'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_cta', sanitize_text_field( wp_unslash( $_POST['mytaxi_cta'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_areas', Fields::encode_list( Fields::line_list( wp_unslash( $_POST['mytaxi_areas'] ?? '' ) ) ) );
		update_post_meta( $post_id, '_mytaxi_phone', Fields::phone( wp_unslash( $_POST['mytaxi_phone'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_whatsapp', Fields::phone( wp_unslash( $_POST['mytaxi_whatsapp'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_viber', Fields::phone( wp_unslash( $_POST['mytaxi_viber'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_email', Fields::email( wp_unslash( $_POST['mytaxi_email'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_gbp_url', Fields::url( wp_unslash( $_POST['mytaxi_gbp_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_gallery', Fields::encode_list( self::gallery_ids( wp_unslash( $_POST['mytaxi_gallery'] ?? '' ) ) ) );
		update_post_meta( $post_id, '_mytaxi_terms', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_terms'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_benefits', Fields::encode_list( Fields::line_list( wp_unslash( $_POST['mytaxi_benefits'] ?? '' ) ) ) );
		update_post_meta( $post_id, '_mytaxi_faq', Fields::encode_list( self::faqs_from_post() ) );
		update_post_meta( $post_id, '_mytaxi_show_home', isset( $_POST['mytaxi_show_home'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_mytaxi_order', (string) absint( $_POST['mytaxi_order'] ?? 0 ) );
		update_post_meta( $post_id, '_mytaxi_meta_title', sanitize_text_field( wp_unslash( $_POST['mytaxi_meta_title'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_meta_description', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_meta_description'] ?? '' ) ) );
		unset( $price_was );

		if ( ! defined( 'MYTAXI_SETUP_RUNNING' ) ) {
			update_post_meta( $post_id, '_mytaxi_client_edited', '1' );
		}
	}

	/**
	 * Save a route.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_route( $post_id ) {
		if ( ! self::authorized( $post_id, 'mytaxi_route_nonce', 'mytaxi_save_route_' . $post_id ) ) {
			return;
		}

		$service_id = absint( $_POST['mytaxi_service_id'] ?? 0 );
		$service    = get_post( $service_id );
		if ( ! $service || 'mytaxi_service' !== $service->post_type ) {
			$service_id = (int) get_post_meta( $post_id, '_mytaxi_service_id', true );
		}

		$type = isset( $_POST['mytaxi_price_type'] ) ? sanitize_text_field( wp_unslash( $_POST['mytaxi_price_type'] ) ) : 'on_request';
		if ( ! in_array( $type, array( 'fixed', 'from', 'on_request' ), true ) ) {
			$type = 'on_request';
		}
		$basis = isset( $_POST['mytaxi_price_basis'] ) ? sanitize_text_field( wp_unslash( $_POST['mytaxi_price_basis'] ) ) : '';
		if ( ! in_array( $basis, array( '', 'per_vehicle', 'per_passenger' ), true ) ) {
			$basis = '';
		}

		$amount = Fields::price( wp_unslash( $_POST['mytaxi_price'] ?? '' ) );
		if ( '' === $amount ) {
			$type = 'on_request';
		}

		update_post_meta( $post_id, '_mytaxi_service_id', (string) $service_id );
		update_post_meta( $post_id, '_mytaxi_origin', sanitize_text_field( wp_unslash( $_POST['mytaxi_origin'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_destination', sanitize_text_field( wp_unslash( $_POST['mytaxi_destination'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_summary', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_summary'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_price', $amount );
		update_post_meta( $post_id, '_mytaxi_currency', 'EUR' );
		update_post_meta( $post_id, '_mytaxi_price_type', $type );
		update_post_meta( $post_id, '_mytaxi_price_basis', $basis );
		update_post_meta( $post_id, '_mytaxi_distance', sanitize_text_field( wp_unslash( $_POST['mytaxi_distance'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_duration', sanitize_text_field( wp_unslash( $_POST['mytaxi_duration'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_capacity', sanitize_text_field( wp_unslash( $_POST['mytaxi_capacity'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_luggage', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_luggage'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_included', Fields::encode_list( Fields::line_list( wp_unslash( $_POST['mytaxi_included'] ?? '' ) ) ) );
		update_post_meta( $post_id, '_mytaxi_meeting', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_meeting'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_delay', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_delay'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_requirements', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_requirements'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_faq', Fields::encode_list( self::faqs_from_post() ) );
		update_post_meta( $post_id, '_mytaxi_popular', isset( $_POST['mytaxi_popular'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_mytaxi_order', (string) absint( $_POST['mytaxi_order'] ?? 0 ) );
		update_post_meta( $post_id, '_mytaxi_meta_title', sanitize_text_field( wp_unslash( $_POST['mytaxi_meta_title'] ?? '' ) ) );
		update_post_meta( $post_id, '_mytaxi_meta_description', sanitize_textarea_field( wp_unslash( $_POST['mytaxi_meta_description'] ?? '' ) ) );

		if ( ! defined( 'MYTAXI_SETUP_RUNNING' ) ) {
			update_post_meta( $post_id, '_mytaxi_client_edited', '1' );
		}
	}

	/**
	 * Shared authorization for meta saves.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field Nonce field.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	private static function authorized( $post_id, $field, $action ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( ! isset( $_POST[ $field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action ) ) {
			return false;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * FAQ rows from the request.
	 *
	 * @return array<int,array<string,string>>
	 */
	private static function faqs_from_post() {
		$rows = isset( $_POST['mytaxi_faq'] ) ? wp_unslash( $_POST['mytaxi_faq'] ) : array();
		$clean = Fields::clean_repeater( $rows, array( 'question', 'answer' ), true );
		return array_values(
			array_filter(
				$clean,
				static function ( $row ) {
					return '' !== $row['question'] && '' !== $row['answer'];
				}
			)
		);
	}

	/**
	 * Attachment IDs from a comma-separated list.
	 *
	 * @param string $raw Raw list.
	 * @return array<int,int>
	 */
	private static function gallery_ids( $raw ) {
		$ids = array();
		foreach ( preg_split( '/\s*,\s*/', (string) $raw ) as $part ) {
			$id = absint( $part );
			if ( $id && 'attachment' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Gallery picker.
	 *
	 * @param array<int,mixed> $ids Attachment IDs.
	 */
	private static function gallery( array $ids ) {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		echo '<div class="mytaxi-gallery" data-gallery>';
		echo '<input type="hidden" name="mytaxi_gallery" value="' . esc_attr( implode( ',', $ids ) ) . '" data-gallery-input>';
		echo '<ul class="mytaxi-gallery-list" data-gallery-list>';
		foreach ( $ids as $id ) {
			echo '<li data-id="' . esc_attr( (string) $id ) . '">';
			echo '<button type="button" data-gallery-remove aria-label="Премахни снимката">×</button>';
			echo wp_get_attachment_image( $id, 'thumbnail' );
			echo '</li>';
		}
		echo '</ul>';
		echo '<p><button type="button" class="button" data-gallery-pick>Избери снимки от библиотеката</button></p>';
		echo '</div>';
	}

	/**
	 * Service selector.
	 *
	 * @param string $selected Selected ID.
	 */
	private static function service_select( $selected ) {
		$services = get_posts(
			array(
				'post_type'      => 'mytaxi_service',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		echo '<p class="mytaxi-field"><label for="mytaxi-service"><strong>Свързана услуга</strong></label><br>';
		echo '<select id="mytaxi-service" name="mytaxi_service_id">';
		echo '<option value="">Изберете услуга</option>';
		foreach ( $services as $service ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $service->ID,
				selected( (string) $service->ID, (string) $selected, false ),
				esc_html( get_the_title( $service ) )
			);
		}
		echo '</select></p>';
	}
}
