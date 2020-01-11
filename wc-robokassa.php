<?php
/*
    Plugin Name: Payment gateway - Robokassa for WooCommerce
    Plugin URI: https://mofsy.ru/projects/wc-robokassa
    Description: Allows you to use Robokassa with the WooCommerce as payment gateway plugin.
    Version: 2.3.0.1
	WC requires at least: 3.0
	WC tested up to: 3.9
    Author: Mofsy
    Author URI: https://mofsy.ru
    Text Domain: wc-robokassa
    Domain Path: /languages
    Copyright: © 2015-2019 Mofsy.
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
 * @action wc_robokassa_gateway_init
 */
add_action('plugins_loaded', 'wc_robokassa_gateway_init', 0);

/**
 * Init plugin gateway
 *
 * @action wc_robokassa_gateway_init_before
 * @action wc_robokassa_gateway_init_after
 */
function wc_robokassa_gateway_init()
{
	// hook
	do_action('wc_robokassa_gateway_init_before');

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
	if (!defined( 'WC_ROBOKASSA_URL' ))
	{
		define('WC_ROBOKASSA_URL', plugin_dir_url(__FILE__));
	}

	/**
	 * Plugin Dir
	 */
	if (!defined( 'WC_ROBOKASSA_PLUGIN_DIR' ))
	{
		define( 'WC_ROBOKASSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin Name
	 */
	if (!defined( 'WC_ROBOKASSA_PLUGIN_NAME' ))
	{
		define( 'WC_ROBOKASSA_PLUGIN_NAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * GateWork
	 */
	include_once __DIR__ . '/gatework/init.php';

	/**
	 * Gateway main class
	 */
	include_once __DIR__ . '/includes/class-wc-robokassa.php';

	/**
	 * Run
	 */
	WC_Robokassa::instance();

	// hook
	do_action('wc_robokassa_gateway_init_after');
}