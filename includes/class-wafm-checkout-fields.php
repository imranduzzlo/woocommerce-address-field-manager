<?php
/**
 * Checkout fields handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WAFM_Checkout_Fields {

	/**
	 * Supported countries with thana data
	 */
	private static $supported_countries = array( 'BD' );

	/**
	 * Initialize
	 */
	public static function init() {
		// Add thana fields to checkout
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_thana_fields' ) );

		// Save thana fields
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_thana_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_thana_fields' ) );

		// Make thana editable in admin order page - DISABLED (using meta box instead)
		// add_filter( 'woocommerce_admin_billing_fields', array( __CLASS__, 'add_editable_billing_thana_to_order_admin' ) );
		// add_filter( 'woocommerce_admin_shipping_fields', array( __CLASS__, 'add_editable_shipping_thana_to_order_admin' ) );
		
		// HPOS compatibility - Add custom meta boxes for thana fields
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_thana_meta_boxes' ), 30 );
		
		// Load thana field values in admin order page
		add_filter( 'woocommerce_found_customer_details', array( __CLASS__, 'load_thana_values_in_order_admin' ), 10, 3 );
		
		// Save thana when order is updated from admin
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_thana_from_order_admin' ), 10, 2 );
		
		// HPOS save action
		add_action( 'woocommerce_update_order', array( __CLASS__, 'save_thana_from_hpos_order' ), 10, 1 );

		// Clear order cache when thana meta is updated (webhooks, external updates, etc.)
		add_action( 'updated_post_meta', array( __CLASS__, 'clear_order_cache_on_meta_update' ), 10, 4 );
		add_action( 'added_post_meta', array( __CLASS__, 'clear_order_cache_on_meta_update' ), 10, 4 );
		add_action( 'woocommerce_update_order_meta', array( __CLASS__, 'clear_order_cache_on_order_meta_update' ), 10, 3 );

		// Register thana as WooCommerce address field
		add_filter( 'woocommerce_get_customer_address_fields', array( __CLASS__, 'register_thana_address_field' ) );

		// Add thana to address format for thank you page and emails
		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'add_thana_to_address_format' ) );

		// Add thana values to formatted address arrays
		add_filter( 'woocommerce_order_formatted_billing_address', array( __CLASS__, 'add_thana_to_formatted_address' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'add_thana_to_formatted_address' ), 10, 2 );

		// Add thana to customer account page
		add_filter( 'woocommerce_customer_meta_fields', array( __CLASS__, 'add_customer_thana_fields' ) );

		// Save thana to user meta
		add_action( 'personal_options_update', array( __CLASS__, 'save_customer_thana' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_customer_thana' ) );

		// Prefill thana from user meta and session
		add_filter( 'woocommerce_checkout_get_value', array( __CLASS__, 'prefill_thana_from_user' ), 10, 2 );

		// Save thana to session for same-session prefilling
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'save_thana_to_session' ) );

		// Add country detection script
		add_action( 'wp_footer', array( __CLASS__, 'add_country_detection_script' ) );

		// Add thana fields to user edit page
		add_action( 'show_user_profile', array( __CLASS__, 'show_thana_fields_on_user_page' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_thana_fields_on_user_page' ) );

		// Clear session when user updates profile
		add_action( 'personal_options_update', array( __CLASS__, 'clear_thana_session' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'clear_thana_session' ) );

		// Display thana as custom field in thank you page and emails
		add_filter( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_thana_custom_field' ) );
	}

	/**
	 * Add thana fields to checkout right after state field
	 */
	public static function add_thana_fields( $fields ) {
		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Add billing thana if enabled
		if ( $billing_settings['enabled'] ) {
			$thana_field = array(
				'type'        => 'select',
				'label'       => $billing_settings['show_label'] ? $billing_settings['label'] : '',
				'placeholder' => $billing_settings['placeholder_select'],
				'required'    => (bool) $billing_settings['required'],
				'class'       => array_filter( explode( ' ', $billing_settings['wrapper_class'] ) ),
				'clear'       => true,
				'options'     => array( '' => $billing_settings['placeholder_select'] ),
			);

			// Insert billing thana right after the configured field
			$billing_fields = array();
			foreach ( $fields['billing'] as $key => $field ) {
				$billing_fields[ $key ] = $field;
				if ( $key === $billing_settings['after_field'] ) {
					$billing_fields[ $billing_settings['field_name'] ] = $thana_field;
				}
			}
			$fields['billing'] = $billing_fields;
		}

		// Add shipping thana if enabled
		if ( $shipping_settings['enabled'] ) {
			$thana_field = array(
				'type'        => 'select',
				'label'       => $shipping_settings['show_label'] ? $shipping_settings['label'] : '',
				'placeholder' => $shipping_settings['placeholder_select'],
				'required'    => (bool) $shipping_settings['required'],
				'class'       => array_filter( explode( ' ', $shipping_settings['wrapper_class'] ) ),
				'clear'       => true,
				'options'     => array( '' => $shipping_settings['placeholder_select'] ),
			);

			// Insert shipping thana right after the configured field
			$shipping_fields = array();
			foreach ( $fields['shipping'] as $key => $field ) {
				$shipping_fields[ $key ] = $field;
				if ( $key === $shipping_settings['after_field'] ) {
					$shipping_fields[ $shipping_settings['field_name'] ] = $thana_field;
				}
			}
			$fields['shipping'] = $shipping_fields;
		}

		return $fields;
	}

	/**
	 * Register thana as WooCommerce address field
	 */
	public static function register_thana_address_field( $fields ) {
		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Register billing thana if enabled
		if ( $billing_settings['enabled'] ) {
			$fields[ $billing_settings['field_name'] ] = array(
				'label'       => $billing_settings['show_label'] ? $billing_settings['label'] : '',
				'placeholder' => $billing_settings['placeholder_select'],
				'required'    => (bool) $billing_settings['required'],
				'class'       => array_filter( explode( ' ', $billing_settings['wrapper_class'] ) ),
			);
		}

		// Register shipping thana if enabled
		if ( $shipping_settings['enabled'] ) {
			$fields[ $shipping_settings['field_name'] ] = array(
				'label'       => $shipping_settings['show_label'] ? $shipping_settings['label'] : '',
				'placeholder' => $shipping_settings['placeholder_select'],
				'required'    => (bool) $shipping_settings['required'],
				'class'       => array_filter( explode( ' ', $shipping_settings['wrapper_class'] ) ),
			);
		}

		return $fields;
	}

	/**
	 * Get customer country following WooCommerce priority:
	 * 1. Saved customer address (logged in user)
	 * 2. Form field (billing_country)
	 * 3. Geolocation (WooCommerce GeoIP)
	 * 4. Store default country
	 */
	public static function get_customer_country() {
		// Priority 1: Check if user is logged in and has saved address
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$saved_country = get_user_meta( $user_id, 'billing_country', true );
			if ( $saved_country ) {
				return $saved_country;
			}
		}

		// Priority 2: Check form POST data
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( wp_unslash( $_POST['post_data'] ), $post_data );
			if ( ! empty( $post_data['billing_country'] ) ) {
				return wc_clean( $post_data['billing_country'] );
			}
		}

		// Priority 3: Check direct POST (AJAX)
		if ( isset( $_POST['billing_country'] ) ) {
			return wc_clean( wp_unslash( $_POST['billing_country'] ) );
		}

		// Priority 4: Try WooCommerce geolocation
		if ( function_exists( 'WC_Geolocation' ) ) {
			$geo = new WC_Geolocation();
			$geolocation = $geo->geolocate_ip();
			if ( ! empty( $geolocation['country'] ) ) {
				return $geolocation['country'];
			}
		}

		// Priority 5: Get store's default country
		$store_country = WC()->countries->get_base_country();
		
		if ( ! $store_country ) {
			$store_country = get_option( 'woocommerce_default_country' );
			if ( strpos( $store_country, ':' ) !== false ) {
				$store_country = explode( ':', $store_country )[0];
			}
		}

		return $store_country;
	}

	/**
	 * Validate thana fields
	 */
	public static function validate_thana_fields() {
		// Get customer country using WooCommerce priority
		$billing_country = self::get_customer_country();
		$billing_state   = isset( $_POST['billing_state'] ) ? wc_clean( wp_unslash( $_POST['billing_state'] ) ) : '';

		// Check if thana is required for this country
		if ( in_array( $billing_country, self::$supported_countries, true ) && empty( $billing_state ) ) {
			wc_add_notice( __( 'Please select a thana.', 'woocommerce-address-field-manager' ), 'error' );
		}
	}

	/**
	 * Save thana fields
	 */
	public static function save_thana_fields( $order_id ) {
		$user_id = get_post_meta( $order_id, '_customer_user', true );

		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Save billing thana to order and user meta
		if ( $billing_settings['enabled'] && isset( $_POST[ $billing_settings['field_name'] ] ) ) {
			$billing_thana = sanitize_text_field( wp_unslash( $_POST[ $billing_settings['field_name'] ] ) );
			update_post_meta( $order_id, '_' . $billing_settings['field_name'], $billing_thana );
			
			// Also save to user meta if logged in
			if ( $user_id ) {
				update_user_meta( $user_id, $billing_settings['field_name'], $billing_thana );
			}
		}

		// Save shipping thana to order and user meta
		if ( $shipping_settings['enabled'] && isset( $_POST[ $shipping_settings['field_name'] ] ) ) {
			$shipping_thana = sanitize_text_field( wp_unslash( $_POST[ $shipping_settings['field_name'] ] ) );
			update_post_meta( $order_id, '_' . $shipping_settings['field_name'], $shipping_thana );
			
			// Also save to user meta if logged in
			if ( $user_id ) {
				update_user_meta( $user_id, $shipping_settings['field_name'], $shipping_thana );
			}
		}
	}

	/**
	 * Format address with thana labels for emails and thank you page
	 */
	public static function format_address_with_labels( $formatted_address, $order ) {
		// Ensure we have a valid order object
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return $formatted_address;
		}

		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Check if this is billing or shipping
		$is_billing = current_filter() === 'woocommerce_order_get_formatted_billing_address';
		$settings = $is_billing ? $billing_settings : $shipping_settings;

		if ( ! $settings['enabled'] ) {
			return $formatted_address;
		}

		// Ensure formatted_address is a string
		if ( ! is_string( $formatted_address ) ) {
			return $formatted_address;
		}

		$meta_key = '_' . $settings['field_name'];
		$thana_code = get_post_meta( $order->get_id(), $meta_key, true );

		if ( $thana_code ) {
			// Convert code to name for display
			$thana_name = self::get_thana_name_from_code( $thana_code );
			$display_value = $thana_name ? $thana_name : $thana_code;
			
			// Add thana with label to formatted address
			$label = $settings['show_label'] ? $settings['label'] : __( 'Thana', 'woocommerce-address-field-manager' );
			$formatted_address .= "\n" . $label . ': ' . $display_value;
		}

		return $formatted_address;
	}

	/**
	 * Add thana to formatted address for emails and thank you page
	 */
	public static function add_thana_to_formatted_address( $address, $order ) {
		// Ensure we have a valid order object
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return $address;
		}

		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Check if this is billing or shipping
		$is_billing = current_filter() === 'woocommerce_order_formatted_billing_address';
		$settings = $is_billing ? $billing_settings : $shipping_settings;

		if ( ! $settings['enabled'] ) {
			return $address;
		}

		// Ensure address is an array
		if ( ! is_array( $address ) ) {
			return $address;
		}

		// Get thana code from order meta - simple and direct like the old version
		$meta_key = '_' . $settings['field_name'];
		$thana_code = $order->get_meta( $meta_key );

		// Debug: Log what we're getting
		error_log( 'WAFM Debug - Filter: ' . current_filter() );
		error_log( 'WAFM Debug - Field name: ' . $settings['field_name'] );
		error_log( 'WAFM Debug - Meta key: ' . $meta_key );
		error_log( 'WAFM Debug - Thana code: ' . $thana_code );
		error_log( 'WAFM Debug - Address array keys: ' . implode( ', ', array_keys( $address ) ) );

		if ( ! $thana_code ) {
			return $address;
		}

		// Convert code to name for display
		$thana_name = self::get_thana_name_from_code( $thana_code );
		$display_value = $thana_name ? $thana_name : $thana_code;
		
		// Add thana to address array with the field name as key
		$address[ $settings['field_name'] ] = $display_value;
		
		error_log( 'WAFM Debug - Added to address: ' . $settings['field_name'] . ' = ' . $display_value );

		return $address;
	}

	/**
	 * Display thana in order - REMOVED (now handled by editable fields)
	 * This method is kept for backward compatibility but does nothing
	 * Thana is now displayed as an editable field in the order edit page
	 */
	public static function display_thana_in_order( $order ) {
		// Thana fields are now editable in the order edit page
		// See add_editable_thana_to_order_admin method
		return;
	}

	/**
	 * Add thana to customer address fields
	 */
	public static function add_customer_thana_fields( $fields ) {
		// Note: Thana fields are rendered manually in show_thana_fields_on_user_page
		// We don't add them here to avoid duplicate rendering and form submission issues
		return $fields;
	}

	/**
	 * Save customer thana to user meta
	 */
	public static function save_customer_thana( $user_id ) {
		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Save billing thana
		if ( $billing_settings['enabled'] && isset( $_POST[ $billing_settings['field_name'] ] ) ) {
			update_user_meta( $user_id, $billing_settings['field_name'], sanitize_text_field( wp_unslash( $_POST[ $billing_settings['field_name'] ] ) ) );
		}

		// Save shipping thana
		if ( $shipping_settings['enabled'] && isset( $_POST[ $shipping_settings['field_name'] ] ) ) {
			update_user_meta( $user_id, $shipping_settings['field_name'], sanitize_text_field( wp_unslash( $_POST[ $shipping_settings['field_name'] ] ) ) );
		}
	}

	/**
	 * Clear thana session when user updates profile
	 */
	public static function clear_thana_session( $user_id ) {
		if ( WC()->session ) {
			$billing_settings = WAFM_Settings::get_billing_settings();
			$shipping_settings = WAFM_Settings::get_shipping_settings();
			
			if ( $billing_settings['enabled'] ) {
				WC()->session->__unset( $billing_settings['field_name'] );
			}
			if ( $shipping_settings['enabled'] ) {
				WC()->session->__unset( $shipping_settings['field_name'] );
			}
		}
	}

	/**
	 * Prefill thana from user meta and session on checkout
	 */
	public static function prefill_thana_from_user( $value, $key ) {
		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Check if this is a thana field
		$is_billing_thana = $billing_settings['enabled'] && $key === $billing_settings['field_name'];
		$is_shipping_thana = $shipping_settings['enabled'] && $key === $shipping_settings['field_name'];

		if ( ! $is_billing_thana && ! $is_shipping_thana ) {
			return $value;
		}

		$thana_code = '';
		$user_id = get_current_user_id();

		// Priority 1: Check user meta first (logged-in user) - takes precedence
		if ( $user_id ) {
			$saved_thana = get_user_meta( $user_id, $key, true );
			if ( $saved_thana ) {
				$thana_code = $saved_thana;
			}
		}

		// Priority 2: Check WooCommerce session data (same session, for non-logged-in or if no user meta)
		if ( ! $thana_code && WC()->session ) {
			$session_value = WC()->session->get( $key );
			if ( $session_value ) {
				$thana_code = $session_value;
			}
		}

		// Return the code (WooCommerce will use this as the value)
		return $thana_code ? $thana_code : $value;
	}

	/**
	 * Save thana to WooCommerce session for same-session prefilling
	 */
	public static function save_thana_to_session() {
		if ( ! WC()->session ) {
			return;
		}

		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Get posted data
		$post_data = array();
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( wp_unslash( $_POST['post_data'] ), $post_data );
		}

		// Save billing thana to session
		if ( $billing_settings['enabled'] && isset( $post_data[ $billing_settings['field_name'] ] ) ) {
			WC()->session->set( $billing_settings['field_name'], sanitize_text_field( $post_data[ $billing_settings['field_name'] ] ) );
		}

		// Save shipping thana to session
		if ( $shipping_settings['enabled'] && isset( $post_data[ $shipping_settings['field_name'] ] ) ) {
			WC()->session->set( $shipping_settings['field_name'], sanitize_text_field( $post_data[ $shipping_settings['field_name'] ] ) );
		}
	}

	/**
	 * Display thana fields on user edit page
	 */
	public static function show_thana_fields_on_user_page( $user ) {
		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// If both disabled, don't show section
		if ( ! $billing_settings['enabled'] && ! $shipping_settings['enabled'] ) {
			return;
		}

		// Get thana data
		$thana_data = self::get_thana_data();
		
		// Get saved thana values (codes)
		$billing_thana_code = $billing_settings['enabled'] ? get_user_meta( $user->ID, $billing_settings['field_name'], true ) : '';
		$shipping_thana_code = $shipping_settings['enabled'] ? get_user_meta( $user->ID, $shipping_settings['field_name'], true ) : '';
		
		// Get saved state values to determine field type
		$billing_state = get_user_meta( $user->ID, 'billing_state', true );
		$shipping_state = get_user_meta( $user->ID, 'shipping_state', true );
		
		// Check if states are BD
		$is_billing_bd = $billing_state && strpos( $billing_state, 'BD-' ) === 0;
		$is_shipping_bd = $shipping_state && strpos( $shipping_state, 'BD-' ) === 0;
		?>
		<script type="text/javascript">
		jQuery(function($) {
			const thanaData = <?php echo wp_json_encode( $thana_data ); ?>;
			const billingSettings = <?php echo wp_json_encode( $billing_settings ); ?>;
			const shippingSettings = <?php echo wp_json_encode( $shipping_settings ); ?>;
			
			// Create and insert thana rows directly into form table
			function insertThanaFields() {
				try {
					// Insert billing thana after billing_state
					if (billingSettings.enabled) {
						const billingStateInput = document.getElementById('billing_state');
						if (billingStateInput) {
							const billingStateRow = billingStateInput.closest('tr');
							if (billingStateRow && billingStateRow.parentNode) {
								// Check if thana row already exists
								if (!document.getElementById('wafm-billing-thana-row')) {
									const billingThanaRow = document.createElement('tr');
									billingThanaRow.id = 'wafm-billing-thana-row';
									
									const th = document.createElement('th');
									const label = document.createElement('label');
									label.htmlFor = billingSettings.field_name;
									label.textContent = billingSettings.show_label ? billingSettings.label : 'Thana';
									th.appendChild(label);
									
									const td = document.createElement('td');
									
									// Create select or input based on state
									let fieldHtml = '';
									if ('<?php echo esc_js( $is_billing_bd ? 'yes' : 'no' ); ?>' === 'yes' && thanaData['<?php echo esc_js( $billing_state ); ?>']) {
										// Create select
										fieldHtml = '<select name="' + billingSettings.field_name + '" id="' + billingSettings.field_name + '" class="regular-text wafm-user-thana-select" data-thana-type="billing" style="width: 25em;"><option value="">Select Thana</option></select>';
									} else {
										// Create input
										fieldHtml = '<input type="text" name="' + billingSettings.field_name + '" id="' + billingSettings.field_name + '" value="<?php echo esc_js( $billing_thana_code ); ?>" class="regular-text" style="width: 25em;" />';
									}
									
									td.innerHTML = fieldHtml + '<p class="description">Thana will appear as select for Bangladesh states, or as text input for other countries.</p>';
									
									billingThanaRow.appendChild(th);
									billingThanaRow.appendChild(td);
									
									// Insert after billing_state row
									if (billingStateRow.nextSibling) {
										billingStateRow.parentNode.insertBefore(billingThanaRow, billingStateRow.nextSibling);
									} else {
										billingStateRow.parentNode.appendChild(billingThanaRow);
									}
									
									// Populate select if it's a select field
									const billingThanaField = document.getElementById(billingSettings.field_name);
									if (billingThanaField && billingThanaField.tagName === 'SELECT') {
										const stateValue = '<?php echo esc_js( $billing_state ); ?>';
										if (stateValue && thanaData[stateValue]) {
											for (const code in thanaData[stateValue]) {
												const option = document.createElement('option');
												option.value = code;
												option.textContent = thanaData[stateValue][code];
												if (code === '<?php echo esc_js( $billing_thana_code ); ?>') {
													option.selected = true;
												}
												billingThanaField.appendChild(option);
											}
										}
									}
								}
							}
						}
					}
					
					// Insert shipping thana after shipping_state
					if (shippingSettings.enabled) {
						const shippingStateInput = document.getElementById('shipping_state');
						if (shippingStateInput) {
							const shippingStateRow = shippingStateInput.closest('tr');
							if (shippingStateRow && shippingStateRow.parentNode) {
								// Check if thana row already exists
								if (!document.getElementById('wafm-shipping-thana-row')) {
									const shippingThanaRow = document.createElement('tr');
									shippingThanaRow.id = 'wafm-shipping-thana-row';
									
									const th = document.createElement('th');
									const label = document.createElement('label');
									label.htmlFor = shippingSettings.field_name;
									label.textContent = shippingSettings.show_label ? shippingSettings.label : 'Thana';
									th.appendChild(label);
									
									const td = document.createElement('td');
									
									// Create select or input based on state
									let fieldHtml = '';
									if ('<?php echo esc_js( $is_shipping_bd ? 'yes' : 'no' ); ?>' === 'yes' && thanaData['<?php echo esc_js( $shipping_state ); ?>']) {
										// Create select
										fieldHtml = '<select name="' + shippingSettings.field_name + '" id="' + shippingSettings.field_name + '" class="regular-text wafm-user-thana-select" data-thana-type="shipping" style="width: 25em;"><option value="">Select Thana</option></select>';
									} else {
										// Create input
										fieldHtml = '<input type="text" name="' + shippingSettings.field_name + '" id="' + shippingSettings.field_name + '" value="<?php echo esc_js( $shipping_thana_code ); ?>" class="regular-text" style="width: 25em;" />';
									}
									
									td.innerHTML = fieldHtml + '<p class="description">Thana will appear as select for Bangladesh states, or as text input for other countries.</p>';
									
									shippingThanaRow.appendChild(th);
									shippingThanaRow.appendChild(td);
									
									// Insert after shipping_state row
									if (shippingStateRow.nextSibling) {
										shippingStateRow.parentNode.insertBefore(shippingThanaRow, shippingStateRow.nextSibling);
									} else {
										shippingStateRow.parentNode.appendChild(shippingThanaRow);
									}
									
									// Populate select if it's a select field
									const shippingThanaField = document.getElementById(shippingSettings.field_name);
									if (shippingThanaField && shippingThanaField.tagName === 'SELECT') {
										const stateValue = '<?php echo esc_js( $shipping_state ); ?>';
										if (stateValue && thanaData[stateValue]) {
											for (const code in thanaData[stateValue]) {
												const option = document.createElement('option');
												option.value = code;
												option.textContent = thanaData[stateValue][code];
												if (code === '<?php echo esc_js( $shipping_thana_code ); ?>') {
													option.selected = true;
												}
												shippingThanaField.appendChild(option);
											}
										}
									}
								}
							}
						}
					}
				} catch(e) {
					console.log('Error inserting thana fields:', e);
				}
			}
			
			// Insert thana fields on page load
			insertThanaFields();
			
			// Update thana field when state changes
			$(document).on('change', '#billing_state, #shipping_state', function() {
				const stateField = $(this);
				const stateValue = stateField.val();
				const fieldType = stateField.attr('id').replace('_state', '');
				const settings = fieldType === 'billing' ? billingSettings : shippingSettings;
				const thanaField = $('#' + settings.field_name);
				
				if (!thanaField.length) return;
				
				const isBD = stateValue && stateValue.toString().startsWith('BD-');
				const currentValue = thanaField.val();
				
				if (isBD && thanaData[stateValue]) {
					// Convert to select if needed
					if (!thanaField.is('select')) {
						// Creating select from input - currentValue is the thana code
						const selectElement = $('<select name="' + settings.field_name + '" id="' + settings.field_name + '" class="regular-text wafm-user-thana-select" data-thana-type="' + fieldType + '" style="width: 25em;"><option value="">Select Thana</option></select>');
						
						// Add thana options with code as value
						$.each(thanaData[stateValue], function(code, name) {
							selectElement.append($('<option>').val(code).text(name));
						});
						
						// Set the value to the code that was in the input
						if (currentValue) {
							selectElement.val(currentValue);
						}
						
						thanaField.replaceWith(selectElement);
					} else {
						// Update options in existing select
						thanaField.find('option:not(:first)').remove();
						
						$.each(thanaData[stateValue], function(code, name) {
							thanaField.append($('<option>').val(code).text(name));
						});
						
						// Restore value if it exists in new state
						if (currentValue) {
							thanaField.val(currentValue);
						}
					}
				} else {
					// Convert to text input if needed
					if (thanaField.is('select')) {
						// Creating input from select - currentValue is the thana code
						const inputElement = $('<input type="text" name="' + settings.field_name + '" id="' + settings.field_name + '" value="' + currentValue + '" class="regular-text" style="width: 25em;" />');
						thanaField.replaceWith(inputElement);
					}
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Add thana to address format for thank you page and emails
	 */
	public static function add_thana_to_address_format( $formats ) {
		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Add thana placeholders to Bangladesh address format
		if ( isset( $formats['BD'] ) ) {
			// We need to add both billing and shipping thana placeholders
			// WooCommerce will use the appropriate one based on context
			$format = $formats['BD'];
			
			// Add billing thana after state if enabled
			if ( $billing_settings['enabled'] ) {
				$format = str_replace( '{state}', '{state}\n{' . $billing_settings['field_name'] . '}', $format );
			}
			
			// Add shipping thana after state if enabled
			if ( $shipping_settings['enabled'] ) {
				// Only add if not already added (in case field names are the same)
				if ( strpos( $format, '{' . $shipping_settings['field_name'] . '}' ) === false ) {
					$format = str_replace( '{state}', '{state}\n{' . $shipping_settings['field_name'] . '}', $format );
				}
			}
			
			$formats['BD'] = $format;
		}

		return $formats;
	}

	/**
	 * Display thana as custom field in thank you page and emails
	 */
	public static function display_thana_custom_field( $order ) {
		// Ensure we have a valid order object
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Collect thana fields to display
		$thana_fields = array();

		// Check billing thana - simple and direct like the old version
		if ( $billing_settings['enabled'] ) {
			$meta_key = '_' . $billing_settings['field_name'];
			$thana_code = $order->get_meta( $meta_key );
			if ( $thana_code ) {
				$thana_name = self::get_thana_name_from_code( $thana_code );
				$display_value = $thana_name ? $thana_name : $thana_code;
				$thana_fields[ $billing_settings['field_name'] ] = array(
					'label' => $billing_settings['show_label'] ? $billing_settings['label'] : __( 'Billing Thana', 'woocommerce-address-field-manager' ),
					'value' => $display_value,
				);
			}
		}

		// Check shipping thana - simple and direct like the old version
		if ( $shipping_settings['enabled'] ) {
			$meta_key = '_' . $shipping_settings['field_name'];
			$thana_code = $order->get_meta( $meta_key );
			if ( $thana_code ) {
				$thana_name = self::get_thana_name_from_code( $thana_code );
				$display_value = $thana_name ? $thana_name : $thana_code;
				$thana_fields[ $shipping_settings['field_name'] ] = array(
					'label' => $shipping_settings['show_label'] ? $shipping_settings['label'] : __( 'Shipping Thana', 'woocommerce-address-field-manager' ),
					'value' => $display_value,
				);
			}
		}

		// Display thana fields if any exist
		if ( ! empty( $thana_fields ) ) {
			?>
			<table class="woocommerce-table woocommerce-table--custom-fields shop_table custom-fields">
				<tbody>
					<tr>
						<th colspan="2" class="thwcfe-section-title"><?php esc_html_e( 'Thana Details', 'woocommerce-address-field-manager' ); ?></th>
					</tr>
					<?php foreach ( $thana_fields as $field_key => $field_data ) : ?>
					<tr>
						<td><?php echo esc_html( $field_data['label'] ); ?>:</td>
						<td><?php echo esc_html( $field_data['value'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Get thana data from JSON file
	 */
	private static function get_thana_data() {
		$json_file = WAFM_PLUGIN_DIR . 'data/thana.json';

		if ( ! file_exists( $json_file ) ) {
			return array();
		}

		$json_content = file_get_contents( $json_file );
		$thana_data   = json_decode( $json_content, true );

		return $thana_data ? $thana_data : array();
	}

	/**
	 * Get thana name from code
	 */
	private static function get_thana_name_from_code( $thana_code ) {
		$thana_data = self::get_thana_data();
		
		// Extract state code from thana code (e.g., "BD-58-02" -> "BD-58")
		$parts = explode( '-', $thana_code );
		if ( count( $parts ) >= 2 ) {
			$state_code = $parts[0] . '-' . $parts[1];
			if ( isset( $thana_data[ $state_code ][ $thana_code ] ) ) {
				return $thana_data[ $state_code ][ $thana_code ];
			}
		}
		
		return '';
	}

	/**
	 * Add inline script to detect country and initialize thana field
	 */
	public static function add_country_detection_script() {
		if ( ! is_checkout() ) {
			return;
		}

		$customer_country = self::get_customer_country();
		
		wp_add_inline_script( 'jquery', "
			jQuery(function($){
				window.wtsCustomerCountry = '" . esc_js( $customer_country ) . "';
				if(window.wtsCustomerCountry === 'BD') {
					setTimeout(function(){
						\$('#billing_state, #shipping_state').trigger('change');
					}, 500);
				}
			});
		" );
	}

	/**
	 * Get supported countries
	 */
	public static function get_supported_countries() {
		return self::$supported_countries;
	}

	/**
	 * Add editable billing thana field to order admin page
	 */
	public static function add_editable_billing_thana_to_order_admin( $fields ) {
		$billing_settings = WAFM_Settings::get_billing_settings();
		
		if ( ! $billing_settings['enabled'] ) {
			return $fields;
		}

		// Add thana field after state field
		$new_fields = array();
		foreach ( $fields as $key => $field ) {
			$new_fields[ $key ] = $field;
			
			// Insert thana field after state
			if ( $key === 'state' ) {
				$new_fields[ $billing_settings['field_name'] ] = array(
					'label' => $billing_settings['show_label'] ? $billing_settings['label'] : __( 'Thana', 'woocommerce-address-field-manager' ),
					'show'  => true,
					'class' => 'wafm-admin-thana-field',
					'wrapper_class' => 'form-field-wide',
					'type'  => 'select',
					'options' => array( '' => __( 'Select Thana', 'woocommerce-address-field-manager' ) ),
				);
			}
		}
		
		return $new_fields;
	}

	/**
	 * Add editable shipping thana field to order admin page
	 */
	public static function add_editable_shipping_thana_to_order_admin( $fields ) {
		$shipping_settings = WAFM_Settings::get_shipping_settings();
		
		if ( ! $shipping_settings['enabled'] ) {
			return $fields;
		}

		// Add thana field after state field
		$new_fields = array();
		foreach ( $fields as $key => $field ) {
			$new_fields[ $key ] = $field;
			
			// Insert thana field after state
			if ( $key === 'state' ) {
				$new_fields[ $shipping_settings['field_name'] ] = array(
					'label' => $shipping_settings['show_label'] ? $shipping_settings['label'] : __( 'Thana', 'woocommerce-address-field-manager' ),
					'show'  => true,
					'class' => 'wafm-admin-thana-field',
					'wrapper_class' => 'form-field-wide',
					'type'  => 'select',
					'options' => array( '' => __( 'Select Thana', 'woocommerce-address-field-manager' ) ),
				);
			}
		}
		
		return $new_fields;
	}

	/**
	 * Save thana when order is updated from admin
	 */
	public static function save_thana_from_order_admin( $post_id, $post ) {
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Save billing thana - BOTH patterns
		if ( $billing_settings['enabled'] && isset( $_POST[ '_' . $billing_settings['field_name'] ] ) ) {
			$billing_thana = sanitize_text_field( wp_unslash( $_POST[ '_' . $billing_settings['field_name'] ] ) );
			
			// Save with underscore prefix
			update_post_meta( $post_id, '_' . $billing_settings['field_name'], $billing_thana );
			
			// Also save without underscore
			update_post_meta( $post_id, $billing_settings['field_name'], $billing_thana );
		}

		// Save shipping thana - BOTH patterns
		if ( $shipping_settings['enabled'] && isset( $_POST[ '_' . $shipping_settings['field_name'] ] ) ) {
			$shipping_thana = sanitize_text_field( wp_unslash( $_POST[ '_' . $shipping_settings['field_name'] ] ) );
			
			// Save with underscore prefix
			update_post_meta( $post_id, '_' . $shipping_settings['field_name'], $shipping_thana );
			
			// Also save without underscore
			update_post_meta( $post_id, $shipping_settings['field_name'], $shipping_thana );
		}
		
		// Clear all caches for this order
		self::clear_all_order_caches( $post_id );
	}

	/**
	 * Load thana field values when loading customer details in order admin
	 */
	public static function load_thana_values_in_order_admin( $customer_data, $user_id, $type_to_load ) {
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Load billing thana from user meta
		if ( $billing_settings['enabled'] && $user_id && ( 'billing' === $type_to_load || 'all' === $type_to_load ) ) {
			$billing_thana = get_user_meta( $user_id, $billing_settings['field_name'], true );
			if ( $billing_thana ) {
				$customer_data[ $billing_settings['field_name'] ] = $billing_thana;
			}
		}

		// Load shipping thana from user meta
		if ( $shipping_settings['enabled'] && $user_id && ( 'shipping' === $type_to_load || 'all' === $type_to_load ) ) {
			$shipping_thana = get_user_meta( $user_id, $shipping_settings['field_name'], true );
			if ( $shipping_thana ) {
				$customer_data[ $shipping_settings['field_name'] ] = $shipping_thana;
			}
		}

		return $customer_data;
	}

	/**
	 * Add thana meta boxes for HPOS compatibility
	 */
	public static function add_thana_meta_boxes() {
		$screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'wafm_thana_fields',
			__( 'Thana / Locality Fields', 'woocommerce-address-field-manager' ),
			array( __CLASS__, 'render_thana_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Render thana meta box
	 */
	public static function render_thana_meta_box( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		
		if ( ! $order ) {
			return;
		}

		$order_id = $order->get_id();
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Get thana data
		$thana_data = self::get_thana_data();

		// Get current values - try both with and without underscore
		$billing_country = $order->get_billing_country();
		$billing_state = $order->get_billing_state();
		$billing_thana = $order->get_meta( '_' . $billing_settings['field_name'], true );
		if ( ! $billing_thana ) {
			$billing_thana = $order->get_meta( $billing_settings['field_name'], true );
		}
		
		$shipping_country = $order->get_shipping_country();
		$shipping_state = $order->get_shipping_state();
		$shipping_thana = $order->get_meta( '_' . $shipping_settings['field_name'], true );
		if ( ! $shipping_thana ) {
			$shipping_thana = $order->get_meta( $shipping_settings['field_name'], true );
		}

		wp_nonce_field( 'wafm_save_thana_meta_box', 'wafm_thana_nonce' );
		
		// Debug output
		?>
		<!-- Debug Info:
		Billing Country: <?php echo esc_html( $billing_country ); ?>
		Billing State: <?php echo esc_html( $billing_state ); ?>
		Billing Thana: <?php echo esc_html( $billing_thana ); ?>
		Shipping Country: <?php echo esc_html( $shipping_country ); ?>
		Shipping State: <?php echo esc_html( $shipping_state ); ?>
		Shipping Thana: <?php echo esc_html( $shipping_thana ); ?>
		-->
		<div class="wafm-thana-meta-box">
			<?php if ( $billing_settings['enabled'] ) : ?>
				<p class="form-field form-field-wide">
					<label for="_<?php echo esc_attr( $billing_settings['field_name'] ); ?>">
						<?php echo esc_html( $billing_settings['label'] ); ?> (Billing):
					</label>
					<?php
					// Check if state is BD (don't require country to be set)
					$is_bd = $billing_state && strpos( $billing_state, 'BD-' ) === 0;
					if ( $is_bd && isset( $thana_data[ $billing_state ] ) ) :
						?>
						<select id="_<?php echo esc_attr( $billing_settings['field_name'] ); ?>" name="_<?php echo esc_attr( $billing_settings['field_name'] ); ?>" class="wc-enhanced-select" style="width: 100%;">
							<option value="">Select Thana</option>
							<?php foreach ( $thana_data[ $billing_state ] as $code => $name ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $billing_thana, $code ); ?>>
									<?php echo esc_html( $name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="text" id="_<?php echo esc_attr( $billing_settings['field_name'] ); ?>" name="_<?php echo esc_attr( $billing_settings['field_name'] ); ?>" value="<?php echo esc_attr( $billing_thana ); ?>" style="width: 100%;" placeholder="<?php echo esc_attr( $billing_settings['placeholder_input'] ); ?>" />
						<small style="display: block; margin-top: 5px; color: #666;">
							Country: <?php echo esc_html( $billing_country ?: 'Not set' ); ?>, 
							State: <?php echo esc_html( $billing_state ?: 'Not set' ); ?>
						</small>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $shipping_settings['enabled'] ) : ?>
				<p class="form-field form-field-wide">
					<label for="_<?php echo esc_attr( $shipping_settings['field_name'] ); ?>">
						<?php echo esc_html( $shipping_settings['label'] ); ?> (Shipping):
					</label>
					<?php
					// Check if state is BD (don't require country to be set)
					$is_bd = $shipping_state && strpos( $shipping_state, 'BD-' ) === 0;
					if ( $is_bd && isset( $thana_data[ $shipping_state ] ) ) :
						?>
						<select id="_<?php echo esc_attr( $shipping_settings['field_name'] ); ?>" name="_<?php echo esc_attr( $shipping_settings['field_name'] ); ?>" class="wc-enhanced-select" style="width: 100%;">
							<option value="">Select Thana</option>
							<?php foreach ( $thana_data[ $shipping_state ] as $code => $name ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $shipping_thana, $code ); ?>>
									<?php echo esc_html( $name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="text" id="_<?php echo esc_attr( $shipping_settings['field_name'] ); ?>" name="_<?php echo esc_attr( $shipping_settings['field_name'] ); ?>" value="<?php echo esc_attr( $shipping_thana ); ?>" style="width: 100%;" placeholder="<?php echo esc_attr( $shipping_settings['placeholder_input'] ); ?>" />
						<small style="display: block; margin-top: 5px; color: #666;">
							Country: <?php echo esc_html( $shipping_country ?: 'Not set' ); ?>, 
							State: <?php echo esc_html( $shipping_state ?: 'Not set' ); ?>
						</small>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<script type="text/javascript">
		jQuery(function($) {
			// Thana data
			var thanaData = <?php echo wp_json_encode( $thana_data ); ?>;
			var billingFieldName = '<?php echo esc_js( $billing_settings['field_name'] ); ?>';
			var shippingFieldName = '<?php echo esc_js( $shipping_settings['field_name'] ); ?>';
			var billingPlaceholder = '<?php echo esc_js( $billing_settings['placeholder_input'] ); ?>';
			var shippingPlaceholder = '<?php echo esc_js( $shipping_settings['placeholder_input'] ); ?>';
			
			// Function to update thana field based on country/state
			function updateThanaField(type) {
				var fieldName = (type === 'billing') ? billingFieldName : shippingFieldName;
				var placeholder = (type === 'billing') ? billingPlaceholder : shippingPlaceholder;
				var countryField = $('#_' + type + '_country');
				var stateField = $('#_' + type + '_state');
				var thanaFieldSelector = '#_' + fieldName;
				var thanaField = $(thanaFieldSelector);
				
				if (!thanaField.length) {
					console.log('Thana field not found: ' + thanaFieldSelector);
					return;
				}
				
				var country = countryField.val();
				var state = stateField.val();
				var currentValue = thanaField.val();
				
				console.log(type + ' - Country:', country, 'State:', state, 'Current Value:', currentValue);
				
				// Check if state is BD
				var isBD = state && state.toString().indexOf('BD-') === 0;
				
				if (isBD && thanaData[state]) {
					console.log(type + ' - Converting to select, options:', Object.keys(thanaData[state]).length);
					// Convert to select if needed
					if (!thanaField.is('select')) {
						// Destroy select2 if exists
						if (thanaField.hasClass('select2-hidden-accessible')) {
							thanaField.select2('destroy');
						}
						
						// Create new select element
						var $newSelect = $('<select id="_' + fieldName + '" name="_' + fieldName + '" class="wc-enhanced-select" style="width: 100%;"></select>');
						$newSelect.append('<option value="">Select Thana</option>');
						
						$.each(thanaData[state], function(code, name) {
							var $option = $('<option></option>').val(code).text(name);
							if (code === currentValue) {
								$option.prop('selected', true);
							}
							$newSelect.append($option);
						});
						
						// Remove old field and insert new one
						thanaField.after($newSelect).remove();
						
						// Initialize selectWoo
						$newSelect.selectWoo();
					} else {
						// Update options in existing select
						thanaField.find('option:not(:first)').remove();
						$.each(thanaData[state], function(code, name) {
							thanaField.append('<option value="' + code + '">' + name + '</option>');
						});
						if (currentValue) {
							thanaField.val(currentValue);
						}
						thanaField.trigger('change');
					}
				} else {
					console.log(type + ' - Converting to input');
					// Convert to input if needed
					if (thanaField.is('select')) {
						// Destroy select2 if exists
						if (thanaField.hasClass('select2-hidden-accessible')) {
							thanaField.select2('destroy');
						}
						
						// Create new input element
						var $newInput = $('<input type="text" id="_' + fieldName + '" name="_' + fieldName + '" value="' + currentValue + '" style="width: 100%;" placeholder="' + placeholder + '" />');
						
						// Remove old field and insert new one
						thanaField.after($newInput).remove();
					}
				}
			}
			
			// Listen for country/state changes - use separate handlers
			$(document).on('change', '#_billing_country, #_billing_state', function() {
				console.log('Billing country/state changed');
				updateThanaField('billing');
			});
			
			$(document).on('change', '#_shipping_country, #_shipping_state', function() {
				console.log('Shipping country/state changed');
				updateThanaField('shipping');
			});
		});
		</script>
		<?php
	}

	/**
	 * Save thana from HPOS order
	 */
	public static function save_thana_from_hpos_order( $order_id ) {
		// Prevent infinite loop - only run when form is submitted
		if ( ! isset( $_POST['wafm_thana_nonce'] ) ) {
			return;
		}
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['wafm_thana_nonce'], 'wafm_save_thana_meta_box' ) ) {
			return;
		}

		// Prevent infinite loop - remove this action temporarily
		remove_action( 'woocommerce_update_order', array( __CLASS__, 'save_thana_from_hpos_order' ), 10 );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Save billing thana - BOTH patterns (with and without underscore)
		if ( $billing_settings['enabled'] && isset( $_POST[ '_' . $billing_settings['field_name'] ] ) ) {
			$billing_thana = sanitize_text_field( wp_unslash( $_POST[ '_' . $billing_settings['field_name'] ] ) );
			
			// Save with underscore prefix (standard WooCommerce pattern)
			$order->update_meta_data( '_' . $billing_settings['field_name'], $billing_thana );
			
			// Also save without underscore for compatibility
			$order->update_meta_data( $billing_settings['field_name'], $billing_thana );
		}

		// Save shipping thana - BOTH patterns (with and without underscore)
		if ( $shipping_settings['enabled'] && isset( $_POST[ '_' . $shipping_settings['field_name'] ] ) ) {
			$shipping_thana = sanitize_text_field( wp_unslash( $_POST[ '_' . $shipping_settings['field_name'] ] ) );
			
			// Save with underscore prefix (standard WooCommerce pattern)
			$order->update_meta_data( '_' . $shipping_settings['field_name'], $shipping_thana );
			
			// Also save without underscore for compatibility
			$order->update_meta_data( $shipping_settings['field_name'], $shipping_thana );
		}

		$order->save();
		
		// Clear all caches for this order
		self::clear_all_order_caches( $order_id );
		
		// Re-add the action for next time
		add_action( 'woocommerce_update_order', array( __CLASS__, 'save_thana_from_hpos_order' ), 10, 1 );
	}

	/**
	 * Clear all possible caches for an order
	 */
	private static function clear_all_order_caches( $order_id ) {
		// Clear WordPress object cache
		wp_cache_delete( $order_id, 'post_meta' );
		wp_cache_delete( $order_id, 'posts' );
		wp_cache_delete( 'wc_order_' . $order_id, 'orders' );
		
		// Clear WooCommerce specific caches
		if ( function_exists( 'wc_delete_shop_order_transients' ) ) {
			wc_delete_shop_order_transients( $order_id );
		}
		
		// Clear post cache
		clean_post_cache( $order_id );
		
		// Clear HPOS cache if enabled
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			wp_cache_delete( 'order-' . $order_id, 'orders' );
			wp_cache_delete( $order_id, 'order-meta' );
		}
	}

	/**
	 * Clear order cache when thana meta is updated via post meta (webhooks, external updates)
	 */
	public static function clear_order_cache_on_meta_update( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Check if this is a thana field
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();
		
		$is_thana_field = false;
		if ( $billing_settings['enabled'] && ( $meta_key === '_' . $billing_settings['field_name'] || $meta_key === $billing_settings['field_name'] ) ) {
			$is_thana_field = true;
		}
		if ( $shipping_settings['enabled'] && ( $meta_key === '_' . $shipping_settings['field_name'] || $meta_key === $shipping_settings['field_name'] ) ) {
			$is_thana_field = true;
		}
		
		if ( ! $is_thana_field ) {
			return;
		}
		
		// Check if this is an order
		$post_type = get_post_type( $object_id );
		if ( $post_type === 'shop_order' || $post_type === 'shop_order_placehold' ) {
			self::clear_all_order_caches( $object_id );
		}
	}

	/**
	 * Clear order cache when thana meta is updated via WooCommerce order meta (HPOS)
	 */
	public static function clear_order_cache_on_order_meta_update( $order_id, $meta_key, $meta_value ) {
		// Check if this is a thana field
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();
		
		$is_thana_field = false;
		if ( $billing_settings['enabled'] && ( $meta_key === '_' . $billing_settings['field_name'] || $meta_key === $billing_settings['field_name'] ) ) {
			$is_thana_field = true;
		}
		if ( $shipping_settings['enabled'] && ( $meta_key === '_' . $shipping_settings['field_name'] || $meta_key === $shipping_settings['field_name'] ) ) {
			$is_thana_field = true;
		}
		
		if ( $is_thana_field ) {
			self::clear_all_order_caches( $order_id );
		}
	}
}
