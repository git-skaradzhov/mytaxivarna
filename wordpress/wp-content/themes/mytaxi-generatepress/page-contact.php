<?php
/**
 * Contact page.
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
		__( 'Contact', 'mytaxi-generatepress' ),
		'taxi-albena',
		array(
			array(
				'label' => __( 'Local taxi', 'mytaxi-generatepress' ),
				'url'   => home_url( '/local-taxi/' ),
				'style' => 'yellow',
				'icon'  => 'car',
			),
			array(
				'label' => __( 'Airport transfers', 'mytaxi-generatepress' ),
				'url'   => home_url( '/airport-transfers/' ),
				'style' => 'ghost',
				'icon'  => 'plane',
			),
		)
	);
	?>
	<section class="mt-section" id="local">
		<div class="mt-section__head">
			<h2><?php esc_html_e( 'Local taxi', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Call the resort you are in. Each service has its own phone.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<div class="mt-places mt-places--local">
			<?php foreach ( $groups['local'] as $service ) : ?>
				<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'place' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<section class="mt-section" id="transfers">
		<div class="mt-section__head">
			<h2><?php esc_html_e( 'Airport transfers', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Call the airport service for that trip.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<div class="mt-places mt-places--airport">
			<?php foreach ( $groups['airport'] as $service ) : ?>
				<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'airport' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endwhile;
mytaxi_close();
