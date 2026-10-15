<?php
/** Run with WP-CLI: wp eval-file /path/to/repo/bin/import-google-business.php */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
$environment = wp_get_environment_type();
if ( ! in_array( $environment, array( 'local', 'staging' ), true ) || ( 'staging' === $environment && 'https://mytaxivarna.skaradzhov.com' !== get_option( 'home' ) ) ) {
	WP_CLI::error( 'This import is authorized only for local and the MyTaxi demo.' );
}
$registry_path = $args[0] ?? dirname( __DIR__ ) . '/docs/google-business-profiles.json';
$registry = json_decode( file_get_contents( $registry_path ), true );
if ( ! is_array( $registry ) || empty( $registry['profiles'] ) ) { WP_CLI::error( 'Verified profile registry is missing.' ); }
$updates = array();
foreach ( $registry['profiles'] as $entry ) {
	$post = get_page_by_path( $entry['service_slug'], OBJECT, 'mytaxi_service' );
	if ( ! $post || 'publish' !== $post->post_status || $entry['phone'] !== get_post_meta( $post->ID, '_mytaxi_phone', true ) ) {
		WP_CLI::error( 'Service or phone does not match: ' . $entry['service_slug'] );
	}
	$url = $entry['google_url'];
	if ( ! preg_match( '~^https://www\.google\.com/maps/place/\?q=place_id:[A-Za-z0-9_-]+$~', $url ) ) { WP_CLI::error( 'Expected a specific Google place URL.' ); }
	$old = (string) get_post_meta( $post->ID, '_mytaxi_gbp_url', true );
	if ( '' !== $old && $old !== $url ) { WP_CLI::error( 'Existing profile needs manual review: ' . $entry['service_slug'] ); }
	$updates[] = array( $post->ID, $url, $entry['service_slug'] );
}
foreach ( $updates as list( $post_id, $url, $slug ) ) {
	update_post_meta( $post_id, '_mytaxi_gbp_url', $url );
	if ( $url !== get_post_meta( $post_id, '_mytaxi_gbp_url', true ) ) { WP_CLI::error( 'Profile update failed: ' . $slug ); }
	WP_CLI::log( $slug . ': verified Google profile linked' );
}
WP_CLI::success( count( $updates ) . ' profiles imported. Other services are unchanged.' );
