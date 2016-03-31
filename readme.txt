=== WooCommerce - Robokassa Payment Gateway===
Contributors: Mofsy
Tags: robokassa, payment gateway, woo commerce, woocommerce, ecommerce, gateway, woo robokassa, robo, merchant, woo, woo robo
Requires at least: 3.0
Tested up to: 4.5
Stable tag: trunk
WC requires at least: 2.2
WC tested up to: 2.5
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Donate link: https://mofsy.ru/about/help

== Description ==
= English =
Allows you to use Robokassa payment gateway with the WooCommerce plugin.
This is the best payment gateway plugin for Robokassa, because it is the most integrated with Robokassa Merchant capabilities and is available for most versions WooCommerce and Wordpress.

Support currency: RUB, USD, EUR

Found a bug? Send it with the settings page in the plugin one click :)

= Russian =
Позволяет использовать платежный шлюз Робокасса с плагином WooCommerce.
Это самый лучший плагин платежного шлюза для Робокассы, т.к. он максимально интегрирован с возможностями Робокассы и доступен под большинство версий WooCommerce и Wordpress.

Поддерживаемые валюты: RUB, USD, EUR

Нашли ошибку? Отправьте её со страницы настроек в плагине одник кликом :)

== Translations ==

* English - default, always included
* Russian - always included

*Note:* All my plugins are localized/ translateable by default. This is very important for all users worldwide.
So please contribute your language to the plugin to make it even more useful. For translating I recommend the awesome for validating the ["Poedit Editor"](http://www.poedit.net/).

== Installation ==
= English =
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

= Russian =
1. Распакуйте архив и загрузите "wc-robokassa" в папку /wp-content/plugins
2. Активируйте плагин
3. Настройте

Настройки для http://partner.robokassa.ru/?culture=ru :
<ul style="list-style:none;">
<li>Result URL: http://your_domain/?wc-api=wc_robokassa&action=result</li>
<li>Success URL: http://your_domain/?wc-api=wc_robokassa&action=success</li>
<li>Fail URL: http://your_domain/?wc-api=wc_robokassa&action=fail</li>
<li>Метод запросов: POST</li>
<li>Метод формирования контрольной подписи: sha256</li>
</ul>

== Changelog ==

= 0.1.3.1 =
* Test WP 4.5, now works.

= 0.1.2.1 =
* Test mode fix, now works.
* Update plugin uri

= 0.1.0.1 =
* Init release