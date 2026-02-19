# Changelog

All notable changes to WooCommerce Extra Product Options will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.7] - 2026-02-17

### Added
- **Hide Default Price** option - Don't display variation price until selection is made
- **Hide Reset Link** option - Remove "Clear" link on single product page
- **Format Sale Price** option - Show regular and sale price format (e.g., From ~~£40~~ £38)
- New "Variable Product Settings" section in settings page
- Helper methods: `get_min_regular_price()` and `has_sale_variations()`
- CSS styling for sale price strikethrough format

## [1.0.6] - 2026-02-17

### Fixed
- "From" text now shows on shop/category pages for variable products
- Increased filter priority to 999 to ensure compatibility with VCC and other price plugins
- Added fallback to add "From" text even when price calculation fails
- Added check to prevent double processing of price HTML

## [1.0.5] - 2026-02-17

### Fixed
- Handling fee is now ALWAYS added to cart subtotal/total regardless of display settings
- Handling fee breakdown now ALWAYS shows in cart/checkout (not tied to "Show Fee Separately" setting)
- "Include in Price Display" setting now only affects the product page price display, not cart totals
- "Show Fee Separately" setting now only affects the product page, cart always shows breakdown

### Changed
- Clarified that display settings only affect product page, not cart/checkout behavior

## [1.0.4] - 2026-02-17

### Fixed
- Fixed product page price not including handling fee when "Include in Price Display" is enabled
- Removed `is_product()` check that was preventing price modification on product pages
- Added proper recursion prevention with `$calculating_price` flag

## [1.0.3] - 2026-02-17

### Added
- Price breakdown feature showing base price, handling fee, and total
- `[wcepo_price_breakdown]` shortcode for displaying price breakdown anywhere
- Shortcode attributes: `product_id`, `show_total`, `layout`, `class`
- CSS styles for vertical and horizontal breakdown layouts
- Price breakdown display in cart and checkout when "Show Fee Separately" is enabled

### Changed
- Made `convert_price_for_display()` and `format_price_html()` methods public for external use
- Improved VCC currency detection with cookie/session fallback

## [1.0.2] - 2026-02-17

### Added
- VCC (Vignette Currency Converter) plugin integration for multi-currency support
- `get_selected_currency()` method to detect customer's selected currency
- `convert_price_for_display()` method for currency conversion
- `format_price_html()` method for proper currency formatting
- Support for EUR, USD, CAD, AUD, JPY and other currencies

### Changed
- Prices now display in customer's selected currency when VCC plugin is active
- Updated CLAUDE.md with VCC integration documentation

## [1.0.1] - 2026-02-17

### Fixed
- Fixed memory exhaustion error on variable product pages
- Replaced `get_available_variations()` with `get_children()` to avoid infinite recursion
- Added recursion prevention flag `$calculating_price`
- Fixed fatal error "Call to a member function is_type() on string"
- Added `is_a($product, 'WC_Product')` type checks throughout

### Added
- HPOS (High-Performance Order Storage) compatibility declaration
- CLAUDE.md project documentation file

## [1.0.0] - 2026-02-17

### Added
- Initial release
- Lowest price display with customizable "From..." prefix for variable products
- Handling fee support for simple products
- Handling fee support for variable products (parent level)
- Per-variation handling fee override capability
- Fixed amount and percentage-based fee options
- Customizable handling fee label
- Option to include handling fee in displayed price
- Option to show handling fee as separate line item
- Dynamic price updates when variations are selected
- Settings page at WooCommerce > Extra Product Options
- Admin fields in product edit screen for handling fees
- Cart and order integration for handling fees
- Frontend JavaScript for real-time price updates
- Responsive CSS styles
