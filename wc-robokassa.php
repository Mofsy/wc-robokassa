<?php
/*
    Plugin Name: WooCommerce - Robokassa Payment Gateway
    Plugin URI: https://mofsy.ru/portfolio/woocommerce-robokassa-payment-gateway.html
    Description: Allows you to use Robokassa payment gateway with the WooCommerce plugin.
    Version: 0.1.3.1
    Author: Mofsy
    Author URI: https://mofsy.ru
    Text Domain: wc-robokassa
    Domain Path: /languages

    Copyright: © 2015-2016 Mofsy.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if(!defined('ABSPATH'))
{
    exit;
}

add_action('plugins_loaded', 'woocommerce_robokassa_init', 0);

function woocommerce_robokassa_init()
{
    /**
     * Main check
     */
    if (!class_exists('WC_Payment_Gateway'))
    {
        return;
    }

    /**
     * Gate exists?
     */
    if(class_exists('WC_Robokassa'))
    {
        return;
    }

    /**
     * Wc_Robokassa_Logger class load
     */
    include_once dirname(__FILE__) . '/class-wc-robokassa-logger.php';

    /**
     * Define plugin url
     */
    define('WC_ROBOKASSA_URL', plugin_dir_url(__FILE__));

    /**
     * Load language
     */
    load_plugin_textdomain( 'wc-robokassa',  false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    /**
     * Check status
     */
    if( class_exists('WooCommerce_Payment_Status') )
    {
        add_filter( 'woocommerce_valid_order_statuses_for_payment', array( 'WC_Robokassa', 'valid_order_statuses_for_payment' ), 52, 2 );
    }

    /**
     * Gateway class load
     */
    include_once dirname(__FILE__) . '/class-wc-robokassa.php';

    /**
     * Add the gateway to WooCommerce
     **/
    function woocommerce_add_robokassa_gateway($methods)
    {
        $methods[] = 'WC_Robokassa';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_robokassa_gateway');
}

/**
 * Get WooCommerce version
 *
 * @return mixed
 */
function woocommerce_robokassa_get_version()
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


add_action( 'admin_footer', 'wc_robokassa_report_javascript' );

function wc_robokassa_report_javascript() { ?>
    <script type="text/javascript" >
        jQuery(document).ready(function($)
        {
            var data =
            {
                'action': 'send'
            };

            var wc_robokassa_url_callback = '<?php echo wc()->api_request_url('wc_robokassa_send_report'); ?>';

                $('.robokassa-report a').click(function()
                {
                    $.post(wc_robokassa_url_callback, data, function(response)
                    {
                        if(response == 'ok')
                        {
                            $('.robokassa-report').html('<?php _e('Report is sended! Thank you.', 'wc-robokassa'); ?>');
                        }
                        else
                        {
                            $('.robokassa-report').html('<?php _e('Report is NOT sended! Please reload page and resend.', 'wc-robokassa'); ?>');
                        }
                    });

                    return false;
                });

        });
    </script>
    <?php
}