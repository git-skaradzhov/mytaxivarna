<?php
/**
 * Route hero: photo, title, the first step of the request form, and the guarantees.
 * The second step replaces the photo with a plain request layout in the same place.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$route    = (array) ( $args['route'] ?? array() );
$service  = (array) ( $route['service'] ?? array() );
$price    = (array) ( $route['price'] ?? array() );
$origin   = (string) ( $route['origin'] ?? '' );
$dest     = (string) ( $route['destination'] ?? '' );
$photo    = mytaxi_hero_photo( (string) ( $route['slug'] ?? '' ) );
$contacts = (array) ( $service['contacts'] ?? array() );
$fixed    = mytaxi_fixed_price( $price );
$lede     = $fixed
	? sprintf( __( 'Private transfer, met in Arrivals with a name sign. %s for the car.', 'mytaxi-generatepress' ), (string) $price['text'] )
	: __( 'Private transfer, met in Arrivals with a name sign. Price per car, confirmed with you before the trip.', 'mytaxi-generatepress' );
?>
<section
	class="mt-photo-hero mt-photo-hero--route"
	id="request"
	data-route-flow
	data-route="<?php echo esc_attr( (string) (int) ( $route['id'] ?? 0 ) ); ?>"
	<?php if ( $fixed ) : ?>
		data-price-amount="<?php echo esc_attr( (string) $price['amount'] ); ?>"
		data-price-text="<?php echo esc_attr( (string) $price['text'] ); ?>"
	<?php endif; ?>
	data-price-note="<?php echo esc_attr( ! empty( $price['demo'] ) ? __( 'Demonstration price only. The team must confirm the actual fare.', 'mytaxi-generatepress' ) : '' ); ?>"
	data-origin="<?php echo esc_attr( $origin ); ?>"
	data-destination="<?php echo esc_attr( $dest ); ?>"
	<?php echo mytaxi_hero_style( $photo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<div class="mt-photo-hero__inner">
		<div data-panel="journey">
			<h1><?php echo esc_html( sprintf( __( 'Transfers from %1$s to %2$s', 'mytaxi-generatepress' ), $origin, $dest ) ); ?></h1>
			<p class="mt-photo-hero__lede"><?php echo esc_html( $lede ); ?></p>
		</div>
		<div class="mt-request-head" data-panel="request">
			<p class="mt-request-head__steps"><span>1 <?php esc_html_e( 'Journey', 'mytaxi-generatepress' ); ?></span><span aria-hidden="true">→</span><strong aria-current="step">2 <?php esc_html_e( 'Your details', 'mytaxi-generatepress' ); ?></strong></p>
			<h2 class="mt-request-head__title"><?php esc_html_e( 'Complete your transfer request', 'mytaxi-generatepress' ); ?></h2>
			<p><?php esc_html_e( 'Your booking is confirmed after we agree the price and receive a passenger name and flight number.', 'mytaxi-generatepress' ); ?></p>
		</div>

		<div class="mt-request-grid">
			<div class="mt-request-grid__form">
				<?php
				get_template_part(
					'template-parts/inquiry-form',
					null,
					array(
						'service_id' => (int) ( $route['service_id'] ?? 0 ),
						'route_id'   => (int) ( $route['id'] ?? 0 ),
					)
				);
				?>
				<p data-panel="journey"><a href="<?php echo esc_url( home_url( '/routes/' ) ); ?>"><?php esc_html_e( 'Choose another route', 'mytaxi-generatepress' ); ?></a></p>
				<?php mytaxi_direct_line( $service, __( 'Transfer today, or more than 5 passengers?', 'mytaxi-generatepress' ), 'journey' ); ?>
			</div>
			<aside class="mt-summary" data-panel="request" aria-label="<?php esc_attr_e( 'Your journey', 'mytaxi-generatepress' ); ?>">
				<div class="mt-summary__card">
					<h2><?php esc_html_e( 'Your journey', 'mytaxi-generatepress' ); ?></h2>
					<dl>
						<div class="mt-summary__route"><dt><?php esc_html_e( 'Route', 'mytaxi-generatepress' ); ?></dt><dd data-summary="route"><?php echo esc_html( trim( $origin . ' → ' . $dest ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Flight arrival', 'mytaxi-generatepress' ); ?></dt><dd data-summary="when">—</dd></div>
						<div><dt><?php esc_html_e( 'Passengers', 'mytaxi-generatepress' ); ?></dt><dd data-summary="passengers">—</dd></div>
						<div><dt><?php esc_html_e( 'Return', 'mytaxi-generatepress' ); ?></dt><dd data-summary="return"><?php esc_html_e( 'Not requested', 'mytaxi-generatepress' ); ?></dd></div>
					</dl>
					<div class="mt-summary__quote" data-summary-quote aria-live="polite">
						<?php mytaxi_quote( $price, false ); ?>
					</div>
					<button type="button" class="mt-summary__edit" data-edit-journey><?php esc_html_e( 'Edit journey', 'mytaxi-generatepress' ); ?></button>
				</div>
				<?php if ( $contacts ) : ?>
					<div class="mt-summary__chat">
						<h2><?php esc_html_e( 'Prefer to message us?', 'mytaxi-generatepress' ); ?></h2>
						<p><?php echo esc_html( sprintf( __( 'The %s team answers 24/7.', 'mytaxi-generatepress' ), (string) ( $service['title'] ?? '' ) ) ); ?></p>
						<?php mytaxi_channel_links( $service ); ?>
					</div>
				<?php endif; ?>
			</aside>
		</div>

		<div data-panel="journey"><?php mytaxi_trust_row(); ?></div>
	</div>
</section>
