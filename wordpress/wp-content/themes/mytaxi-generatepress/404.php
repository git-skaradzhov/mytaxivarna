<?php
/**
 * Not found.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

mytaxi_open();
$groups = mytaxi_service_groups();
?>
<header class="mt-page-head">
	<h1><?php esc_html_e( 'Page not found', 'mytaxi-generatepress' ); ?></h1>
	<p><?php esc_html_e( 'The address is not on this site. Choose one of the services below.', 'mytaxi-generatepress' ); ?></p>
</header>
<div class="mt-places mt-places--local">
	<?php foreach ( array_merge( $groups['local'], $groups['airport'] ) as $service ) : ?>
		<?php get_template_part( 'template-parts/service-card', null, array( 'service' => $service, 'variant' => 'place' ) ); ?>
	<?php endforeach; ?>
</div>
<?php
mytaxi_close();
