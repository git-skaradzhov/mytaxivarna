<?php
/**
 * Local taxi listing.
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
		__( 'Coastal taxi', 'mytaxi-generatepress' ),
		'home',
		array(
			array(
				'label' => __( 'Contact us', 'mytaxi-generatepress' ),
				'url'   => home_url( '/contact/' ),
				'style' => 'yellow',
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
	<section class="mt-section">
		<div class="mt-section__head">
			<h2><?php esc_html_e( 'Choose a destination', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Each resort keeps its own phone.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<div class="mt-places mt-places--local">
			<?php foreach ( $groups['local'] as $service ) : ?>
				<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'place' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<section class="mt-band">
		<div>
			<h2><?php esc_html_e( 'Need an airport transfer?', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Varna Airport and Sofia Airport are separate services, each with its own phone.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/airport-transfers/' ) ); ?>"><?php mytaxi_icon( 'plane' ); ?><span><?php esc_html_e( 'Airport transfers', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
	</section>
	<?php
endwhile;
mytaxi_close();
