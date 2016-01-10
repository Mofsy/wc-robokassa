=== WooCommerce - Robokassa Payment Gateway===
Contributors: Mofsy
Tags: robokassa, payment gateway, woo commerce, woocommerce, ecommerce, gateway, woo robokassa, robo, merchant, woo, woo robo
Requires at least: 3.0
Tested up to: 4.4.1
Stable tag: trunk
WC requires at least: 2.2
WC tested up to: 2.5
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://mofsy.ru/about/help

== Description ==
Allows you to use Robokassa payment gateway with the WooCommerce plugin.
This is the best payment gateway plugin for Robokassa, because it is the most integrated with Robokassa Merchant capabilities and is available for most versions WooCommerce and Wordpress.

Error found? [bug report](https://mofsy.ru/contacts/email)

Localization support:
<ul style="list-style:none;">
<li>English</li>
<li>Russian</li>
</ul>

== Installation ==
1. Archive extract and upload "wc-robokassa" to /wp-content/plugins
2. Activation plugin
3. Setting

For http://partner.robokassa.ru/?culture=en settings:
<ul style="list-style:none;">
<li>Result URL: http://your_domain/?wc-api=wc_robokassa&action=result</li>
<li>Success URL: http://your_domain/?wc-api=wc_robokassa&action=success</li>
<li>Fail URL: http://your_domain/?wc-api=wc_robokassa&action=fail</li>
<li>Request method for all: POST</li>
<li>Control sign forming method: sha256</li>
</ul>

== Upgrade Notice ==
New settings save

== Changelog ==

= 0.1.0.1 =
* Init release