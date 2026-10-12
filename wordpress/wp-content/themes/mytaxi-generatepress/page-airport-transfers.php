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
		__( 'Our services', 'mytaxi-generatepress' ),
		'varna-airport-transfers',
		array(
			array(
				'label' => __( 'Choose an airport', 'mytaxi-generatepress' ),
				'url'   => '#services',
				'style' => 'yellow',
			),
		)
	);
	?>
	<section class="mt-section" id="services">
		<div class="mt-section__head">
			<h2><?php echo esc_html( mytaxi_line( 'airport_heading' ) ); ?></h2>
			<?php if ( '' !== mytaxi_line( 'airport_text' ) ) : ?>
				<p><?php echo esc_html( mytaxi_line( 'airport_text' ) ); ?></p>
			<?php endif; ?>
		</div>
		<div class="mt-places mt-places--airport">
			<?php foreach ( $groups['airport'] as $service ) : ?>
				<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'airport' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<section class="mt-band mt-band--cta">
		<div>
			<h2><?php echo esc_html( mytaxi_line( 'airport_cta_heading' ) ); ?></h2>
			<p><?php echo esc_html( mytaxi_line( 'airport_cta_text' ) ); ?></p>
		</div>
		<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><span><?php esc_html_e( 'Contact us', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
	</section>
	<?php
endwhile;
mytaxi_close();
