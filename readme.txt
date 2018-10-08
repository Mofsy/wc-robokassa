=== WooCommerce - Robokassa Payment Gateway===
Contributors: Mofsy
Tags: robokassa, payment gateway, woo commerce, woocommerce, ecommerce, gateway, woo robokassa, robo, merchant, woo, woo robo, робокасса
Requires at least: 3.0
Tested up to: 4.9
Requires PHP: 5.4
Stable tag: trunk
WC requires at least: 3.0
WC tested up to: 3.5
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://mofsy.ru/others/help

== Description ==
Allows you to use Robokassa payment gateway with the WooCommerce plugin.
This is the best payment gateway plugin for Robokassa, because it is the most integrated with Robokassa Merchant capabilities and is available for most versions WooCommerce and Wordpress.

Support currency: RUB, USD, EUR

Found a bug? Send it with the settings page in the plugin one click :)

Support external plugins:
1. WPML (http://wpml.org)

== Translations ==

* English - default, always included
* Russian - always included

*Note:* All my plugins are localized/ translateable by default. This is very important for all users worldwide.
So please contribute your language to the plugin to make it even more useful. For translating I recommend the awesome for validating the ["Poedit Editor"](http://www.poedit.net/).

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

== Changelog ==

= 1.0.0.1 =
* Remove free report
* Add support last WordPress & WooCommerce
* Init Gatework
* Sending content cart (54fz)
* Fix admin title
* Minor fix

= 0.3.1.1 =
* Hash fix for md5

= 0.3.0.3 =
* Minor fix

= 0.3.0.2 =
* Test on WP 4.7 and WooCommerce 2.6.9

= 0.3.0.1 =
* Fix readme.txt
* Add more notices
* Fix design in admin panel
* Add more information for log
* More minor fix

= 0.2.0.1 =
* Add WPML support

= 0.1.3.1 =
* Test WP 4.5, now works.

= 0.1.2.1 =
* Test mode fix, now works.
* Update plugin uri

= 0.1.0.1 =
* Init release