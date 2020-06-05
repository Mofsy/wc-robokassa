<?php
/**
 * Main instance of WC_Robokassa
 *
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

/**
 * Logger
 *
 * @since 3.1
 *
 * @return WC_Robokassa_Logger
 */
function wc_robokassa_logger()
{
	return WC_Robokassa()->get_logger();
}


/**
 * Load localisation files
 */
function wc_robokassa_plugin_text_domain()
{
	/**
	 * WP 5.x or later
	 */
	if(function_exists('determine_locale'))
	{
		$locale = determine_locale();
	}
	else
	{
		$locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
	}

	/**
	 * Change locale from external code
	 *
	 * @since 2.4.0
	 */
	$locale = apply_filters('plugin_locale', $locale, 'wc-robokassa');

	/**
	 * Unload & load
	 */
	unload_textdomain('wc-robokassa');
	load_textdomain('wc-robokassa', WP_LANG_DIR . '/wc-robokassa/wc-robokassa-' . $locale . '.mo');
	load_textdomain('wc-robokassa', WC_ROBOKASSA_PLUGIN_DIR . 'languages/wc-robokassa-' . $locale . '.mo');
}