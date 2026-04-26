/**
 * WooCommerce Address Field Manager - Frontend Script
 * Populates thana options based on selected state
 * Preserves thana value on AJAX checkout updates
 * Prefills from session (non-logged-in) or user meta (logged-in)
 * Stores codes in DB but displays names in UI
 */

jQuery(function($) {
	'use strict';

	const ThanaSelector = {
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

		// Store thana values to preserve during AJAX
		storedValues: {
			billing: null,
			shipping: null
		},

		/**
		 * Initialize
		 */
		init: function() {
			this.setupEventListeners();
			this.initializeFields();
		},

		/**
		 * Setup event listeners
		 */
		setupEventListeners: function() {
			const self = this;

			// Store thana values before they change
			if (this.config.billingEnabled) {
				$(document).on('change', this.config.billingThanaField, function() {
					self.storedValues.billing = $(this).val();
				});
				$(document).on('change', this.config.billingStateField, function() {
					self.updateThanaField('billing');
				});
			}

			// Store thana values before they change
			if (this.config.shippingEnabled) {
				$(document).on('change', this.config.shippingThanaField, function() {
					self.storedValues.shipping = $(this).val();
				});
				$(document).on('change', this.config.shippingStateField, function() {
					self.updateThanaField('shipping');
				});
			}

			// Re-initialize on checkout update (AJAX) - preserves thana values
			$(document.body).on('updated_checkout', function() {
				self.initializeFields();
			});
		},

		/**
		 * Initialize fields on page load and after AJAX with staggered timing
		 */
		initializeFields: function() {
			const self = this;
			
			if (this.config.billingEnabled) {
				setTimeout(function() {
					self.updateThanaField('billing');
				}, 50);
			}
			if (this.config.shippingEnabled) {
				setTimeout(function() {
					self.updateThanaField('shipping');
				}, 100);
			}
		},

		/**
		 * Update thana field for specific address type
		 */
		updateThanaField: function(type) {
			const stateField = type === 'billing' ? this.config.billingStateField : this.config.shippingStateField;
			const thanaField = type === 'billing' ? this.config.billingThanaField : this.config.shippingThanaField;

			const $stateField = $(stateField);
			const $thanaField = $(thanaField);

			if (!$stateField.length || !$thanaField.length) {
				return;
			}

			const stateValue = $stateField.val();
			const isBD = stateValue && stateValue.toString().startsWith('BD-');

			if (isBD) {
				this.makeSelect($thanaField, stateValue, type);
			} else {
				this.makeInput($thanaField, type);
			}
		},

		/**
		 * Convert field to select and populate options
		 */
		makeSelect: function($field, stateValue, type) {
			if ($field.is('select')) {
				this.populateOptions($field, stateValue, type);
				// Reinitialize Select2 after populating options
				const self = this;
				setTimeout(function() {
					self.initializeSelect2($field, type);
				}, 10);
				return;
			}

			const fieldId = $field.attr('id');
			const fieldName = $field.attr('name');
			const fieldClass = $field.attr('class');
			const currentValue = $field.val();
			const placeholder = type === 'billing' ? this.config.billingPlaceholderSelect : this.config.shippingPlaceholderSelect;

			const $select = $('<select>')
				.attr('id', fieldId)
				.attr('name', fieldName)
				.attr('class', fieldClass)
				.append($('<option>').val('').text(placeholder));

			$field.replaceWith($select);

			const $newSelect = $('#' + fieldId);
			this.populateOptions($newSelect, stateValue, type);

			// Restore previous thana value if it exists in new state
			if (currentValue) {
				$newSelect.val(currentValue);
			}

			// Initialize Select2
			const self = this;
			setTimeout(function() {
				self.initializeSelect2($newSelect, type);
			}, 10);
		},

		/**
		 * Initialize Select2 on select field
		 */
		initializeSelect2: function($field, type) {
			if (!$.fn.select2) {
				return;
			}

			const placeholder = type === 'billing' ? this.config.billingPlaceholderSelect : this.config.shippingPlaceholderSelect;

			try {
				// Check if Select2 is already initialized
				if ($field.data('select2')) {
					// Update the Select2 instance
					$field.select2('destroy');
				}

				// Initialize Select2
				$field.select2({
					width: '100%',
					placeholder: placeholder,
					allowClear: false,
					minimumInputLength: 0
				});
			} catch (e) {
				console.log('Select2 initialization error:', e);
			}
		},

		/**
		 * Convert field to text input
		 */
		makeInput: function($field, type) {
			if ($field.is('input[type="text"]')) {
				return;
			}

			const fieldId = $field.attr('id');
			const fieldName = $field.attr('name');
			const fieldClass = $field.attr('class');
			const currentValue = $field.val();
			const placeholder = type === 'billing' ? this.config.billingPlaceholderInput : this.config.shippingPlaceholderInput;

			if ($field.hasClass('select2-hidden-accessible')) {
				try {
					$field.select2('destroy');
				} catch (e) {}
			}

			const $input = $('<input>')
				.attr('type', 'text')
				.attr('id', fieldId)
				.attr('name', fieldName)
				.attr('class', fieldClass)
				.attr('placeholder', placeholder)
				.val(currentValue);

			$field.replaceWith($input);
		},

		/**
		 * Populate thana select options
		 */
		populateOptions: function($select, stateValue, type) {
			const thanaList = this.config.thanaData[stateValue] || {};
			const storedValue = type === 'billing' ? this.storedValues.billing : this.storedValues.shipping;
			
			// Priority for prefilling:
			// 1. Stored value in memory (from previous selection in this session)
			// 2. User meta data (for logged-in users) - takes precedence over session
			// 3. Session data (for non-logged-in users)
			let prefillValue = storedValue;
			if (!prefillValue) {
				prefillValue = type === 'billing' ? this.config.billingUserThana : this.config.shippingUserThana;
			}
			if (!prefillValue) {
				prefillValue = type === 'billing' ? this.config.billingSessionThana : this.config.shippingSessionThana;
			}

			// Clear existing options except the first one
			$select.find('option:not(:first)').remove();

			// Add thana options with code as value and name as display
			$.each(thanaList, function(code, name) {
				$select.append(
					$('<option>').val(code).text(name)
				);
			});

			// Prefill with priority value (code)
			if (prefillValue) {
				$select.val(prefillValue);
				// Store in memory for AJAX updates
				if (type === 'billing') {
					ThanaSelector.storedValues.billing = prefillValue;
				} else {
					ThanaSelector.storedValues.shipping = prefillValue;
				}
			}

			// Trigger change to update any dependent fields
			$select.trigger('change');
		},
	};

	// Initialize on document ready
	ThanaSelector.init();

	// Re-initialize on checkout update
	$(document.body).on('updated_checkout', function() {
		ThanaSelector.initializeFields();
	});
});
