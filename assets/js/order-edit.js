/**
 * WooCommerce Address Field Manager - Order Edit Page Script
 * Makes thana fields dynamic in admin order edit page
 * Converts between select dropdown and text input based on country
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
			shippingThana: wafmOrderEditData.shippingThana || '',
			billingCountry: wafmOrderEditData.billingCountry || '',
			shippingCountry: wafmOrderEditData.shippingCountry || ''
		},

		init: function() {
			this.setupEventListeners();
			this.initializeFields();
		},

		setupEventListeners: function() {
			const self = this;

			// Listen for billing country changes
			if (this.config.billingSettings.enabled) {
				$(document).on('change', '#_billing_country', function() {
					self.updateThanaField('billing');
				});
				
				$(document).on('change', '#_billing_state', function() {
					self.updateThanaField('billing');
				});
			}

			// Listen for shipping country changes
			if (this.config.shippingSettings.enabled) {
				$(document).on('change', '#_shipping_country', function() {
					self.updateThanaField('shipping');
				});
				
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
			const countryFieldId = type === 'billing' ? '#_billing_country' : '#_shipping_country';
			const stateFieldId = type === 'billing' ? '#_billing_state' : '#_shipping_state';
			const thanaFieldId = '#_' + settings.field_name;

			const $countryField = $(countryFieldId);
			const $stateField = $(stateFieldId);
			const $thanaField = $(thanaFieldId);

			if (!$thanaField.length) {
				return;
			}

			const countryValue = $countryField.val();
			const stateValue = $stateField.val();
			const isBD = countryValue === 'BD' && stateValue && stateValue.toString().startsWith('BD-');

			if (isBD && this.config.thanaData[stateValue]) {
				// Convert to select and populate
				this.makeSelect($thanaField, stateValue, type);
			} else {
				// Convert to text input
				this.makeInput($thanaField, type);
			}
		},

		makeSelect: function($field, stateValue, type) {
			const settings = type === 'billing' ? this.config.billingSettings : this.config.shippingSettings;
			const fieldId = '_' + settings.field_name;
			const currentValue = $field.val();

			// If already a select, just populate
			if ($field.is('select')) {
				this.populateThanaOptions($field, stateValue, type);
				return;
			}

			// Convert input to select
			const $wrapper = $field.closest('p.form-field');
			const $label = $wrapper.find('label');
			
			// Create select element
			const $select = $('<select>')
				.attr('id', fieldId)
				.attr('name', fieldId)
				.attr('class', 'select short')
				.css('width', '100%');

			// Replace input with select
			$field.replaceWith($select);

			// Populate options
			this.populateThanaOptions($select, stateValue, type);

			// Restore value if it was a code
			if (currentValue) {
				$select.val(currentValue);
			}
		},

		makeInput: function($field, type) {
			const settings = type === 'billing' ? this.config.billingSettings : this.config.shippingSettings;
			const fieldId = '_' + settings.field_name;
			const currentValue = $field.val();

			// If already an input, do nothing
			if ($field.is('input[type="text"]')) {
				return;
			}

			// Convert select to input
			const $wrapper = $field.closest('p.form-field');
			
			// Create input element
			const $input = $('<input>')
				.attr('type', 'text')
				.attr('id', fieldId)
				.attr('name', fieldId)
				.attr('class', 'short')
				.attr('placeholder', settings.placeholder_input || 'Enter locality')
				.css('width', '100%')
				.val(currentValue);

			// Replace select with input
			$field.replaceWith($input);
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
