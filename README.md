# Autocomplete Orders for WooCommerce

**Say goodbye to manual order processing!** 👋

Autocomplete Orders for WooCommerce is a smart plugin that automatically changes the status of your WooCommerce orders from "Processing" to "Completed" based on your preferences. No more tedious clicking - let your store run on autopilot!

## 🚀 Features

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

## 🔌 Installation

1. Download the plugin zip file
2. Go to WordPress Dashboard > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin
5. Go to WooCommerce > Settings > Autocomplete Orders to configure

## ⚙️ Configuration

The plugin settings can be found under **WooCommerce > Settings > Autocomplete Orders**.

1. **Autocomplete Mode**:
   * None: No orders will be automatically completed
   * All Orders: All paid orders will be automatically completed
   * Virtual Orders: Orders with only virtual products will be automatically completed
   * Virtual & Downloadable Orders: Default WooCommerce behavior

2. **Payment Gateway Order Status**:
   * Customize the default status for each payment gateway
   * Options include: Default, Processing, Completed, On Hold

3. **Double Check Payment Status**:
   * Enable to verify payment status after 1 minute
   * Ensures orders are only completed if payment is successful

## ❓ How It Works

This plugin works with orders that call the `woocommerce_payment_complete()` or `$order->payment_complete()` methods. Most payment gateways like PayPal and credit cards trigger this action automatically.

Since shipping and access to products shouldn't happen until payment is received, orders that haven't been paid won't be automatically completed.

**Note:** The plugin won't automatically complete orders with these payment methods (unless you change their default status in the settings):
* Check Payments (WooCommerce core)
* Direct Bank Transfer / BACS (WooCommerce core)
* Cash on Delivery (WooCommerce core)
* Other gateways that default to "On Hold" or "Pending Payment" instead of "Processing"

## 📋 Requirements

* WordPress 6.7+
* PHP 8.0+
* WooCommerce 8.0+

## 🛠️ Changelog

### 1.2.0
* **FIX**: Improved GitHub release workflow for better reliability
* **NEW**: Enhanced plugin packaging for smoother WordPress installation

### 1.1.1
* **NEW**: Added GitHub-based automatic updates
* **NEW**: Consistent plugin ZIP file for easier installation

### 1.1.0
* **FIX**: Updated plugin metadata and general improvements

### 1.0.0
* **NEW**: Initial release with multiple autocomplete modes
* **NEW**: Support for changing default order status by payment gateway
* **NEW**: Payment verification with 1-minute delay option
* **NEW**: Modern settings page with intuitive controls
* **NEW**: Detailed order notes when actions are taken

## 🌟 Support

Need help? Have a feature request? You can reach us at:
* [Plugin Homepage](https://wplove.co/community/space/plugins-themes/home)
* [Support](https://wplove.co)

## 📝 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## 🙏 Credits

Created with ❤️ by [wplove.co](https://wplove.co/community/space/plugins-themes/home)

---

*"Life's too short for manual order processing!"* - The Autocomplete Orders Team 