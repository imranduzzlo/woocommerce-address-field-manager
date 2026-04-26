/**
 * WooCommerce Address Field Manager - Admin Settings Script
 */

jQuery(function($) {
	'use strict';

	// Tab switching
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();

		const tabName = $(this).data('tab');
		const tabId = '#' + tabName;

		// Remove active class from all tabs and contents
		$('.nav-tab').removeClass('nav-tab-active');
		$('.tab-content').removeClass('active');

		// Add active class to clicked tab and corresponding content
		$(this).addClass('nav-tab-active');
		$(tabId).addClass('active');
	});

	// Set first tab as active on page load
	$('.nav-tab').first().addClass('nav-tab-active');
	$('.tab-content').first().addClass('active');
});
