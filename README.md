# WooCommerce Address Field Manager

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A professional WordPress plugin that adds dynamic thana (police station/sub-district) dropdown fields to WooCommerce checkout for Bangladesh addresses. Features 520+ thanas across all 64 districts with smart field conversion and comprehensive admin integration.

## 🎯 Features

### Core Functionality
- **520+ Bangladesh Thanas**: Complete coverage of all districts (BD-01 to BD-64)
- **Dynamic Field Conversion**: Automatically switches between dropdown (Bangladesh) and text input (other countries)
- **Dual Address Support**: Independent configuration for billing and shipping addresses
- **Smart Prefilling**: Automatically fills thana from user profile or session data
- **AJAX Compatible**: Preserves selections during checkout updates

### Admin Features
- **Editable Order Fields**: Modify thana directly in WooCommerce order edit page
- **Dynamic Dropdowns**: Admin dropdowns populate based on selected district
- **Plugin Updater Tool**: One-click cache refresh to check for updates instantly
- **Comprehensive Settings**: Tabbed interface for billing, shipping, and updater configuration
- **Customizable Display**: Control labels, placeholders, positioning, and validation

### Technical Excellence
- **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage
- **Fresh Data Reads**: No caching issues - always displays current thana data
- **Select2 Integration**: Enhanced dropdown experience when available
- **Responsive Design**: Mobile-friendly interface
- **Modular Architecture**: Easy to extend and customize

## 📦 Installation

