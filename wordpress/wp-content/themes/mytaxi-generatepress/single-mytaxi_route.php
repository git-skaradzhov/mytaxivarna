<?php
/**
 * Route page.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

mytaxi_open();
while ( have_posts() ) :
	the_post();
	$route   = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::route( get_the_ID() ) : array();
	$related = class_exists( '\MyTaxi\Core\Data' ) ? \MyTaxi\Core\Data::related_routes( get_the_ID() ) : array();
	$service = $route['service'] ?? array();
	$price   = $route['price'] ?? array( 'text' => '', 'basis_label' => '', 'type' => 'on_request', 'offer' => false );
	$facts   = array(
		__( 'Distance', 'mytaxi-generatepress' )     => $route['distance'] ?? '',
		__( 'Journey time', 'mytaxi-generatepress' ) => $route['duration'] ?? '',
		__( 'Capacity', 'mytaxi-generatepress' )     => $route['capacity'] ?? '',
		__( 'Luggage', 'mytaxi-generatepress' )      => $route['luggage'] ?? '',
	);
	$phone = $service['contacts']['phone'] ?? '';
	?>
	<?php mytaxi_breadcrumbs(); ?>
	<header class="mt-hero">
		<div class="mt-hero__copy">
			<p class="mt-kicker"><?php esc_html_e( 'Airport transfer', 'mytaxi-generatepress' ); ?></p>
			<h1><?php echo esc_html( trim( ( $route['origin'] ?? '' ) . ' → ' . ( $route['destination'] ?? '' ) ) ); ?></h1>
			<?php mytaxi_text( $route['summary'] ?? '' ); ?>
			<?php if ( $phone ) : ?>
				<p class="mt-phone"><a href="<?php echo esc_url( $service['contacts']['phone_href'] ); ?>"><?php mytaxi_icon( 'phone' ); ?><span><?php echo esc_html( mytaxi_phone_text( $phone ) ); ?></span></a></p>
			<?php endif; ?>
			<div class="mt-actions">
				<a class="mt-btn mt-btn--yellow" href="#request"><span><?php esc_html_e( 'Request a quote', 'mytaxi-generatepress' ); ?></span><?php mytaxi_icon( 'arrow' ); ?></a>
				<?php if ( ! empty( $service['contacts']['whatsapp_url'] ) ) : ?>
					<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( $service['contacts']['whatsapp_url'] ); ?>">WhatsApp</a>
				<?php endif; ?>
				<?php if ( ! empty( $service['contacts']['viber_url'] ) ) : ?>
					<a class="mt-btn mt-btn--ghost" href="<?php echo esc_url( $service['contacts']['viber_url'] ); ?>">Viber</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="mt-hero__media">
			<?php mytaxi_picture( $route['slug'] ?? '', (int) ( $route['image_id'] ?? 0 ), get_the_title(), true ); ?>
		</div>
	</header>

	<div class="mt-split">
		<div>
			<section class="mt-panel">
				<h2><?php esc_html_e( 'Your transfer, step by step', 'mytaxi-generatepress' ); ?></h2>
				<ol class="mt-steps">
					<li><span>1</span><div><strong><?php esc_html_e( 'Send your details', 'mytaxi-generatepress' ); ?></strong><p><?php esc_html_e( 'Tell us when and where you need a transfer.', 'mytaxi-generatepress' ); ?></p></div></li>
					<li><span>2</span><div><strong><?php esc_html_e( 'Receive confirmation', 'mytaxi-generatepress' ); ?></strong><p><?php esc_html_e( 'The team confirms availability and your quote.', 'mytaxi-generatepress' ); ?></p></div></li>
					<li><span>3</span><div><strong><?php esc_html_e( 'Arrange your pickup', 'mytaxi-generatepress' ); ?></strong><p><?php echo ! empty( $route['meeting'] ) ? esc_html( $route['meeting'] ) : esc_html__( 'Agree the pickup details with the team.', 'mytaxi-generatepress' ); ?></p></div></li>
				</ol>
			</section>

			<section class="mt-panel">
				<p class="mt-price"><?php echo esc_html( $price['text'] ); ?></p>
				<?php if ( ! empty( $price['basis_label'] ) && ! empty( $price['offer'] ) ) : ?>
					<p><?php echo esc_html( $price['basis_label'] ); ?></p>
				<?php elseif ( 'from' === ( $price['type'] ?? '' ) ) : ?>
					<p><?php esc_html_e( 'The amount is a starting price, not a fixed fare.', 'mytaxi-generatepress' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'Tell us your travel details to receive a quote.', 'mytaxi-generatepress' ); ?></p>
				<?php endif; ?>
				<dl class="mt-facts">
					<div><dt><?php esc_html_e( 'Pickup', 'mytaxi-generatepress' ); ?></dt><dd><?php echo esc_html( $route['origin'] ?? '' ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Destination', 'mytaxi-generatepress' ); ?></dt><dd><?php echo esc_html( $route['destination'] ?? '' ); ?></dd></div>
					<?php if ( ! empty( $service['title'] ) ) : ?>
						<div><dt><?php esc_html_e( 'Service', 'mytaxi-generatepress' ); ?></dt><dd><?php echo esc_html( $service['title'] ); ?></dd></div>
					<?php endif; ?>
				</dl>
			</section>

			<?php if ( array_filter( $facts ) || ! empty( $route['included'] ) || ! empty( $route['delay'] ) || ! empty( $route['requirements'] ) ) : ?>
				<section class="mt-panel">
					<h2><?php esc_html_e( 'Journey details', 'mytaxi-generatepress' ); ?></h2>
					<dl class="mt-facts">
						<?php foreach ( $facts as $label => $value ) : ?>
							<?php if ( '' !== trim( (string) $value ) ) : ?>
								<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
							<?php endif; ?>
						<?php endforeach; ?>
					</dl>
					<?php if ( ! empty( $route['included'] ) ) : ?>
						<h3><?php esc_html_e( 'Included', 'mytaxi-generatepress' ); ?></h3>
						<ul class="mt-list">
							<?php foreach ( $route['included'] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $route['delay'] ) ) : ?>
						<h3><?php esc_html_e( 'Delayed flight', 'mytaxi-generatepress' ); ?></h3>
						<?php mytaxi_text( $route['delay'] ); ?>
					<?php endif; ?>
					<?php if ( ! empty( $route['requirements'] ) ) : ?>
						<h3><?php esc_html_e( 'Requirements', 'mytaxi-generatepress' ); ?></h3>
						<?php mytaxi_text( $route['requirements'] ); ?>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="mt-prose"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
		<aside class="mt-form-card" id="request">
			<h2><?php esc_html_e( 'Request your transfer', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Send your details. We’ll confirm availability and your quote.', 'mytaxi-generatepress' ); ?></p>
			<?php
			get_template_part(
				'template-parts/inquiry-form',
				null,
				array(
					'service_id' => (int) ( $route['service_id'] ?? 0 ),
					'route_id'   => get_the_ID(),
				)
			);
			?>
		</aside>
	</div>

	<?php mytaxi_faqs( $route['faqs'] ?? array() ); ?>

	<?php if ( $related ) : ?>
		<section class="mt-section">
			<div class="mt-section__head">
				<h2><?php esc_html_e( 'Other airport routes', 'mytaxi-generatepress' ); ?></h2>
				<?php if ( ! empty( $service['url'] ) ) : ?>
					<a href="<?php echo esc_url( $service['url'] ); ?>"><?php echo esc_html( $service['title'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="mt-route-list">
				<?php foreach ( $related as $item ) : ?>
					<?php get_template_part( 'template-parts/route-card', null, array( 'route' => $item, 'variant' => 'row' ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
mytaxi_close();
