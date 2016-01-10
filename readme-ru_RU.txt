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
Позволяет использовать платежный шлюз Робокасса с плагином WooCommerce.
Это самый лучший плагин платежного шлюза для Робокассы, т.к. он максимально интегрирован с возможностями Робокассы и доступен под большинство версий WooCommerce и Wordpress.

Нашли ошибку? [напишите об этом](https://mofsy.ru/contacts/email)

Поддерживаемые языки:
<ul style="list-style:none;">
<li>Английский</li>
<li>Русский</li>
</ul>

== Installation ==
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

== Upgrade Notice ==
Просто пере-сохраните настройки

== Changelog ==

= 0.1.0.1 =
* Init release