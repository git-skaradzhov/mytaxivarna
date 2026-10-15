<?php
/**
 * Front page.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

$groups = mytaxi_service_groups();
$routes = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::home_routes() : array();
$extras = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::home_extras() : array( 'fleet' => array(), 'benefits' => array(), 'reviews' => array(), 'faqs' => array() );

$search   = array();
$vehicles = array();
if ( class_exists( '\MyTaxi\Core\Data' ) ) {
	foreach ( $groups['airport'] as $airport ) {
		$airport_routes = \MyTaxi\Core\Data::routes_for_service( (int) $airport['id'] );
		$search         = array_merge( $search, $airport_routes );
		$vehicles       = array_merge( $vehicles, mytaxi_vehicle_items( $airport, mytaxi_routes_from( $airport_routes ), true ) );
	}
}

$airports = array_map(
	static function ( $service ) {
		return mytaxi_place_name( (string) $service['title'] );
	},
	$groups['airport']
);

$local = array();
foreach ( $groups['local'] as $service ) {
	$local[] = array(
		'image_id' => (int) ( $service['image_id'] ?? 0 ),
		'title'    => mytaxi_place_name( (string) $service['title'] ),
		'text'     => mytaxi_card_text( (string) ( $service['intro'] ?? '' ) ),
		'url'      => (string) $service['url'],
		'link'     => __( 'View service', 'mytaxi-generatepress' ),
	);
}

$first_airport = $groups['airport'][0] ?? array();

mytaxi_open(
	'template-parts/home-hero',
	array(
		'routes' => $search,
		'photo'  => mytaxi_hero_photo( 'home' ),
	)
);

if ( have_posts() ) {
	the_post();
}

get_template_part(
	'template-parts/strip',
	null,
	array(
		'items' => array(
			array(
				'icon'  => 'plane',
				'value' => (string) count( $airports ),
				'label' => sprintf( _n( 'airport: %s', 'airports: %s', count( $airports ), 'mytaxi-generatepress' ), implode( ' & ', $airports ) ),
			),
			array(
				'icon'  => 'pin',
				'value' => (string) count( $local ),
				'label' => _n( 'resort with a local taxi', 'resorts with a local taxi', count( $local ), 'mytaxi-generatepress' ),
			),
			array(
				'icon'  => 'phone',
				'value' => __( '24/7', 'mytaxi-generatepress' ),
				'label' => __( 'call, WhatsApp or Viber', 'mytaxi-generatepress' ),
			),
		),
	)
);

get_template_part(
	'template-parts/vehicles',
	null,
	array(
		'items' => $vehicles,
		'link'  => home_url( '/airport-transfers/' ),
		'text'  => __( 'Private cars for up to 4 or 5 passengers. The price is for the car, not per person.', 'mytaxi-generatepress' ),
	)
);

get_template_part(
	'template-parts/route-tiles',
	null,
	array(
		'routes'     => $routes,
		'id'         => 'routes',
		'heading'    => mytaxi_line( 'home_routes_heading' ),
		'text'       => mytaxi_line( 'home_routes_text' ),
		'more_url'   => home_url( '/airport-transfers/' ),
		'more_label' => __( 'All airport transfers', 'mytaxi-generatepress' ),
	)
);

get_template_part(
	'template-parts/photo-cards',
	null,
	array(
		'id'      => 'local',
		'heading' => mytaxi_line( 'home_local_heading' ),
		'text'    => mytaxi_line( 'home_local_text' ),
		'items'   => $local,
	)
);

get_template_part(
	'template-parts/steps',
	null,
	array_merge(
		mytaxi_transfer_steps(),
		array(
			'image_id' => (int) ( $first_airport['image_id'] ?? 0 ),
			'file'     => 'varna-airport.jpg',
		)
	)
);

get_template_part(
	'template-parts/travel-needs',
	null,
	array(
		'url'   => home_url( '/airport-transfers/' ),
		'label' => __( 'Arrange your transfer', 'mytaxi-generatepress' ),
	)
);

get_template_part( 'template-parts/review-sources', null, array( 'reviews' => mytaxi_review_sources( array_merge( $groups['airport'], $groups['local'] ) ) ) );
?>

<?php if ( ! empty( $extras['fleet'] ) ) : ?>
	<section class="mt-st-section">
		<div class="mt-st-head"><h2><?php esc_html_e( 'Vehicles', 'mytaxi-generatepress' ); ?></h2></div>
		<div class="mt-places mt-places--local">
			<?php foreach ( $extras['fleet'] as $item ) : ?>
				<article class="mt-panel">
					<h3><?php echo esc_html( $item['title'] ); ?></h3>
					<?php mytaxi_text( $item['text'] ); ?>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty( $extras['benefits'] ) ) : ?>
	<section class="mt-st-section">
		<div class="mt-st-head"><h2><?php esc_html_e( 'What is included', 'mytaxi-generatepress' ); ?></h2></div>
		<ul class="mt-list">
			<?php foreach ( $extras['benefits'] as $benefit ) : ?>
				<li><?php echo esc_html( $benefit ); ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( ! empty( $extras['reviews'] ) ) : ?>
	<section class="mt-st-section">
		<div class="mt-st-head"><h2><?php esc_html_e( 'Reviews', 'mytaxi-generatepress' ); ?></h2></div>
		<div class="mt-places mt-places--local">
			<?php foreach ( $extras['reviews'] as $review ) : ?>
				<blockquote class="mt-panel">
					<p><?php echo esc_html( $review['quote'] ); ?></p>
					<footer>
						<?php echo esc_html( $review['name'] ); ?>
						<?php if ( ! empty( $review['detail'] ) ) : ?>
							<span><?php echo esc_html( $review['detail'] ); ?></span>
						<?php endif; ?>
					</footer>
				</blockquote>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php mytaxi_faqs( $extras['faqs'] ); ?>

<section class="mt-st-cta">
	<div>
		<h2><?php echo esc_html( mytaxi_line( 'home_cta_heading' ) ); ?></h2>
		<p><?php echo esc_html( mytaxi_line( 'home_cta_text' ) ); ?></p>
	</div>
	<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( mytaxi_line( 'home_cta_button' ) ); ?></a>
</section>
<?php if ( get_the_content() ) : ?>
	<section class="mt-st-section mt-prose">
		<?php the_content(); ?>
	</section>
<?php endif; ?>
<?php
mytaxi_close();
