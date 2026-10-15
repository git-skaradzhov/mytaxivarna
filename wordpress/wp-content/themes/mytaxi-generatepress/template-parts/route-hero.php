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

$route   = (array) ( $args['route'] ?? array() );
$service = (array) ( $route['service'] ?? array() );
$price   = (array) ( $route['price'] ?? array() );
$origin  = (string) ( $route['origin'] ?? '' );
$dest    = (string) ( $route['destination'] ?? '' );
$photo   = mytaxi_hero_photo( (string) ( $route['slug'] ?? '' ) );
$phone   = (string) ( $service['contacts']['phone'] ?? '' );
$lede    = ! empty( $price['offer'] )
	? sprintf( __( 'Private transfer, met in Arrivals with a name sign. %s for the car.', 'mytaxi-generatepress' ), (string) $price['text'] )
	: __( 'Private transfer, met in Arrivals with a name sign.', 'mytaxi-generatepress' );
?>
<section
	class="mt-photo-hero mt-photo-hero--route"
	id="request"
	data-route-flow
	data-route="<?php echo esc_attr( (string) (int) ( $route['id'] ?? 0 ) ); ?>"
	data-price="<?php echo esc_attr( (string) ( $price['text'] ?? '' ) ); ?>"
	data-origin="<?php echo esc_attr( $origin ); ?>"
	data-destination="<?php echo esc_attr( $dest ); ?>"
	<?php echo $photo ? ' style="background-image:url(' . esc_url( $photo ) . ')"' : ''; ?>
>
	<div class="mt-photo-hero__inner">
		<div data-panel="journey">
			<h1><?php echo esc_html( sprintf( __( 'Transfers from %1$s to %2$s', 'mytaxi-generatepress' ), $origin, $dest ) ); ?></h1>
			<p class="mt-photo-hero__lede"><?php echo esc_html( $lede ); ?></p>
		</div>
		<div class="mt-request-head" data-panel="request">
			<p class="mt-request-head__steps"><span>1 <?php esc_html_e( 'Journey', 'mytaxi-generatepress' ); ?></span><span aria-hidden="true">→</span><strong aria-current="step">2 <?php esc_html_e( 'Your details', 'mytaxi-generatepress' ); ?></strong></p>
			<h1><?php esc_html_e( 'Complete your transfer request', 'mytaxi-generatepress' ); ?></h1>
			<p><?php esc_html_e( 'We will contact you to agree the price and confirm the transfer.', 'mytaxi-generatepress' ); ?></p>
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
			</div>
			<aside class="mt-summary" data-panel="request">
				<div class="mt-summary__card">
					<h2><?php esc_html_e( 'Your journey', 'mytaxi-generatepress' ); ?></h2>
					<dl>
						<div><dt><?php esc_html_e( 'Route', 'mytaxi-generatepress' ); ?></dt><dd data-summary="route"><?php echo esc_html( trim( $origin . ' → ' . $dest ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Flight arrival', 'mytaxi-generatepress' ); ?></dt><dd data-summary="when">—</dd></div>
						<div><dt><?php esc_html_e( 'Passengers', 'mytaxi-generatepress' ); ?></dt><dd data-summary="passengers">—</dd></div>
						<div><dt><?php esc_html_e( 'Return', 'mytaxi-generatepress' ); ?></dt><dd data-summary="return"><?php esc_html_e( 'Not requested', 'mytaxi-generatepress' ); ?></dd></div>
						<div class="mt-summary__price"><dt><?php esc_html_e( 'Price for the car', 'mytaxi-generatepress' ); ?></dt><dd data-summary="price"><?php echo esc_html( (string) ( $price['text'] ?? '' ) ); ?></dd></div>
					</dl>
					<button type="button" class="mt-summary__edit" data-edit-journey><?php esc_html_e( 'Edit journey', 'mytaxi-generatepress' ); ?></button>
				</div>
				<?php if ( ! empty( $service['contacts']['whatsapp_url'] ) ) : ?>
					<div class="mt-summary__chat">
						<h2><?php esc_html_e( 'Prefer WhatsApp?', 'mytaxi-generatepress' ); ?></h2>
						<p><?php echo esc_html( sprintf( __( 'Message the %s team directly.', 'mytaxi-generatepress' ), (string) ( $service['title'] ?? '' ) ) ); ?></p>
						<a class="mt-btn mt-btn--teal" href="<?php echo esc_url( $service['contacts']['whatsapp_url'] ); ?>">WhatsApp</a>
						<?php if ( $phone ) : ?>
							<p class="mt-summary__phone"><a href="<?php echo esc_url( $service['contacts']['phone_href'] ); ?>"><?php echo esc_html( mytaxi_phone_text( $phone ) ); ?></a></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</aside>
		</div>

		<div data-panel="journey"><?php mytaxi_trust_row(); ?></div>
	</div>
</section>
