<?php
/**
 * Settings page handler for WooCommerce Address Field Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WAFM_Settings {

	/**
	 * Initialize settings
	 */
	public static function init() {
		// Add settings page to WooCommerce menu
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_menu' ) );

		// Register settings
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Enqueue admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		
		// Enqueue order edit page scripts
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_order_edit_assets' ) );
	}

	/**
	 * Add settings menu to WooCommerce
	 */
	public static function add_settings_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Address Field Manager Settings', 'woocommerce-address-field-manager' ),
			__( 'Address Field Manager', 'woocommerce-address-field-manager' ),
			'manage_woocommerce',
			'wafm-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		// Billing settings
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_enabled' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_field_name' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_after_field' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_wrapper_class' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_required' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_label' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_show_label' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_placeholder_select' );
		register_setting( 'WAFM_billing_settings', 'WAFM_billing_placeholder_input' );

		// Shipping settings
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_enabled' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_field_name' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_after_field' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_wrapper_class' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_required' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_label' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_show_label' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_placeholder_select' );
		register_setting( 'WAFM_shipping_settings', 'WAFM_shipping_placeholder_input' );
		
		// Handle plugin update cache refresh
		if ( isset( $_POST['WAFM_refresh_update_cache'] ) && check_admin_referer( 'WAFM_refresh_update_cache' ) ) {
			self::refresh_plugin_update_cache();
		}
	}

	/**
	 * Enqueue admin assets
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'woocommerce_page_wafm-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wafm-admin-settings',
			WAFM_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			WAFM_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'wafm-admin-settings',
			WAFM_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			WAFM_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Enqueue order edit page assets
	 * DISABLED - Using meta box instead of dynamic JavaScript fields
	 */
	public static function enqueue_order_edit_assets( $hook ) {
		// DISABLED: The meta box handles thana fields now
		// The JavaScript was causing conflicts (duplicate fields, infinite loading)
		// Meta box provides a cleaner, more reliable solution
		return;
		
		/* OLD CODE - DISABLED
		// Only load on order edit pages
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' ), true ) ) {
			return;
		}

		global $post;
		$screen = get_current_screen();
		
		// Check if we're editing a shop_order or on the new HPOS order edit page
		$is_order_edit = false;
		if ( $post && 'shop_order' === $post->post_type ) {
			$is_order_edit = true;
		} elseif ( $screen && 'woocommerce_page_wc-orders' === $screen->id ) {
			$is_order_edit = true;
		}

		if ( ! $is_order_edit ) {
			return;
		}

		// Get thana data
		$json_file = WAFM_PLUGIN_DIR . 'data/thana.json';
		$thana_data = array();
		if ( file_exists( $json_file ) ) {
			$json_content = file_get_contents( $json_file );
			$thana_data = json_decode( $json_content, true );
		}

		// Get settings
		$billing_settings = self::get_billing_settings();
		$shipping_settings = self::get_shipping_settings();

		// Get current order data
		$order_id = 0;
		if ( $post ) {
			$order_id = $post->ID;
		} elseif ( isset( $_GET['id'] ) ) {
			$order_id = absint( $_GET['id'] );
		}

		$billing_state = '';
		$billing_thana = '';
		$billing_country = '';
		$shipping_state = '';
		$shipping_thana = '';
		$shipping_country = '';

		if ( $order_id ) {
			$billing_country = get_post_meta( $order_id, '_billing_country', true );
			$billing_state = get_post_meta( $order_id, '_billing_state', true );
			$billing_thana = get_post_meta( $order_id, '_' . $billing_settings['field_name'], true );
			$shipping_country = get_post_meta( $order_id, '_shipping_country', true );
			$shipping_state = get_post_meta( $order_id, '_shipping_state', true );
			$shipping_thana = get_post_meta( $order_id, '_' . $shipping_settings['field_name'], true );
		}

		// Enqueue script
		wp_enqueue_script(
			'wafm-order-edit',
			WAFM_PLUGIN_URL . 'assets/js/order-edit.js',
			array( 'jquery' ),
			WAFM_PLUGIN_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'wafm-order-edit',
			'wafmOrderEditData',
			array(
				'thanaData' => $thana_data,
				'billingSettings' => $billing_settings,
				'shippingSettings' => $shipping_settings,
				'billingCountry' => $billing_country,
				'billingState' => $billing_state,
				'billingThana' => $billing_thana,
				'shippingCountry' => $shipping_country,
				'shippingState' => $shipping_state,
				'shippingThana' => $shipping_thana,
			)
		);
		*/
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		// Get billing settings
		$billing_enabled = get_option( 'WAFM_billing_enabled', 1 );
		$billing_field_name = get_option( 'WAFM_billing_field_name', 'billing_thana' );
		$billing_after_field = get_option( 'WAFM_billing_after_field', 'billing_state' );
		$billing_wrapper_class = get_option( 'WAFM_billing_wrapper_class', 'form-row-wide wafm-thana-field' );
		$billing_required = get_option( 'WAFM_billing_required', 0 );
		$billing_label = get_option( 'WAFM_billing_label', 'Thana' );
		$billing_show_label = get_option( 'WAFM_billing_show_label', 1 );
		$billing_placeholder_select = get_option( 'WAFM_billing_placeholder_select', 'Select Thana' );
		$billing_placeholder_input = get_option( 'WAFM_billing_placeholder_input', 'Enter Thana' );

		// Get shipping settings
		$shipping_enabled = get_option( 'WAFM_shipping_enabled', 1 );
		$shipping_field_name = get_option( 'WAFM_shipping_field_name', 'shipping_thana' );
		$shipping_after_field = get_option( 'WAFM_shipping_after_field', 'shipping_state' );
		$shipping_wrapper_class = get_option( 'WAFM_shipping_wrapper_class', 'form-row-wide wafm-thana-field' );
		$shipping_required = get_option( 'WAFM_shipping_required', 0 );
		$shipping_label = get_option( 'WAFM_shipping_label', 'Thana' );
		$shipping_show_label = get_option( 'WAFM_shipping_show_label', 1 );
		$shipping_placeholder_select = get_option( 'WAFM_shipping_placeholder_select', 'Select Thana' );
		$shipping_placeholder_input = get_option( 'WAFM_shipping_placeholder_input', 'Enter Thana' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooCommerce Address Field Manager Settings', 'woocommerce-address-field-manager' ); ?></h1>

			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="#billing" class="nav-tab nav-tab-active" data-tab="billing">
					<?php esc_html_e( 'Billing Address', 'woocommerce-address-field-manager' ); ?>
				</a>
				<a href="#shipping" class="nav-tab" data-tab="shipping">
					<?php esc_html_e( 'Shipping Address', 'woocommerce-address-field-manager' ); ?>
				</a>
				<a href="#updater" class="nav-tab" data-tab="updater">
					<?php esc_html_e( 'Plugin Updater', 'woocommerce-address-field-manager' ); ?>
				</a>
			</nav>

			<!-- Billing Settings Tab -->
			<div id="billing" class="tab-content active">
				<form method="post" action="options.php">
					<?php settings_fields( 'WAFM_billing_settings' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="WAFM_billing_enabled">
									<?php esc_html_e( 'Enable Billing Thana Field', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" id="WAFM_billing_enabled" name="WAFM_billing_enabled" value="1" <?php checked( $billing_enabled, 1 ); ?> />
								<p class="description">
									<?php esc_html_e( 'Check to enable thana field for billing address. If disabled, the field will not be rendered.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_field_name">
									<?php esc_html_e( 'Field Name', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_billing_field_name" name="WAFM_billing_field_name" value="<?php echo esc_attr( $billing_field_name ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'The field name used in the form. Default: billing_thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_after_field">
									<?php esc_html_e( 'Display After Field', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_billing_after_field" name="WAFM_billing_after_field" value="<?php echo esc_attr( $billing_after_field ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'The field name after which thana field should appear. Default: billing_state', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_wrapper_class">
									<?php esc_html_e( 'Wrapper CSS Class', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_billing_wrapper_class" name="WAFM_billing_wrapper_class" value="<?php echo esc_attr( $billing_wrapper_class ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'CSS classes to apply to the field wrapper. Default: form-row-wide wafm-thana-field', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_label">
									<?php esc_html_e( 'Field Label', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_billing_label" name="WAFM_billing_label" value="<?php echo esc_attr( $billing_label ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'The label text displayed for the field. Default: Thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_show_label">
									<?php esc_html_e( 'Show Label', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" id="WAFM_billing_show_label" name="WAFM_billing_show_label" value="1" <?php checked( $billing_show_label, 1 ); ?> />
								<p class="description">
									<?php esc_html_e( 'Check to display the label. Uncheck to hide the label.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_placeholder_select">
									<?php esc_html_e( 'Placeholder (Select)', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_billing_placeholder_select" name="WAFM_billing_placeholder_select" value="<?php echo esc_attr( $billing_placeholder_select ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Placeholder text for select dropdown. Default: Select Thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_placeholder_input">
									<?php esc_html_e( 'Placeholder (Input)', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_billing_placeholder_input" name="WAFM_billing_placeholder_input" value="<?php echo esc_attr( $billing_placeholder_input ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Placeholder text for text input. Default: Enter Thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_billing_required">
									<?php esc_html_e( 'Make Field Required', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" id="WAFM_billing_required" name="WAFM_billing_required" value="1" <?php checked( $billing_required, 1 ); ?> />
								<p class="description">
									<?php esc_html_e( 'Check to make this field required during checkout.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php submit_button(); ?>
				</form>
			</div>

			<!-- Shipping Settings Tab -->
			<div id="shipping" class="tab-content">
				<form method="post" action="options.php">
					<?php settings_fields( 'WAFM_shipping_settings' ); ?>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="WAFM_shipping_enabled">
									<?php esc_html_e( 'Enable Shipping Thana Field', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" id="WAFM_shipping_enabled" name="WAFM_shipping_enabled" value="1" <?php checked( $shipping_enabled, 1 ); ?> />
								<p class="description">
									<?php esc_html_e( 'Check to enable thana field for shipping address. If disabled, the field will not be rendered.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_field_name">
									<?php esc_html_e( 'Field Name', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_shipping_field_name" name="WAFM_shipping_field_name" value="<?php echo esc_attr( $shipping_field_name ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'The field name used in the form. Default: shipping_thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_after_field">
									<?php esc_html_e( 'Display After Field', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_shipping_after_field" name="WAFM_shipping_after_field" value="<?php echo esc_attr( $shipping_after_field ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'The field name after which thana field should appear. Default: shipping_state', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_wrapper_class">
									<?php esc_html_e( 'Wrapper CSS Class', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_shipping_wrapper_class" name="WAFM_shipping_wrapper_class" value="<?php echo esc_attr( $shipping_wrapper_class ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'CSS classes to apply to the field wrapper. Default: form-row-wide wafm-thana-field', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_label">
									<?php esc_html_e( 'Field Label', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_shipping_label" name="WAFM_shipping_label" value="<?php echo esc_attr( $shipping_label ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'The label text displayed for the field. Default: Thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_show_label">
									<?php esc_html_e( 'Show Label', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" id="WAFM_shipping_show_label" name="WAFM_shipping_show_label" value="1" <?php checked( $shipping_show_label, 1 ); ?> />
								<p class="description">
									<?php esc_html_e( 'Check to display the label. Uncheck to hide the label.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_placeholder_select">
									<?php esc_html_e( 'Placeholder (Select)', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_shipping_placeholder_select" name="WAFM_shipping_placeholder_select" value="<?php echo esc_attr( $shipping_placeholder_select ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Placeholder text for select dropdown. Default: Select Thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_placeholder_input">
									<?php esc_html_e( 'Placeholder (Input)', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="WAFM_shipping_placeholder_input" name="WAFM_shipping_placeholder_input" value="<?php echo esc_attr( $shipping_placeholder_input ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Placeholder text for text input. Default: Enter Thana', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="WAFM_shipping_required">
									<?php esc_html_e( 'Make Field Required', 'woocommerce-address-field-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" id="WAFM_shipping_required" name="WAFM_shipping_required" value="1" <?php checked( $shipping_required, 1 ); ?> />
								<p class="description">
									<?php esc_html_e( 'Check to make this field required during checkout.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php submit_button(); ?>
				</form>
			</div>

			<!-- Plugin Updater Tab -->
			<div id="updater" class="tab-content">
				<h2><?php esc_html_e( 'Plugin Update Cache', 'woocommerce-address-field-manager' ); ?></h2>
				<p><?php esc_html_e( 'Use this tool to refresh the WordPress plugin update cache. This will force WordPress to check for new plugin updates immediately.', 'woocommerce-address-field-manager' ); ?></p>
				
				<?php
				// Display success message if cache was just refreshed
				if ( isset( $_GET['cache_refreshed'] ) && $_GET['cache_refreshed'] === '1' ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<strong><?php esc_html_e( 'Success!', 'woocommerce-address-field-manager' ); ?></strong>
							<?php esc_html_e( 'Plugin update cache has been refreshed. WordPress will now check for new updates.', 'woocommerce-address-field-manager' ); ?>
						</p>
					</div>
					<?php
				}
				?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Current Plugin Version', 'woocommerce-address-field-manager' ); ?>
						</th>
						<td>
							<strong><?php echo esc_html( WAFM_PLUGIN_VERSION ); ?></strong>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Last Update Check', 'woocommerce-address-field-manager' ); ?>
						</th>
						<td>
							<?php
							$update_plugins = get_site_transient( 'update_plugins' );
							if ( $update_plugins && isset( $update_plugins->last_checked ) ) {
								echo esc_html( human_time_diff( $update_plugins->last_checked, time() ) . ' ago' );
							} else {
								esc_html_e( 'Never', 'woocommerce-address-field-manager' );
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Refresh Update Cache', 'woocommerce-address-field-manager' ); ?>
						</th>
						<td>
							<form method="post" action="">
								<?php wp_nonce_field( 'WAFM_refresh_update_cache' ); ?>
								<input type="hidden" name="WAFM_refresh_update_cache" value="1" />
								<button type="submit" class="button button-primary">
									<span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
									<?php esc_html_e( 'Refresh Update Cache Now', 'woocommerce-address-field-manager' ); ?>
								</button>
								<p class="description">
									<?php esc_html_e( 'Click this button to clear the plugin update cache and force WordPress to check for new updates immediately.', 'woocommerce-address-field-manager' ); ?>
								</p>
							</form>
						</td>
					</tr>
				</table>

				<div class="wafm-updater-info" style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'How It Works', 'woocommerce-address-field-manager' ); ?></h3>
					<ul style="margin-left: 20px;">
						<li><?php esc_html_e( 'WordPress checks for plugin updates every 12 hours by default', 'woocommerce-address-field-manager' ); ?></li>
						<li><?php esc_html_e( 'This tool clears the update cache and forces an immediate check', 'woocommerce-address-field-manager' ); ?></li>
						<li><?php esc_html_e( 'Useful when you know a new version is available but WordPress hasn\'t detected it yet', 'woocommerce-address-field-manager' ); ?></li>
						<li><?php esc_html_e( 'After refreshing, go to Plugins → Installed Plugins to see available updates', 'woocommerce-address-field-manager' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get billing settings
	 */
	public static function get_billing_settings() {
		return array(
			'enabled'                => get_option( 'WAFM_billing_enabled', 1 ),
			'field_name'             => get_option( 'WAFM_billing_field_name', 'billing_thana' ),
			'after_field'            => get_option( 'WAFM_billing_after_field', 'billing_state' ),
			'wrapper_class'          => get_option( 'WAFM_billing_wrapper_class', 'form-row-wide wafm-thana-field' ),
			'required'               => get_option( 'WAFM_billing_required', 0 ),
			'label'                  => get_option( 'WAFM_billing_label', 'Thana' ),
			'show_label'             => get_option( 'WAFM_billing_show_label', 1 ),
			'placeholder_select'     => get_option( 'WAFM_billing_placeholder_select', 'Select Thana' ),
			'placeholder_input'      => get_option( 'WAFM_billing_placeholder_input', 'Enter Thana' ),
		);
	}

	/**
	 * Get shipping settings
	 */
	public static function get_shipping_settings() {
		return array(
			'enabled'                => get_option( 'WAFM_shipping_enabled', 1 ),
			'field_name'             => get_option( 'WAFM_shipping_field_name', 'shipping_thana' ),
			'after_field'            => get_option( 'WAFM_shipping_after_field', 'shipping_state' ),
			'wrapper_class'          => get_option( 'WAFM_shipping_wrapper_class', 'form-row-wide wafm-thana-field' ),
			'required'               => get_option( 'WAFM_shipping_required', 0 ),
			'label'                  => get_option( 'WAFM_shipping_label', 'Thana' ),
			'show_label'             => get_option( 'WAFM_shipping_show_label', 1 ),
			'placeholder_select'     => get_option( 'WAFM_shipping_placeholder_select', 'Select Thana' ),
			'placeholder_input'      => get_option( 'WAFM_shipping_placeholder_input', 'Enter Thana' ),
		);
	}

	/**
	 * Refresh plugin update cache
	 */
	public static function refresh_plugin_update_cache() {
		// Delete the update_plugins transient to force a fresh check
		delete_site_transient( 'update_plugins' );
		
		// Force WordPress to check for updates
		wp_update_plugins();
		
		// Redirect with success message and preserve tab
		wp_safe_redirect( add_query_arg( array(
			'page' => 'wafm-settings',
			'cache_refreshed' => '1',
		), admin_url( 'admin.php' ) ) . '#updater' );
		exit;
	}
}
