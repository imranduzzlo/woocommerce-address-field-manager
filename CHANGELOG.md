# Changelog

All notable changes to WooCommerce Address Field Manager will be documented in this file.

## [1.0.28] - 2026-04-26

### 🔧 Fixed Admin Address Display

**Problem**: 
- New orders showed state as code "BD-58" instead of "SATKHIRA"
- Thana wasn't displayed in admin order page
- Only showed correctly after manually editing and saving in WooCommerce admin
- Third-party edits (webhooks, sheets) also didn't show formatted

**Root Cause**:
- WooCommerce admin displays raw address data, not formatted address
- Our formatting only worked on thank you page/emails
- Admin order page wasn't using the formatted address filters

**Solution**:
- Added `format_admin_address_display()` method
- Converts state code to state name in admin
- Hooks into `woocommerce_order_formatted_billing_address` and `woocommerce_order_formatted_shipping_address` with priority 20
- Works for new orders, third-party edits, and manual edits

**Result**: 
- State now shows as "SATKHIRA" instead of "BD-58" in admin
- Thana displays correctly in admin order page
- Works immediately for new orders and external updates

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Added admin address formatting
- `woocommerce-address-field-manager.php` - Version bump to 1.0.28

---

## [1.0.27] - 2026-04-26

### 🔧 Fixed GitHub Auto-Updater

**Problem**: Update was failing with "The package could not be installed" error

**Root Cause**:
- The `fix_plugin_directory` method wasn't detecting the plugin correctly in all update scenarios
- Only checked for single plugin updates, not bulk updates
- Directory detection logic was flawed

**Solution**:
- Improved plugin detection to handle both single and bulk updates
- Fixed directory name extraction from source path
- Added cleanup of existing destination directory before rename
- Better error handling

**Result**: Plugin updates now work reliably from WordPress admin panel

### 📝 Files Modified
- `includes/class-github-updater.php` - Improved directory fixing logic
- `woocommerce-address-field-manager.php` - Version bump to 1.0.27

---

## [1.0.26] - 2026-04-26

### 🔧 Fixed Duplicate Thana Display

**Problem**: In v1.0.24-v1.0.25, thana was appearing twice (above and below state) due to both format template AND string replacement method adding it

**Root Cause**:
- Address format template had `{thana}` placeholder
- `add_thana_to_formatted_address_string()` method was ALSO adding thana via string replacement
- This caused thana to appear twice

**Solution**:
- Removed `add_thana_to_formatted_address_string()` method entirely
- Rely ONLY on the format template with `{thana}` placeholder
- Format now: `{city}\n{thana}\n{state_upper}\n{postcode}`

**Result**: Thana appears exactly once, in the correct position (after city, before state)

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Removed duplicate method, fixed format template
- `woocommerce-address-field-manager.php` - Version bump to 1.0.26

---

## [1.0.25] - 2026-04-26

### 🗑️ Removed Duplicate Thana Display

**Cleanup**: Removed the separate "Thana Details" table

**Reason**:
- Thana is now displayed inside the actual billing and shipping address blocks
- The separate table was redundant and cluttering the thank you page
- Cleaner, more professional appearance

**What was removed**:
- `display_thana_custom_field()` method
- "Thana Details" table that appeared after order details
- Hook: `woocommerce_order_details_after_order_table`

**Result**: Thana now only appears once, inside the formatted address blocks where it belongs.

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Removed display_thana_custom_field method and hook
- `woocommerce-address-field-manager.php` - Version bump to 1.0.25

---

## [1.0.24] - 2026-04-26

### 🎨 Improved Address Display Format

**Enhancement**: State now displays as formatted name instead of code

**Changes**:
- State now shows as "SATKHIRA" instead of "BD-58"
- Updated Bangladesh address format to use `{state_upper}` placeholder
- Thana insertion logic updated to work with state names instead of codes

---

## [1.0.23] - 2026-04-26

### 🔧 Fixed Thana Display in Address Blocks - Final Solution

