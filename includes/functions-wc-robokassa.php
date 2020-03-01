<?php
/**
 * Main instance of WC_Robokassa
 * @since 3.0.0
 *
 * @return WC_Robokassa|false
 */
function WC_Robokassa()
{
	if(is_callable('WC_Robokassa::instance'))
	{
		return WC_Robokassa::instance();
	}

	return false;
}

/**
 * Get current version WooCommerce
 *
 * @since 3.0.2
 */
function wc_robokassa_get_wc_version()
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
 * @since 3.0.2
 *
 * @return string
 */
function wc_robokassa_get_wc_currency()
{
	return get_woocommerce_currency();
}