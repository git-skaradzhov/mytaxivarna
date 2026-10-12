<?php
/**
 * Service page.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

mytaxi_open();
while ( have_posts() ) :
	the_post();
	$service  = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::service( get_the_ID() ) : array();
	$routes   = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::routes_for_service( get_the_ID() ) : array();
	$airport  = isset( $service['type'] ) && 'airport_transfer' === $service['type'];
	$transfer = $airport ? array() : mytaxi_transfer_for_local( $service );
	$phone    = $service['contacts']['phone'] ?? '';
	?>
	<?php mytaxi_breadcrumbs(); ?>
	<header class="mt-hero">
		<div class="mt-hero__copy">
			<p class="mt-kicker"><?php echo esc_html( $service['type_label'] ?? '' ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php mytaxi_text( $service['intro'] ?? '' ); ?>
			<?php if ( $phone ) : ?>
				<p class="mt-phone"><a href="<?php echo esc_url( $service['contacts']['phone_href'] ); ?>"><?php mytaxi_icon( 'phone' ); ?><span><?php echo esc_html( mytaxi_phone_text( $phone ) ); ?></span></a></p>
			<?php endif; ?>
			<div class="mt-actions">
				<?php if ( $airport ) : ?>
					<a class="mt-btn mt-btn--yellow" href="#request"><span><?php esc_html_e( 'Request a quote', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
				<?php elseif ( $phone ) : ?>
					<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( $service['contacts']['phone_href'] ); ?>"><?php mytaxi_icon( 'phone' ); ?><span><?php echo esc_html( sprintf( __( 'Call %s', 'mytaxi-generatepress' ), get_the_title() ) ); ?></span></a>
				<?php endif; ?>
				<?php if ( ! empty( $service['contacts']['whatsapp_url'] ) ) : ?>
					<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( $service['contacts']['whatsapp_url'] ); ?>">WhatsApp</a>
				<?php endif; ?>
				<?php if ( ! empty( $service['contacts']['viber_url'] ) ) : ?>
					<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( $service['contacts']['viber_url'] ); ?>">Viber</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="mt-hero__media">
			<?php mytaxi_picture( $service['slug'] ?? '', (int) ( $service['image_id'] ?? 0 ), get_the_title(), true ); ?>
		</div>
	</header>

	<?php if ( ! empty( $service['areas'] ) || $phone ) : ?>
		<ul class="mt-facts mt-facts--row">
			<?php if ( ! empty( $service['areas'] ) ) : ?>
				<li><?php mytaxi_icon( 'pin' ); ?><span><strong><?php echo esc_html( $airport ? __( 'Pickup', 'mytaxi-generatepress' ) : __( 'Pickup area', 'mytaxi-generatepress' ) ); ?></strong><?php echo esc_html( implode( ', ', $service['areas'] ) ); ?></span></li>
			<?php endif; ?>
			<?php if ( $phone ) : ?>
				<li><?php mytaxi_icon( 'phone' ); ?><span><strong><?php esc_html_e( 'Phone', 'mytaxi-generatepress' ); ?></strong><a href="<?php echo esc_url( $service['contacts']['phone_href'] ); ?>"><?php echo esc_html( mytaxi_phone_text( $phone ) ); ?></a></span></li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<div class="mt-split">
		<div>
			<?php if ( get_the_content() ) : ?>
				<div class="mt-prose"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( $routes ) : ?>
				<section class="mt-section">
					<h2><?php echo esc_html( $airport ? sprintf( __( 'Destinations from %s', 'mytaxi-generatepress' ), mytaxi_place_name( get_the_title() ) ) : __( 'Where would you like to go?', 'mytaxi-generatepress' ) ); ?></h2>
					<?php if ( $airport ) : ?>
						<p><?php esc_html_e( 'Choose a route for your destination or send a transfer request.', 'mytaxi-generatepress' ); ?></p>
					<?php endif; ?>
					<div class="mt-route-list">
						<?php foreach ( $routes as $route ) : ?>
							<?php get_template_part( 'template-parts/route-card', null, array( 'route' => $route, 'variant' => 'row' ) ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $service['benefits'] ) ) : ?>
				<section class="mt-section">
					<h2><?php esc_html_e( 'Confirmed details', 'mytaxi-generatepress' ); ?></h2>
					<ul class="mt-list">
						<?php foreach ( $service['benefits'] as $benefit ) : ?>
							<li><?php echo esc_html( $benefit ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>
		<aside class="mt-form-card" id="request">
			<h2><?php echo esc_html( $airport ? __( 'Request your transfer', 'mytaxi-generatepress' ) : __( 'Request a local taxi', 'mytaxi-generatepress' ) ); ?></h2>
			<p><?php echo esc_html( $airport ? __( 'Send your details. We’ll confirm availability and your quote.', 'mytaxi-generatepress' ) : __( 'Send your details. We’ll confirm availability with you.', 'mytaxi-generatepress' ) ); ?></p>
			<?php get_template_part( 'template-parts/inquiry-form', null, array( 'service_id' => get_the_ID(), 'route_id' => 0 ) ); ?>
		</aside>
	</div>

	<?php if ( $transfer ) : ?>
		<section class="mt-band mt-band--photo">
			<div>
				<p class="mt-kicker"><?php echo esc_html( $transfer['service']['title'] ?? '' ); ?></p>
				<h2><?php echo esc_html( sprintf( __( 'Arriving at %s?', 'mytaxi-generatepress' ), $transfer['origin'] ) ); ?></h2>
				<a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( $transfer['url'] ); ?>"><?php echo esc_html( $transfer['origin'] . ' → ' . $transfer['destination'] ); ?></a>
			</div>
			<div class="mt-band__media">
				<?php mytaxi_picture( $transfer['slug'] ?? '', (int) ( $transfer['image_id'] ?? 0 ), $transfer['title'] ?? '' ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $service['gallery'] ) ) : ?>
		<section class="mt-section">
			<h2><?php esc_html_e( 'Photos', 'mytaxi-generatepress' ); ?></h2>
			<div class="mt-gallery">
				<?php foreach ( $service['gallery'] as $image_id ) : ?>
					<?php mytaxi_image( (int) $image_id, 'mytaxi-card', get_the_title() ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php mytaxi_faqs( $service['faqs'] ?? array() ); ?>
	<?php
endwhile;
mytaxi_close();
