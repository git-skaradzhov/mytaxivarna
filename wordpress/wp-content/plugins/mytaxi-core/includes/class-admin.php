<?php
/**
 * Admin screens and list columns.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Bulgarian admin screens.
 */
class Admin {

	/**
	 * Hook admin UI.
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_filter( 'manage_mytaxi_service_posts_columns', array( __CLASS__, 'service_columns' ) );
		add_action( 'manage_mytaxi_service_posts_custom_column', array( __CLASS__, 'service_column' ), 10, 2 );
		add_filter( 'manage_mytaxi_route_posts_columns', array( __CLASS__, 'route_columns' ) );
		add_action( 'manage_mytaxi_route_posts_custom_column', array( __CLASS__, 'route_column' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'sample_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'missing_contact_notice' ) );
		add_action( 'admin_post_mytaxi_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_mytaxi_save_home', array( __CLASS__, 'save_home' ) );
		add_action( 'admin_post_mytaxi_save_prices', array( __CLASS__, 'save_prices' ) );
		add_action( 'admin_post_mytaxi_run_setup', array( __CLASS__, 'run_setup' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Settings menu. Logo and site name stay in WordPress site identity.
	 */
	public static function menu() {
		add_menu_page( 'MyTaxi', 'MyTaxi', 'manage_options', 'mytaxi-settings', array( __CLASS__, 'render_settings' ), 'dashicons-admin-generic', 58 );
		add_submenu_page( 'mytaxi-settings', 'Общи настройки', 'Общи настройки', 'manage_options', 'mytaxi-settings', array( __CLASS__, 'render_settings' ) );
		add_submenu_page( 'mytaxi-settings', 'Начална страница', 'Начална страница', 'manage_options', 'mytaxi-home', array( __CLASS__, 'render_home' ) );
		add_submenu_page( 'mytaxi-settings', 'Цени', 'Цени', 'edit_posts', 'mytaxi-prices', array( __CLASS__, 'render_prices' ) );
		add_submenu_page( 'mytaxi-settings', 'Първоначална настройка', 'Първоначална настройка', 'manage_options', 'mytaxi-setup', array( __CLASS__, 'render_setup' ) );
	}

	/**
	 * Load admin assets on MyTaxi screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'toplevel_page_mytaxi-settings', 'mytaxi_page_mytaxi-home', 'mytaxi_page_mytaxi-setup', 'mytaxi_page_mytaxi-prices' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'mytaxi-admin', MYTAXI_CORE_URL . 'assets/admin.css', array(), MYTAXI_CORE_VERSION );
		wp_enqueue_script( 'mytaxi-admin', MYTAXI_CORE_URL . 'assets/admin.js', array(), MYTAXI_CORE_VERSION, true );
	}

	/**
	 * Service list columns.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public static function service_columns( $columns ) {
		$next = array();
		foreach ( $columns as $key => $label ) {
			$next[ $key ] = $label;
			if ( 'title' === $key ) {
				$next['mytaxi_type']   = 'Тип';
				$next['mytaxi_phone']  = 'Телефон';
				$next['mytaxi_status'] = 'Статус';
			}
		}
		return $next;
	}

	/**
	 * Service column values.
	 *
	 * @param string $column Column.
	 * @param int    $post_id Post ID.
	 */
	public static function service_column( $column, $post_id ) {
		if ( 'mytaxi_type' === $column ) {
			$type = (string) get_post_meta( $post_id, '_mytaxi_type', true );
			echo esc_html( 'airport_transfer' === $type ? 'Летищен трансфер' : 'Локално такси' );
		}
		if ( 'mytaxi_phone' === $column ) {
			$phone = (string) get_post_meta( $post_id, '_mytaxi_phone', true );
			echo $phone ? esc_html( $phone ) : '—';
		}
		if ( 'mytaxi_status' === $column ) {
			echo esc_html( self::status_label( (string) get_post_status( $post_id ) ) );
		}
	}

