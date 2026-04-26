/**
 * WooCommerce Address Field Manager - Order Edit Page Script
 * Makes thana fields dynamic in admin order edit page
 * Converts between select dropdown and text input based on country
 */

jQuery(function($) {
	'use strict';

	// Check if data is available
	if (typeof wafmOrderEditData === 'undefined') {
		console.log('WAFM: Order edit data not loaded');
		return;
	}

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
			console.log('WAFM: Initializing order edit thana selector');
			console.log('Billing State:', this.config.billingState);
			console.log('Billing Thana:', this.config.billingThana);
			console.log('Billing Country:', this.config.billingCountry);
			
			this.setupEventListeners();
			
			// Initialize fields after a short delay to ensure DOM is ready
			const self = this;
			setTimeout(function() {
				self.initializeFields();
			}, 500);
		},

		setupEventListeners: function() {
			const self = this;

			// Listen for billing country changes
			if (this.config.billingSettings.enabled) {
				$(document).on('change', '#_billing_country', function() {
					console.log('Billing country changed:', $(this).val());
					self.updateThanaField('billing');
				});
				
				$(document).on('change', '#_billing_state', function() {
					console.log('Billing state changed:', $(this).val());
					self.updateThanaField('billing');
				});
			}

			// Listen for shipping country changes
			if (this.config.shippingSettings.enabled) {
				$(document).on('change', '#_shipping_country', function() {
					console.log('Shipping country changed:', $(this).val());
					self.updateThanaField('shipping');
				});
				
				$(document).on('change', '#_shipping_state', function() {
					console.log('Shipping state changed:', $(this).val());
					self.updateThanaField('shipping');
				});
			}
		},

		initializeFields: function() {
			console.log('WAFM: Initializing fields');
			
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

			console.log('Updating thana field for:', type);
			console.log('Thana field selector:', thanaFieldId);
			console.log('Thana field found:', $thanaField.length);

			if (!$thanaField.length) {
				console.log('Thana field not found, retrying...');
				// Try to find the field with different selectors
				const $altField = $('input[name="_' + settings.field_name + '"], select[name="_' + settings.field_name + '"]');
				if ($altField.length) {
					console.log('Found thana field with alternative selector');
					this.processThanaField($altField, type);
				}
				return;
			}

			this.processThanaField($thanaField, type);
		},

		processThanaField: function($thanaField, type) {
			const settings = type === 'billing' ? this.config.billingSettings : this.config.shippingSettings;
			const countryFieldId = type === 'billing' ? '#_billing_country' : '#_shipping_country';
			const stateFieldId = type === 'billing' ? '#_billing_state' : '#_shipping_state';

			const $countryField = $(countryFieldId);
			const $stateField = $(stateFieldId);

			const countryValue = $countryField.val();
			const stateValue = $stateField.val();
			
			console.log('Country:', countryValue);
			console.log('State:', stateValue);
			console.log('Is BD:', countryValue === 'BD');
			console.log('State starts with BD-:', stateValue && stateValue.toString().startsWith('BD-'));

			const isBD = countryValue === 'BD' && stateValue && stateValue.toString().startsWith('BD-');

			if (isBD && this.config.thanaData[stateValue]) {
				console.log('Converting to select for state:', stateValue);
				console.log('Thana data available:', Object.keys(this.config.thanaData[stateValue]).length, 'options');
				// Convert to select and populate
				this.makeSelect($thanaField, stateValue, type);
			} else {
				console.log('Converting to text input');
				// Convert to text input
				this.makeInput($thanaField, type);
			}
		},

		makeSelect: function($field, stateValue, type) {
			const settings = type === 'billing' ? this.config.billingSettings : this.config.shippingSettings;
			const fieldId = '_' + settings.field_name;
			const currentValue = $field.val();
			const savedValue = type === 'billing' ? this.config.billingThana : this.config.shippingThana;

			console.log('makeSelect - Current value:', currentValue);
			console.log('makeSelect - Saved value:', savedValue);

			// If already a select, just populate
			if ($field.is('select')) {
				console.log('Field is already a select, populating options');
				this.populateThanaOptions($field, stateValue, type);
				return;
			}

			// Convert input to select
			const $wrapper = $field.closest('p.form-field');
			
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

			// Restore value - prefer saved value over current
			const valueToSet = savedValue || currentValue;
			if (valueToSet) {
				console.log('Setting value to:', valueToSet);
				$select.val(valueToSet);
			}
		},

		makeInput: function($field, type) {
			const settings = type === 'billing' ? this.config.billingSettings : this.config.shippingSettings;
			const fieldId = '_' + settings.field_name;
			const currentValue = $field.val();

			console.log('makeInput - Current value:', currentValue);

			// If already an input, do nothing
			if ($field.is('input[type="text"]')) {
				console.log('Field is already an input');
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
			const savedThana = type === 'billing' ? this.config.billingThana : this.config.shippingThana;
			const savedState = type === 'billing' ? this.config.billingState : this.config.shippingState;

			console.log('Populating options for state:', stateValue);
			console.log('Saved state:', savedState);
			console.log('Saved thana:', savedThana);
			console.log('Number of thanas:', Object.keys(thanaList).length);

			// Clear existing options
			$select.empty();
			$select.append($('<option>').val('').text('Select Thana'));

			// Add thana options with code as value and name as display
			$.each(thanaList, function(code, name) {
				$select.append($('<option>').val(code).text(name));
			});

			// Set the current value if state matches
			if (stateValue === savedState && savedThana) {
				console.log('Setting saved thana value:', savedThana);
				$select.val(savedThana);
			}
		}
	};

	// Initialize on document ready
	OrderThanaSelector.init();
});