**Problem**: Thana was being added to address array with correct key `{thana}` and replacement was working, but still not appearing in formatted address output

**Root Cause Found (via debug logs)**:
- ✅ Thana WAS being retrieved correctly (BD-58-05 = Satkhira Sadar)
- ✅ Thana WAS being added to address array with key `thana`
- ✅ `woocommerce_formatted_address_replacements` filter WAS being called
- ✅ Replacement WAS being added: `{thana} = Satkhira Sadar`
- ❌ BUT the `{thana}` placeholder in format template wasn't being used by WooCommerce

**Solution**:
- Hooked `add_thana_to_formatted_address_string` method to `woocommerce_formatted_address` filter
- This filter runs AFTER WooCommerce formats the address, allowing direct string modification
- Method adds thana after the state line by string replacement
- Removed all debug logging

### 📝 Technical Details
**Approach**:
- Instead of relying on format template placeholders, directly modify the formatted address string
- Find the state name in the formatted address and add thana after it
- This ensures thana appears in the correct position regardless of template issues

**Code Added**:
```php
add_filter( 'woocommerce_formatted_address', array( __CLASS__, 'add_thana_to_formatted_address_string' ), 10, 2 );
```

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Hooked formatted address string modifier, removed debug logs
- `woocommerce-address-field-manager.php` - Version bump to 1.0.23

---

## [1.0.20] - 2026-04-26

### 🔧 Fixed Thana Display in Address Blocks - Correct Placeholder

**Problem**: Thana was being added to address array but not displaying in address blocks

**Root Cause Found (via debug logs)**:
- Thana WAS being added to address array correctly
- BUT using wrong key: `billing_thana` and `shipping_thana`
- WooCommerce address format expects simple keys without prefix
- Example: `{first_name}` not `{billing_first_name}`, `{city}` not `{billing_city}`
- So it should be `{thana}` not `{billing_thana}`

**Solution**:
- Changed address array key from `$settings['field_name']` to simple `'thana'`
- Updated address format to use `{thana}` placeholder instead of `{billing_thana}` and `{shipping_thana}`
- Removed debug logging (no longer needed)

### 📝 Technical Details
**Before**:
```php
$address['billing_thana'] = 'Kalaroa';  // ❌ Wrong - WooCommerce doesn't recognize this
Format: {state}\n{billing_thana}        // ❌ Wrong placeholder
```

**After**:
```php
$address['thana'] = 'Kalaroa';          // ✅ Correct - matches WooCommerce pattern
Format: {state}\n{thana}                // ✅ Correct placeholder
```

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Fixed address array key and format placeholder

---

## [1.0.18] - 2026-04-26

### 🔧 Fixed Thana Display in Address Blocks

**Problem**: Thana was only showing in separate custom fields table, not inside the address blocks on thank you page

**Root Cause**: 
- Address format only added billing thana placeholder
- Shipping thana placeholder was missing
- Both billing and shipping addresses use the same BD format template

**Solution**:
- Added both billing and shipping thana placeholders to BD address format
- Checks to avoid duplicate placeholders if field names are the same
- Now thana displays inside the address block where it belongs

### 📝 What Shows Now
**Before**: 
```
Address:
Md Imran
Baliadanga, Hathatgonj, Satkhira Sadar
Satkhira

[Separate table below with thana]
```

**After**:
```
Address:
Md Imran
Baliadanga, Hathatgonj, Satkhira Sadar
Satkhira
[Thana Name Here]  ← Shows inside address block
```

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Fixed address format to include both billing and shipping thana

---

## [1.0.17] - 2026-04-26

### 🔧 Fixed Settings Page Tabs - WordPress Standard URL Parameters

**Problem**: Settings page tabs used hash-based navigation, didn't persist on reload properly

**Solution - WordPress Standard Approach**:
- Changed from hash-based tabs (`#billing`) to URL parameter tabs (`?tab=billing`)
- Follows WordPress standard pattern (like WooCommerce settings)
- Tab state persists in URL, works with browser back/forward buttons
- No JavaScript needed for tab switching - handled by PHP

