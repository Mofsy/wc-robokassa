<?php
/**
 * Plugin Name: Payment gateway - Robokassa for WooCommerce
 * Description: Integration Robokassa in WooCommerce as payment gateway plugin.
 * Plugin URI: https://mofsy.ru/projects/wc-robokassa
 * Version: 2.5.0
 * WC requires at least: 3.0
 * WC tested up to: 3.9
 * Text Domain: wc-robokassa
 * Domain Path: /languages
 * Author: Mofsy
 * Author URI: https://mofsy.ru
 * Copyright: Mofsy © 2015-2020
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Mofsy/WC_Robokassa
 */
defined('ABSPATH') || exit;

if(!defined('WC_ROBOKASSA_URL'))
{
	define('WC_ROBOKASSA_URL', plugin_dir_url(__FILE__));
}

if(!defined('WC_ROBOKASSA_PLUGIN_DIR'))
{
	define('WC_ROBOKASSA_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if(!defined('WC_ROBOKASSA_PLUGIN_NAME'))
{
	define('WC_ROBOKASSA_PLUGIN_NAME', plugin_basename(__FILE__));
}

/**
 * GateWork
 */
include_once __DIR__ . '/gatework/init.php';

/**
 * Gateway class
 */
if(!class_exists('WC_Robokassa'))
{
	include_once __DIR__ . '/includes/functions-wc-robokassa.php';
	include_once __DIR__ . '/includes/class-wc-robokassa.php';
}

/**
 * Run
 */
add_action('plugins_loaded', 'WC_Robokassa', 0);