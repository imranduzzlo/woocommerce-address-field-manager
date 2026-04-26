<?php
/**
 * Plugin Name: WooCommerce Address Field Manager
 * Plugin URI: https://github.com/imranduzzlo/woocommerce-address-field-manager
 * Description: Dynamic address field manager for WooCommerce - Add custom locality/sub-district fields that adapt to any country
 * Version: 1.0.3
 * Author: Imran Hossain
 * Author URI: https://imranhossain.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-address-field-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.7.0
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'WAFM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WAFM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WAFM_PLUGIN_VERSION', '1.0.3' );

// ============================================================================
// LOAD GITHUB UPDATER EARLY (works even when plugin is inactive)
// ============================================================================
require_once WAFM_PLUGIN_DIR . 'includes/class-github-updater.php';

// Declare WooCommerce HPOS compatibility
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
	}
} );

// Include main plugin class
require_once WAFM_PLUGIN_DIR . 'includes/class-wafm-main.php';

// Initialize plugin
add_action( 'plugins_loaded', array( 'WAFM_Main', 'init' ) );
