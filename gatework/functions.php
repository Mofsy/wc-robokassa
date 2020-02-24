<?php
/*
  +----------------------------------------------------------+
  | Gatework                                                 |
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

/**
 * Get current version WooCommerce
 *
 * @since 0.2.0
 */
function gatework_get_wc_version()
{
	if(function_exists('is_woocommerce_active') && is_woocommerce_active())
	{
		global $woocommerce;

		if(isset($woocommerce->version))
		{
			return $woocommerce->version;
		}
	}

	if(!function_exists('get_plugins'))
	{
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}

	$plugin_folder = get_plugins('/woocommerce');
	$plugin_file = 'woocommerce.php';

	if(isset($plugin_folder[$plugin_file]['Version']))
	{
		return $plugin_folder[$plugin_file]['Version'];
	}

	return null;
}

/**
 * Get WooCommerce currency code
 *
 * @return string
 */
function gatework_get_wc_currency()
{
	return get_woocommerce_currency();
}