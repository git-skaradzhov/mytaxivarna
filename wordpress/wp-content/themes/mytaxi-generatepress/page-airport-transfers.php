<?php
/**
 * Airport transfer listing.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

mytaxi_open();
while ( have_posts() ) :
	the_post();
	$groups = mytaxi_service_groups();
	mytaxi_breadcrumbs();
	mytaxi_page_hero(
		__( 'Planned transfer', 'mytaxi-generatepress' ),
		'varna-airport-transfers',
		array(
			array(
				'label' => __( 'View routes', 'mytaxi-generatepress' ),
				'url'   => home_url( '/routes/' ),
				'style' => 'yellow',
				'icon'  => 'pin',
			),
			array(
				'label' => __( 'Contact us', 'mytaxi-generatepress' ),
				'url'   => home_url( '/contact/' ),
				'style' => 'ghost',
			),
		)
	);
	?>
	<section class="mt-section">
		<div class="mt-section__head">
			<h2><?php esc_html_e( 'Choose an airport', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Each airport service keeps its own phone.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<div class="mt-places mt-places--airport">
			<?php foreach ( $groups['airport'] as $service ) : ?>
				<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'airport' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<section class="mt-band">
		<div>
			<h2><?php esc_html_e( 'Already at the resort?', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Local taxi is a separate service for Golden Sands, Albena, and Kranevo.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/local-taxi/' ) ); ?>"><?php mytaxi_icon( 'car' ); ?><span><?php esc_html_e( 'Local taxi', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
	</section>
	<?php
endwhile;
mytaxi_close();
