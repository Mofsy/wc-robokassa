<?php
/**
 * Plugin Name: Payment gateway - Robokassa for WooCommerce
 * Description: Integration Robokassa in WooCommerce as payment gateway plugin.
 * Plugin URI: https://mofsy.ru/projects/wc-robokassa
 * Version: 2.4.0
 * WC requires at least: 3.0
 * WC tested up to: 3.9
 * Text Domain: wc-robokassa
 * Domain Path: /languages
 * Author: Mofsy
 * Author URI: https://mofsy.ru
 * Copyright: © 2015-2020 Mofsy
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Mofsy/WC_Robokassa
 */
defined('ABSPATH') || exit;

/**
 * Run
 */
add_action('plugins_loaded', 'wc_robokassa_gateway_init', 0);

/**
 * Init plugin gateway
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
	if(!defined('WC_ROBOKASSA_URL'))
	{
		define('WC_ROBOKASSA_URL', plugin_dir_url(__FILE__));
	}

	/**
	 * Plugin Dir
	 */
	if(!defined('WC_ROBOKASSA_PLUGIN_DIR'))
	{
		define('WC_ROBOKASSA_PLUGIN_DIR', plugin_dir_path(__FILE__));
	}

	/**
	 * Plugin Name
	 */
	if(!defined('WC_ROBOKASSA_PLUGIN_NAME'))
	{
		define('WC_ROBOKASSA_PLUGIN_NAME', plugin_basename(__FILE__));
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