# Changelog

All notable changes to WooCommerce Address Field Manager will be documented in this file.

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
