# WooCommerce Extra Product Options

## Overview
A WordPress/WooCommerce plugin that enhances product pricing display and adds handling fee functionality.

## Features
1. **Lowest Price Display** - Shows minimum price with "From..." prefix for variable products
2. **Handling Fees** - Configurable fees at product and variation level
3. **Dynamic Price Updates** - Price changes when variations are selected

## File Structure
```
├── woocommerce-extra-product-options.php  # Main plugin file, constants, HPOS compatibility
├── includes/
│   ├── class-wcepo-admin.php              # Admin product meta boxes for handling fees
│   ├── class-wcepo-settings.php           # Settings page (WooCommerce > Extra Product Options)
│   ├── class-wcepo-frontend.php           # Frontend display, cart & order handling
│   └── class-wcepo-price-display.php      # Price calculations and HTML output
├── assets/
│   ├── js/
│   │   ├── admin.js                       # Variation fee field toggle
│   │   └── frontend.js                    # Dynamic price updates on variation change
│   └── css/
│       ├── admin.css
│       └── frontend.css
└── readme.txt                             # WordPress plugin readme
```

## Data Storage

### Product/Variation Meta Keys
| Key | Type | Description |
|-----|------|-------------|
| `_wcepo_handling_fee` | float | Handling fee amount |
| `_wcepo_handling_fee_type` | string | `fixed` or `percentage` |
| `_wcepo_use_parent_fee` | string | `yes`/`no` - variation uses parent fee |

### Plugin Options (wp_options)
| Option | Default | Description |
|--------|---------|-------------|
| `wcepo_handling_fee_label` | "Handling Fee" | Display label for the fee |
| `wcepo_from_text` | "From" | Text before minimum price |
| `wcepo_show_from_text` | "yes" | Show/hide "From" text |
| `wcepo_include_handling_in_price` | "yes" | Include fee in displayed price |
| `wcepo_show_handling_fee_separately` | "no" | Show fee as separate line |

## Key WooCommerce Hooks

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

## Development Notes

### HPOS Compatibility
The plugin declares compatibility with WooCommerce High-Performance Order Storage via `FeaturesUtil::declare_compatibility()`. All data storage uses:
- Product post meta (unaffected by HPOS)
- WooCommerce Order Item API (`add_meta_data()`)

### VCC (Vignette Currency Converter) Integration
The plugin integrates with the Vignette Currency Converter plugin for multi-currency support:

**How it works:**
1. Prices are stored and calculated in GBP (shop base currency)
2. If VCC is active, prices are converted to the customer's selected currency
3. Integration happens in `class-wcepo-price-display.php`

**Key methods:**
- `is_vcc_active()` - Check if VCC plugin is available
- `get_selected_currency()` - Get customer's chosen currency from VCC
- `convert_price_for_display($gbp_price)` - Convert GBP to selected currency
- `format_price_html($price)` - Format with correct currency symbol

**Without VCC:**
- Plugin works standalone using WooCommerce's `wc_price()` function
- Prices display in shop's base currency (GBP)

### Adding New Features
1. Product-level settings go in `class-wcepo-admin.php`
2. Global settings go in `class-wcepo-settings.php`
3. Price calculations go in `class-wcepo-price-display.php`
4. Cart/order handling goes in `class-wcepo-frontend.php`

### Testing
1. Test with both simple and variable products
2. Test variation selection updates price correctly
3. Test handling fee appears in cart and order
4. Verify HPOS compatibility in WooCommerce > Status > Features
5. Test currency conversion with VCC plugin (if installed)
