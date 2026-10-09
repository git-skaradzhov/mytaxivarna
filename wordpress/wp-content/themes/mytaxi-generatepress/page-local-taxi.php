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
	?>
	<?php mytaxi_breadcrumbs(); ?>
	<header class="mt-page-head">
		<h1><?php the_title(); ?></h1>
		<div class="mt-prose"><?php the_content(); ?></div>
	</header>
	<section class="mt-section">
		<div class="mt-places mt-places--local">
			<?php foreach ( $groups['local'] as $service ) : ?>
				<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'place' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endwhile;
mytaxi_close();
