<?php
/*
  +----------------------------------------------------------+
  | WooCommerce - Robokassa Payment Gateway                  |
  +----------------------------------------------------------+
  | Author: Oleg Budrin (Mofsy) <support@mofsy.ru>           |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class WC_Robokassa extends WC_Payment_Gateway
{
    /**
     * Current WooCommerce version
     *
     * @var
     */
    public $wc_version;

    /**
     * Current currency
     *
     * @var string
     */
    public $currency;

    /**
     * All support currency
     *
     * @var array
     */
    public $currency_all = array('RUB', 'USD', 'EUR');

    /**
     * Shop login
     *
     * @var string
     */
    public $shop_login;

    /**
     * Shop pass 1
     *
     * @var string
     */
    public $shop_pass_1 = '';

    /**
     * Shop pass 2
     *
     * @var string
     */
    public $shop_pass_2 = '';

    /**
     * Hashing for signature
     *
     * @var string
     */
    public $sign_method = 'sha256';

    /**
     * Unique gateway id
     *
     * @var string
     */
    public $id = 'robokassa';

    /**
     * Form url for Merchant
     *
     * @var string
     */
    public $form_url = 'https://merchant.roboxchange.com/Index.aspx';

    /**
     * User language
     *
     * @var string
     */
    public $language = 'ru';

    /**
     * @var mixed
     */
    public $test = 'no';

    /**
     * @var string
     *
     * deprecated
     */
    public $test_form_url = 'http://test.robokassa.ru/Index.aspx';

    /**
     * Test shop pass 1
     *
     * @var string
     */
    public $test_shop_pass_1 = '';

    /**
     * Test shop pass 2
     *
     * @var string
     */
    public $test_shop_pass_2 = '';

    /**
     * Hashing signature for test mode
     *
     * @var string
     */
    public $test_sign_method = 'sha256';

    /**
     * WC_Robokassa constructor
     */
    public function __construct()
    {
        /**
         * Get currency
         */
        $this->currency = get_woocommerce_currency();

        /**
         * Set WooCommerce version
         */
        $this->wc_version = woocommerce_robokassa_get_version();

        /**
         * Set unique id
         */
        $this->id = 'robokassa';

        /**
         * What?
         */
        $this->has_fields = false;

        /**
         * Load settings
         */
        $this->init_form_fields();
        $this->init_settings();

        /**
         * Title for user interface
         */
        $this->title = $this->get_option('title');

        /**
         * Testing?
         */
        $this->test = $this->get_option('test');

        /**
         * Default language for Robokassa interface
         */
        $this->language = $this->get_option('language');

        /**
         * Automatic language
         */
        if($this->get_option('language_auto') === 'yes')
        {
            $lang = get_locale();
            switch($lang)
            {
                case 'en_EN':
                    $this->language = 'en';
                    break;
                case 'ru_RU':
                    $this->language = 'ru';
                    break;
                default:
                    $this->language = 'ru';
                    break;
            }
        }

        /**
         * Set description
         */
        $this->description = $this->get_option('description');

        /**
         * Set order button text
         */
        $this->order_button_text = $this->get_option('order_button_text');

        /**
         * Set shop pass 1
         */
        if($this->get_option('shop_pass_1') !== '')
        {
            $this->shop_pass_1 = $this->get_option('shop_pass_1');
        }
        
        /**
         * Set shop pass 2
         */
        if($this->get_option('shop_pass_2') !== '')
        {
            $this->shop_pass_2 = $this->get_option('shop_pass_2');
        }

        /**
         * Load shop login
         */
        $this->shop_login = $this->get_option('shop_login');

        /**
         * Load sign method
         */
        $this->sign_method = $this->get_option('sign_method');

        /**
         * Set shop pass 1 for testing
         */
        if($this->get_option('test_shop_pass_1') !== '')
        {
            $this->test_shop_pass_1 = $this->get_option('test_shop_pass_1');
        }

        /**
         * Set shop pass 2 for testing
         */
        if($this->get_option('test_shop_pass_2') !== '')
        {
            $this->test_shop_pass_2 = $this->get_option('test_shop_pass_2');
        }

        /**
         * Load sign method for testing
         */
        $this->test_sign_method = $this->get_option('test_sign_method');

        /**
         * Set icon
         */
        if($this->get_option('enable_icon') === 'yes')
        {
            $this->icon = apply_filters('woocommerce_robokassa_icon', WC_ROBOKASSA_URL . '/assets/img/robokassa.png');
        }

        /**
         * Save admin options
         */
        if(current_user_can( 'manage_options' ))
        {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Gate allow?
         */
        if ($this->is_valid_for_use())
        {
            /**
             * Receipt page
             */
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            /**
             * Payment listener/API hook
             */
            add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_ipn'));
        }
        else
        {
            $this->enabled = false;
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     */
    public function is_valid_for_use()
    {
        $return = true;

        /**
         * Check allow currency
         */
        if (!in_array($this->currency, $this->currency_all, false))
        {
            $return = false;
        }

        /**
         * Check test mode and admin rights
         */
        if ($this->test !== '' && !current_user_can( 'manage_options' ))
        {
            $return = false;
        }

        return $return;
    }

    /**
     * Admin Panel Options
     **/
    public function admin_options()
    {
        ?>
        <h1><?php _e('Robokassa', 'wc-robokassa'); ?></h1><?php $this->get_icon(); ?>
        <p><?php _e('Setting receiving payments through Robokassa Merchant.', 'wc-robokassa'); ?></p>

        <?php if ( $this->is_valid_for_use() ) : ?>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>

    <?php else : ?>
        <div class="inline error"><p><strong><?php _e('Gateway offline', 'wc-robokassa'); ?></strong>: <?php _e('Robokassa does not support the currency your store.', 'wc-robokassa' ); ?></p></div>
        <?php
    endif;
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array
        (
            'enabled' => array
            (
                'title' => __('Online/Offline gateway', 'wc-robokassa'),
                'type' => 'checkbox',
                'label' => __('Online', 'wc-robokassa'),
                'default' => 'yes'
            ),
            'interface' => array(
                'title'       => __( 'Interface', 'wc-robokassa' ),
                'type'        => 'title',
                'description' => '',
            ),
            'enable_icon' => array
            (
                'title' => __('Show gateway icon?', 'wc-robokassa'),
                'type' => 'checkbox',
                'label' => __('Show', 'wc-robokassa'),
                'default' => 'yes'
            ),
            'language' => array
            (
                'title' => __( 'Language interface', 'wc-robokassa' ),
                'type' => 'select',
                'options' => array
                (
                    'ru' => __('Russian', 'wc-robokassa'),
                    'en' => __('English', 'wc-robokassa')
                ),
                'description' => __( 'What language interface displayed for the customer on Robokassa?', 'wc-robokassa' ),
                'default' => 'ru'
            ),
            'language_auto' => array
            (
                'title' => __( 'Language based on the locale?', 'wc-robokassa' ),
                'type' => 'select',
                'options' => array
                (
                    'yes' => __('Yes', 'wc-robokassa'),
                    'no' => __('No', 'wc-robokassa')
                ),
                'description' => __( 'Trying to get the language based on the locale?', 'wc-robokassa' ),
                'default' => 'ru'
            ),
            'title' => array
            (
                'title' => __('Title', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'This is the name that the user sees during the payment.', 'wc-robokassa' ),
                'default' => __('Robokassa', 'wc-robokassa')
            ),
            'order_button_text' => array
            (
                'title' => __('Order button text', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'This is the button text that the user sees during the payment.', 'wc-robokassa' ),
                'default' => __('Pay', 'wc-robokassa')
            ),
            'description' => array
            (
                'title' => __( 'Description', 'wc-robokassa' ),
                'type' => 'textarea',
                'description' => __( 'Description of the method of payment that the customer will see on our website.', 'wc-robokassa' ),
                'default' => __( 'Payment by Robokassa.', 'wc-robokassa' )
            ),
            'technical' => array(
                'title'       => __( 'Technical details', 'wc-robokassa' ),
                'type'        => 'title',
                'description' => '',
            ),
            'sign_method' => array
            (
                'title' => __( 'Signature method', 'wc-robokassa' ),
                'type' => 'select',
                'options' => array
                (
                    'md5' => 'md5',
                    'ripemd160' => 'RIPEMD160',
                    'sha1' => 'SHA1',
                    'sha256' => 'SHA256',
                    'sha384' => 'SHA384',
                    'sha512' => 'SHA512'
                ),
                'default' => 'sha256'
            ),
            'shop_login' => array
            (
                'title' => __('Shop login', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'Unique identification for shop.', 'wc-robokassa' ),
                'default' => ''
            ),
            'shop_pass_1' => array
            (
                'title' => __('Shop pass 1', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'Please write Shop pass 1.', 'wc-robokassa' ),
                'default' => ''
            ),
            'shop_pass_2' => array
            (
                'title' => __('Shop pass 2', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'Please write Shop pass 2.', 'wc-robokassa' ),
                'default' => ''
            ),
            'test_payments' => array(
                'title'       => __( 'Settings for test payments', 'wc-robokassa' ),
                'type'        => 'title',
                'description' => '',
            ),
            'test' => array
            (
                'title' => __( 'Test mode', 'wc-robokassa' ),
                'type'        => 'select',
                'description'	=>  __( 'Activate testing mode for admins.', 'wc-robokassa' ),
                'default'	=> '2',
                'options'     => array
                (
                    'no' => __( 'Off', 'wc-robokassa' ),
                    'yes' => __( 'On', 'wc-robokassa' ),
                )
            ),
            'test_sign_method' => array
            (
                'title' => __( 'Signature method', 'wc-robokassa' ),
                'type' => 'select',
                'options' => array
                (
                    'md5' => 'md5',
                    'ripemd160' => 'RIPEMD160',
                    'sha1' => 'SHA1',
                    'sha256' => 'SHA256',
                    'sha384' => 'SHA384',
                    'sha512' => 'SHA512'
                ),
                'default' => 'sha256'
            ),
            'test_shop_pass_1' => array
            (
                'title' => __('Shop pass 1', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'Please write Shop pass 1 for testing payments.', 'wc-robokassa' ),
                'default' => ''
            ),
            'test_shop_pass_2' => array
            (
                'title' => __('Shop pass 2', 'wc-robokassa'),
                'type' => 'text',
                'description' => __( 'Please write Shop pass 2 for testing payments.', 'wc-robokassa' ),
                'default' => ''
            )
        );
    }

    /**
     * There are no payment fields for sprypay, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        if ($this->description)
        {
            echo wpautop(wptexturize($this->description));
        }
    }

    /**
     * @param $statuses
     * @param $order
     * @return mixed
     */
    public static function valid_order_statuses_for_payment($statuses, $order)
    {
        if($order->payment_method !== 'robokassa')
        {
            return $statuses;
        }

        $option_value = get_option( 'woocommerce_payment_status_action_pay_button_controller', array() );

        if(!is_array($option_value))
        {
            $option_value = array('pending', 'failed');
        }

        if( is_array($option_value) && !in_array('pending', $option_value, false) )
        {
            $pending = array('pending');
            $option_value = array_merge($option_value, $pending);
        }

        return $option_value;
    }

    /**
     * Generate payments form
     *
     * @param $order_id
     *
     * @return string Payment form
     **/
    public function generate_form($order_id)
    {
        /**
         * Create order object
         */
        $order = wc_get_order($order_id);

        /**
         * Form parameters
         */
        $args = array();

        /**
         * Shop login
         */
        $args['MerchantLogin'] = $this->shop_login;

        /**
         * Sum
         */
        $out_sum = number_format($order->order_total, 2, '.', '');
        $args['OutSum'] = $out_sum;

        /**
         * Order id
         */
        $args['InvId'] = $order_id;

        /**
         * Product description
         */
        $description = '';
        $items = $order->get_items();
        foreach ( $items as $item )
        {
            $description .= $item['name'];
        }
        if(count($description) > 99)
        {
            $description = __('Product number: ' . $order_id, 'wc-robokassa');
        }
        $args['InvDesc'] = $description;

        /**
         * Rewrite currency from order
         */
        $this->currency = $order->order_currency;

        /**
         * Set currency to robokassa
         */
        if($this->currency === 'USD')
        {
            $args['OutSumCurrency'] = 'USD';
        }
        elseif($this->currency === 'EUR')
        {
            $args['OutSumCurrency'] = 'EUR';
        }

        /**
         * Test mode
         */
        if ($this->test === 'yes')
        {
            /**
             * Signature pass for testing
             */
            $signature_pass = $this->test_shop_pass_1;

            /**
             * Sign method
             */
            $signature_method = $this->test_sign_method;

            /**
             * Test flag
             */
            $args['IsTest'] = 1;
        }
        /**
         * Real payments
         */
        else
        {
            /**
             * Signature pass for real payments
             */
            $signature_pass = $this->shop_pass_1;

            /**
             * Sign method
             */
            $signature_method = $this->sign_method;
        }

        /**
         * Billing email
         */
        if(!empty($order->billing_email))
        {
            $args['Email'] = $order->billing_email;
        }

        /**
         * Signature
         */

        if(array_key_exists('OutSumCurrency',$args))
        {
            $signature_payload = $args['MerchantLogin'].':'.$args['OutSum'].':'.$args['InvId'].':'.$args['OutSumCurrency'].':'.$signature_pass;
        }
        else
        {
            $signature_payload = $args['MerchantLogin'].':'.$args['OutSum'].':'.$args['InvId'].':'.$signature_pass;
        }
        $args['SignatureValue'] = $this->get_signature($signature_payload, $signature_method);

        /**
         * Encoding
         */
        $args['Encoding'] = 'utf-8';

        /**
         * Language (culture)
         */
        $args['Culture'] = $this->language;

        /**
         * Execute filter woocommerce_robokassa_args
         */
        $args = apply_filters('woocommerce_robokassa_args', $args);

        /**
         * Form inputs generic
         */
        $args_array = array();
        foreach ($args as $key => $value)
        {
            $args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
        }

        /**
         * Return full form
         */
        return '<form action="'.esc_url($this->form_url).'" method="POST" id="robokassa_payment_form" accept-charset="utf-8">'."\n".
        implode("\n", $args_array).
        '<input type="submit" class="button alt" id="submit_robokassa_payment_form" value="'.__('Pay', 'wc-robokassa').
        '" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel & return to cart', 'wc-robokassa').'</a>'."\n".
        '</form>';
    }

    /**
     * Get signature
     *
     * @param $string
     * @param $method
     *
     * @return string
     */
    public function get_signature($string, $method = 'sha256')
    {
        switch($method)
        {
            case 'ripemd160':
                $signature = strtoupper(hash('ripemd160', $string));
                break;

            case 'sha1':
                $signature = strtoupper(sha1($string));
                break;

            case 'sha256':
                $signature = strtoupper(hash('sha256', $string));
                break;

            case 'sha384':
                $signature = strtoupper(hash('sha384', $string));
                break;

            case 'sha512':
                $signature = strtoupper(hash('sha512', $string));
                break;

            default:

                $signature = md5($string);
        }

        return $signature;
    }

    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        /**
         * Add order note
         */
        $order->add_order_note(__('The client started to pay.', 'wc-robokassa'));

        if ( !version_compare( $this->wc_version, '2.1.0', '<' ) )
        {
            return array
            (
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        }

        return array
        (
            'result' => 'success',
            'redirect'	=> add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
        );
    }

    /**
     * receipt_page
     **/
    public function receipt_page($order)
    {
        echo '<p>'.__('Thank you for your order, please press the button below to pay.', 'wc-robokassa').'</p>';
        echo $this->generate_form($order);
    }

    /**
     * Check instant payment notification
     **/
    public function check_ipn()
    {
        /**
         * Hook wc_robokassa
         */
        if ($_GET['wc-api'] === 'wc_robokassa')
        {
            /**
             * Order id
             */
            $order_id = 0;
            if(array_key_exists('InvId', $_REQUEST))
            {
                $order_id = $_REQUEST['InvId'];
            }

            /**
             * Sum
             */
            $sum = 0;
            if(array_key_exists('OutSum', $_REQUEST))
            {
                $sum = $_REQUEST['OutSum'];
            }

            /**
             * Test mode
             */
            if ($this->test === 'yes' || (array_key_exists('IsTest', $_REQUEST) && $_REQUEST['IsTest'] == '1'))
            {
                /**
                 * Test flag
                 */
                $test = true;

                /**
                 * Signature pass for testing
                 */
                $signature_pass = $this->test_shop_pass_2;

                /**
                 * Sign method
                 */
                $signature_method = $this->test_sign_method;
            }
            /**
             * Real payments
             */
            else
            {
                /**
                 * Test flag
                 */
                $test = false;

                /**
                 * Signature pass for real payments
                 */
                $signature_pass = $this->shop_pass_2;

                /**
                 * Sign method
                 */
                $signature_method = $this->sign_method;
            }

            /**
             * Signature
             */
            $signature = '';
            if(array_key_exists('SignatureValue', $_REQUEST))
            {
                $signature = $_REQUEST['SignatureValue'];
            }

            /**
             * Local signature
             */
            $signature_payload = "{$sum}:{$order_id}:{$signature_pass}";
            $local_signature = $this->get_signature($signature_payload, $signature_method);

            /**
             * Get order object
             */
            $order = wc_get_order($order_id);

            /**
             * Result
             */
            if ($_GET['action'] === 'result')
            {
                /**
                 * Validated flag
                 */
                $validate = true;

                /**
                 * Check signature
                 */
                if($signature !== $local_signature)
                {
                    $validate = false;

                    /**
                     * Add order note
                     */
                    $order->add_order_note(sprintf(__('Validate hash error. Local: %1$s Remote: %2$s', 'wc-robokassa'), $local_signature, $signature));
                }

                /**
                 * Validated
                 */
                if($validate === true)
                {
                    /**
                     * Testing
                     */
                    if($test === true)
                    {
                        /**
                         * Add order note
                         */
                        $order->add_order_note(__('Order successfully paid (TEST MODE).', 'wc-robokassa'));
                    }
                    /**
                     * Real payment
                     */
                    else
                    {
                        /**
                         * Add order note
                         */
                        $order->add_order_note(__('Order successfully paid.', 'wc-robokassa'));
                    }

                    /**
                     * Set status is payment
                     */
                    $order->payment_complete($order_id);
                    die('OK'.$order_id);
                }
                else
                {
                    /**
                     * Send Service unavailable
                     */
                    wp_die(__('Payment error, please pay other time.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
                }

                /**
                 * Send Service unavailable
                 */
                wp_die(__('Payment error, please pay other time.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
            }
            /**
             * Success
             */
            else if ($_GET['action'] === 'success')
            {
                /**
                 * Empty cart
                 */
                WC()->cart->empty_cart();

                /**
                 * Redirect to success
                 */
                wp_redirect( $this->get_return_url( $order ) );
            }
            /**
             * Fail
             */
            else if ($_GET['action'] === 'fail')
            {
                /**
                 * Add order note
                 */
                $order->add_order_note(__('The order has not been paid.', 'wc-robokassa'));

                /**
                 * Sen status is failed
                 */
                $order->update_status('failed');

                /**
                 * Redirect to cancel
                 */
                wp_redirect( str_replace('&amp;', '&', $order->get_cancel_order_url() ) );
            }
        }
        else
        {
            die('IPN Request Failure');
        }
    }
}