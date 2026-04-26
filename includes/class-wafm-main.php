<?php
/**
 * Main plugin class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WAFM_Main {

	/**
	 * Initialize plugin
	 */
	public static function init() {
		// Load text domain
		load_plugin_textdomain( 'woocommerce-address-field-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Include required files
		require_once WAFM_PLUGIN_DIR . 'includes/class-wafm-checkout-fields.php';
		require_once WAFM_PLUGIN_DIR . 'includes/class-wafm-assets.php';
		require_once WAFM_PLUGIN_DIR . 'includes/class-wafm-settings.php';

		// Initialize classes
		WAFM_Checkout_Fields::init();
		WAFM_Assets::init();
		WAFM_Settings::init();
	}
}
