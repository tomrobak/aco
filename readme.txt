=== Autocomplete Orders for WooCommerce ===
Contributors: wplove
Tags: woocommerce, order, complete, autocomplete, virtual, payment
Requires at least: 6.7
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically complete your WooCommerce orders based on order type and payment method. Work smarter, not harder!

== Description ==

**Say goodbye to manual order processing!** 👋

Autocomplete Orders for WooCommerce is a smart plugin that automatically changes the status of your WooCommerce orders from "Processing" to "Completed" based on your preferences. No more tedious clicking - let your store run on autopilot!

= 🚀 Features =

* **Multiple Autocomplete Modes**
  * None - Keep things manual (boring, but we won't judge 😉)
  * All Orders - Automatically complete all paid orders (perfect for digital products)
  * Virtual Orders - Automatically complete orders with only virtual products
  * Virtual & Downloadable Orders - Keep the default WooCommerce behavior

* **Gateway-Specific Order Statuses**
  * Customize the default order status for each payment gateway
  * Take control of your order flow for each payment method

* **Payment Verification**
  * Double-check payment status after 1 minute to ensure payment is successful
  * Prevents accidental order completion for failed payments

* **User-Friendly Interface**
  * Beautiful settings page integrated with WooCommerce
  * Clear, helpful descriptions for each option
  * Modern toggle switches and intuitive controls

= ❓ How It Works =

This plugin works with orders that call the `woocommerce_payment_complete()` or `$order->payment_complete()` methods. Most payment gateways like PayPal and credit cards trigger this action automatically.

Since shipping and access to products shouldn't happen until payment is received, orders that haven't been paid won't be automatically completed.

**Note:** The plugin won't automatically complete orders with these payment methods (unless you change their default status in the settings):
* Check Payments (WooCommerce core)
* Direct Bank Transfer / BACS (WooCommerce core)
* Cash on Delivery (WooCommerce core)
* Other gateways that default to "On Hold" or "Pending Payment" instead of "Processing"

= 🌟 Support =

Need help? Have a feature request? You can reach us at:
* [Plugin Homepage](https://wplove.co/community/space/plugins-themes/home)
* [Support](https://wplove.co)

== Installation ==

1. Download the plugin zip file
2. Go to WordPress Dashboard > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin
5. Go to WooCommerce > Settings > Autocomplete Orders to configure

== Frequently Asked Questions ==

= Will this work with all payment gateways? =

This plugin works with any payment gateway that properly calls the WooCommerce payment_complete() method. Most popular payment gateways like PayPal, Stripe, and credit card processors do this correctly.

However, some payment methods like Check Payments, Direct Bank Transfer, and Cash on Delivery typically set orders to "On Hold" or "Pending Payment" instead of "Processing". For these methods, you can use our plugin settings to change their default status.

= Will this plugin complete orders that haven't been paid? =

No! This plugin only completes orders after payment has been confirmed. Orders that are in "Pending Payment" or "Failed" status won't be automatically completed.

= Can I still manually complete orders? =

Absolutely! This plugin doesn't prevent you from manually changing order statuses. It simply automates the process based on your preferences.

= Will this plugin work with my custom order statuses? =

Yes, as long as your custom order statuses are properly registered with WooCommerce, our plugin will respect them and work correctly.

= Does this plugin slow down my site? =

Not at all! The plugin is designed to be lightweight and only runs when an order status changes or a payment is completed. It won't affect your site's performance.

== Screenshots ==

1. Settings page with multiple autocomplete options
2. Payment gateway specific settings
3. Modern toggle switches and user-friendly interface

== Changelog ==

= 1.2.3 =
* **NEW**: Added fun confetti celebration when settings are saved (because saving should be fun!)
* **NEW**: Added witty success messages when saving settings (they're randomized for extra joy!)
* **FIX**: Permanently fixed duplicate Settings links in the plugins page

= 1.2.2 =
* **FIX**: Fixed duplicate Settings links in the plugin list
* **FIX**: Fixed saving indicator animation loop that never ended
* **NEW**: Made settings page fully responsive for all devices and screen sizes
* **NEW**: Improved status notifications with better positioning and animations

= 1.2.1 =
* **NEW**: Added magical 'Update Check' button to manually check for plugin updates
* **FIX**: Fixed AJAX settings saving to actually work this time (we promise!)
* **NEW**: Improved payment method override explanations so you'll actually know what they do
* **NEW**: Added colorful alerts for update notifications because updates should be exciting!

= 1.2.0 =
* **FIX**: Fixed Payment Verification toggle not saving correctly
* **FIX**: Improved GitHub release workflow for better reliability  
* **NEW**: Added AJAX settings saving for instant changes without page reloads
* **NEW**: Improved settings UI with clearer explanations of payment method overrides
* **NEW**: Enhanced status notifications with attractive message styles

= 1.1.1 =
* **NEW**: Added GitHub-based automatic updates
* **NEW**: Consistent plugin ZIP file for easier installation  

= 1.1.0 =
* **FIX**: Updated plugin metadata and general improvements

= 1.0.0 =
* **NEW**: Initial release with multiple autocomplete modes
* **NEW**: Support for changing default order status by payment gateway  
* **NEW**: Payment verification with 1-minute delay option
* **NEW**: Modern settings page with intuitive controls
* **NEW**: Detailed order notes when actions are taken

== Upgrade Notice ==

= 1.2.3 =
🎉 Confetti celebration when you save your settings! Plus witty & random success messages to brighten your day! Settings saving has never been this fun! 

= 1.2.2 =
📱 Now mobile-friendly! Fixed duplicate Settings links and the infinite saving animation. Works beautifully on all your devices, from phones to desktops! Upgrade now for a smoother experience!

= 1.2.1 =
Fixes the AJAX saving (for real this time!) and adds a magical Update Check button. Also makes payment method settings way easier to understand. It's a party! 🎊

= 1.2.0 =
This update adds magical instant-save settings, fixes the Payment Verification toggle, and makes everything more intuitive! Update for a silky-smooth experience! 🎯

= 1.1.1 =
This update adds automatic updates directly from GitHub and fixes installation issues. Update recommended!

= 1.1.0 =
This update includes general improvements and metadata updates. Update recommended!

= 1.0.0 =
Initial release of Autocomplete Orders for WooCommerce 