	/**
	 * Route list columns.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public static function route_columns( $columns ) {
		$next = array();
		foreach ( $columns as $key => $label ) {
			$next[ $key ] = $label;
			if ( 'title' === $key ) {
				$next['mytaxi_service'] = 'Услуга';
				$next['mytaxi_origin']  = 'Начална точка';
				$next['mytaxi_dest']    = 'Крайна точка';
				$next['mytaxi_price']   = 'Цена';
				$next['mytaxi_status']  = 'Статус';
			}
		}
		return $next;
	}

	/**
	 * Route column values.
	 *
	 * @param string $column Column.
	 * @param int    $post_id Post ID.
	 */
	public static function route_column( $column, $post_id ) {
		if ( 'mytaxi_service' === $column ) {
			$service_id = (int) get_post_meta( $post_id, '_mytaxi_service_id', true );
			echo $service_id ? esc_html( get_the_title( $service_id ) ) : '—';
		}
		if ( 'mytaxi_origin' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mytaxi_origin', true ) );
		}
		if ( 'mytaxi_dest' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_mytaxi_destination', true ) );
		}
		if ( 'mytaxi_price' === $column ) {
			$price = Price::present(
				array(
					'price'       => (string) get_post_meta( $post_id, '_mytaxi_price', true ),
					'price_type'  => (string) get_post_meta( $post_id, '_mytaxi_price_type', true ),
					'price_basis' => (string) get_post_meta( $post_id, '_mytaxi_price_basis', true ),
				)
			);
			echo esc_html( (string) $price['text'] );
			if ( '1' === (string) get_post_meta( $post_id, '_mytaxi_price_sample', true ) ) {
				echo ' <em>(примерна)</em>';
			}
		}
		if ( 'mytaxi_status' === $column ) {
			echo esc_html( self::status_label( (string) get_post_status( $post_id ) ) );
		}
	}

