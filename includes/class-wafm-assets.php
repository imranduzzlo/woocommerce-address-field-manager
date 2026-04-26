<?php
/**
 * Assets handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WAFM_Assets {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles
	 */
	public static function enqueue_scripts() {
		// Only load on checkout page
		if ( ! is_checkout() ) {
			return;
		}

		// Get settings
		$billing_settings = WAFM_Settings::get_billing_settings();
		$shipping_settings = WAFM_Settings::get_shipping_settings();

		// Get user meta data for logged-in users (highest priority)
		$billing_user_thana = '';
		$shipping_user_thana = '';
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$billing_user_thana = get_user_meta( $user_id, $billing_settings['field_name'], true ) ?: '';
			$shipping_user_thana = get_user_meta( $user_id, $shipping_settings['field_name'], true ) ?: '';
		}

		// Get session data for non-logged-in users (lower priority)
		$billing_session_thana = '';
		$shipping_session_thana = '';
		if ( ! $user_id && WC()->session ) {
			$billing_session_thana = WC()->session->get( $billing_settings['field_name'] ) ?: '';
			$shipping_session_thana = WC()->session->get( $shipping_settings['field_name'] ) ?: '';
		}

		// Localize script with thana data and field names
		wp_localize_script(
			'jquery',
			'wtsThanaData',
			array(
				'thanaData'                  => self::get_thana_data(),
				'supportedCountries'         => WAFM_Checkout_Fields::get_supported_countries(),
				'billingFieldName'           => $billing_settings['enabled'] ? $billing_settings['field_name'] : '',
				'billingAfterField'          => $billing_settings['enabled'] ? $billing_settings['after_field'] : '',
				'billingPlaceholderSelect'   => $billing_settings['placeholder_select'],
				'billingPlaceholderInput'    => $billing_settings['placeholder_input'],
				'billingSessionThana'        => $billing_session_thana,
				'billingUserThana'           => $billing_user_thana,
				'shippingFieldName'          => $shipping_settings['enabled'] ? $shipping_settings['field_name'] : '',
				'shippingAfterField'         => $shipping_settings['enabled'] ? $shipping_settings['after_field'] : '',
				'shippingPlaceholderSelect'  => $shipping_settings['placeholder_select'],
				'shippingPlaceholderInput'   => $shipping_settings['placeholder_input'],
				'shippingSessionThana'       => $shipping_session_thana,
				'shippingUserThana'          => $shipping_user_thana,
			)
		);

		// Inline script instead of external file
		wp_add_inline_script( 'jquery', self::get_inline_script() );

		// Enqueue styles
		wp_enqueue_style(
			'wafm-thana-selector',
			WAFM_PLUGIN_URL . 'assets/css/thana-selector.css',
			array(),
			WAFM_PLUGIN_VERSION
		);
	}

	/**
	 * Get inline JavaScript with proper country detection
	 */
	private static function get_inline_script() {
		$script = <<<'JS'
jQuery(function($){
	var ThanaSelector = {
		config: {
			billingStateField: '#billing_state',
			billingThanaField: '#' + (wtsThanaData.billingFieldName || 'billing_thana'),
			shippingStateField: '#shipping_state',
			shippingThanaField: '#' + (wtsThanaData.shippingFieldName || 'shipping_thana'),
			thanaData: wtsThanaData.thanaData || {},
			billingEnabled: !!wtsThanaData.billingFieldName,
			shippingEnabled: !!wtsThanaData.shippingFieldName,
			billingPlaceholderSelect: wtsThanaData.billingPlaceholderSelect || 'Select Thana',
			billingPlaceholderInput: wtsThanaData.billingPlaceholderInput || 'Enter Thana',
			shippingPlaceholderSelect: wtsThanaData.shippingPlaceholderSelect || 'Select Thana',
			shippingPlaceholderInput: wtsThanaData.shippingPlaceholderInput || 'Enter Thana',
			billingSessionThana: wtsThanaData.billingSessionThana || '',
			billingUserThana: wtsThanaData.billingUserThana || '',
			shippingSessionThana: wtsThanaData.shippingSessionThana || '',
			shippingUserThana: wtsThanaData.shippingUserThana || ''
		},
		storedValues: {
			billing: null,
			shipping: null
		},
		init: function() {
			this.setupEventListeners();
			this.detectCountryAndInit();
		},
		setupEventListeners: function() {
			var self = this;
			if (wtsThanaData.billingFieldName) {
				$(document).on('change', this.config.billingThanaField, function() {
					self.storedValues.billing = $(this).val();
				});
				$(document).on('change', '#billing_country', function() {
					self.updateThanaField('billing');
				});
				$(document).on('change', this.config.billingStateField, function() {
					self.updateThanaField('billing');
				});
			}
			if (wtsThanaData.shippingFieldName) {
				$(document).on('change', this.config.shippingThanaField, function() {
					self.storedValues.shipping = $(this).val();
				});
				$(document).on('change', '#shipping_country', function() {
					self.updateThanaField('shipping');
				});
				$(document).on('change', this.config.shippingStateField, function() {
					self.updateThanaField('shipping');
				});
			}
			$(document.body).on('updated_checkout', function() {
				self.detectCountryAndInit();
			});
		},
		detectCountryAndInit: function() {
			var self = this;
			setTimeout(function() {
				if (wtsThanaData.billingFieldName) {
					self.updateThanaField('billing');
				}
				if (wtsThanaData.shippingFieldName) {
					self.updateThanaField('shipping');
				}
			}, 100);
		},
		updateThanaField: function(type) {
			var stateField = type === 'billing' ? this.config.billingStateField : this.config.shippingStateField;
			var thanaField = type === 'billing' ? this.config.billingThanaField : this.config.shippingThanaField;
			var $stateField = $(stateField);
			var $thanaField = $(thanaField);
			if (!$stateField.length || !$thanaField.length) {
				return;
			}
			var stateValue = $stateField.val();
			var isBD = stateValue && stateValue.toString().startsWith('BD-');
			if (isBD) {
				this.makeSelect($thanaField, stateValue, type);
			} else {
				this.makeInput($thanaField, type);
			}
		},
		makeSelect: function($field, stateValue, type) {
			if ($field.is('select')) {
				this.populateOptions($field, stateValue, type);
				var self = this;
				setTimeout(function() {
					self.initializeSelect2($field, type);
				}, 10);
				return;
			}
			var fieldId = $field.attr('id');
			var fieldName = $field.attr('name');
			var fieldClass = $field.attr('class');
			var currentValue = $field.val();
			var placeholder = type === 'billing' ? this.config.billingPlaceholderSelect : this.config.shippingPlaceholderSelect;
			var $select = $('<select>').attr('id', fieldId).attr('name', fieldName).attr('class', fieldClass).append($('<option>').val('').text(placeholder));
			$field.replaceWith($select);
			var $newSelect = $('#' + fieldId);
			this.populateOptions($newSelect, stateValue, type);
			if (currentValue) {
				$newSelect.val(currentValue);
			}
			var self = this;
			setTimeout(function() {
				self.initializeSelect2($newSelect, type);
			}, 10);
		},
		initializeSelect2: function($field, type) {
			if (!$.fn.select2) {
				return;
			}
			var placeholder = type === 'billing' ? this.config.billingPlaceholderSelect : this.config.shippingPlaceholderSelect;
			try {
				if ($field.data('select2')) {
					$field.select2('destroy');
				}
				$field.select2({width: '100%', placeholder: placeholder, allowClear: false, minimumInputLength: 0});
			} catch(e) {}
		},
		makeInput: function($field, type) {
			if ($field.is('input[type="text"]')) {
				return;
			}
			var fieldId = $field.attr('id');
			var fieldName = $field.attr('name');
			var fieldClass = $field.attr('class');
			var currentValue = $field.val();
			var placeholder = type === 'billing' ? this.config.billingPlaceholderInput : this.config.shippingPlaceholderInput;
			if ($field.hasClass('select2-hidden-accessible')) {
				$field.select2('destroy');
			}
			var $input = $('<input>').attr('type', 'text').attr('id', fieldId).attr('name', fieldName).attr('class', fieldClass).attr('placeholder', placeholder).val(currentValue);
			$field.replaceWith($input);
		},
		populateOptions: function($select, stateValue, type) {
			var thanaList = this.config.thanaData[stateValue] || {};
			var storedValue = type === 'billing' ? this.storedValues.billing : this.storedValues.shipping;
			
			// Priority for prefilling:
			// 1. Stored value in memory (from previous selection in this session)
			// 2. Session data (for non-logged-in users)
			// 3. User meta data (for logged-in users)
			var prefillValue = storedValue;
			if (!prefillValue) {
				prefillValue = type === 'billing' ? this.config.billingSessionThana : this.config.shippingSessionThana;
			}
			if (!prefillValue) {
				prefillValue = type === 'billing' ? this.config.billingUserThana : this.config.shippingUserThana;
			}
			
			$select.find('option:not(:first)').remove();
			$.each(thanaList, function(code, name) {
				$select.append($('<option>').val(code).text(name));
			});
			if (prefillValue) {
				$select.val(prefillValue);
				// Store in memory for AJAX updates
				if (type === 'billing') {
					ThanaSelector.storedValues.billing = prefillValue;
				} else {
					ThanaSelector.storedValues.shipping = prefillValue;
				}
			}
			// Trigger change to ensure value is saved
			$select.trigger('change');
		}
	};
	ThanaSelector.init();
	$(document.body).on('updated_checkout', function() {
		ThanaSelector.detectCountryAndInit();
	});
});
JS;
		return $script;
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
}