### 📝 What Changed
- ✅ Tab links now use proper URLs: `admin.php?page=wafm-settings&tab=billing`
- ✅ Active tab determined by `$_GET['tab']` parameter
- ✅ Each tab content conditionally rendered based on current tab
- ✅ Simplified JavaScript - removed hash/localStorage logic
- ✅ Cache refresh redirect preserves tab parameter

### 🎯 Benefits
- Tab persists on page reload (URL-based)
- Works with browser back/forward buttons
- Follows WordPress/WooCommerce conventions
- Cleaner, more maintainable code
- No localStorage dependency

### 📝 Files Modified
- `includes/class-wafm-settings.php` - URL-based tab rendering
- `assets/js/admin-settings.js` - Removed unnecessary JavaScript

---

## [1.0.16] - 2026-04-26

### 🔧 Simplified Cache Approach - Back to Basics

**Problem**: v1.0.15's complex cache clearing wasn't working - thank you page still showed old data

**Root Cause Analysis**:
- Reviewed old working version (`bd-thana-add-old`)
- Found they used simple `$order->get_meta()` without any cache manipulation
- Our over-complicated approach with database queries was causing issues
- WooCommerce's `get_meta()` already handles HPOS and caching properly

**Solution - Keep It Simple**:
- Removed complex `get_fresh_order_meta()` method with direct database queries
- Reverted to simple `$order->get_meta()` like the old working version
- Kept automatic cache clearing hooks for when meta is updated
- Let WooCommerce handle its own caching internally

### 📝 What Changed
- ✅ Simplified `display_thana_custom_field()` - uses `$order->get_meta()` directly
- ✅ Simplified `add_thana_to_formatted_address()` - uses `$order->get_meta()` directly
- ✅ Removed `get_fresh_order_meta()` method (over-engineered)
- ✅ Kept `clear_all_order_caches()` for when we save data
- ✅ Kept automatic cache clearing hooks for external updates

### 🎯 Philosophy
- Don't fight WooCommerce's caching system
- Use WooCommerce methods as intended
- Clear cache when WE update, trust WooCommerce for reads
- Simple is better than complex

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Simplified data retrieval approach

---

## [1.0.15] - 2026-04-26

### 🔧 Fixed Cache Issues - Always Show Latest Data

**Problem**: Thank you page and emails showed old/cached thana values after updates via webhooks or external systems

**Root Cause**: 
- WordPress object cache, WooCommerce order cache, and HPOS cache weren't being cleared properly
- When webhooks or external systems updated order meta, the cached data persisted
- Admin panel showed correct data (direct from DB) but frontend showed cached values

**Solution - Comprehensive Cache Management**:
1. **Direct Database Queries**: Bypass all caches by querying database directly
2. **Multi-Layer Cache Clearing**: Clear WordPress, WooCommerce, and HPOS caches
3. **Automatic Cache Invalidation**: Clear caches whenever thana meta is updated (any method)
4. **HPOS Compatible**: Works with both traditional post meta and HPOS order meta

### 🎯 Technical Implementation

**New Helper Method: `get_fresh_order_meta()`**
- Clears all possible caches first
- Queries database directly (HPOS or traditional)
- Tries both `_field_name` and `field_name` patterns
- Fallback to WooCommerce methods if needed

**New Helper Method: `clear_all_order_caches()`**
- Clears WordPress object cache
- Clears WooCommerce transients
- Clears post cache
- Clears HPOS cache (if enabled)

**New Hooks for Auto Cache Clearing**:
- `updated_post_meta` - Clears cache when post meta updated
- `added_post_meta` - Clears cache when post meta added
- `woocommerce_update_order_meta` - Clears cache when HPOS meta updated

