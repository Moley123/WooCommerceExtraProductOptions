=== WooCommerce Extra Product Options ===
Contributors: Moley123
Tags: woocommerce, variable products, price display, handling fee, from price, price breakdown
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds handling fees and displays lowest price with "From..." prefix for variable products in WooCommerce.

== Description ==

WooCommerce Extra Product Options enhances your WooCommerce store with:

**Lowest Price Display**
* Shows the minimum price for variable products with a customizable "From..." prefix
* Price updates dynamically when customers select different variations
* Works with all WooCommerce themes

**Handling Fees**
* Add handling fees to simple products
* Add handling fees to variable products (parent level)
* Override handling fees for individual variations
* Choose between fixed amount or percentage-based fees
* Customize the handling fee label (e.g., "Processing Fee", "Service Charge")

**Price Breakdown**
* Display price breakdown showing base price, handling fee, and total
* Show breakdown on product pages, cart, and checkout
* Use the [wcepo_price_breakdown] shortcode anywhere

**Multi-Currency Support**
* Integrates with Vignette Currency Converter (VCC) plugin
* Automatically converts prices to customer's selected currency

**Flexible Display Options**
* Include handling fee in the displayed price
* Show handling fee as a separate line item
* Customize the "From" text

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-extra-product-options` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings at WooCommerce > Extra Product Options.

== Frequently Asked Questions ==

= How do I add a handling fee to a product? =

1. Edit the product in WooCommerce
2. For simple products: Find the "Handling Fee" field in the General tab
3. For variable products: Find the "Handling Fee" field in the General tab (applies to all variations by default), or override per variation in the Variations tab

= Can I use different handling fees for different variations? =

Yes! When editing a variable product, go to the Variations tab. For each variation, you can either use the parent product's handling fee or set a custom fee.

= How do I customize the "From" text? =

Go to WooCommerce > Extra Product Options and change the "From Text" setting.

= Can I show the handling fee separately from the product price? =

Yes! In the plugin settings, enable "Show Fee Separately" to display the handling fee as a separate line item on the product page.

= How do I use the price breakdown shortcode? =

Use [wcepo_price_breakdown] on any product page. You can also specify options:
[wcepo_price_breakdown product_id="123" show_total="yes" layout="horizontal"]

= Does the plugin support multi-currency? =

Yes! The plugin integrates with the Vignette Currency Converter (VCC) plugin. When VCC is active, all prices are automatically converted to the customer's selected currency.

== Screenshots ==

1. Product page showing "From" price for variable products
2. Handling fee settings in product edit screen
3. Plugin settings page
4. Price breakdown in cart

== Changelog ==

= 1.0.4 =
* Fixed product page price not including handling fee when "Include in Price Display" is enabled
* Improved price display reliability across all page contexts

= 1.0.3 =
* Added price breakdown feature with [wcepo_price_breakdown] shortcode
* Added price breakdown display in cart and checkout
* Improved VCC currency detection with cookie/session fallback
* Added CSS styles for vertical and horizontal breakdown layouts

= 1.0.2 =
* Added VCC (Vignette Currency Converter) plugin integration
* Added multi-currency support for EUR, USD, CAD, AUD, JPY and more
* Prices now display in customer's selected currency when VCC is active

= 1.0.1 =
* Fixed memory exhaustion error on variable product pages
* Fixed fatal error when product global is not a WC_Product object
* Added HPOS (High-Performance Order Storage) compatibility

= 1.0.0 =
* Initial release
* Lowest price display with "From..." prefix
* Handling fee support for simple and variable products
* Settings page for customization

== Upgrade Notice ==

= 1.0.4 =
Fixes product page price display when "Include in Price Display" is enabled.

= 1.0.3 =
New price breakdown shortcode and improved cart/checkout display.

= 1.0.2 =
Adds multi-currency support via VCC plugin integration.

= 1.0.1 =
Important bug fixes for memory and error handling. HPOS compatibility added.
