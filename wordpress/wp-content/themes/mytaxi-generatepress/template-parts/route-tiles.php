<?php
/**
 * Grey band with compact route tiles, optionally split into groups.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$groups = array_values( (array) ( $args['groups'] ?? array() ) );
if ( ! $groups && ! empty( $args['routes'] ) ) {
	$groups = array(
		array(
			'label'  => '',
			'routes' => (array) $args['routes'],
		),
	);
}
$groups = array_values(
	array_filter(
		$groups,
		static function ( $group ) {
			return ! empty( $group['routes'] );
		}
	)
);
if ( ! $groups ) {
	return;
}
?>
<section class="mt-band-grey"<?php echo ! empty( $args['id'] ) ? ' id="' . esc_attr( (string) $args['id'] ) . '"' : ''; ?><?php echo ! empty( $args['panel'] ) ? ' data-panel="journey"' : ''; ?>>
	<div class="mt-band-grey__inner">
		<div class="mt-st-head">
			<h2><?php echo esc_html( (string) ( $args['heading'] ?? '' ) ); ?></h2>
			<?php if ( ! empty( $args['text'] ) ) : ?>
				<p><?php echo esc_html( (string) $args['text'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php foreach ( $groups as $group ) : ?>
			<?php if ( ! empty( $group['label'] ) ) : ?>
				<h3 class="mt-route-tiles__label"><?php echo esc_html( (string) $group['label'] ); ?></h3>
			<?php endif; ?>
			<div class="mt-route-tiles">
				<?php foreach ( (array) $group['routes'] as $route ) : ?>
					<?php
					$dest  = (string) ( $route['destination'] ?? '' );
					$from  = (string) ( $route['origin'] ?? '' );
					$price = (array) ( $route['price'] ?? array() );
					$facts = array_filter( array( (string) ( $route['distance'] ?? '' ), (string) ( $route['duration'] ?? '' ) ) );
					?>
					<a class="mt-route-tile" href="<?php echo esc_url( (string) ( $route['url'] ?? '' ) ); ?>">
						<span class="mt-route-tile__media"><?php mytaxi_picture( (string) ( $route['slug'] ?? '' ), (int) ( $route['image_id'] ?? 0 ), trim( $from . ' → ' . $dest ) ); ?></span>
						<span class="mt-route-tile__body">
							<span class="mt-route-tile__title"><?php echo esc_html( sprintf( __( 'Transfer to %s', 'mytaxi-generatepress' ), $dest ) ); ?></span>
							<span class="mt-route-tile__from"><?php echo esc_html( sprintf( __( 'From %s', 'mytaxi-generatepress' ), $from ) ); ?></span>
							<?php if ( $facts ) : ?>
								<span class="mt-route-tile__facts"><?php echo esc_html( implode( ' · ', $facts ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="mt-route-tile__price">
							<?php if ( ! empty( $price['offer'] ) ) : ?>
								<strong><?php echo esc_html( (string) ( $price['text'] ?? '' ) ); ?></strong>
							<?php else : ?>
								<?php echo esc_html( (string) ( $price['text'] ?? '' ) ); ?>
							<?php endif; ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<?php if ( ! empty( $args['more_url'] ) ) : ?>
			<p class="mt-band-grey__more"><a href="<?php echo esc_url( (string) $args['more_url'] ); ?>"><?php echo esc_html( (string) ( $args['more_label'] ?? __( 'View all routes', 'mytaxi-generatepress' ) ) ); ?></a></p>
		<?php endif; ?>
	</div>
</section>
