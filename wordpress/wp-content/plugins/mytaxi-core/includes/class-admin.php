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
		add_action( 'admin_post_mytaxi_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_mytaxi_save_home', array( __CLASS__, 'save_home' ) );
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
		add_submenu_page( 'mytaxi-settings', 'Първоначална настройка', 'Първоначална настройка', 'manage_options', 'mytaxi-setup', array( __CLASS__, 'render_setup' ) );
	}

	/**
	 * Load admin assets on MyTaxi screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'toplevel_page_mytaxi-settings', 'mytaxi_page_mytaxi-home', 'mytaxi_page_mytaxi-setup' ), true ) ) {
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
		Fields::section( 'Поща за заявки', 'From адресът е фиксиран адрес на сайта. Reply-To става имейлът на клиента, когато е валиден.' );
		Fields::input( 'mail_from', 'From имейл', (string) get_option( 'mytaxi_mail_from', '' ), 'Използвайте пощенска кутия на домейна, от който се изпраща пощата.', 'email' );
		Fields::input( 'mail_from_name', 'From име', (string) get_option( 'mytaxi_mail_from_name', '' ) );
		Fields::end_section();
		Fields::section( 'Примерно съдържание' );
		Fields::checkbox( 'sample_content', 'Публичните текстове още са примерни', Data::is_sample(), 'Махнете отметката само след като текстовете са проверени и може да се скрие банерът.' );
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
		if ( $front ) {
			echo '<p><a class="button" href="' . esc_url( get_edit_post_link( $front ) ) . '">Редактирай заглавие, описание, снимка и представяне</a></p>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mytaxi_save_home">';
		wp_nonce_field( 'mytaxi_save_home' );

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
