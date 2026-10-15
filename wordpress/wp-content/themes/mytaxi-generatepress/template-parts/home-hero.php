<?php
/**
 * Full-width photo hero with the transfer search. Used by the homepage and the airport listing pages.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$routes = array_values( (array) ( $args['routes'] ?? array() ) );
$photo  = (string) ( $args['photo'] ?? '' );
$title  = (string) ( $args['title'] ?? mytaxi_line( 'home_title' ) );
$lede   = (string) ( $args['lede'] ?? mytaxi_line( 'home_lead' ) );
$local  = $args['local'] ?? true;

$origins = array();
foreach ( $routes as $route ) {
	$origin = trim( (string) ( $route['origin'] ?? '' ) );
	if ( '' !== $origin && '' !== trim( (string) ( $route['destination'] ?? '' ) ) ) {
		$origins[ $origin ][] = $route;
	}
}
?>
<section class="mt-photo-hero"<?php echo $photo ? ' style="background-image:url(' . esc_url( $photo ) . ')"' : ''; ?>>
	<div class="mt-photo-hero__inner">
		<?php if ( ! empty( $args['crumbs'] ) ) : ?>
			<?php mytaxi_breadcrumbs(); ?>
		<?php endif; ?>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( '' !== $lede ) : ?>
			<p class="mt-photo-hero__lede"><?php echo esc_html( $lede ); ?></p>
		<?php endif; ?>

		<?php if ( $origins ) : ?>
			<form class="mt-search" action="<?php echo esc_url( home_url( '/routes/' ) ); ?>" method="get" data-transfer-search novalidate>
				<div class="mt-search__field">
					<label for="mt-search-from"><?php esc_html_e( 'Arrival airport', 'mytaxi-generatepress' ); ?></label>
					<select id="mt-search-from" data-search-from>
						<?php foreach ( array_keys( $origins ) as $origin ) : ?>
							<option value="<?php echo esc_attr( $origin ); ?>"><?php echo esc_html( $origin ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="mt-search__field">
					<label for="mt-search-to"><?php esc_html_e( 'Going to', 'mytaxi-generatepress' ); ?></label>
					<select id="mt-search-to" data-search-to required>
						<option value=""><?php esc_html_e( 'Choose destination', 'mytaxi-generatepress' ); ?></option>
						<?php foreach ( $origins as $origin => $items ) : ?>
							<?php foreach ( $items as $route ) : ?>
								<option value="<?php echo esc_url( (string) $route['url'] ); ?>" data-origin="<?php echo esc_attr( $origin ); ?>" data-route="<?php echo esc_attr( (string) (int) $route['id'] ); ?>"><?php echo esc_html( (string) $route['destination'] ); ?></option>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="mt-search__field mt-search__field--when">
					<label for="mt-search-date"><?php esc_html_e( 'Flight arrival', 'mytaxi-generatepress' ); ?></label>
					<span class="mt-search__pair">
						<input id="mt-search-date" type="date" data-search-date aria-label="<?php esc_attr_e( 'Arrival date', 'mytaxi-generatepress' ); ?>">
						<input type="time" data-search-time aria-label="<?php esc_attr_e( 'Arrival time', 'mytaxi-generatepress' ); ?>">
					</span>
				</div>
				<div class="mt-search__field mt-search__field--small">
					<label for="mt-search-passengers"><?php esc_html_e( 'Passengers', 'mytaxi-generatepress' ); ?></label>
					<select id="mt-search-passengers" data-search-passengers>
						<?php for ( $count = 1; $count <= 8; $count++ ) : ?>
							<option value="<?php echo esc_attr( (string) $count ); ?>"<?php selected( 2, $count ); ?>><?php echo esc_html( (string) $count ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<button type="submit" class="mt-btn mt-btn--navy mt-search__go"><?php esc_html_e( 'Search', 'mytaxi-generatepress' ); ?></button>
			</form>
		<?php endif; ?>

		<?php mytaxi_trust_row(); ?>

		<?php if ( $local ) : ?>
			<p class="mt-photo-hero__local">
				<?php esc_html_e( 'Already at the resort?', 'mytaxi-generatepress' ); ?>
				<a href="<?php echo esc_url( home_url( '/local-taxi/' ) ); ?>"><?php echo esc_html( mytaxi_line( 'home_local_button' ) ); ?> →</a>
			</p>
		<?php endif; ?>
	</div>
</section>
