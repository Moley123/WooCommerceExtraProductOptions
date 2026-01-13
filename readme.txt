=== WooCommerce Extra Product Options ===
Contributors: Moley123
Tags: woocommerce, variable products, price display, handling fee, from price
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.0.0
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

== Screenshots ==

1. Product page showing "From" price for variable products
2. Handling fee settings in product edit screen
3. Plugin settings page

== Changelog ==

= 1.0.0 =
* Initial release
* Lowest price display with "From..." prefix
* Handling fee support for simple and variable products
* Settings page for customization

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin.
