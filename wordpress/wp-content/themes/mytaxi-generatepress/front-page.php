<?php
/**
 * Front page.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

mytaxi_open();

if ( have_posts() ) {
	the_post();
}

$groups = mytaxi_service_groups();
$routes = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::home_routes() : array();
$extras = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::home_extras() : array( 'fleet' => array(), 'benefits' => array(), 'reviews' => array(), 'faqs' => array() );
?>
<section class="mt-hero">
	<div class="mt-hero__copy">
		<p class="mt-kicker"><?php esc_html_e( 'Taxi & airport transfers', 'mytaxi-generatepress' ); ?></p>
		<h1><?php esc_html_e( 'Your journey starts here.', 'mytaxi-generatepress' ); ?></h1>
		<p class="mt-lead"><?php esc_html_e( 'Local taxi services and airport transfers, with the right team for every destination.', 'mytaxi-generatepress' ); ?></p>
		<div class="mt-actions">
			<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/local-taxi/' ) ); ?>"><?php mytaxi_icon( 'car' ); ?><span><?php esc_html_e( 'Find a local taxi', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
			<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( home_url( '/airport-transfers/' ) ); ?>"><?php mytaxi_icon( 'plane' ); ?><span><?php esc_html_e( 'Plan an airport transfer', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
		</div>
	</div>
	<div class="mt-hero__media mt-hero__media--home">
		<?php mytaxi_picture( 'home', (int) get_post_thumbnail_id(), get_bloginfo( 'name' ), true ); ?>
	</div>
</section>

<section class="mt-section" id="local">
	<div class="mt-section__head">
		<h2><?php esc_html_e( 'Local taxi', 'mytaxi-generatepress' ); ?></h2>
		<p><?php esc_html_e( 'Popular coastal destinations around Varna.', 'mytaxi-generatepress' ); ?></p>
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
		<p><?php esc_html_e( 'Transfers to and from Varna Airport and Sofia Airport.', 'mytaxi-generatepress' ); ?></p>
	</div>
	<div class="mt-places mt-places--airport">
		<?php foreach ( $groups['airport'] as $service ) : ?>
			<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'airport' ) ); ?>
		<?php endforeach; ?>
	</div>
</section>

<?php if ( $routes ) : ?>
	<section class="mt-section" id="routes">
		<div class="mt-section__head">
			<h2><?php esc_html_e( 'Popular airport routes', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'A price is shown only when it is confirmed.', 'mytaxi-generatepress' ); ?></p>
		</div>
		<div class="mt-route-list">
			<?php foreach ( $routes as $route ) : ?>
				<?php get_template_part( 'template-parts/route-card', null, array( 'route' => $route, 'variant' => 'row' ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty( $extras['fleet'] ) ) : ?>
	<section class="mt-section">
		<h2><?php esc_html_e( 'Vehicles', 'mytaxi-generatepress' ); ?></h2>
		<div class="mt-places mt-places--local">
			<?php foreach ( $extras['fleet'] as $item ) : ?>
				<article class="mt-panel">
					<h3><?php echo esc_html( $item['title'] ); ?></h3>
					<?php mytaxi_text( $item['text'] ); ?>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! empty( $extras['benefits'] ) ) : ?>
	<section class="mt-section">
		<h2><?php esc_html_e( 'What is included', 'mytaxi-generatepress' ); ?></h2>
		<ul class="mt-list">
			<?php foreach ( $extras['benefits'] as $benefit ) : ?>
				<li><?php echo esc_html( $benefit ); ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( ! empty( $extras['reviews'] ) ) : ?>
	<section class="mt-section">
		<h2><?php esc_html_e( 'Reviews', 'mytaxi-generatepress' ); ?></h2>
		<div class="mt-places mt-places--local">
			<?php foreach ( $extras['reviews'] as $review ) : ?>
				<blockquote class="mt-panel">
					<p><?php echo esc_html( $review['quote'] ); ?></p>
					<footer>
						<?php echo esc_html( $review['name'] ); ?>
						<?php if ( ! empty( $review['detail'] ) ) : ?>
							<span><?php echo esc_html( $review['detail'] ); ?></span>
						<?php endif; ?>
					</footer>
				</blockquote>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php mytaxi_faqs( $extras['faqs'] ); ?>

<section class="mt-band">
	<div>
		<h2><?php esc_html_e( 'Need a taxi or an airport transfer?', 'mytaxi-generatepress' ); ?></h2>
		<p><?php esc_html_e( 'Choose the service for the trip. Each one keeps its own phone.', 'mytaxi-generatepress' ); ?></p>
	</div>
	<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><span><?php esc_html_e( 'Contact us', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
</section>
<?php if ( get_the_content() ) : ?>
	<section class="mt-section mt-prose">
		<?php the_content(); ?>
	</section>
<?php endif; ?>
<?php
mytaxi_close();
