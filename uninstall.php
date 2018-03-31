<?php
/*
  +----------------------------------------------------------+
  | Woocommerce - Robokassa Payment Gateway                  |
  +----------------------------------------------------------+
  | Author: Oleg Budrin (Mofsy) <support@mofsy.ru>           |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

if(!defined('WP_UNINSTALL_PLUGIN'))
{
	exit();
}

global $wpdb;

// Delete plugin options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_robokassa%';");