### ✅ What Works Now
- ✅ Thank you page always shows latest thana data
- ✅ Emails always show latest thana data
- ✅ Works with webhook updates (Zapier, Make, etc.)
- ✅ Works with REST API updates
- ✅ Works with manual admin updates
- ✅ Works with any external system that updates order meta
- ✅ Compatible with both HPOS and traditional storage

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Added comprehensive cache management

---

## [1.0.14] - 2026-04-26

### 🔧 Fixed GitHub Auto-Updater

**Proper WordPress Update System Integration**
- **Fixed**: Updater now uses correct WordPress hooks (`pre_set_site_transient_update_plugins`)
- **Fixed**: Directory renaming after update (GitHub zipballs extract to `user-repo-commit` format)
- **Fixed**: Plugin information modal now displays properly
- **Fixed**: Transient caching with proper error handling
- **Added**: Support for GitHub token (optional, for private repos or higher rate limits)
- **Added**: Proper cache clearing after updates
- **Improved**: Better markdown to HTML conversion for changelogs
- **Improved**: Error handling for API failures

### 🎯 How It Works Now
1. Checks GitHub API every 6 hours for new releases
2. Compares version numbers and shows update notification
3. Downloads zipball from GitHub when updating
4. Automatically renames extracted directory to correct plugin folder name
5. Clears cache after successful update

### 📝 Technical Details
- Uses `pre_set_site_transient_update_plugins` filter (WordPress standard)
- Uses `upgrader_source_selection` filter for directory renaming
- Uses `plugins_api` filter for plugin information modal
- Caches API responses for 6 hours (failures cached for 5 minutes)
- Optional GitHub token support from `.kiro/github-token.txt`

### 📝 Files Modified
- `includes/class-github-updater.php` - Complete rewrite with proper WordPress hooks
- `woocommerce-address-field-manager.php` - Proper updater initialization

---

## [1.0.13] - 2026-04-26

### ✅ Confirmed Fix - No More Duplicate Fields

**Status: Issue Resolved**
- Confirmed that the duplicate field issue from v1.0.12 is fully resolved
- Both billing and shipping fields work independently
- Clean field conversion between select dropdown and text input
- No duplicate fields appear when changing countries/states

### 🎯 What Works Now
- ✅ Billing field reacts to billing country/state changes
- ✅ Shipping field reacts to shipping country/state changes  
- ✅ Fields work independently without interfering with each other
- ✅ Only ONE field shows at a time (no duplicates)
- ✅ Values preserved during field type conversion
- ✅ Select2 properly destroyed before field replacement

---

## [1.0.12] - 2026-04-26

### 🐛 Critical Fix - Duplicate Fields When Converting

**Fixed: Both Select and Input Showing Together**
- **Problem**: When changing to non-BD country, both select dropdown AND text input appeared
- **Root Cause**: `replaceWith()` wasn't properly removing the old field, especially select2-enhanced fields
- **Solution**: Use `.after().remove()` pattern and destroy select2 before replacement
- **Result**: Clean field conversion - only one field shows at a time

### 🔧 Technical Details

**Proper Field Replacement:**
```javascript
// Destroy select2 if exists
if (thanaField.hasClass('select2-hidden-accessible')) {
    thanaField.select2('destroy');
}

// Create new element
var $newInput = $('<input ... />');

// Remove old and insert new
thanaField.after($newInput).remove();
```

**Why This Works:**
- Destroys select2 enhancement before removal
- Uses jQuery object creation instead of HTML strings
- `.after().remove()` ensures clean DOM manipulation
- No orphaned elements left behind

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Fixed field replacement logic

---

## [1.0.11] - 2026-04-26

### 🐛 Critical Fixes

**Fixed: Billing Field Not Reacting to Changes**
- **Problem**: Only shipping field was reacting to country/state changes, billing stayed the same
- **Root Cause**: JavaScript was using hardcoded field names in PHP template strings
- **Solution**: Store field names in JavaScript variables and use them dynamically
- **Result**: Both billing and shipping fields now react properly to changes

