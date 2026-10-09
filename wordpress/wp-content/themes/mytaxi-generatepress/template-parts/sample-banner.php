<?php
/**
 * Sample-content banner.
 *
 * @package MyTaxi_GeneratePress
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\MyTaxi\Core\Data' ) || ! \MyTaxi\Core\Data::is_sample() ) {
	return;
}
?>
<p class="mt-sample" role="note"><?php esc_html_e( 'Sample content for review. These pages are not confirmed business information.', 'mytaxi-generatepress' ); ?></p>
