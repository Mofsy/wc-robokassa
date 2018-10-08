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
	 * @var bool
	 */
	public $ofd_status = false;

	/**
	 * @var string
	 */
	public $ofd_sno = 'usn';

	/**
	 * @var string
	 */
	public $ofd_nds = 'none';

    /**
     * Logger
     *
     * @var WC_Gatework_Logger
     */
    public $logger;

    /**
     * Logger path
     *
     * array
     * (
     *  'dir' => 'C:\path\to\wordpress\wp-content\uploads\logname.log',
     *  'url' => 'http://example.com/wp-content/uploads/logname.log'
     * )
     *
     * @var array
     */
    public $logger_path;

    /**
     * WC_Robokassa constructor
     */
    public function __construct()
    {
        /**
         * Logger?
         */
        $wp_dir = wp_upload_dir();
        $this->logger_path = array
        (
            'dir' => $wp_dir['basedir'] . '/wc-robokassa.txt',
            'url' => $wp_dir['baseurl'] . '/wc-robokassa.txt'
        );

        $this->logger = new WC_Gatework_Logger($this->logger_path['dir'], $this->get_option('logger'));

        /**
         * Get currency
         */
        $this->currency = gatework_get_wc_currency();

        /**
         * Logger debug
         */
        $this->logger->addDebug('Current currency: ' . $this->currency);

        /**
         * Set WooCommerce version
         */
        $this->wc_version = gatework_wc_get_version_active();

        /**
         * Logger debug
         */
        $this->logger->addDebug('WooCommerce version: ' . $this->wc_version);

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
         * Gateway enabled?
         */
        if($this->get_option('enabled') !== 'yes')
        {
            $this->enabled = false;

            /**
             * Logger notice
             */
            $this->logger->addNotice('Gateway is NOT enabled.');
        }

        /**
         * Title for user interface
         */
        $this->title = $this->get_option('title');

	    /**
	     * Admin title
	     */
	    $this->method_title = __( 'Robokassa', 'wc-robokassa' );

	    /**
	     * Admin method description
	     */
	    $this->method_description = __( 'Pay via Robokassa.', 'wc-robokassa' );

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
            /**
             * Logger notice
             */
            $this->logger->addNotice('Language auto is enable.');

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
         * Logger debug
         */
        $this->logger->addDebug('Language: ' . $this->language);

        /**
         * Set description
         */
        $this->description = $this->get_option('description');

        /**
         * Set order button text
         */
        $this->order_button_text = $this->get_option('order_button_text');

	    /**
	     * Ofd
	     */
	    if($this->get_option('ofd_status') == 'yes')
	    {
		    $this->ofd_status = true;

		    /**
		     * Logger notice
		     */
		    $this->logger->addDebug('ofd_status = yes');
	    }

	    /**
	     * Ofd sno
	     */
	    $ofd_sno_code = $this->get_option('ofd_sno');
	    if($ofd_sno_code !== '')
	    {
		    $ofd_sno = 'osn';

		    if($ofd_sno_code == '1')
		    {
			    $ofd_sno = 'usn_income';
		    }

		    if($ofd_sno_code == '2')
		    {
			    $ofd_sno = 'usn_income_outcome';
		    }

		    if($ofd_sno_code == '3')
		    {
			    $ofd_sno = 'envd';
		    }

		    if($ofd_sno_code == '4')
		    {
			    $ofd_sno = 'esn';
		    }

		    if($ofd_sno_code == '5')
		    {
			    $ofd_sno = 'patent';
		    }

		    $this->ofd_sno = $ofd_sno;
	    }

	    /**
	     * Ofd nds
	     */
	    $ofd_nds_code = $this->get_option('ofd_nds');
	    if($ofd_nds_code !== '')
	    {
		    $ofd_nds = 'none';

		    if($ofd_nds_code == '1')
		    {
			    $ofd_nds = 'vat0';
		    }

		    if($ofd_nds_code == '2')
		    {
			    $ofd_nds = 'vat10';
		    }

		    if($ofd_nds_code == '3')
		    {
			    $ofd_nds = 'vat18';
		    }

		    if($ofd_nds_code == '4')
		    {
			    $ofd_nds = 'vat110';
		    }

		    if($ofd_nds_code == '5')
		    {
			    $ofd_nds = 'vat118';
		    }

		    $this->ofd_nds = $ofd_nds;
	    }

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

            /**
             * Logger notice
             */
            $this->logger->addDebug('Manage options is allow.');
        }

        /**
         * Receipt page
         */
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        /**
         * Payment listener/API hook
         */
        add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_ipn'));

        /**
         * Gate allow?
         */
        if ($this->is_valid_for_use())
        {
            /**
             * Logger notice
             */
            $this->logger->addInfo('Is valid for use.');
        }
        else
        {
            $this->enabled = false;

            /**
             * Logger notice
             */
            $this->logger->addInfo('Is NOT valid for use.');
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

            /**
             * Logger notice
             */
            $this->logger->addDebug('Currency not support:'.$this->currency);
        }

        /**
         * Check test mode and admin rights
         */
        if ($this->test === 'yes' && !current_user_can( 'manage_options' ))
        {
            $return = false;

            /**
             * Logger notice
             */
            $this->logger->addNotice('Test mode only admins.');
        }

        return $return;
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
            'technical' => array
            (
                'title' => __( 'Technical details', 'wc-robokassa' ),
                'type' => 'title',
                'description' => '',
            ),
            'test' => array
            (
	            'title' => __( 'Test mode', 'wc-robokassa' ),
	            'type'        => 'select',
	            'description'	=>  __( 'Activate testing mode for admins.', 'wc-robokassa' ),
	            'default'	=> 'yes',
	            'options'     => array
	            (
		            'no' => __( 'Off', 'wc-robokassa' ),
		            'yes' => __( 'On', 'wc-robokassa' ),
	            )
            ),
            'logger' => array
            (
	            'title' => __( 'Enable logging?', 'wc-robokassa' ),
	            'type'        => 'select',
	            'description'	=>  __( 'You can enable gateway logging, specify the level of error that you want to benefit from logging. You can send reports to developer manually by pressing the button. All sensitive data in the report are deleted.
By default, the error rate should not be less than ERROR.', 'wc-robokassa' ),
	            'default'	=> '400',
	            'options'     => array
	            (
		            '' => __( 'Off', 'wc-robokassa' ),
		            '100' => 'DEBUG',
		            '200' => 'INFO',
		            '250' => 'NOTICE',
		            '300' => 'WARNING',
		            '400' => 'ERROR',
		            '500' => 'CRITICAL',
		            '550' => 'ALERT',
		            '600' => 'EMERGENCY'
	            )
            ),
            'real' => array
            (
	            'title' => __( 'Settings for real payments', 'wc-robokassa' ),
	            'type' => 'title',
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
            'test_payments' => array
            (
                'title'       => __( 'Settings for test payments', 'wc-robokassa' ),
                'type'        => 'title',
                'description' => '',
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
            ),
            'ofd' => array
            (
	            'title' => __( 'Cart content sending (54fz)', 'wc-robokassa' ),
	            'type' => 'title',
	            'description' => '',
            ),
            'ofd_status' => array
            (
	            'title' => __('Передача корзины товаров', 'woocommerce'),
	            'type' => 'checkbox',
	            'label' => __('Включена', 'woocommerce'),
	            'description' => __('При выборе опции, будет сформирован и отправлен в налоговую и клиенту чек. При использовании необходимо настроить НДС продаваемых товаров. НДС рассчитывается согласно законодательству РФ, возможны расхождения в размере НДС с суммой рассчитанной магазином.', 'woocommerce'),
	            'default' => 'off'
            ),
            'ofd_sno' => array
            (
	            'title' => __('Система налогообложения', 'woocommerce'),
	            'type' => 'select',
	            'default' => '0',
	            'options' => array
	            (
		            '0' => __('Общая', 'woocommerce'),
		            '1' => __('Упрощённая, доход', 'woocommerce'),
		            '2' => __('Упрощённая, доход минус расход', 'woocommerce'),
		            '3' => __('Eдиный налог на вменённый доход', 'woocommerce'),
		            '4' => __('Eдиный сельскохозяйственный налог', 'woocommerce'),
		            '5' => __('Патентная система налогообложения', 'woocommerce'),
	            ),
            ),
            'ofd_nds' => array
            (
	            'title' => __('Ставка НДС по умолчанию', 'woocommerce'),
	            'type' => 'select',
	            'default' => '0',
	            'options' => array
	            (
		            '0' => __('Без НДС', 'woocommerce'),
		            '1' => __('НДС по ставке 0%', 'woocommerce'),
		            '2' => __('НДС чека по ставке 10%', 'woocommerce'),
		            '3' => __('НДС чека по ставке 18%', 'woocommerce'),
		            '4' => __('НДС чека по расчетной ставке 10/110', 'woocommerce'),
		            '5' => __('НДС чека по расчетной ставке 10/118', 'woocommerce'),
	            ),
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
        $out_sum = number_format($order->get_total(), 2, '.', '');
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
            $description .= ' ' . $item['name'];
        }

	    /**
	     * Description count
	     */
	    if(function_exists('mb_strlen'))
	    {
		    $description_count = mb_strlen($description);
	    }
        elseif (function_exists('iconv_strlen'))
	    {
		    $description_count = iconv_strlen($description);
	    }
	    else
	    {
		    $description_count = strlen($description);
	    }

        if($description_count > 99)
        {
            $description = __('Product number: ' . $order_id, 'wc-robokassa');
        }

        $args['InvDesc'] = $description;

        /**
         * Rewrite currency from order
         */
        $this->currency = $order->get_currency();

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
        $billing_email = $order->get_billing_email();
        if(!empty($billing_email))
        {
            $args['Email'] = $billing_email;
        }
        unset($billing_email);

	    /**
	     * Receipt
	     */
	    $receipt_result = '';

	    if($this->ofd_status)
	    {
		    /**
		     * Container
		     */
		    $receipt = array();

		    /**
		     * Items
		     */
		    $receipt_items = array();

		    foreach ($order->get_items() as $receipt_items_key => $receipt_items_value)
		    {
			    $item_quantity = $receipt_items_value->get_quantity(); // Get the item quantity

			    $item_total = $receipt_items_value->get_total(); // Get the item line total

			    /**
			     * Build positions
			     */
			    $receipt_items[] = array
			    (
				    /**
				     * Название товара
				     *
				     * максимальная длина 128 символов
				     */
				    'name' => $receipt_items_value['name'],

				    /**
				     * Стоимость предмета расчета с учетом скидок и наценок
				     *
				     * Цена в рублях:
				     *  целая часть не более 8 знаков;
				     *  дробная часть не более 2 знаков.
				     */
				    'sum' => intval($item_total),

				    /**
				     * Количество/вес
				     *
				     * максимальная длина 128 символов
				     */
				    'quantity' => intval($item_quantity),

				    /**
				     * Tax
				     */
				    'tax' => $this->ofd_nds
			    );
		    }

		    // DELIVERY POSITION
		    if ($order->shipping_total > 0)
		    {
			    /**
			     * Build positions
			     */
			    $receipt_items[] = array
			    (
				    /**
				     * Название товара
				     *
				     * максимальная длина 128 символов
				     */
				    'name' => 'Доставка',

				    /**
				     * Стоимость предмета расчета с учетом скидок и наценок
				     *
				     * Цена в рублях:
				     *  целая часть не более 8 знаков;
				     *  дробная часть не более 2 знаков.
				     */
				    'sum' => intval($order->shipping_total),

				    /**
				     * Количество/вес
				     *
				     * максимальная длина 128 символов
				     */
				    'quantity' => 1,

				    /**
				     * Tax
				     */
				    'tax' => $this->ofd_nds
			    );
		    }

		    /**
		     * Sno
		     */
		    $receipt['sno'] = $this->ofd_sno;

		    /**
		     * Items
		     */
		    $receipt['items'] = $receipt_items;

		    /**
		     * Insert $receipt into debug mode
		     */
		    $this->logger->addDebug('$receipt', $receipt);

		    /**
		     * Result
		     */
		    $receipt_result = json_encode($receipt);

		    /**
		     * Insert $receipt_result into debug mode
		     */
		    $this->logger->addDebug('$receipt_result' . $receipt_result);
	    }

	    /**
	     * Signature
	     */
	    $receipt_signature = '';
	    if($receipt_result != '')
	    {
		    $receipt_signature = ':'.$receipt_result;

		    $args['Receipt'] = $receipt_result;
	    }

	    if(array_key_exists('OutSumCurrency', $args))
	    {
		    $signature_payload = $args['MerchantLogin'].':'.$args['OutSum'].':'.$args['InvId'].$receipt_signature.':'.$args['OutSumCurrency'].':'.$signature_pass;
	    }
	    else
	    {
		    $signature_payload = $args['MerchantLogin'].':'.$args['OutSum'].':'.$args['InvId'].$receipt_signature.':'.$signature_pass;
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

                $signature = strtoupper(md5($string));
        }

        return $signature;
    }

    /**
     * Process the payment and return the result
     *
	 * @param int $order_id
	 *
	 * @return array
	 */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        /**
         * Add order note
         */
        $order->add_order_note(__('The client started to pay.', 'wc-robokassa'));

        /**
         * Logger notice
         */
        $this->logger->addNotice('The client started to pay.');

	    /**
	     * Return data
	     */
        return array
        (
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url( true )
        );
    }

    /**
     * receipt_page
     */
    public function receipt_page($order)
    {
        echo '<p>'.__('Thank you for your order, please press the button below to pay.', 'wc-robokassa').'</p>';
        echo $this->generate_form($order);
    }

    /**
     * Check instant payment notification
     */
    public function check_ipn()
    {
        /**
         * Insert $_REQUEST into debug mode
         */
        $this->logger->addDebug(print_r($_REQUEST, true));

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
                if ($_GET['action'] === 'success')
                {
                    $signature_pass = $this->test_shop_pass_1;
                }
                else
                {
                    $signature_pass = $this->test_shop_pass_2;
                }

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
                if ($_GET['action'] === 'success')
                {
                    $signature_pass = $this->shop_pass_1;
                }
                else
                {
                    $signature_pass = $this->shop_pass_2;
                }

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
             * Get order object
             */
            $order = wc_get_order($order_id);

            /**
             * Order not found
             */
            if($order === false)
            {
                /**
                 * Logger notice
                 */
                $this->logger->addNotice('Api RESULT request error. Order not found.');

                /**
                 * Send Service unavailable
                 */
                wp_die(__('Order not found.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
            }

            /**
             * Local signature
             */
            $signature_payload = $sum.':'.$order_id.':'.$signature_pass;
            $local_signature = $this->get_signature($signature_payload, $signature_method);

            /**
             * Add order note
             */
            $order->add_order_note(sprintf(__('Robokassa request success. Sum: %1$s Signature: %2$s Remote signature: %3$s', 'wc-robokassa'), $sum, $local_signature, $signature));

            /**
             * Logger info
             */
            $this->logger->addInfo('Robokassa request success.');

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

                    /**
                     * Logger info
                     */
                    $this->logger->addError('Validate secret key error. Local hash != remote hash.');
                }

                /**
                 * Validated
                 */
                if($validate === true)
                {
                    /**
                     * Logger info
                     */
                    $this->logger->addInfo('Result Validated success.');

                    /**
                     * Testing
                     */
                    if($test === true)
                    {
                        /**
                         * Add order note
                         */
                        $order->add_order_note(__('Order successfully paid (TEST MODE).', 'wc-robokassa'));

                        /**
                         * Logger notice
                         */
                        $this->logger->addNotice('Order successfully paid (TEST MODE).');
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

                        /**
                         * Logger notice
                         */
                        $this->logger->addNotice('Order successfully paid.');
                    }

                    /**
                     * Logger notice
                     */
                    $this->logger->addInfo('Payment complete.');

                    /**
                     * Set status is payment
                     */
                    $order->payment_complete();
                    die('OK'.$order_id);
                }

                /**
                 * Logger notice
                 */
                $this->logger->addError('Result Validated error. Payment error, please pay other time.');

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
                 * Add order note
                 */
                $order->add_order_note(__('Client return to success page.', 'wc-robokassa'));

                /**
                 * Logger info
                 */
                $this->logger->addInfo('Client return to success page.');

                /**
                 * Empty cart
                 */
                WC()->cart->empty_cart();

                /**
                 * Redirect to success
                 */
                wp_redirect( $this->get_return_url( $order ) );
                die();
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
                 * Logger info
                 */
                $this->logger->addInfo('The order has not been paid.');

                /**
                 * Sen status is failed
                 */
                $order->update_status('failed');

                /**
                 * Redirect to cancel
                 */
                wp_redirect( str_replace('&amp;', '&', $order->get_cancel_order_url() ) );
                die();
            }
        }

        /**
         * Logger notice
         */
        $this->logger->addNotice('Api request error. Action not found.');

        /**
         * Send Service unavailable
         */
        wp_die(__('Api request error. Action not found.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
    }

	/**
	 * Check if the gateway is available for use.
	 *
	 * @since 1.0.0.1
	 *
	 * @return bool
	 */
	public function is_available()
	{
		return parent::is_available();
	}

	/**
	 * Интерфейс расчёта суммы к получению магазином
     *
     * - Только для физических лиц.
     *
     * Позволяет рассчитать сумму к получению, исходя из текущих курсов ROBOKASSA, по сумме, которую заплатит пользователь.
     *
     * @param $IncCurrLabel string Код валюты, для которой нужно произвести расчет суммы к оплате.
     * @param $IncSum mixed Сумма, которую должен будет заплатить пользователь.
     *
     * @return mixed
	 */
	public function xml_calc_out_sum($IncCurrLabel, $IncSum)
    {
	    /**
	     * Check SimpleXMLElement installed
	     */
        if(!class_exists('SimpleXMLElement'))
        {
            return false;
        }

	    /**
	     * Request args
	     */
	    $args = array
	    (
		    'timeout' => 20,
		    'body' => ''
	    );

	    /**
	     * Request execute
	     */
	    $response = wp_remote_post('https://auth.robokassa.ru/Merchant/WebService/Service.asmx/CalcOutSumm?MerchantLogin=' . $this->shop_login . '&IncCurrLabel='.$IncCurrLabel.'&IncSum=' . $IncSum, $args);

	    /**
	     * Response get
	     */
	    $response_body = wp_remote_retrieve_body($response);

	    /**
         * Response normalize
         */
	    $response_data = new SimpleXMLElement($response_body);

	    /**
	     * Check error
	     */
	    if($response_data->Result->Code != 0)
        {
            return false;
        }

	    return $response_data->OutSum;
    }

	/**
	 * Интерфейс получения состояния оплаты счета
     *
     * Возвращает детальную информацию о текущем состоянии и реквизитах оплаты.
     * Необходимо помнить, что операция инициируется не в момент ухода пользователя на оплату,
     * а позже – после подтверждения его платежных реквизитов, т.е. Вы вполне можете не находить операцию,
     * которая по Вашему мнению уже должна начаться.
	 */
    public function xml_op_state()
    {

    }

	/**
	 * Интерфейс получения состояния оплаты счета (расширенный)
	 *
	 * Возвращает детальную информацию о текущем состоянии и реквизитах оплаты.
	 * Необходимо помнить, что операция инициируется не в момент ухода пользователя на оплату,
	 * а позже – после подтверждения его платежных реквизитов, т.е. Вы вполне можете не находить операцию,
	 * которая по Вашему мнению уже должна начаться.
	 */
	public function xml_op_state_ext()
	{

	}

	/**
	 * Интерфейс получения списка валют
     *
     * Возвращает список валют, доступных для оплаты заказов указанного магазина/сайта.
     * Используется для указания значений параметра IncCurrLabel, также используется
     * для отображения доступных вариантов оплаты непосредственно на сайте.
     *
	 */
    public function xml_get_currencies()
    {

    }

	/**
	 * Интерфейс получения списка доступных способов оплаты
     *
     * Возвращает список способов оплаты, доступных для оплаты заказов указанного магазина/сайта.
     * Используется для отображения доступных способов оплаты непосредственно на сайте,
     * если Вы желаете дать больше информации своим клиентам. Основное отличие от Списка валют – здесь
     * не показывается детальная информация по всем вариантам оплаты, здесь отображаются группы/методы оплаты.
	 */
    public function xml_get_payment_methods()
    {

    }

	/**
	 * Интерфейс расчёта суммы к оплате с учётом комиссии сервиса
     *
     * - Только для физических лиц.
     *
     * Позволяет рассчитать сумму, которую должен будет заплатить покупатель,
     * с учётом комиссий ROBOKASSA (согласно тарифам)
     * и тех систем, через которые покупатель решил совершать оплату заказа.
     * Может быть использован как для внутренних расчётов,
     * так и для дополнительного информирования клиентов на сайте.
	 */
    public function xm_get_rates()
    {

    }

	/**
	 * Получение информации о доступном лимите платежей
	 */
    public function xml_get_limit()
    {
    }

	/**
     * Convert xml to array
     *
	 * @param $xml
	 */
    public function xml_to_array($xml)
    {

    }

    /**
     * Display the test notice
     **/
    public function test_notice()
    {
        ?>
        <div class="update-nag" style="">
            <?php $link = '<a href="'. admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_robokassa') .'">'.__('here', 'wc-robokassa').'</a>';
            echo sprintf( __( 'Robokassa test mode is enabled. Click %s -  to disable it when you want to start accepting live payment on your site.', 'wc-robokassa' ), $link ) ?>
        </div>
        <?php
    }

    /**
     * Display the debug notice
     **/
    public function debug_notice()
    {
        ?>
        <div class="update-nag">
            <?php $link = '<a href="'. admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_robokassa') .'">'.__('here', 'wc-robokassa').'</a>';
            echo sprintf( __( 'Robokassa debug tool is enabled. Click %s -  to disable.', 'wc-robokassa' ), $link ) ?>
        </div>
        <?php
    }
}