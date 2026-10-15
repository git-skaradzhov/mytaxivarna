<?php
/**
 * Review-source cards. Hidden unless every public value was saved.
 *
 * @package MyTaxi_GeneratePress
 *
 * @var array<string,mixed> $args
 */

defined( 'ABSPATH' ) || exit;

$reviews = array_values( (array) ( $args['reviews'] ?? array() ) );
if ( ! $reviews ) {
	return;
}
?>
<section class="mt-band-grey mt-reviews-band"<?php echo ! empty( $args['panel'] ) ? ' data-panel="journey"' : ''; ?>>
	<div class="mt-band-grey__inner">
		<div class="mt-st-head mt-st-head--center">
			<h2><?php esc_html_e( 'What travellers say', 'mytaxi-generatepress' ); ?></h2>
		</div>
		<div class="mt-review-cards">
			<?php foreach ( $reviews as $review ) : ?>
				<?php
				$platform = (string) ( $review['platform'] ?? '' );
				$key      = sanitize_title( $platform );
				$score    = (float) str_replace( ',', '.', (string) ( $review['rating'] ?? '' ) );
				$score    = max( 0, min( 5, $score ) );
				$count    = (string) ( $review['count'] ?? '' );
				$count    = preg_match( '/^[\d\s.,+]+$/', $count ) ? sprintf( __( '%s reviews', 'mytaxi-generatepress' ), trim( $count ) ) : $count;
				?>
				<article class="mt-review-card mt-review-card--<?php echo esc_attr( $key ); ?>">
					<p class="mt-review-card__platform">
						<?php if ( 'google' === $key ) : ?>
							<span class="mt-g-b">G</span><span class="mt-g-r">o</span><span class="mt-g-y">o</span><span class="mt-g-b">g</span><span class="mt-g-g">l</span><span class="mt-g-r">e</span>
						<?php else : ?>
							<?php echo esc_html( $platform ); ?>
						<?php endif; ?>
					</p>
					<span class="mt-stars" style="--mt-score:<?php echo esc_attr( (string) ( $score / 5 * 100 ) ); ?>%" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%s out of 5', 'mytaxi-generatepress' ), (string) $score ) ); ?>"></span>
					<p class="mt-review-card__score"><?php echo esc_html( sprintf( '%s / 5', (string) $score ) ); ?></p>
					<a class="mt-review-card__count" href="<?php echo esc_url( (string) ( $review['url'] ?? '' ) ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( $count ); ?></a>
					<?php if ( ! empty( $review['checked'] ) ) : ?>
						<p class="mt-review-card__checked"><?php echo esc_html( sprintf( __( 'Checked %s', 'mytaxi-generatepress' ), (string) $review['checked'] ) ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
