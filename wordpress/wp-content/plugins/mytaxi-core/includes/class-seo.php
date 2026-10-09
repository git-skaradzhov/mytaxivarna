<?php
/**
 * SEO output owned by MyTaxi when no SEO plugin is active.
 *
 * @package MyTaxi
 */

namespace MyTaxi\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Titles, descriptions, Open Graph, robots, and structured data.
 */
class Seo {

	/**
	 * Hook SEO output.
	 */
	public static function boot() {
		add_filter( 'document_title_parts', array( __CLASS__, 'title' ) );
		add_action( 'wp_head', array( __CLASS__, 'meta' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'sitemap_provider' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'sitemap_types' ) );
	}

	/**
	 * Whether Yoast, Rank Math, or SEOPress already owns the tags.
	 *
	 * @return bool
	 */
	public static function external() {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Document title.
	 *
	 * @param array<string,string> $parts Title parts.
	 * @return array<string,string>
	 */
	public static function title( $parts ) {
		if ( self::external() ) {
			return $parts;
		}
		$custom = self::current_meta( 'meta_title' );
		if ( $custom ) {
			$parts['title'] = $custom;
		}
		return $parts;
	}

	/**
	 * Description and Open Graph tags.
	 */
	public static function meta() {
		if ( self::external() ) {
			return;
		}
		$description = self::public_copy( self::current_meta( 'meta_desc' ) );
		if ( ! $description ) {
			$description = self::public_copy( self::fallback_description() );
		}
		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}

		$title = $description ? wp_get_document_title() : wp_get_document_title();
		$url   = self::current_url();
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:locale" content="en_US">' . "\n";
		if ( $description ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		$image = self::image_url();
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}

		echo self::schema() . "\n";
	}

	/**
	 * Keep local and staging sites out of the index.
	 *
	 * @param array<string,bool|string> $robots Robots directives.
	 * @return array<string,bool|string>
	 */
	public static function robots( $robots ) {
		$environment = wp_get_environment_type();
		if ( in_array( $environment, array( 'local', 'staging' ), true ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}

	/**
	 * Drop the users sitemap.
	 *
	 * @param mixed  $provider Provider.
	 * @param string $name Provider name.
	 * @return mixed
	 */
	public static function sitemap_provider( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}
		return $provider;
	}

	/**
	 * Services and routes stay in the core sitemap. Attachments do not.
	 *
	 * @param array<string,\WP_Post_Type> $types Post types.
	 * @return array<string,\WP_Post_Type>
	 */
	public static function sitemap_types( $types ) {
		unset( $types['attachment'] );
		return $types;
	}

	/**
	 * JSON-LD graph for the current view.
	 *
	 * @return string
	 */
	private static function schema() {
		$graph = array(
			self::organization(),
			self::website(),
		);

		if ( is_singular( 'mytaxi_service' ) ) {
			$service = Data::service( get_the_ID() );
			if ( $service ) {
				$graph[] = self::taxi_service( $service );
			}
		} elseif ( is_singular( 'mytaxi_route' ) ) {
			$route = Data::route( get_the_ID() );
			if ( $route ) {
				$graph[] = self::route_service( $route );
			}
		}

		$faqs = self::current_faqs();
		if ( $faqs ) {
			$graph[] = self::faq_schema( $faqs );
		}
		$graph[] = self::breadcrumbs();

		return '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( array_filter( $graph ) ),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . '</script>';
	}

	/**
	 * Organization from confirmed fields only.
	 *
	 * @return array<string,mixed>
	 */
	private static function organization() {
		$company = Data::company();
		$data    = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => $company['legal_name'] ? $company['legal_name'] : get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);
		if ( $company['address'] ) {
			$data['address'] = array(
				'@type'         => 'PostalAddress',
				'streetAddress' => $company['address'],
			);
		}
		if ( $company['vat'] ) {
			$data['vatID'] = $company['vat'];
		}
		$same = array_values( Data::social() );
		if ( $same ) {
			$data['sameAs'] = $same;
		}
		return $data;
	}

	/**
	 * Website node.
	 *
	 * @return array<string,mixed>
	 */
	private static function website() {
		return array(
			'@type'     => 'WebSite',
			'@id'       => home_url( '/#website' ),
			'url'       => home_url( '/' ),
			'name'      => get_bloginfo( 'name' ),
			'publisher' => array( '@id' => home_url( '/#organization' ) ),
		);
	}

	/**
	 * TaxiService for a service page. The provider is the site organization.
	 *
	 * @param array<string,mixed> $service Service.
	 * @return array<string,mixed>
	 */
	private static function taxi_service( array $service ) {
		$data = array(
			'@type'    => 'TaxiService',
			'@id'      => $service['url'] . '#service',
			'name'     => $service['title'],
			'url'      => $service['url'],
			'provider' => array( '@id' => home_url( '/#organization' ) ),
		);
		if ( ! empty( $service['intro'] ) ) {
			$data['description'] = self::public_copy( (string) $service['intro'] );
		}
		if ( ! empty( $service['areas'] ) ) {
			$data['areaServed'] = array_values( $service['areas'] );
		}
		if ( ! empty( $service['contacts']['phone'] ) ) {
			$data['telephone'] = $service['contacts']['phone'];
		}
		return $data;
	}