	/**
	 * Bulgarian status label.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private static function status_label( $status ) {
		$labels = array(
			'publish' => 'Публикувана',
			'draft'   => 'Чернова',
			'pending' => 'Чакаща',
			'private' => 'Лична',
			'future'  => 'Насрочена',
			'trash'   => 'Кошче',
		);
		return $labels[ $status ] ?? $status;
	}

	/**
	 * Warn while sample copy is still published.
	 */
	public static function sample_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! Data::is_sample() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>Публичните текстове са примерни и чакат потвърждение. Не ги качвайте на живия сайт, докато не бъдат заменени от <a href="' . esc_url( admin_url( 'admin.php?page=mytaxi-settings' ) ) . '">общите настройки</a>.</p></div>';
	}

	/**
	 * List published services that still have no phone or request email.
	 */
	public static function missing_contact_notice() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$posts = get_posts(
			array(
				'post_type'      => 'mytaxi_service',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		$lines = array();
		foreach ( $posts as $post ) {
			if ( '' === (string) get_post_meta( $post->ID, '_mytaxi_phone', true ) ) {
				$lines[] = get_the_title( $post ) . ' — липсва телефон';
			}
		}
		if ( class_exists( '\MyTaxi\Core\ContactForm' ) ) {
			if ( 'test' === \MyTaxi\Core\ContactForm::mail_mode() ) {
				$test = \MyTaxi\Core\ContactForm::test_recipient();
				echo '<div class="notice notice-warning"><p>Тестовият режим е включен. Заявките ' . ( '' !== $test ? 'отиват само до ' . esc_html( $test ) : 'не се изпращат, защото тестовият адрес е празен' ) . '. Това не е production получателят.</p></div>';
			} elseif ( '' === \MyTaxi\Core\ContactForm::production_recipient() ) {
				echo '<div class="notice notice-warning"><p>Няма production имейл за заявките. Формите не се изпращат и не използват тестов адрес. Посетителят вижда WhatsApp и обаждане за съответната услуга. Запишете общия адрес или включете тестовия режим от Общи настройки.</p></div>';
			}
		}
		if ( ! $lines ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Блокер за пускане.</strong> Попълнете телефона на услугата. Не се ползва номер от друга услуга.</p><ul>';
		foreach ( $lines as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * General settings.
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$company = Data::company();
		$social  = get_option( 'mytaxi_social', array() );
		$social  = is_array( $social ) ? $social : array();
		$updated = isset( $_GET['updated'] );
		echo '<div class="wrap mytaxi-admin"><h1>Общи настройки</h1>';
		if ( $updated ) {
			echo '<div class="notice notice-success"><p>Настройките са записани.</p></div>';
		}
		echo '<p>Логото се сменя от <a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ) . '">Външен вид → Персонализиране → Идентичност на сайта</a>. Името на сайта се сменя от <a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">Настройки → Общи</a>. Тук не се дублират.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mytaxi_save_settings">';
		wp_nonce_field( 'mytaxi_save_settings' );
		Fields::section( 'Потвърдени фирмени данни', 'Попълвайте само данни, които са проверени. Празните полета не се показват на сайта.' );
		Fields::input( 'legal_name', 'Юридическо име', $company['legal_name'] );
		Fields::input( 'company_id', 'ЕИК', $company['company_id'] );
		Fields::input( 'vat', 'ДДС номер', $company['vat'] );
		Fields::textarea( 'address', 'Адрес', $company['address'] );
		Fields::end_section();
		Fields::section( 'Общи социални връзки' );
		foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok' ) as $key => $label ) {
			Fields::input( 'social_' . $key, $label, (string) ( $social[ $key ] ?? '' ), '', 'url' );
		}
		Fields::end_section();
		Fields::section( 'Поща за заявки', 'Всички форми използват един общ адрес. Production и тестовият режим са отделни. Празен production адрес не праща към тестовия. From е техническият адрес на сайта. Reply-To става имейлът на клиента, когато е валиден.' );
		Fields::checkbox( 'mail_test_mode', 'Тестов режим', 'test' === (string) get_option( 'mytaxi_mail_mode', 'production' ), 'Включете го само докато проверявате изпращането. Без отметка се ползва само общият имейл.' );
		Fields::input( 'test_email', 'Тестов имейл', (string) get_option( 'mytaxi_test_email', '' ), 'Само при включен тестов режим.', 'email' );
		Fields::input( 'request_email', 'Общ имейл за заявки', (string) get_option( 'mytaxi_request_email', '' ), 'Реалният адрес за всички услуги. Желаният адрес е bookings@mytaxivarna.com. Попълнете го едва след като кутията е създадена и доставката е проверена.', 'email' );
		Fields::input( 'mail_from', 'From имейл', (string) get_option( 'mytaxi_mail_from', '' ), 'Използвайте пощенска кутия на домейна, от който се изпраща пощата.', 'email' );
		Fields::input( 'mail_from_name', 'From име', (string) get_option( 'mytaxi_mail_from_name', '' ) );
		Fields::end_section();
		Fields::section( 'Примерно съдържание' );
		Fields::checkbox( 'sample_content', 'Публичните текстове още са примерни', Data::is_sample(), 'Махнете отметката, когато текстовете са проверени за публикуване.' );
		Fields::end_section();
		submit_button( 'Запиши' );
		echo '</form></div>';
	}

	/**
	 * Homepage selection screen.
	 */
	public static function render_home() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$front = (int) get_option( 'page_on_front' );
		echo '<div class="wrap mytaxi-admin"><h1>Начална страница</h1>';
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success"><p>Началната страница е обновена.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mytaxi_save_home">';
		wp_nonce_field( 'mytaxi_save_home' );
		Fields::section( 'Текстове на сайта', 'Кратките заглавия и описания. Празно поле връща текста по подразбиране. Телефоните и цените не се пишат тук.' );
		$labels = array(
			'home_kicker'             => 'Начало: надзаглавие',
			'home_title'              => 'Начало: H1',
			'home_lead'               => 'Начало: кратко описание',
			'home_local_button'       => 'Начало: бутон за местно такси',
			'home_airport_button'     => 'Начало: бутон за летищен трансфер',
			'home_local_heading'      => 'Начало: заглавие на местните таксита',
			'home_local_text'         => 'Начало: описание на местните таксита',
			'home_airport_heading'    => 'Начало: заглавие на трансферите',
			'home_airport_text'       => 'Начало: описание на трансферите',
			'home_routes_heading'     => 'Начало: заглавие на маршрутите',
			'home_routes_text'        => 'Начало: описание на маршрутите',
			'home_cta_heading'        => 'Начало: финално заглавие',
			'home_cta_text'           => 'Начало: финален текст',
			'home_cta_button'         => 'Начало: финален бутон',
			'local_heading'           => 'Местно такси: заглавие на списъка',
			'local_text'              => 'Местно такси: описание на списъка',
			'local_cta_heading'       => 'Местно такси: финално заглавие',
			'local_cta_text'          => 'Местно такси: финален текст',
			'airport_heading'         => 'Летище: заглавие на списъка',
			'airport_text'            => 'Летище: описание на списъка',
			'airport_cta_heading'     => 'Летище: финално заглавие',
			'airport_cta_text'        => 'Летище: финален текст',
			'routes_heading'          => 'Маршрути: заглавие на списъка',
			'routes_text'             => 'Маршрути: описание на списъка',
			'routes_cta_heading'      => 'Маршрути: финално заглавие',
			'routes_cta_text'         => 'Маршрути: финален текст',
			'contact_local_heading'   => 'Контакт: заглавие за местно такси',
			'contact_local_text'      => 'Контакт: описание за местно такси',
			'contact_airport_heading' => 'Контакт: заглавие за трансфер',
			'contact_airport_text'    => 'Контакт: описание за трансфер',
		);
		$lines = Data::lines();
		foreach ( $labels as $key => $label ) {
			Fields::input( 'copy_' . $key, $label, $lines[ $key ] );
		}
		Fields::end_section();
		if ( $front ) {
			echo '<p><a class="button" href="' . esc_url( get_edit_post_link( $front ) ) . '">Редактирай заглавие, описание, снимка и представяне</a></p>';
		}

		Fields::section( 'Услуги', 'Картите четат телефона от самата услуга.' );
		self::post_picker( 'service', 'mytaxi_service', '_mytaxi_show_home' );
		Fields::end_section();

		Fields::section( 'Маршрути', 'Цената идва от маршрута. Контактът идва от свързаната услуга.' );
		self::post_picker( 'route', 'mytaxi_route', '_mytaxi_popular' );
		Fields::end_section();

		$extras = Data::home_extras();
		Fields::section( 'Автопарк', 'Секцията се показва само когато има записи. Не добавяйте непотвърдени автомобили.' );
		Fields::repeater( 'fleet', $extras['fleet'], array( 'title' => 'Име', 'text' => 'Описание' ), 'Добави автомобил' );
		Fields::end_section();

		Fields::section( 'Потвърдени предимства' );
		Fields::lines( 'benefits', 'Предимства', $extras['benefits'], 'По едно на ред.' );
		Fields::end_section();

		Fields::section( 'Проверени отзиви', 'Добавяйте само реални отзиви. Няма поле за измислена оценка.' );
		Fields::repeater( 'reviews', $extras['reviews'], array( 'quote' => 'Текст', 'name' => 'Име', 'detail' => 'Контекст' ), 'Добави отзив' );
		Fields::end_section();

		Fields::section( 'Общи въпроси' );
		Fields::repeater( 'home_faq', $extras['faqs'], array( 'question' => 'Въпрос', 'answer' => 'Отговор' ), 'Добави въпрос' );
		Fields::end_section();

		submit_button( 'Запиши началната страница' );
		echo '</form></div>';
	}

	/**
	 * All route prices in one table.
	 */
	public static function render_prices() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$routes = get_posts(
			array(
				'post_type'      => 'mytaxi_route',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 200,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);
		$groups = array();
		foreach ( $routes as $route ) {
			$service_id = (int) get_post_meta( $route->ID, '_mytaxi_service_id', true );
			$groups[ $service_id ][] = $route;
		}

		echo '<div class="wrap mytaxi-admin"><h1>Цени</h1>';
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success"><p>Цените са записани. Сайтът показва новите цени веднага.</p></div>';
		}
		echo '<p>Цената е в EUR. Празно поле означава „Price on request“. Отметката „Примерна“ показва бележка, че цената още не е потвърдена, и не подава цената към Google.</p>';
		if ( ! $routes ) {
			echo '<p>Няма маршрути.</p></div>';
			return;
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mytaxi_save_prices">';
		wp_nonce_field( 'mytaxi_save_prices' );
		$types  = array(
			'fixed'      => 'Фиксирана',
			'from'       => 'От',
			'on_request' => 'При запитване',
		);
		$bases  = array(
			''              => 'Не е посочена',
			'per_vehicle'   => 'За автомобил',
			'per_passenger' => 'За пътник',
		);
		foreach ( $groups as $service_id => $items ) {
			Fields::section( $service_id ? get_the_title( $service_id ) : 'Без услуга' );
			echo '<table class="widefat striped mytaxi-prices"><thead><tr><th>Маршрут</th><th>Цена (EUR)</th><th>Тип</th><th>Основа</th><th>Примерна (не се показва)</th><th></th></tr></thead><tbody>';
			foreach ( $items as $route ) {
				if ( ! current_user_can( 'edit_post', $route->ID ) ) {
					continue;
				}
				$id     = (int) $route->ID;
				$amount = (string) get_post_meta( $id, '_mytaxi_price', true );
				$type   = (string) get_post_meta( $id, '_mytaxi_price_type', true ) ?: 'on_request';
				$basis  = (string) get_post_meta( $id, '_mytaxi_price_basis', true );
				$sample = '1' === (string) get_post_meta( $id, '_mytaxi_price_sample', true );
				$label  = trim( get_post_meta( $id, '_mytaxi_origin', true ) . ' → ' . get_post_meta( $id, '_mytaxi_destination', true ), ' →' );
				echo '<tr>';
				echo '<td><strong>' . esc_html( $label ? $label : get_the_title( $route ) ) . '</strong>' . ( 'publish' !== $route->post_status ? ' <em>(' . esc_html( self::status_label( $route->post_status ) ) . ')</em>' : '' ) . '</td>';
				echo '<td><input type="text" inputmode="decimal" class="small-text" name="price[' . esc_attr( (string) $id ) . '][amount]" value="' . esc_attr( is_numeric( $amount ) ? (string) (float) $amount : '' ) . '" aria-label="' . esc_attr( 'Цена: ' . $label ) . '"></td>';
				echo '<td><select name="price[' . esc_attr( (string) $id ) . '][type]">';
				foreach ( $types as $value => $text ) {
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $type, $value, false ) . '>' . esc_html( $text ) . '</option>';
				}
				echo '</select></td>';
				echo '<td><select name="price[' . esc_attr( (string) $id ) . '][basis]">';
				foreach ( $bases as $value => $text ) {
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $basis, $value, false ) . '>' . esc_html( $text ) . '</option>';
				}
				echo '</select></td>';
				echo '<td><input type="checkbox" name="price[' . esc_attr( (string) $id ) . '][sample]" value="1"' . checked( $sample, true, false ) . ' aria-label="' . esc_attr( 'Примерна цена: ' . $label ) . '"></td>';
				echo '<td><a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">Редактирай маршрута</a></td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			Fields::end_section();
		}
		submit_button( 'Запиши цените' );
		echo '</form></div>';
	}

	/**
	 * Save the price table.
	 */
	public static function save_prices() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Нямате право за тази промяна.' );
		}
		check_admin_referer( 'mytaxi_save_prices' );
		$rows = isset( $_POST['price'] ) && is_array( $_POST['price'] ) ? wp_unslash( $_POST['price'] ) : array();
		foreach ( $rows as $post_id => $row ) {
			$post_id = absint( $post_id );
			if ( ! $post_id || ! is_array( $row ) || 'mytaxi_route' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			Meta::store_price( $post_id, $row['amount'] ?? '', $row['type'] ?? 'on_request', $row['basis'] ?? '', ! empty( $row['sample'] ) );
			update_post_meta( $post_id, '_mytaxi_client_edited', '1' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mytaxi-prices&updated=1' ) );
		exit;
	}

	/**
	 * Setup screen.
	 */
	public static function render_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>Първоначална настройка</h1>';
		echo '<p>Създава липсващите пет услуги, пет маршрута и страниците Начало и Контакт. Не презаписва вече създадени записи и не публикува непотвърдени цени.</p>';
		if ( isset( $_GET['done'] ) ) {
			echo '<div class="notice notice-success"><p>Настройката приключи без дублиране на съществуващи записи.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mytaxi_run_setup">';
		wp_nonce_field( 'mytaxi_run_setup' );
		submit_button( 'Пусни настройката', 'primary', 'submit', false );
		echo '</form></div>';
	}

	/**
	 * Save general settings.
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Нямате право за тази промяна.' );
		}
		check_admin_referer( 'mytaxi_save_settings' );
		$company = array(
			'legal_name' => sanitize_text_field( wp_unslash( $_POST['legal_name'] ?? '' ) ),
			'company_id' => sanitize_text_field( wp_unslash( $_POST['company_id'] ?? '' ) ),
			'vat'        => sanitize_text_field( wp_unslash( $_POST['vat'] ?? '' ) ),
			'address'    => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
		);
		$social = array();
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok' ) as $key ) {
			$social[ $key ] = Fields::url( wp_unslash( $_POST[ 'social_' . $key ] ?? '' ) );
		}
		update_option( 'mytaxi_company', $company, false );
		update_option( 'mytaxi_social', $social, false );
		update_option( 'mytaxi_mail_mode', isset( $_POST['mail_test_mode'] ) ? 'test' : 'production', false );
		update_option( 'mytaxi_test_email', Fields::email( wp_unslash( $_POST['test_email'] ?? '' ) ), false );
		update_option( 'mytaxi_request_email', Fields::email( wp_unslash( $_POST['request_email'] ?? '' ) ), false );
		update_option( 'mytaxi_mail_from', Fields::email( wp_unslash( $_POST['mail_from'] ?? '' ) ), false );
		update_option( 'mytaxi_mail_from_name', sanitize_text_field( wp_unslash( $_POST['mail_from_name'] ?? '' ) ), false );
		update_option( 'mytaxi_sample_content', isset( $_POST['sample_content'] ) ? '1' : '0', false );
		wp_safe_redirect( admin_url( 'admin.php?page=mytaxi-settings&updated=1' ) );
		exit;
	}

	/**
	 * Save homepage selections into the same meta the public cards read.
	 */
	public static function save_home() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Нямате право за тази промяна.' );
		}
		check_admin_referer( 'mytaxi_save_home' );
		self::save_picker( 'service', 'mytaxi_service', '_mytaxi_show_home' );
		self::save_picker( 'route', 'mytaxi_route', '_mytaxi_popular' );

		$fleet = Fields::clean_repeater( wp_unslash( $_POST['fleet'] ?? array() ), array( 'title', 'text' ), true );
		$fleet = array_values( array_filter( $fleet, static function ( $row ) { return '' !== $row['title']; } ) );
		$reviews = Fields::clean_repeater( wp_unslash( $_POST['reviews'] ?? array() ), array( 'quote', 'name', 'detail' ), true );
		$reviews = array_values( array_filter( $reviews, static function ( $row ) { return '' !== $row['quote']; } ) );
		$faqs = Fields::clean_repeater( wp_unslash( $_POST['home_faq'] ?? array() ), array( 'question', 'answer' ), true );
		$faqs = array_values( array_filter( $faqs, static function ( $row ) { return '' !== $row['question'] && '' !== $row['answer']; } ) );

		update_option( 'mytaxi_home_fleet', Fields::encode_list( $fleet ), false );
		update_option( 'mytaxi_home_benefits', Fields::encode_list( Fields::line_list( wp_unslash( $_POST['benefits'] ?? '' ) ) ), false );
		update_option( 'mytaxi_home_reviews', Fields::encode_list( $reviews ), false );
		update_option( 'mytaxi_home_faq', Fields::encode_list( $faqs ), false );

		$copy = array();
		foreach ( array_keys( Data::lines() ) as $key ) {
			$copy[ $key ] = sanitize_text_field( wp_unslash( $_POST[ 'copy_' . $key ] ?? '' ) );
		}
		update_option( 'mytaxi_public_copy', $copy, false );

		wp_safe_redirect( admin_url( 'admin.php?page=mytaxi-home&updated=1' ) );
		exit;
	}

	/**
	 * Run setup from the admin.
	 */
	public static function run_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Нямате право за тази промяна.' );
		}
		check_admin_referer( 'mytaxi_run_setup' );
		Setup::run();
		wp_safe_redirect( admin_url( 'admin.php?page=mytaxi-setup&done=1' ) );
		exit;
	}

	/**
	 * Checkbox and order list.
	 *
	 * @param string $prefix Prefix.
	 * @param string $type Post type.
	 * @param string $flag Meta flag.
	 */
	private static function post_picker( $prefix, $type, $flag ) {
		$posts = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		if ( ! $posts ) {
			echo '<p>Няма записи. Пуснете първоначалната настройка.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>Покажи</th><th>Запис</th><th>Ред</th></tr></thead><tbody>';
		foreach ( $posts as $post ) {
			$checked = '1' === (string) get_post_meta( $post->ID, $flag, true );
			$order   = (string) get_post_meta( $post->ID, '_mytaxi_order', true );
			echo '<tr><td><input type="checkbox" name="' . esc_attr( $prefix ) . '_show[]" value="' . esc_attr( (string) $post->ID ) . '" ' . checked( $checked, true, false ) . '></td>';
			echo '<td>' . esc_html( get_the_title( $post ) ) . '</td>';
			echo '<td><input type="number" name="' . esc_attr( $prefix ) . '_order[' . esc_attr( (string) $post->ID ) . ']" value="' . esc_attr( $order ) . '"></td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Persist picker rows.
	 *
	 * @param string $prefix Prefix.
	 * @param string $type Post type.
	 * @param string $flag Meta flag.
	 */
	private static function save_picker( $prefix, $type, $flag ) {
		$shown = isset( $_POST[ $prefix . '_show' ] ) ? array_map( 'absint', (array) wp_unslash( $_POST[ $prefix . '_show' ] ) ) : array();
		$orders = isset( $_POST[ $prefix . '_order' ] ) && is_array( $_POST[ $prefix . '_order' ] ) ? wp_unslash( $_POST[ $prefix . '_order' ] ) : array();
		$posts = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $posts as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			update_post_meta( $post_id, $flag, in_array( (int) $post_id, $shown, true ) ? '1' : '0' );
			if ( isset( $orders[ $post_id ] ) ) {
				update_post_meta( $post_id, '_mytaxi_order', (string) absint( $orders[ $post_id ] ) );
			}
			update_post_meta( $post_id, '_mytaxi_client_edited', '1' );
		}
	}
}
