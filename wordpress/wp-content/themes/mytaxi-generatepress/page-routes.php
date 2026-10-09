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
		__( 'Airport routes', 'mytaxi-generatepress' ),
		'taxi-golden-sands',
		array(
			array(
				'label' => __( 'Airport transfers', 'mytaxi-generatepress' ),
				'url'   => home_url( '/airport-transfers/' ),
				'style' => 'yellow',
				'icon'  => 'plane',
			),
			array(
				'label' => __( 'Contact us', 'mytaxi-generatepress' ),
				'url'   => home_url( '/contact/' ),
				'style' => 'ghost',
			),
		)
	);
	if ( $routes ) :
		?>
		<section class="mt-section">
			<div class="mt-section__head">
				<h2><?php esc_html_e( 'Choose a route', 'mytaxi-generatepress' ); ?></h2>
				<p><?php esc_html_e( 'A price is shown only when it is confirmed.', 'mytaxi-generatepress' ); ?></p>
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
	<section class="mt-band">
		<div>
			<h2><?php esc_html_e( 'Need a taxi while you are there?', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Local taxi uses a different phone from the airport transfer.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/local-taxi/' ) ); ?>"><?php mytaxi_icon( 'car' ); ?><span><?php esc_html_e( 'Local taxi', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
	</section>
	<?php
endwhile;
mytaxi_close();
