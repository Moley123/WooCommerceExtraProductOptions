# WooCommerce Extra Product Options

A WordPress/WooCommerce plugin that enhances product pricing display with handling fees and "From..." price prefix for variable products.

## Features

### Lowest Price Display
- Shows the minimum price for variable products with a customizable "From..." prefix
- Price updates dynamically when customers select different variations
- Works with all WooCommerce themes

### Handling Fees
- Add handling fees to simple products
- Add handling fees to variable products (parent level)
- Override handling fees for individual variations
- Choose between **fixed amount** or **percentage-based** fees
- Customize the handling fee label (e.g., "Processing Fee", "Service Charge")

### Price Breakdown
- Display price breakdown showing base price, handling fee, and total
- Show breakdown on product pages, cart, and checkout
- Use the `[wcepo_price_breakdown]` shortcode anywhere

### Multi-Currency Support
- Integrates with Vignette Currency Converter (VCC) plugin
- Automatically converts prices to customer's selected currency
- Supports GBP, EUR, USD, CAD, AUD, JPY, and more

### Flexible Display Options
- Include handling fee in the displayed price
- Show handling fee as a separate line item
- Customize the "From" text

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-extra-product-options/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure settings at **WooCommerce > Extra Product Options**

## Configuration

### Global Settings

Navigate to **WooCommerce > Extra Product Options** to configure:

| Setting | Description |
|---------|-------------|
| **From Text** | Text displayed before minimum price (e.g., "From", "Starting at") |
| **Show "From" Text** | Enable/disable the "From" prefix |
| **Handling Fee Label** | Label for the handling fee (e.g., "Handling Fee", "Processing Fee") |
| **Include in Price Display** | Include handling fee in the displayed product price |
| **Show Fee Separately** | Show handling fee as a separate line item |

### Product-Level Settings

#### Simple Products
1. Edit the product in WooCommerce
2. Find the **Handling Fee** field in the General tab
3. Enter the fee amount and select the fee type (Fixed/Percentage)

#### Variable Products
1. Edit the product in WooCommerce
2. In the General tab, set the default handling fee for all variations
3. Optionally, override per variation in the Variations tab

## Shortcodes

### Price Breakdown Shortcode

Display a price breakdown anywhere on your site:

```
[wcepo_price_breakdown]
[wcepo_price_breakdown product_id="123"]
[wcepo_price_breakdown show_total="yes" layout="horizontal"]
```

#### Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `product_id` | Current product | Specific product ID to display |
| `show_total` | `yes` | Show total row (`yes`/`no`) |
| `layout` | `vertical` | Layout style (`vertical`/`horizontal`) |
| `class` | ` ` | Additional CSS class |

### Programmatic Usage

```php
$price_display = WCEPO_Price_Display::get_instance();

// Get breakdown data array
$breakdown = $price_display->get_price_breakdown($product);

// Render breakdown HTML
echo $price_display->render_price_breakdown($product, array(
    'show_total' => true,
    'layout' => 'vertical'
));
```

## Data Storage

### Product Meta Keys

| Key | Type | Description |
|-----|------|-------------|
| `_wcepo_handling_fee` | float | Handling fee amount |
| `_wcepo_handling_fee_type` | string | `fixed` or `percentage` |
| `_wcepo_use_parent_fee` | string | `yes`/`no` - variation uses parent fee |

### Plugin Options

| Option | Default | Description |
|--------|---------|-------------|
| `wcepo_handling_fee_label` | "Handling Fee" | Display label for the fee |
| `wcepo_from_text` | "From" | Text before minimum price |
| `wcepo_show_from_text` | "yes" | Show/hide "From" text |
| `wcepo_include_handling_in_price` | "yes" | Include fee in displayed price |
| `wcepo_show_handling_fee_separately` | "no" | Show fee as separate line |

## Compatibility

- **WordPress**: 5.0+
- **WooCommerce**: 5.0 - 8.0+
- **PHP**: 7.4+
- **HPOS**: Fully compatible with WooCommerce High-Performance Order Storage

## Hooks & Filters

### Filters
- `woocommerce_variable_price_html` - Modify variable product price display
- `woocommerce_get_price_html` - Modify simple product price display
- `woocommerce_available_variation` - Add custom data to variation JSON

### Actions
- `woocommerce_product_options_pricing` - Add fields to simple product
- `woocommerce_product_options_general_product_data` - Add fields to variable product
- `woocommerce_variation_options_pricing` - Add fields to variations
- `woocommerce_before_calculate_totals` - Modify cart prices
- `woocommerce_checkout_create_order_line_item` - Save fee to order

## File Structure

```
woocommerce-extra-product-options/
├── woocommerce-extra-product-options.php  # Main plugin file
├── includes/
│   ├── class-wcepo-admin.php              # Admin product meta boxes
│   ├── class-wcepo-settings.php           # Settings page
│   ├── class-wcepo-frontend.php           # Frontend display & cart handling
│   └── class-wcepo-price-display.php      # Price calculations & HTML output
├── assets/
│   ├── js/
│   │   ├── admin.js                       # Admin JavaScript
│   │   └── frontend.js                    # Frontend JavaScript
│   └── css/
│       ├── admin.css                      # Admin styles
│       └── frontend.css                   # Frontend styles
├── CHANGELOG.md                           # Version history
├── CLAUDE.md                              # Developer documentation
├── README.md                              # This file
└── readme.txt                             # WordPress plugin readme
```

## Frequently Asked Questions

### How do I add a handling fee to a product?

1. Edit the product in WooCommerce
2. For simple products: Find the "Handling Fee" field in the General tab
3. For variable products: Set the fee in the General tab (applies to all variations by default), or override per variation

### Can I use different handling fees for different variations?

Yes! When editing a variable product, go to the Variations tab. For each variation, you can either use the parent product's handling fee or set a custom fee by unchecking "Use parent handling fee".

### How does the plugin work with multi-currency?

The plugin integrates with the Vignette Currency Converter (VCC) plugin. When VCC is active, all prices (including handling fees) are automatically converted to the customer's selected currency.

### Can I show the handling fee separately from the product price?

Yes! In the plugin settings, enable "Show Fee Separately" to display the handling fee as a separate line item on the product page and in the cart.

## Support

For issues and feature requests, please visit:
https://github.com/Moley123/WooCommerceExtraProductOptions/issues

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

**Mark Lebrett**
- Website: https://marklebrett.co.uk
- GitHub: https://github.com/Moley123
