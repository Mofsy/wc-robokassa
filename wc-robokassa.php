<?php
/*
    Plugin Name: WooCommerce - Robokassa Payment Gateway
    Plugin URI: https://mofsy.ru/projects/wc-robokassa
    Description: Allows you to use Robokassa payment gateway with the WooCommerce plugin.
    Version: 1.0.0.1
	WC requires at least: 3.0
	WC tested up to: 3.5
    Author: Mofsy
    Author URI: https://mofsy.ru
    Text Domain: wc-robokassa
    Domain Path: /languages
    Copyright: © 2015-2018 Mofsy.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if(!defined('ABSPATH'))
{
    exit;
}

/**
 * Run
 *
 * @action woocommerce_robokassa_gateway_init
 */
add_action('plugins_loaded', 'woocommerce_robokassa_gateway_init', 0);

/**
 * Init plugin gateway
 */
function woocommerce_robokassa_gateway_init()
{
    /**
     * Main check
     */
    if (!class_exists('WC_Payment_Gateway') || class_exists('WC_Robokassa'))
    {
        return;
    }

    /**
     * Define plugin url
     */
    define('WC_ROBOKASSA_URL', plugin_dir_url(__FILE__));

	/**
	 * GateWork
	 */
	include_once __DIR__ . '/gatework/init.php';

	/**
	 * Gateway main class
	 */
	include_once __DIR__ . '/class-wc-robokassa.php';

	/**
     * Load language
     *
     * todo: optimize load
     */
    load_plugin_textdomain( 'wc-robokassa',  false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods
	 *
	 * @return array
	 */
    function woocommerce_robokassa_gateway_add($methods)
    {
        $methods[] = 'WC_Robokassa';

        return $methods;
    }

	/**
	 * Add payment method
	 *
	 * @filter woocommerce_robokassa_gateway_add
	 */
    add_filter('woocommerce_payment_gateways', 'woocommerce_robokassa_gateway_add');
}

/**
 * Plugin links right
 */
add_filter('plugin_row_meta',  'wc_robokassa_register_plugins_links_right', 10, 2);

function wc_robokassa_register_plugins_links_right($links, $file)
{
    $base = plugin_basename(__FILE__);
    if ($file === $base)
    {
        $links[] = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_robokassa').'">' . __('Settings') . '</a>';
    }
    return $links;
}

/**
 * Plugin links left
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_robokassa_register_plugins_links_left' );

function wc_robokassa_register_plugins_links_left( $links )
{
    return array_merge(array('settings' => '<a href="https://mofsy.ru/about/help">' . __('Donate for author', 'wc-robokassa') . '</a>'), $links);
}