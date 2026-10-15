<?php
/**
 * Homepage: choose a local taxi or an airport transfer before entering journey details.
 * @package MyTaxi_GeneratePress
 */
defined( 'ABSPATH' ) || exit;
$groups = mytaxi_service_groups();
$extras = class_exists( '\\MyTaxi\\Core\\Data' ) ? \MyTaxi\Core\Data::home_extras() : array( 'faqs' => array() );
mytaxi_open( 'template-parts/home-services', array( 'groups' => $groups ) );
if ( have_posts() ) { the_post(); }
?>
<section class="mt-home-benefits" aria-label="Service overview">
 <?php foreach ( array( 'clock' => array( '24/7 service', 'Contact your local team, day or night.' ), 'pin' => array( 'Local taxi teams', 'Golden Sands, Albena and Kranevo.' ), 'plane' => array( 'Airport transfers', 'Varna and Sofia airport services.' ), 'phone' => array( 'Phone, WhatsApp & Viber', 'Choose your service for the right contact.' ) ) as $icon => $item ) : ?>
 <div><?php mytaxi_icon( $icon ); ?><span><strong><?php echo esc_html( $item[0] ); ?></strong><small><?php echo esc_html( $item[1] ); ?></small></span></div>
 <?php endforeach; ?>
</section>
<section class="mt-home-intro">
 <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/varna-vehicle.webp' ); ?>" alt="Vehicle for the Varna airport transfer service" width="800" height="560" loading="lazy">
 <div><p class="mt-home-eyebrow">YOUR JOURNEY, YOUR CHOICE</p>
 <h2>Local rides and airport journeys, made simple</h2>
 <p>Already staying on the Black Sea coast? Choose the taxi team in Golden Sands, Albena or Kranevo for a pickup from your hotel or address.</p>
 <p>Flying into Bulgaria? Choose Varna Airport for transfers to Golden Sands, Albena, Kranevo and Balchik, or Sofia Airport for a transfer to Sofia. Send an enquiry and the team will confirm your journey directly.</p>
 <div class="mt-home-facts">
 <div><?php mytaxi_icon( 'phone' ); ?><strong>Direct contact</strong><span>A dedicated number for each service.</span></div>
 <div><?php mytaxi_icon( 'sign' ); ?><strong>Airport meeting</strong><span>Meet your driver in arrivals with a name sign.</span></div>
 <div><?php mytaxi_icon( 'card' ); ?><strong>No prepayment</strong><span>Pay the driver after your airport transfer.</span></div>
 <div><?php mytaxi_icon( 'seat' ); ?><strong>Child seats</strong><span>Request with your transfer; availability is confirmed.</span></div>
 </div></div>
</section>
<?php
get_template_part( 'template-parts/review-sources', null, array( 'reviews' => mytaxi_review_sources( array_merge( $groups['local'], $groups['airport'] ) ) ) );
mytaxi_faqs( $extras['faqs'] );
?>
<section class="mt-st-cta"><div><h2>Not sure which service to choose?</h2><p>Find the contact details for each local taxi and airport transfer team.</p></div><a class="mt-btn mt-btn--yellow" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact our teams</a></section>
<?php if ( get_the_content() ) : ?><section class="mt-st-section mt-prose"><?php the_content(); ?></section><?php endif; ?>
<?php mytaxi_close(); ?>