### Method 1: Download from GitHub
1. Download the latest release ZIP from [Releases](https://github.com/imranduzzlo/woocommerce-address-field-manager/releases)
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin

### Method 2: Manual Installation
1. Download or clone this repository
2. Upload the `woocommerce-address-field-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### Method 3: Git Clone
```bash
cd wp-content/plugins/
git clone https://github.com/imranduzzlo/woocommerce-address-field-manager.git woocommerce-address-field-manager
```

## 🚀 Quick Start

1. **Activate the Plugin**: Go to Plugins → Installed Plugins and activate "WooCommerce Address Field Manager"

2. **Configure Settings**: Navigate to WooCommerce → Address Field Manager
   - **Billing Address Tab**: Configure billing thana field
   - **Shipping Address Tab**: Configure shipping thana field
   - **Plugin Updater Tab**: Refresh update cache when needed

3. **Test on Checkout**: 
   - Go to your WooCommerce checkout page
   - Select Bangladesh as country
   - Choose a district/state (e.g., Dhaka)
   - Thana dropdown will automatically populate

## ⚙️ Configuration

### Billing & Shipping Settings

Each address type can be configured independently:

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Field** | Show/hide thana field | Enabled |
| **Field Name** | Internal field identifier | `billing_thana` / `shipping_thana` |
| **Display After** | Position after which field | `billing_state` / `shipping_state` |
| **Wrapper Class** | CSS classes for styling | `form-row-wide wafm-thana-field` |
| **Field Label** | Display label text | `Thana` |
| **Show Label** | Display or hide label | Enabled |
| **Placeholder (Select)** | Dropdown placeholder | `Select Thana` |
| **Placeholder (Input)** | Text input placeholder | `Enter Thana` |
| **Required** | Make field mandatory | Disabled |

### Plugin Updater

- **Current Version**: Displays installed plugin version
- **Last Update Check**: Shows when WordPress last checked for updates
- **Refresh Cache**: Force immediate update check with one click

## 📊 How It Works

### For Customers

1. Customer selects **Bangladesh** as country
2. Customer selects their **district/state** (e.g., BD-13 for Dhaka)
3. **Thana dropdown** automatically populates with relevant options
4. Customer selects their specific thana
5. Thana is saved with the order and user profile

### For Other Countries

- Field automatically converts to a **text input**
- Customers can manually enter their locality/area
- Seamless experience regardless of country

### Data Structure

Thanas are organized by district codes in `data/thana.json`:

```json
{
    "BD-13": {
        "BD-13-01": "Dhamrai",
        "BD-13-02": "Dohar",
        "BD-13-06": "Adabor",
        "BD-13-16": "Gulshan"
    }
}
```

- **Key**: District code (e.g., "BD-13" for Dhaka)
- **Value**: Object with thana codes and names
- **Storage**: Codes stored in database, names displayed in UI

## 🛠️ Admin Integration

### Order Edit Page

When editing an order in WooCommerce:
- Thana fields appear as **editable dropdowns**
- Dropdowns populate based on selected district
- Current thana value is automatically selected
- Changes save when order is updated
- Works with both traditional and HPOS orders

### Order Display

Thana information appears in:
- ✅ Admin order details page
- ✅ Order confirmation emails
- ✅ Thank you page
- ✅ Customer account order view
- ✅ Formatted addresses

## 🎨 Customization

### Styling

Edit `assets/css/thana-selector.css` to customize appearance:

```css
.wafm-thana-field select,
.wafm-thana-field input {
    /* Your custom styles */
}
```

### JavaScript Hooks

Hook into field changes:

```javascript
jQuery(document).on('change', '#billing_state', function() {
    // Custom logic when state changes
});
```

### PHP Filters

Available filters for developers:

```php
// Modify thana data
add_filter('WAFM_thana_data', function($thana_data) {
    // Modify or add thana data
    return $thana_data;
});
```

## 🌍 Adding New Countries

To add support for additional countries:

1. **Update supported countries** in `includes/class-wafm-checkout-fields.php`:
```php
private static $supported_countries = array( 'BD', 'IN' );
```

2. **Add country data** to `data/thana.json`:
```json
{
    "IN-DL": {
        "IN-DL-01": "New Delhi",
        "IN-DL-02": "Central Delhi"
    }
}
```

3. Update field labels and validation as needed

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **WooCommerce**: 8.0 or higher (tested up to 10.7.0)
- **PHP**: 7.4 or higher
- **Browsers**: All modern browsers (Chrome, Firefox, Safari, Edge)

## 🔧 Troubleshooting

### Thana field not appearing

1. Ensure WooCommerce is installed and activated
2. Check that customer is on checkout page
3. Verify country is set to Bangladesh (BD)
4. Clear browser and WordPress cache

### Thana dropdown not populating

1. Verify `data/thana.json` exists and is readable
2. Check browser console for JavaScript errors
3. Ensure state/district code matches JSON keys (e.g., "BD-13")
4. Clear all caches

### Field not converting properly

1. Check that jQuery is loaded
2. Verify WooCommerce checkout scripts are loaded
3. Clear all caches (browser, WordPress, plugins)

### Admin order edit not showing thana

1. Ensure you're editing an order (not creating new)
2. Check that thana was saved during checkout
3. Verify HPOS compatibility if using new order tables

## 📁 File Structure

```
woocommerce-address-field-manager/
├── woocommerce-address-field-manager.php    # Main plugin file
├── includes/
│   ├── class-wafm-main.php            # Main initialization class
│   ├── class-wafm-checkout-fields.php # Checkout field handler
│   ├── class-wafm-assets.php          # Assets manager
│   └── class-wafm-settings.php        # Settings page handler
├── assets/
│   ├── js/
│   │   ├── thana-selector.js         # Frontend logic
│   │   ├── order-edit.js             # Admin order edit
│   │   └── admin-settings.js         # Settings page
│   └── css/
│       ├── thana-selector.css        # Frontend styles
│       └── admin-settings.css        # Admin styles
├── data/
│   └── thana.json                    # Thana database (520+ entries)
├── README.md                          # This file
├── CHANGELOG.md                       # Version history
└── deploy-to-github.ps1              # Deployment script
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed version history.

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Imran Hossain**
- Website: [imranhossain.me](https://imranhossain.me)
- GitHub: [@imranduzzlo](https://github.com/imranduzzlo)

## 🙏 Acknowledgments

- Thanks to the WooCommerce team for their excellent documentation
- Bangladesh government for official thana/upazila data
- WordPress community for continuous support

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/imranduzzlo/woocommerce-address-field-manager/issues)
- **Documentation**: [GitHub Wiki](https://github.com/imranduzzlo/woocommerce-address-field-manager/wiki)
- **Email**: Contact through GitHub profile

---

Made with ❤️ for the Bangladesh WooCommerce community

## Features

- **Automatic Thana Selection**: Dynamically shows thana dropdown based on selected state/district
- **Dual Address Support**: Works independently for both billing and shipping addresses
- **Smart Field Conversion**: Automatically converts between select and input fields based on country
- **WooCommerce Compatible**: Follows WooCommerce standards and best practices
- **Future-Proof**: Easily extensible to support additional countries
- **Select2 Integration**: Optional Select2 support for enhanced dropdown experience
- **Responsive Design**: Mobile-friendly interface
- **520+ Thanas**: Comprehensive coverage of Bangladesh police stations

## Installation

1. Download the plugin folder `woocommerce-address-field-manager`
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin from WordPress admin panel
4. The thana fields will automatically appear on WooCommerce checkout for Bangladesh addresses

## How It Works

### For Customers

1. Customer selects their country (Bangladesh)
2. Customer selects their state/district (e.g., BD-13 for Dhaka)
3. Thana dropdown automatically populates with available police stations
4. Customer selects their specific thana
5. Thana information is saved with the order

### For Developers

The plugin uses a modular architecture:

- **class-wafm-main.php**: Main plugin initialization
- **class-wafm-checkout-fields.php**: Handles checkout field registration and validation
- **class-wafm-assets.php**: Manages script and style loading
- **thana-selector.js**: Frontend logic for field conversion and population
- **thana.json**: Thana data organized by district code

## Adding New Countries

To add support for additional countries:

1. Add country code to `$supported_countries` array in `class-wafm-checkout-fields.php`
2. Add country data to `data/thana.json` with format: `"COUNTRY-CODE": { "REGION-CODE": "Region Name", ... }`
3. Update field labels and validation as needed

Example for India:
```php
private static $supported_countries = array( 'BD', 'IN' );
```

Then add to `thana.json`:
```json
{
    "BD-13": { ... },
    "IN-DL": { "IN-DL-01": "New Delhi", ... }
}
```

## Data Structure

### thana.json Format

```json
{
    "BD-13": {
        "BD-13-01": "Dhamrai",
        "BD-13-02": "Dohar",
        ...
    },
    "BD-27": {
        "BD-27-01": "Batiaghata",
        ...
    }
}
```

- **Key**: District code (e.g., "BD-13" for Dhaka)
- **Value**: Object with thana codes and names

## Customization

### Styling

Edit `assets/css/thana-selector.css` to customize appearance:

```css
.wafm-thana-field select,
.wafm-thana-field input {
    /* Your custom styles */
}
```

### JavaScript Hooks

The plugin uses jQuery events that can be hooked into:

```javascript
jQuery(document).on('change', '#billing_state', function() {
    // Custom logic when state changes
});
```

## Compatibility

- **WordPress**: 6.0+
- **WooCommerce**: 8.0+ (tested up to 10.7.0)
- **PHP**: 7.4+
- **Browsers**: All modern browsers (Chrome, Firefox, Safari, Edge)
- **HPOS**: Fully compatible with WooCommerce High-Performance Order Storage

## Support for Select2

If Select2 is available (usually included with WooCommerce), the plugin automatically initializes it for enhanced dropdown experience.

## Troubleshooting

### Thana field not appearing

1. Ensure WooCommerce is installed and activated
2. Check that customer is on checkout page
3. Verify country is set to Bangladesh (BD)
4. Clear browser cache

### Thana dropdown not populating

1. Verify `data/thana.json` exists and is readable
2. Check browser console for JavaScript errors
3. Ensure state/district code matches JSON keys (e.g., "BD-13")

### Field not converting properly

1. Check that jQuery is loaded
2. Verify WooCommerce checkout scripts are loaded
3. Clear all caches (browser, WordPress, plugins)

## File Structure

```
woocommerce-address-field-manager/
├── woocommerce-address-field-manager.php    # Main plugin file
├── includes/
│   ├── class-wafm-main.php            # Main class
│   ├── class-wafm-checkout-fields.php # Checkout fields handler
│   └── class-wafm-assets.php          # Assets handler
├── assets/
│   ├── js/
│   │   └── thana-selector.js         # Frontend logic
│   └── css/
│       └── thana-selector.css        # Styles
├── data/
│   └── thana.json                    # Thana data
├── languages/                         # Translation files
└── README.md                          # This file
```

## License

GPL v2 or later

## Author

Your Name

## Changelog

### Version 1.0.0 (2026-04-26)
- **Initial Release**: First public release of WooCommerce Address Field Manager
- **Core Features**: Dynamic thana selection for Bangladesh addresses
- **520+ Thanas**: Complete coverage of all Bangladesh districts
- **Admin Integration**: Editable thana fields in order edit page
- **Plugin Updater**: Built-in update cache refresh tool
- **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage
- **Smart Prefilling**: Automatic field population from user data
- **Responsive Design**: Mobile-friendly interface
- **Select2 Support**: Enhanced dropdown experience
