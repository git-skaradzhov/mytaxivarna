<?php
/**
 * Root permalinks, slug collisions, and legacy redirects.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * URL ownership for services and routes.
 */
class Routing {

	/**
	 * Same-host paths handled by this site. Cross-domain rules stay in deploy files.
	 *
	 * @return array<string,string>
	 */
	public static function active_map() {
		return array(
			'/golden-sands'                         => '/taxi-golden-sands/',
			'/albena'                               => '/taxi-albena/',
			'/kranevo'                              => '/taxi-kranevo/',
			'/destinations/varna-to-golden-sands'   => '/varna-airport-to-golden-sands/',
			'/destinations/varna-to-albena'         => '/varna-airport-to-albena/',
			'/destinations/varna-to-kranevo'        => '/varna-airport-to-kranevo/',
			'/destinations/varna-to-balchik'        => '/varna-airport-to-balchik/',
		);
	}

	/**
	 * Hook routing.
	 */
	public static function boot() {
		add_filter( 'post_type_link', array( __CLASS__, 'link' ), 10, 2 );
		add_filter( 'wp_unique_post_slug', array( __CLASS__, 'unique_slug' ), 10, 6 );
		add_action( 'save_post', array( __CLASS__, 'flag_on_save' ), 20, 2 );
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush' ) );
		add_action( 'template_redirect', array( __CLASS__, 'legacy_redirect' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'resolve_missing_pretty_url' ), 1 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'canonical' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'slug_notice' ) );
	}

	/**
	 * Pretty root permalinks.
	 *
	 * @param string   $url  Default URL.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public static function link( $url, $post ) {
		if ( $post instanceof \WP_Post && in_array( $post->post_type, array( 'mytaxi_service', 'mytaxi_route' ), true ) && $post->post_name ) {
			return home_url( user_trailingslashit( $post->post_name ) );
		}
		return $url;
	}

	/**
	 * Add explicit rules, then flush once when requested.
	 */
	public static function maybe_flush() {
		if ( '1' !== (string) get_option( 'mytaxi_needs_flush' ) ) {
			return;
		}

		self::add_rules();
		flush_rewrite_rules( false );
		update_option( 'mytaxi_needs_flush', '0', false );
	}

	/**
	 * Register one rule per published slug.
	 */
	public static function add_rules() {
		foreach ( array( 'mytaxi_service', 'mytaxi_route' ) as $type ) {
			$posts = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			foreach ( $posts as $post_id ) {
				$slug = (string) get_post_field( 'post_name', $post_id );
				if ( '' === $slug ) {
					continue;
				}
				add_rewrite_rule(
					'^' . preg_quote( $slug, '#' ) . '/?$',
					'index.php?' . $type . '=' . $slug,
					'top'
				);
			}
		}
	}

	/**
	 * Ask for one flush after a slug or status change.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post.
	 */
	public static function flag_on_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || ! $post instanceof \WP_Post ) {
			return;
		}
		if ( in_array( $post->post_type, array( 'mytaxi_service', 'mytaxi_route', 'page' ), true ) ) {
			update_option( 'mytaxi_needs_flush', '1', false );
		}
	}

	/**
	 * Keep slugs unique across services, routes, pages, posts, and system paths.
	 *
	 * @param string $slug          Candidate.
	 * @param int    $post_id       Post ID.
	 * @param string $post_status   Status.
	 * @param string $post_type     Type.
	 * @param int    $post_parent   Parent.
	 * @param string $original_slug Requested slug.
	 * @return string
	 */
	public static function unique_slug( $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug ) {
		unset( $post_status, $post_parent );
		if ( ! in_array( $post_type, array( 'mytaxi_service', 'mytaxi_route', 'page', 'post' ), true ) ) {
			return $slug;
		}

		$base      = $original_slug ? $original_slug : $slug;
		$candidate = $slug;
		$index     = 2;
		while ( self::conflicts( $candidate, (int) $post_id, $post_type ) ) {
			$candidate = $base . '-' . $index;
			++$index;
			if ( $index > 30 ) {
				break;
			}
		}

		if ( $candidate !== $slug && is_admin() && get_current_user_id() ) {
			set_transient(
				'mytaxi_slug_notice_' . get_current_user_id(),
				sprintf( 'Адресът „%1$s“ е зает и е записан като „%2$s“.', $slug, $candidate ),
				60
			);
		}

		return $candidate;
	}

	/**
	 * Whether a slug is reserved or already used.
	 *
	 * @param string $slug Slug.
	 * @param int    $post_id Current post.
	 * @param string $post_type Current type.
	 * @return bool
	 */
	public static function conflicts( $slug, $post_id, $post_type = '' ) {
		$slug = trim( (string) $slug, '/' );
		if ( '' === $slug ) {
			return true;
		}

		$reserved = array(
			'wp-admin',
			'wp-content',
			'wp-includes',
			'wp-json',
			'wp-login',
			'xmlrpc',
			'feed',
			'embed',
			'comments',
			'search',
			'author',
			'category',
			'tag',
			'robots.txt',
			'favicon.ico',
			'sitemap',
			'sitemap.xml',
			'wp-sitemap.xml',
		);
		if ( in_array( $slug, $reserved, true ) ) {
			return true;
		}

		$pages = array( 'home', 'contact', 'local-taxi', 'airport-transfers', 'routes' );
		if ( in_array( $slug, $pages, true ) && 'page' !== $post_type ) {
			return true;
		}

		$existing = get_posts(
			array(
				'post_type'      => array( 'page', 'post', 'mytaxi_service', 'mytaxi_route' ),
				'name'           => $slug,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 1,
				'post__not_in'   => array( $post_id ),
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return ! empty( $existing );
	}

	/**
	 * Show a slug collision notice.
	 */
	public static function slug_notice() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$key     = 'mytaxi_slug_notice_' . $user_id;
		$message = get_transient( $key );
		if ( ! is_string( $message ) || '' === $message ) {
			return;
		}
		delete_transient( $key );
		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Redirect legacy paths that belong to this host.
	 */
	public static function legacy_redirect() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			return;
		}

		$path = self::request_path();
		$map  = self::active_map();
		if ( ! isset( $map[ $path ] ) ) {
			return;
		}

		wp_safe_redirect( home_url( $map[ $path ] ), 301 );
		exit;
	}

	/**
	 * Open a published service or route when the pretty rule is not flushed yet.
	 */
	public static function resolve_missing_pretty_url() {
		if ( ! is_404() ) {
			return;
		}

		$path = trim( self::request_path(), '/' );
		if ( '' === $path || str_contains( $path, '/' ) ) {
			return;
		}

		$found = get_page_by_path( $path, OBJECT, 'mytaxi_service' );
		if ( ! $found instanceof \WP_Post ) {
			$found = get_page_by_path( $path, OBJECT, 'mytaxi_route' );
		}
		if ( ! $found instanceof \WP_Post || 'publish' !== $found->post_status ) {
			return;
		}

		global $wp_query, $post;
		$post     = $found;
		$wp_query = new \WP_Query(
			array(
				'p'         => $found->ID,
				'post_type' => $found->post_type,
			)
		);
		$wp_query->is_404      = false;
		$wp_query->is_single   = true;
		$wp_query->is_singular = true;
		$wp_query->is_home     = false;
		status_header( 200 );
	}

	/**
	 * Avoid redirect loops on root service and route URLs.
	 *
	 * @param string|false $redirect Redirect target.
	 * @param string       $requested Requested URL.
	 * @return string|false
	 */
	public static function canonical( $redirect, $requested ) {
		unset( $requested );
		if ( is_singular( array( 'mytaxi_service', 'mytaxi_route' ) ) ) {
			return false;
		}
		return $redirect;
	}

	/**
	 * Current path relative to the WordPress home path.
	 *
	 * @return string
	 */
	public static function request_path() {
		$raw  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = (string) wp_parse_url( $raw, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home && '/' !== $home && str_starts_with( $path, $home ) ) {
			$path = substr( $path, strlen( rtrim( $home, '/' ) ) );
		}
		$path = '/' . trim( rawurldecode( $path ), '/' );
		return '/' === $path ? '/' : untrailingslashit( $path );
	}
}
