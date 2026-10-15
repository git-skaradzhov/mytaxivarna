<?php
/**
 * Contact page.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

the_post();
$groups = mytaxi_service_groups();

mytaxi_open(
	'template-parts/page-hero',
	array(
		'title'   => get_the_title(),
		'lede'    => wp_strip_all_tags( get_the_content() ),
		'photo'   => mytaxi_hero_photo( 'home' ),
		'compact' => true,
	)
);

$sections = array(
	array(
		'id'       => 'airport',
		'heading'  => __( 'Airport transfers', 'mytaxi-generatepress' ),
		'text'     => __( 'Each airport has its own team. Use the number of the airport you land at.', 'mytaxi-generatepress' ),
		'services' => $groups['airport'],
	),
	array(
		'id'       => 'local',
		'heading'  => __( 'Local taxi', 'mytaxi-generatepress' ),
		'text'     => __( 'Already at the resort? Call the local team.', 'mytaxi-generatepress' ),
		'services' => $groups['local'],
	),
);

foreach ( $sections as $section ) :
	if ( ! $section['services'] ) {
		continue;
	}
	?>
	<section class="mt-st-section" id="<?php echo esc_attr( $section['id'] ); ?>">
		<div class="mt-st-head">
			<h2><?php echo esc_html( $section['heading'] ); ?></h2>
			<p><?php echo esc_html( $section['text'] ); ?></p>
		</div>
		<div class="mt-contacts">
			<?php foreach ( $section['services'] as $service ) : ?>
				<?php get_template_part( 'template-parts/contact-card', null, array( 'service' => $service ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
endforeach;
?>
<div class="mt-st-end"></div>
<?php
mytaxi_close();
