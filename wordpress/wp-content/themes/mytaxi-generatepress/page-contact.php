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
	mytaxi_page_hero( __( 'Contact', 'mytaxi-generatepress' ), 'taxi-albena' );
	?>
	<section class="mt-section" id="details">
		<div class="mt-section__head">
			<h2><?php echo esc_html( mytaxi_line( 'contact_local_heading' ) ); ?></h2>
		</div>
		<div class="mt-contacts">
			<?php foreach ( array_merge( $groups['local'], $groups['airport'] ) as $service ) : ?>
				<?php get_template_part( 'template-parts/contact-card', null, array( 'service' => $service ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endwhile;
mytaxi_close();
