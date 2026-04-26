/**
 * WooCommerce Address Field Manager - Admin Settings Script
 */

jQuery(function($) {
	'use strict';

	// Function to activate a specific tab
	function activateTab(tabName) {
		const tabId = '#' + tabName;

		// Remove active class from all tabs and contents
		$('.nav-tab').removeClass('nav-tab-active');
		$('.tab-content').removeClass('active');

		// Add active class to the specified tab and corresponding content
		$('.nav-tab[data-tab="' + tabName + '"]').addClass('nav-tab-active');
		$(tabId).addClass('active');

		// Update URL hash without scrolling
		if (history.pushState) {
			history.pushState(null, null, '#' + tabName);
		} else {
			window.location.hash = tabName;
		}

		// Save to localStorage
		localStorage.setItem('wafm_active_tab', tabName);
	}

	// Tab switching
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		const tabName = $(this).data('tab');
		activateTab(tabName);
	});

	// Restore active tab on page load
	let activeTab = null;

	// Priority 1: Check URL hash
	if (window.location.hash) {
		const hashTab = window.location.hash.substring(1);
		if ($('.nav-tab[data-tab="' + hashTab + '"]').length > 0) {
			activeTab = hashTab;
		}
	}

	// Priority 2: Check localStorage
	if (!activeTab) {
		const savedTab = localStorage.getItem('wafm_active_tab');
		if (savedTab && $('.nav-tab[data-tab="' + savedTab + '"]').length > 0) {
			activeTab = savedTab;
		}
	}

	// Priority 3: Default to first tab
	if (!activeTab) {
		activeTab = $('.nav-tab').first().data('tab');
	}

	// Activate the determined tab
	if (activeTab) {
		activateTab(activeTab);
	}
});
