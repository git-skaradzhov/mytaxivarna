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
	?>
	<?php mytaxi_breadcrumbs(); ?>
	<header class="mt-page-head">
		<h1><?php the_title(); ?></h1>
		<div class="mt-prose"><?php the_content(); ?></div>
	</header>
	<?php if ( $routes ) : ?>
		<section class="mt-section">
			<div class="mt-route-list">
				<?php foreach ( $routes as $route ) : ?>
					<?php get_template_part( 'template-parts/route-card', null, array( 'route' => $route, 'variant' => 'row' ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
mytaxi_close();
