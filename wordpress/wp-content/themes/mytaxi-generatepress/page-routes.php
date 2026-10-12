<?php
/**
 * Route listing.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

mytaxi_open();
while ( have_posts() ) :
	the_post();
	$routes = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::home_routes() : array();
	mytaxi_breadcrumbs();
	mytaxi_page_hero(
		__( 'Our services', 'mytaxi-generatepress' ),
		'varna-airport-transfers',
		array(
			array(
				'label' => __( 'Choose a destination', 'mytaxi-generatepress' ),
				'url'   => '#routes',
				'style' => 'yellow',
			),
		)
	);
	if ( $routes ) :
		?>
		<section class="mt-section" id="routes">
			<div class="mt-section__head">
				<h2><?php echo esc_html( mytaxi_line( 'routes_heading' ) ); ?></h2>
				<?php if ( '' !== mytaxi_line( 'routes_text' ) ) : ?>
					<p><?php echo esc_html( mytaxi_line( 'routes_text' ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="mt-route-list">
				<?php foreach ( $routes as $route ) : ?>
					<?php get_template_part( 'template-parts/route-card', null, array( 'route' => $route, 'variant' => 'row' ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	endif;
	?>
	<section class="mt-band mt-band--cta">
		<div>
			<h2><?php echo esc_html( mytaxi_line( 'routes_cta_heading' ) ); ?></h2>
			<p><?php echo esc_html( mytaxi_line( 'routes_cta_text' ) ); ?></p>
		</div>
		<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><span><?php esc_html_e( 'Contact us', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
	</section>
	<?php
endwhile;
mytaxi_close();