	/**
	 * Service node for a route. Numeric Offer only for a confirmed price.
	 *
	 * @param array<string,mixed> $route Route.
	 * @return array<string,mixed>
	 */
	private static function route_service( array $route ) {
		$data = array(
			'@type'    => 'Service',
			'@id'      => $route['url'] . '#route',
			'name'     => $route['title'],
			'url'      => $route['url'],
			'provider' => array( '@id' => home_url( '/#organization' ) ),
		);
		if ( ! empty( $route['summary'] ) ) {
			$data['description'] = self::public_copy( (string) $route['summary'] );
		}
		$price = $route['price'];
		if ( ! empty( $price['offer'] ) && null !== $price['amount'] ) {
			$offer = array(
				'@type'         => 'Offer',
				'url'           => $route['url'],
				'priceCurrency' => 'EUR',
			);
			if ( 'from' === $price['type'] ) {
				$offer['priceSpecification'] = array(
					'@type'         => 'PriceSpecification',
					'minPrice'      => number_format( (float) $price['amount'], 2, '.', '' ),
					'priceCurrency' => 'EUR',
				);
			} else {
				$offer['price'] = number_format( (float) $price['amount'], 2, '.', '' );
			}
			if ( ! empty( $price['basis_label'] ) ) {
				$offer['description'] = (string) $price['basis_label'];
			}
			$data['offers'] = $offer;
		}
		return $data;
	}

	/**
	 * FAQ schema matching visible questions.
	 *
	 * @param array<int,array{question:string,answer:string}> $faqs FAQs.
	 * @return array<string,mixed>
	 */
	private static function faq_schema( array $faqs ) {
		$entities = array();
		foreach ( $faqs as $faq ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq['answer'],
				),
			);
		}
		return array(
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
	}

	/**
	 * Breadcrumb schema from the same trail as the template.
	 *
	 * @return array<string,mixed>
	 */
	private static function breadcrumbs() {
		$items = array();
		foreach ( Data::breadcrumbs() as $index => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}
		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * FAQs visible on this request.
	 *
	 * @return array<int,array{question:string,answer:string}>
	 */
	private static function current_faqs() {
		if ( is_front_page() ) {
			return Data::home_extras()['faqs'];
		}
		if ( is_singular( 'mytaxi_service' ) ) {
			$service = Data::service( get_the_ID() );
			return $service['faqs'] ?? array();
		}
		if ( is_singular( 'mytaxi_route' ) ) {
			$route = Data::route( get_the_ID() );
			return $route['faqs'] ?? array();
		}
		return array();
	}

	/**
	 * Custom meta field for the current singular item.
	 *
	 * @param string $key meta_title or meta_desc.
	 * @return string
	 */
	private static function current_meta( $key ) {
		if ( is_singular( 'mytaxi_service' ) ) {
			$service = Data::service( get_the_ID() );
			return trim( (string) ( $service[ $key ] ?? '' ) );
		}
		if ( is_singular( 'mytaxi_route' ) ) {
			$route = Data::route( get_the_ID() );
			return trim( (string) ( $route[ $key ] ?? '' ) );
		}
		if ( is_front_page() ) {
			return 'meta_desc' === $key ? trim( (string) get_the_excerpt() ) : '';
		}
		return '';
	}

	/**
	 * Description fallback from the intro or summary.
	 *
	 * @return string
	 */
	private static function fallback_description() {
		if ( is_singular( 'mytaxi_service' ) ) {
			$service = Data::service( get_the_ID() );
			return trim( (string) ( $service['intro'] ?? '' ) );
		}
		if ( is_singular( 'mytaxi_route' ) ) {
			$route = Data::route( get_the_ID() );
			return trim( (string) ( $route['summary'] ?? '' ) );
		}
		return '';
	}

	/**
	 * Canonical URL for Open Graph.
	 *
	 * @return string
	 */
	private static function current_url() {
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		return home_url( '/' );
	}

	/**
	 * Featured image for Open Graph.
	 *
	 * @return string
	 */
	private static function image_url() {
		if ( ! is_singular() ) {
			$front = (int) get_option( 'page_on_front' );
			$id    = $front ? (int) get_post_thumbnail_id( $front ) : 0;
		} else {
			$id = (int) get_post_thumbnail_id();
		}
		if ( ! $id ) {
			return '';
		}
		$image = wp_get_attachment_image_url( $id, 'large' );
		return $image ? $image : '';
	}

	/**
	 * Public copy without the review-only sentences.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	private static function public_copy( $text ) {
		$text = wp_strip_all_tags( (string) $text );
		$text = (string) preg_replace( '/\s*Sample text for review\.(?:\s+[^.]+?\.)?/iu', '', $text );
		return trim( $text );
	}
}
