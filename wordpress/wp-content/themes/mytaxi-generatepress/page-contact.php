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
	?>
	<header class="mt-hero mt-hero--compact">
		<div class="mt-hero__copy">
			<p class="mt-kicker"><?php esc_html_e( 'Contact', 'mytaxi-generatepress' ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( get_the_content() ) : ?>
				<div class="mt-prose"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
	</header>
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
