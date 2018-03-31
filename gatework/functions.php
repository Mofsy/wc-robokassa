<?php
/*
  +----------------------------------------------------------+
  | Gatework                                                 |
  +----------------------------------------------------------+
  | Author: Oleg Budrin (Mofsy) <support@mofsy.ru>           |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

/**
 * Get current version WooCommerce
 *
 * @since 0.4.0.1
 */
function gatework_wc_get_version_active()
{
	if ( ! function_exists( 'get_plugins' ) )
	{
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$plugin_folder = get_plugins( '/' . 'woocommerce' );
	$plugin_file = 'woocommerce.php';

	if(isset( $plugin_folder[$plugin_file]['Version'] ))
	{
		return $plugin_folder[$plugin_file]['Version'];
	}

	return null;
}

/**
 * Get Base Currency Code from WooCommerce.
 *
 * @return string
 */
function gatework_get_wc_currency()
{
	return get_woocommerce_currency();
}