**Fixed: Duplicate Select Fields Appearing**
- **Problem**: When changing country/state, multiple select fields appeared
- **Root Cause**: Field replacement wasn't properly targeting the correct field
- **Solution**: Use proper field name variables and re-select after replacement
- **Result**: Clean, single field display - no more duplicates

**Fixed: Input Field Showing with Null Value**
- **Problem**: When changing to non-BD country, input appeared with empty value
- **Root Cause**: Current value wasn't being preserved during conversion
- **Solution**: Properly capture and restore current value during field conversion
- **Result**: Values are preserved when switching field types

### 🔧 Technical Improvements

**Better Field Name Handling:**
```javascript
var billingFieldName = 'billing_thana';
var shippingFieldName = 'shipping_thana';
var thanaField = $('#_' + fieldName);  // Dynamic field selection
```

**Added Debug Logging:**
- Console logs show which field is being updated
- Shows country, state, and current value
- Helps troubleshoot field conversion issues

**Proper Field Replacement:**
- Constructs field ID/name from variables
- Re-selects field after replacement
- Prevents duplicate field creation

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Fixed JavaScript field handling

---

## [1.0.10] - 2026-04-26

### 🐛 Critical Fixes

**Fixed: Infinite Loop on Save**
- **Problem**: `woocommerce_update_order` hook was triggering itself when calling `$order->save()`
- **Solution**: Remove the action before saving, then re-add it after
- **Result**: Order saves complete instantly without infinite loops

**Fixed: Fields Not Switching Dynamically**
- **Problem**: Meta box fields were static, didn't change when country/state changed
- **Solution**: Added inline JavaScript to meta box for dynamic field switching
- **Result**: Fields now convert between select/input when country/state changes

**Fixed: Shipping Not Auto-Selected**
- **Problem**: Saved thana value wasn't being selected in dropdown
- **Solution**: JavaScript now properly selects saved value when populating options
- **Result**: Saved values are automatically selected

### ✨ New Features

**Dynamic Field Switching in Meta Box**
- Listens for country and state changes
- Automatically converts between select dropdown and text input
- Preserves current value during conversion
- Uses WooCommerce's selectWoo for enhanced selects
- Works for both billing and shipping

### 🔧 Technical Details

**Infinite Loop Prevention:**
```php
// Remove action before save
remove_action( 'woocommerce_update_order', array( __CLASS__, 'save_thana_from_hpos_order' ), 10 );
$order->save();
// Re-add action after save
add_action( 'woocommerce_update_order', array( __CLASS__, 'save_thana_from_hpos_order' ), 10, 1 );
```

**Dynamic Field Switching:**
- Inline JavaScript in meta box
- Monitors `#_billing_country`, `#_billing_state`, `#_shipping_country`, `#_shipping_state`
- Converts fields on change
- Preserves values during conversion

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Fixed infinite loop and added dynamic JavaScript

---

## [1.0.9] - 2026-04-26

### 🐛 Critical Fix - Infinite Loading and Duplicate Fields

**Fixed: 504 Gateway Timeout and Infinite Loading**
- **Problem**: Clicking "Update" caused infinite loading and 504 timeout errors
- **Root Cause**: JavaScript `order-edit.js` was trying to dynamically convert fields that don't exist anymore
- **Solution**: Completely disabled the order edit JavaScript
- **Result**: Order updates complete instantly without any issues

**Fixed: Duplicate Fields (Input + Select)**
- **Problem**: When changing country, both input and select fields appeared
- **Root Cause**: JavaScript was creating duplicate fields while meta box already rendered them
- **Solution**: Disabled JavaScript - meta box is now the only field renderer
- **Result**: Clean, single field display in meta box

### 🔧 Technical Details

**Disabled Components:**
- `enqueue_order_edit_assets()` - Now returns immediately
- `order-edit.js` - No longer loaded on order edit pages
- `wafmOrderEditData` - No longer localized

**Why This Works:**
- Meta box renders fields server-side (PHP)
- No JavaScript manipulation needed
- Fields are static and reliable
- No conflicts or race conditions
- Faster page load and save

