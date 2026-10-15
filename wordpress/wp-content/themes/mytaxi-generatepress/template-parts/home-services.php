<?php
/**
 * Five service choices, with live service links, images and phone numbers.
 * @package MyTaxi_GeneratePress
 */
defined( 'ABSPATH' ) || exit;
$groups = $args['groups'] ?? array( 'local' => array(), 'airport' => array() );
$photos = array( 'taxi-golden-sands' => 'golden-sands.webp', 'taxi-albena' => 'albena.webp', 'taxi-kranevo' => 'kranevo.webp', 'varna-airport-transfers' => 'varna-airport.webp', 'sofia-airport-transfers' => 'sofia-airport-800.webp' );
?>
<section class="mt-home-choices" style="--mt-home-photo:url('<?php echo esc_url( mytaxi_hero_photo( 'home' ) ); ?>')">
 <div class="mt-home-choices__inner">
 <header><h1><?php echo esc_html( mytaxi_line( 'home_title' ) ); ?></h1><p><?php echo esc_html( mytaxi_line( 'home_lead' ) ); ?></p></header>
 <div class="mt-home-groups">
 <?php foreach ( array( 'local' => array( 'Local taxis', 'Already at the resort? Choose your local taxi team.', 'car' ), 'airport' => array( 'Airport transfers', 'Flying into Bulgaria? Choose your arrival airport.', 'plane' ) ) as $key => $group ) : ?>
 <section class="mt-home-group mt-home-group--<?php echo esc_attr( $key ); ?>" aria-labelledby="home-<?php echo esc_attr( $key ); ?>">
 <div class="mt-home-group__heading"><span class="mt-home-group__icon"><?php mytaxi_icon( $group[2] ); ?></span><div><h2 id="home-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $group[0] ); ?></h2><p><?php echo esc_html( $group[1] ); ?></p></div></div>
 <div class="mt-home-service-list">
 <?php foreach ( $groups[ $key ] as $service ) :
 $slug = (string) get_post_field( 'post_name', (int) $service['id'] );
 $photo = get_stylesheet_directory_uri() . '/assets/images/' . ( $photos[ $slug ] ?? 'coast-800.webp' );
 $phone = (string) ( $service['contacts']['phone'] ?? '' );
 ?>
 <a class="mt-home-service" href="<?php echo esc_url( $service['url'] ); ?>">
 <?php if ( ! empty( $service['image_id'] ) ) : ?>
 <?php echo wp_get_attachment_image( (int) $service['image_id'], 'medium', false, array( 'alt' => '', 'class' => 'mt-home-service__image' ) ); ?>
 <?php else : ?><img class="mt-home-service__image" src="<?php echo esc_url( $photo ); ?>" alt="" width="240" height="150"><?php endif; ?>
 <span class="mt-home-service__copy"><strong><?php echo esc_html( mytaxi_place_name( $service['title'] ) ); ?></strong><span><?php echo esc_html( 'local' === $key ? 'Taxi from your hotel or address' : 'View routes and request a transfer' ); ?></span><?php if ( $phone ) : ?><small><?php echo esc_html( mytaxi_phone_text( $phone ) ); ?></small><?php endif; ?></span>
 <span class="mt-home-service__arrow"><?php mytaxi_icon( 'arrow' ); ?></span>
 </a>
 <?php endforeach; ?>
 </div></section>
 <?php endforeach; ?>
 </div></div>
</section>
