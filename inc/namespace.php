<?php

namespace Altis\Consent;

use Altis;

function bootstrap() {
	// Register this plugin with the consent API.
	add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

	// Default Altis consent type to "opt-in".
	add_filter( 'wp_get_consent_type', function() {
		return 'optin';
	} );

	// Enqueue the javascript handler.
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );

	// Shortcode. Replace with an actual way to display a banner.
	add_shortcode( 'cookie-consent-banner', __NAMESPACE__ . '\\render_consent_banner' );
}

function enqueue_assets() {
	$js = plugin_dir_url( __DIR__ ) . 'dist/js/main.js';

	// If working locally, load the unminified version of the js file.
	if ( Altis\get_environment_type() === 'local' ) {
		$js = plugin_dir_url( __DIR__ ) . 'assets/js/main.js';
	}

	wp_enqueue_script( 'altis-consent', $js, [ 'jquery' ], '0.0.1', true );
	wp_enqueue_style( 'altis-consent', plugin_dir_url( __DIR__ ) . 'dist/css/styles.css', [], '0.0.1-' . time(), 'screen' );
}

function render_consent_banner() : string {
	ob_start();
	load_consent_banner();
	return ob_get_clean();
}
