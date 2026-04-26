/**
 * WooCommerce Address Field Manager - Order Edit Page Script
 * Makes thana fields dynamic in admin order edit page
 */

jQuery(function($) {
	'use strict';

	const OrderThanaSelector = {
		config: {
			thanaData: wafmOrderEditData.thanaData || {},
			billingSettings: wafmOrderEditData.billingSettings || {},
			shippingSettings: wafmOrderEditData.shippingSettings || {},
			billingState: wafmOrderEditData.billingState || '',
			billingThana: wafmOrderEditData.billingThana || '',
			shippingState: wafmOrderEditData.shippingState || '',
			shippingThana: wafmOrderEditData.shippingThana || ''
		},

		init: function() {
			this.setupEventListeners();
			this.initializeFields();
		},

		setupEventListeners: function() {
			const self = this;

			// Listen for billing state changes
			if (this.config.billingSettings.enabled) {
				$(document).on('change', '#_billing_state', function() {
					self.updateThanaField('billing');
				});
			}

			// Listen for shipping state changes
			if (this.config.shippingSettings.enabled) {
				$(document).on('change', '#_shipping_state', function() {
					self.updateThanaField('shipping');
				});
			}
		},

		initializeFields: function() {
			// Initialize billing thana
			if (this.config.billingSettings.enabled) {
				this.updateThanaField('billing');
			}

			// Initialize shipping thana
			if (this.config.shippingSettings.enabled) {
				this.updateThanaField('shipping');
			}
		},

		updateThanaField: function(type) {
			const settings = type === 'billing' ? this.config.billingSettings : this.config.shippingSettings;
			const stateFieldId = type === 'billing' ? '#_billing_state' : '#_shipping_state';
			const thanaFieldId = '#_' + settings.field_name;

			const $stateField = $(stateFieldId);
			const $thanaField = $(thanaFieldId);

			if (!$stateField.length || !$thanaField.length) {
				return;
			}

			const stateValue = $stateField.val();
			const isBD = stateValue && stateValue.toString().startsWith('BD-');

			if (isBD && this.config.thanaData[stateValue]) {
				this.populateThanaOptions($thanaField, stateValue, type);
			} else {
				// Clear options and add placeholder
				$thanaField.empty();
				$thanaField.append($('<option>').val('').text('Select Thana'));
			}
		},

		populateThanaOptions: function($select, stateValue, type) {
			const thanaList = this.config.thanaData[stateValue] || {};
			const currentThana = type === 'billing' ? this.config.billingThana : this.config.shippingThana;
			const currentState = type === 'billing' ? this.config.billingState : this.config.shippingState;

			// Clear existing options
			$select.empty();
			$select.append($('<option>').val('').text('Select Thana'));

			// Add thana options with code as value and name as display
			$.each(thanaList, function(code, name) {
				$select.append($('<option>').val(code).text(name));
			});

			// Set the current value if state matches
			if (stateValue === currentState && currentThana) {
				$select.val(currentThana);
			}
		}
	};

	// Initialize on document ready
	OrderThanaSelector.init();
});
