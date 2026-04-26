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

		// Add plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( WAFM_PLUGIN_DIR . 'woocommerce-address-field-manager.php' ), array( __CLASS__, 'add_action_links' ) );
		
		// Handle manual update check
		add_action( 'admin_init', array( __CLASS__, 'handle_manual_update_check' ) );
	}

	/**
	 * Add plugin action links
	 */
	public static function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wafm-settings' ) ) . '">' . esc_html__( 'Settings', 'woocommerce-address-field-manager' ) . '</a>';
		$update_link = '<a href="' . esc_url( admin_url( 'plugins.php?wafm_force_update_check=1' ) ) . '" style="color: #2271b1; font-weight: 600;">' . esc_html__( 'Check Updates', 'woocommerce-address-field-manager' ) . '</a>';
		array_unshift( $links, $settings_link, $update_link );
		return $links;
	}

	/**
	 * Handle manual update check
	 */
	public static function handle_manual_update_check() {
		if ( ! isset( $_GET['wafm_force_update_check'] ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// Clear ALL update caches
		delete_transient( 'wafm_github_release' );
		delete_transient( 'wafm_github_release_v2' );
		delete_site_transient( 'update_plugins' );
		
		// Also clear the timeout transient
		delete_site_transient( 'update_plugins_last_checked' );
		
		// Clear any plugin-specific transients
		wp_cache_delete( 'plugins', 'plugins' );
		
		// Force WordPress to check for updates
		wp_clean_plugins_cache();
		wp_update_plugins();

		// Add admin notice with current status
		add_action( 'admin_notices', function() {
			// Get current version
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-address-field-manager/woocommerce-address-field-manager.php' );
			$current_version = $plugin_data['Version'];
			
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>' . esc_html__( 'WooCommerce Address Field Manager:', 'woocommerce-address-field-manager' ) . '</strong> ';
			echo esc_html__( 'Update check completed!', 'woocommerce-address-field-manager' );
			echo '<br><small>Current version: ' . esc_html( $current_version ) . '</small>';
			echo '</p></div>';
		} );

		// Redirect to remove query parameter
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
}