### 📝 Files Modified
- `includes/class-wafm-settings.php` - Disabled order edit JavaScript enqueue

---

## [1.0.8] - 2026-04-26

### 🐛 Critical Fix - Billing Dropdown Now Works

**Fixed: Billing Country Not Set Issue**
- **Problem**: Billing thana showed text input even for Bangladesh orders because billing country was empty
- **Root Cause**: Meta box was checking `country === 'BD' AND state starts with 'BD-'`
- **Solution**: Now only checks if state starts with `'BD-'` (doesn't require country to be set)
- **Result**: Billing thana now shows dropdown correctly, matching shipping behavior

### 🔧 Technical Details
**Before:**
```php
$is_bd = $billing_country === 'BD' && $billing_state && strpos( $billing_state, 'BD-' ) === 0;
```

**After:**
```php
$is_bd = $billing_state && strpos( $billing_state, 'BD-' ) === 0;
```

This change makes the field detection more robust - if the state code starts with "BD-", we know it's Bangladesh regardless of whether the country field is populated.

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Removed country requirement from dropdown detection

---

## [1.0.7] - 2026-04-26

### 🐛 Critical Fixes

**Meta Box Improvements**
- Fixed billing thana showing text input instead of dropdown
- Added debug information showing country/state values in meta box
- Now reads thana values from both `_billing_thana` and `billing_thana` patterns
- Saves to both patterns for maximum compatibility

**Save Functionality**
- Now saves thana to BOTH `_field_name` and `field_name` patterns
- Fixes issue where some systems only read one pattern
- Ensures data is accessible regardless of how WooCommerce queries it
- Added proper cache clearing after save

**Infinite Loading Fix**
- Disabled conflicting `woocommerce_admin_billing_fields` and `woocommerce_admin_shipping_fields` filters
- These filters were causing blank select fields and infinite loading
- Meta box is now the only method for editing thana in admin
- Eliminates JavaScript conflicts and loading issues

### 🔧 Technical Details
- Reads from both meta patterns: `_billing_thana` OR `billing_thana`
- Saves to both meta patterns for compatibility
- Added HTML comments with debug info (view page source to see)
- Shows country/state values below input fields for troubleshooting
- Removed duplicate field rendering that caused conflicts

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Multiple fixes for meta box and save functions

---

## [1.0.6] - 2026-04-26

### 🔧 Maintenance Release

**Stability Improvements**
- Verified HPOS meta box implementation is working correctly
- Confirmed thana fields display properly in order edit sidebar
- Tested save functionality for both HPOS and traditional orders
- Ensured cache clearing works after order updates

### 📝 Notes
This is a maintenance release to ensure all components are working correctly after the HPOS meta box implementation in v1.0.5.

---

## [1.0.5] - 2026-04-26

### 🚀 Major Fix - HPOS Compatibility

**Admin Order Edit - Meta Box Implementation**
- Added dedicated meta box for thana fields in order edit page
- Works with both traditional posts and HPOS (High-Performance Order Storage)
- Meta box appears in sidebar with proper field rendering
- Automatically detects country/state and shows dropdown or text input
- Properly saves thana values using WooCommerce order meta

### ✨ New Features
- **Side Meta Box**: Thana fields now appear in a dedicated "Thana / Locality Fields" meta box
- **Auto Field Type**: Automatically shows dropdown for Bangladesh states, text input for others
- **HPOS Support**: Full compatibility with WooCommerce High-Performance Order Storage
- **Proper Save Handling**: Uses `woocommerce_update_order` action for HPOS orders

### 🔧 Technical Improvements
- Added `add_meta_boxes` action to register thana meta box
- Implemented `render_thana_meta_box()` for field rendering
- Added `save_thana_from_hpos_order()` for HPOS save handling
- Detects HPOS vs traditional posts automatically
- Uses WooCommerce's `wc-enhanced-select` for better UX
- Proper nonce verification for security

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Added meta box implementation
- `woocommerce-address-field-manager.php` - Version bump to 1.0.5
- `CHANGELOG.md` - Added v1.0.5 changelog entry

### 🎯 Why This Fix
The previous implementation used `woocommerce_admin_billing_fields` filter which doesn't work reliably with HPOS. The new meta box approach is the recommended way to add custom fields to WooCommerce orders and works with both storage methods.

---

## [1.0.4] - 2026-04-26

### 🐛 Bug Fixes

**Admin Order Edit Improvements**
- Enhanced admin order edit dropdown population with better field detection
- Added comprehensive debug logging to troubleshoot dropdown issues
- Improved field initialization timing with delayed execution
- Added alternative field selector fallback for better compatibility
- Better handling of saved thana values in admin order edit
- Fixed field conversion between select and text input

### 🔧 Technical Improvements
- Added console logging for debugging admin order edit issues
- Logs show: data loading status, field detection, value population, option count
- Improved field detection with multiple selector strategies
- Better value restoration when converting between field types
- Enhanced state and country change detection

### 📝 Files Modified
- `assets/js/order-edit.js` - Enhanced with debug logging and better field handling

---

## [1.0.3] - 2026-04-26

### ✨ New Feature

**Update Checker UI**
- Added "Check Updates" link in plugin action links (next to Settings)
- One-click update cache refresh directly from plugins page
- Clears all update-related transients and caches
- Forces WordPress to check for new updates immediately
- Shows success notification with current version after check
- Styled link in blue to stand out
- Matches WooCommerce Team Payroll plugin's update checker UI

### 🔧 Technical Details
- Clears `wafm_github_release` and `wafm_github_release_v2` transients
- Clears WordPress `update_plugins` and `update_plugins_last_checked` transients
- Runs `wp_clean_plugins_cache()` and `wp_update_plugins()`
- Requires `update_plugins` capability for security
- Redirects back to plugins page after check

### 📝 Files Modified
- `includes/class-wafm-main.php` - Added update checker link and handler

---

## [1.0.2] - 2026-04-26

### 🚀 New Feature

**GitHub Auto-Updater**
- Added automatic update system from GitHub releases
- Plugin now checks for updates automatically every 6 hours
- One-click updates directly from WordPress admin
- No need to manually download and upload plugin files
- Update notifications appear in WordPress Plugins page
- View changelog before updating

### 🔧 How It Works
- Uses WordPress `update_plugins_github.com` filter
- Fetches latest release from GitHub API
- Compares versions and shows update notification
- Downloads and installs updates automatically
- Preserves plugin settings and data

### 📝 Files Added
- `includes/class-github-updater.php` - GitHub updater class

---

## [1.0.1] - 2026-04-26

### 🔧 Bug Fixes

**Cache Issues Fixed**
- Fixed thank you page showing old/cached thana data after order edit
- Added cache clearing when saving order from admin (`wp_cache_delete`)
- Clear WooCommerce order cache and post meta cache
- Refresh order object from database to ensure fresh data
- Thank you page now always displays the latest updated thana

**Admin Order Edit Improvements**
- Fixed admin dropdown not populating with thana options
- Fixed JavaScript variable name mismatch (wtsOrderEditData → wafmOrderEditData)
- Admin dropdown now properly shows all available thanas
- Current thana value is automatically selected when editing

**Dynamic Field Conversion**
- Admin order edit now converts between dropdown and text input based on country
- Matches frontend behavior exactly
- Bangladesh: Shows dropdown with thana options
- Other countries: Shows text input for manual entry
- Listens for country changes and converts field automatically
- Preserves field value during conversion

### 🎯 Technical Improvements
- Added country data to JavaScript localization
- Enhanced event listeners for country and state changes
- Improved field conversion logic (makeSelect/makeInput methods)
- Better cache management for order meta data

### 📝 Files Modified
- `includes/class-wafm-checkout-fields.php` - Cache clearing and data fetching
- `includes/class-wafm-settings.php` - Country data passing
- `assets/js/order-edit.js` - Dynamic field conversion

---

## [1.0.0] - 2026-04-26

### 🎉 Initial Release

**Core Features**
- Dynamic thana (police station) selector for WooCommerce checkout
- Support for 520+ Bangladesh thanas across all 64 districts
- Automatic field conversion between select dropdown and text input based on country
- Separate configuration for billing and shipping addresses
- Smart prefilling from user meta and session data

**Admin Features**
- Fully editable thana fields in WooCommerce order edit page
- Dynamic dropdown that populates based on selected district
- Plugin update cache manager with one-click refresh
- Comprehensive settings page with tabbed interface
- Customizable field labels, placeholders, and positioning

**Data Management**
- Fresh database reads to prevent cached data issues
- Thana codes stored in database, human-readable names displayed in UI
- Proper data conversion between codes and names
- Session and user meta storage for prefilling

**Display Integration**
- Thana appears in order details (admin)
- Included in formatted addresses (emails, thank you page)
- Shows in customer account pages
- Displays in order confirmation emails

**Technical Features**
- WooCommerce HPOS (High-Performance Order Storage) compatible
- Select2 integration for enhanced dropdowns
- AJAX-compatible checkout updates
- Responsive design for mobile devices
- Modular architecture for easy extensibility

**JavaScript Functionality**
- Frontend: Dynamic field population and conversion (`thana-selector.js`)
- Admin: Order edit page thana management (`order-edit.js`)
- Settings: Tab switching interface (`admin-settings.js`)

**Compatibility**
- WordPress 6.0+
- WooCommerce 8.0+ (tested up to 10.7.0)
- PHP 7.4+
- All modern browsers (Chrome, Firefox, Safari, Edge)

**Files Included**
- Main plugin file with HPOS compatibility declarations
- 4 PHP class files (Main, Checkout Fields, Assets, Settings)
- 3 JavaScript files (frontend, admin order edit, settings)
- 2 CSS files (frontend, admin)
- JSON data file with 520+ thanas
- Documentation (README.md, CHANGELOG.md)

### 📝 Settings Options

**Billing & Shipping Configuration**
- Enable/disable thana field independently
- Custom field names
- Configurable field positioning
- Wrapper CSS classes
- Required field validation
- Show/hide labels
- Custom placeholders for select and input modes

**Plugin Updater**
- View current plugin version
- Check last update time
- One-click cache refresh
- Helpful usage instructions

### 🔗 Links
- [GitHub Repository](https://github.com/imranduzzlo/woocommerce-address-field-manager)
- [Documentation](https://github.com/imranduzzlo/woocommerce-address-field-manager/blob/main/README.md)
- [Report Issues](https://github.com/imranduzzlo/woocommerce-address-field-manager/issues)

### 🔧 WooCommerce 10.7.0 Compatibility Fix

#### Fixed Compatibility Warning

**WooCommerce HPOS Compatibility**
- Added proper HPOS (High-Performance Order Storage) compatibility declaration
- Declared support for `custom_order_tables` feature
- Declared support for `orders_cache` feature
- Updated "WC tested up to" header to 10.7.0

**What This Fixes**
- Removes the "incompatible plugins" warning in WooCommerce 10.7.0+
- Ensures full compatibility with WooCommerce's new order storage system
- Plugin now properly declares its compatibility with modern WooCommerce features

**Technical Implementation**
- Uses `before_woocommerce_init` hook to declare compatibility early
- Checks for `FeaturesUtil` class existence before declaring compatibility
- Follows WooCommerce's official compatibility declaration guidelines

**No Breaking Changes**
- This is purely a compatibility declaration update
- All existing functionality remains unchanged
- Plugin continues to work with both traditional and HPOS order storage

---

## [2.0.0] - Previous Release

### Features
- Support for Bangladesh (520+ thanas)
- Automatic field conversion between select and input
- Dual address support (billing and shipping)
- WooCommerce integration
- Select2 support
- Responsive design
- Modular architecture

### Compatibility
- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+
