<?php
/**
 * Main method class
 *
 * @package Mofsy/WC_Robokassa
 */
defined('ABSPATH') || exit;

class Wc_Robokassa_Method extends WC_Payment_Gateway
{
	/**
	 * Unique gateway id
	 *
	 * @var string
	 */
	public $id = 'robokassa';

	/**
	 * All support WooCommerce currency
	 *
	 * @var array
	 */
	public $currency_all = array
	(
		'RUB', 'USD', 'EUR', 'KZT'
	);

	/**
	 * Shop login from Robokassa
	 *
	 * @var string
	 */
	public $shop_login = '';

	/**
	 * Shop pass 1 from Robokassa
	 *
	 * @var string
	 */
	public $shop_pass_1 = '';

	/**
	 * Shop pass 2 from Robokassa
	 *
	 * @var string
	 */
	public $shop_pass_2 = '';

	/**
	 * Hashing for signature from Robokassa
	 *
	 * @var string
	 */
	public $sign_method = 'sha256';

	/**
	 * Form url for Merchant
	 *
	 * @var string
	 */
	public $form_url = 'https://auth.robokassa.ru/Merchant/Index.aspx';

	/**
	 * User language
	 *
	 * @var string
	 */
	public $user_interface_language = 'ru';

	/**
	 * Flag for test mode
	 *
	 * @var mixed
	 */
	public $test = 'no';

	/**
	 * Test shop pass 1 from Robokassa
	 *
	 * @var string
	 */
	public $test_shop_pass_1 = '';

	/**
	 * Test shop pass 2 from Robokassa
	 *
	 * @var string
	 */
	public $test_shop_pass_2 = '';

	/**
	 * Hashing for signature with test mode from Robokassa
	 *
	 * @var string
	 */
	public $test_sign_method = 'sha256';

	/**
	 * Receipt status
	 *
	 * @var bool
	 */
	public $ofd_status = false;

	/**
	 * Tax system
	 *
	 * @var string
	 */
	public $ofd_sno = 'usn';

	/**
	 * @var string
	 */
	public $ofd_nds = 'none';

	/**
	 * @var string
	 */
	public $ofd_payment_method = '';

	/**
	 * @var string
	 */
	public $ofd_payment_object = '';

	/**
	 * Page skipping
	 *
	 * @var string
	 */
	public $page_skipping = 'no';

	/**
	 * Max receipt items
	 *
	 * @var int
	 */
	protected $receipt_items_limit = 100;

	/**
	 * WC_Robokassa constructor
	 */
	public function __construct()
	{
		/**
		 * The gateway shows fields on the checkout OFF
		 */
		$this->has_fields = false;

		/**
		 * Admin title
		 */
		$this->method_title = __('Robokassa', 'wc-robokassa');

		/**
		 * Admin method description
		 */
		$this->method_description = __('Pay via Robokassa.', 'wc-robokassa');

		/**
		 * Init
		 */
		$this->init_logger();
		$this->init_filters();
		$this->init_form_fields();
		$this->init_settings();
		$this->init_options();
		$this->init_actions();

		/**
		 * Save options
		 */
		$this->process_options();

		/**
		 * Receipt page
		 */
		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'), 10);

		/**
		 * Auto redirect
		 */
		add_action('wc_robokassa_input_payment_notifications', array($this, 'wc_robokassa_input_payment_notifications_redirect_by_form'), 20);

		/**
		 * Payment listener/API hook
		 */
		add_action('woocommerce_api_wc_' . $this->id, array($this, 'input_payment_notifications'), 10);
	}

	/**
	 * Admin options
	 */
	public function process_options()
	{
		wc_robokassa_logger()->info('process_options: start');

		if(current_user_can('manage_options') && is_admin())
		{
			wc_robokassa_logger()->info('process_options: manage_options success');

			/**
			 * Options save
			 */
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			), 10);

			/**
			 * Update last version
			 */
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'wc_robokassa_last_settings_update_version'
			), 10);
		}

		wc_robokassa_logger()->info('process_options: end');
	}

	/**
	 * Logger
	 */
	public function init_logger()
	{
		if($this->get_option('logger') !== '')
		{
			wc_robokassa_logger()->set_level($this->get_option('logger'));

			$file_name = WC_Robokassa()->get_logger_filename();

			wc_robokassa_logger()->set_name($file_name);
		}
	}

	/**
	 * Initialize filters
	 */
	public function init_filters()
	{
		wc_robokassa_logger()->info('init_filters: start');

		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_main'), 10);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_test_payments'), 20);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_interface'), 30);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_ofd'), 40);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_order_notes'), 45);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_technical'), 50);

		wc_robokassa_logger()->info('init_filters: end');
	}

	/**
	 * Init actions
	 */
	public function init_actions()
	{
		wc_robokassa_logger()->info('init_actions: start');

		/**
		 * Payment fields description show
		 */
		add_action('wc_robokassa_payment_fields_show', array($this, 'payment_fields_description_show'), 10);

		/**
		 * Payment fields test mode show
		 */
		add_action('wc_robokassa_payment_fields_after_show', array($this, 'payment_fields_test_mode_show'), 10);

		/**
		 * Receipt form show
		 */
		add_action('wc_robokassa_receipt_page_show', array($this, 'wc_robokassa_receipt_page_show_form'), 10);

		wc_robokassa_logger()->info('init_actions: end');
	}

	/**
	 * Update plugin version at settings update
	 */
	public function wc_robokassa_last_settings_update_version()
	{
		wc_robokassa_logger()->info('wc_robokassa_last_settings_update_version: start');

		$result = update_option('wc_robokassa_last_settings_update_version', WC_ROBOKASSA_VERSION);

		if($result)
		{
			wc_robokassa_logger()->info('wc_robokassa_last_settings_update_version: success');
		}
		else
		{
			wc_robokassa_logger()->notice('wc_robokassa_last_settings_update_version: not updated');
		}

		wc_robokassa_logger()->info('wc_robokassa_last_settings_update_version: end');
	}

	/**
	 * Init gateway options
	 */
	public function init_options()
	{
		wc_robokassa_logger()->info('init_options: start');

		/**
		 * Gateway not enabled?
		 */
		if($this->get_option('enabled') !== 'yes')
		{
			$this->enabled = false;
		}

		/**
		 * Page skipping enabled?
		 */
		if($this->get_option('page_skipping') === 'yes')
		{
			$this->set_page_skipping('yes');
		}

		/**
		 * Title for user interface
		 */
		$this->title = $this->get_option('title');

		/**
		 * Set description
		 */
		$this->description = $this->get_option('description');

		/**
		 * Testing?
		 */
		$this->set_test($this->get_option('test'));

		/**
		 * Default language for Robokassa interface
		 */
		$this->set_user_interface_language($this->get_option('language'));

		/**
		 * Automatic language
		 */
		if($this->get_option('language_auto') === 'yes')
		{
			$lang = get_locale();
			switch($lang)
			{
				case 'en_EN':
					$this->set_user_interface_language('en');
					break;
				default:
					$this->set_user_interface_language('ru');
					break;
			}
		}

		/**
		 * Set order button text
		 */
		$this->order_button_text = $this->get_option('order_button_text');

		/**
		 * Ofd
		 */
		if($this->get_option('ofd_status') === 'yes')
		{
			$this->set_ofd_status(true);
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

			$this->set_ofd_sno($ofd_sno);
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
				$ofd_nds = 'vat20';
			}

			if($ofd_nds_code == '4')
			{
				$ofd_nds = 'vat110';
			}

			if($ofd_nds_code == '5')
			{
				$ofd_nds = 'vat120';
			}

			$this->set_ofd_nds($ofd_nds);
		}

		/**
		 * Set ofd_payment_method
		 */
		if($this->get_option('ofd_payment_method') !== '')
		{
			$this->set_ofd_payment_method($this->get_option('ofd_payment_method'));
		}

		/**
		 * Set ofd_payment_object
		 */
		if($this->get_option('ofd_payment_object') !== '')
		{
			$this->set_ofd_payment_object($this->get_option('ofd_payment_object'));
		}

		/**
		 * Set shop pass 1
		 */
		if($this->get_option('shop_pass_1') !== '')
		{
			$this->set_shop_pass_1($this->get_option('shop_pass_1'));
		}

		/**
		 * Set shop pass 2
		 */
		if($this->get_option('shop_pass_2') !== '')
		{
			$this->set_shop_pass_2($this->get_option('shop_pass_2'));
		}

		/**
		 * Load shop login
		 */
		$this->set_shop_login($this->get_option('shop_login'));

		/**
		 * Load sign method
		 */
		$this->set_sign_method($this->get_option('sign_method'));

		/**
		 * Set shop pass 1 for testing
		 */
		if($this->get_option('test_shop_pass_1') !== '')
		{
			$this->set_test_shop_pass_1($this->get_option('test_shop_pass_1'));
		}

		/**
		 * Set shop pass 2 for testing
		 */
		if($this->get_option('test_shop_pass_2') !== '')
		{
			$this->set_test_shop_pass_2($this->get_option('test_shop_pass_2'));
		}

		/**
		 * Load sign method for testing
		 */
		$this->set_test_sign_method($this->get_option('test_sign_method'));

		/**
		 * Set icon
		 */
		if($this->get_option('enable_icon') === 'yes')
		{
			$this->icon = apply_filters('woocommerce_icon_robokassa', WC_ROBOKASSA_URL . 'assets/img/robokassa.png');
		}

		/**
		 * Gateway allowed?
		 */
		if($this->is_valid_for_use() === false)
		{
			$this->enabled = false;
		}

		wc_robokassa_logger()->info('init_options: success');
	}

	/**
	 * Get shop login
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_shop_login()
	{
		return $this->shop_login;
	}

	/**
	 * Set shop login
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $shop_login
	 */
	public function set_shop_login($shop_login)
	{
		$this->shop_login = $shop_login;
	}

	/**
	 * Get shop pass 1
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_shop_pass_1()
	{
		return $this->shop_pass_1;
	}

	/**
	 * Set shop pass 1
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $shop_pass_1
	 */
	public function set_shop_pass_1($shop_pass_1)
	{
		$this->shop_pass_1 = $shop_pass_1;
	}

	/**
	 * Get shop pass 2
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_shop_pass_2()
	{
		return $this->shop_pass_2;
	}

	/**
	 * Set shop pass 2
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $shop_pass_2
	 */
	public function set_shop_pass_2($shop_pass_2)
	{
		$this->shop_pass_2 = $shop_pass_2;
	}

	/**
	 * Get signature method for real payments
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_sign_method()
	{
		return $this->sign_method;
	}

	/**
	 * Set signature method for real payments
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $sign_method
	 */
	public function set_sign_method($sign_method)
	{
		$this->sign_method = $sign_method;
	}

	/**
	 * Get form url for send
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_form_url()
	{
		return $this->form_url;
	}

	/**
	 * Set form url for send
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $form_url
	 */
	public function set_form_url($form_url)
	{
		$this->form_url = $form_url;
	}

	/**
	 * Get user interface language
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_user_interface_language()
	{
		return $this->user_interface_language;
	}

	/**
	 * Set user interface language
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $user_interface_language
	 */
	public function set_user_interface_language($user_interface_language)
	{
		$this->user_interface_language = $user_interface_language;
	}

	/**
	 * Get flag for test mode
	 *
	 * @since 2.2.0.1
	 *
	 * @return mixed
	 */
	public function get_test()
	{
		return $this->test;
	}

	/**
	 * Set flag for test mode
	 *
	 * @since 2.2.0.1
	 *
	 * @param mixed $test
	 */
	public function set_test($test)
	{
		$this->test = $test;
	}

	/**
	 * Get page skipping flag
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_page_skipping()
	{
		return $this->page_skipping;
	}

	/**
	 * Set page skipping flag
	 *
	 * @since 2.4.0
	 *
	 * @param string $page_skipping
	 */
	public function set_page_skipping($page_skipping)
	{
		$this->page_skipping = $page_skipping;
	}

	/**
	 * Get test shop pass 1
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_test_shop_pass_1()
	{
		return $this->test_shop_pass_1;
	}

	/**
	 * Set test shop pass 1
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $test_shop_pass_1
	 */
	public function set_test_shop_pass_1($test_shop_pass_1)
	{
		$this->test_shop_pass_1 = $test_shop_pass_1;
	}

	/**
	 * Get test shop pass 2
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_test_shop_pass_2()
	{
		return $this->test_shop_pass_2;
	}

	/**
	 * Set test shop pass 2
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $test_shop_pass_2
	 */
	public function set_test_shop_pass_2($test_shop_pass_2)
	{
		$this->test_shop_pass_2 = $test_shop_pass_2;
	}

	/**
	 * Get test signature method
	 *
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_test_sign_method()
	{
		return $this->test_sign_method;
	}

	/**
	 * Set test signature method
	 *
	 * @since 2.2.0.1
	 *
	 * @param string $test_sign_method
	 */
	public function set_test_sign_method($test_sign_method)
	{
		$this->test_sign_method = $test_sign_method;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @return bool
	 */
	public function is_ofd_status()
	{
		return $this->ofd_status;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @param bool $ofd_status
	 */
	public function set_ofd_status($ofd_status)
	{
		$this->ofd_status = $ofd_status;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_ofd_sno()
	{
		return $this->ofd_sno;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @param string $ofd_sno
	 */
	public function set_ofd_sno($ofd_sno)
	{
		$this->ofd_sno = $ofd_sno;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_ofd_nds()
	{
		return $this->ofd_nds;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @param string $ofd_nds
	 */
	public function set_ofd_nds($ofd_nds)
	{
		$this->ofd_nds = $ofd_nds;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_ofd_payment_method()
	{
		return $this->ofd_payment_method;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @param string $ofd_payment_method
	 */
	public function set_ofd_payment_method($ofd_payment_method)
	{
		$this->ofd_payment_method = $ofd_payment_method;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @return string
	 */
	public function get_ofd_payment_object()
	{
		return $this->ofd_payment_object;
	}

	/**
	 * @since 2.2.0.1
	 *
	 * @param string $ofd_payment_object
	 */
	public function set_ofd_payment_object($ofd_payment_object)
	{
		$this->ofd_payment_object = $ofd_payment_object;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = apply_filters('wc_robokassa_init_form_fields', array());
	}

	/**
	 * Add fields for main settings
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_main($fields)
	{
		$fields['main'] = array
		(
			'title'       => __('Main settings', 'wc-robokassa'),
			'type'        => 'title',
			'description' => __('Without these settings, the payment gateway will not work. Be sure to make settings in this block.', 'wc-robokassa'),
		);

		$fields['enabled'] = array
		(
			'title'       => __('Online / Offline', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Tick the checkbox if you need to activate the payment gateway.', 'wc-robokassa'),
			'description' => __('On disconnection, the payment gateway will not be available for selection on the site. It is useful for payments through subsidiaries, or just in case of temporary disconnection.', 'wc-robokassa'),
			'default'     => 'off'
		);

		$fields['shop_login'] = array
		(
			'title'       => __('Shop identifier', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Unique identifier for shop from Robokassa.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['sign_method'] = array
		(
			'title'       => __('Hash calculation algorithm', 'wc-robokassa'),
			'description' => __('The algorithm must match the one specified in the personal account of Robokassa.', 'wc-robokassa'),
			'type'        => 'select',
			'options'     => array
			(
				'md5'       => 'MD5',
				'ripemd160' => 'RIPEMD160',
				'sha1'      => 'SHA1',
				'sha256'    => 'SHA256',
				'sha384'    => 'SHA384',
				'sha512'    => 'SHA512'
			),
			'default'     => 'sha256'
		);

		$fields['shop_pass_1'] = array
		(
			'title'       => __('Password #1', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Shop pass 1. The pass must match the one specified in the personal account of Robokassa.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['shop_pass_2'] = array
		(
			'title'       => __('Password #2', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Shop pass 2. The pass must match the one specified in the personal account of Robokassa.', 'wc-robokassa'),
			'default'     => ''
		);

		$result_url_description = '<p class="input-text regular-input robokassa_urls">' . WC_Robokassa()->get_result_url() . '</p>' . __('Address to notify the site of the results of operations in the background. Copy the address and enter it in your personal account ROBOKASSA in the technical settings. Notification method: POST.', 'wc-robokassa');

		$fields['result_url'] = array
		(
			'title'       => __('Result Url', 'wc-robokassa'),
			'type'        => 'text',
			'disabled'    => true,
			'description' => $result_url_description,
			'default'     => ''
		);

		$success_url_description = '<p class="input-text regular-input robokassa_urls">' . WC_Robokassa()->get_success_url() . '</p>' . __('The address for the user to go to the site after successful payment. Copy the address and enter it in your personal account ROBOKASSA in the technical settings. Notification method: POST. You can specify other addresses of your choice.', 'wc-robokassa');

		$fields['success_url'] = array
		(
			'title'       => __('Success Url', 'wc-robokassa'),
			'type'        => 'text',
			'disabled'    => true,
			'description' => $success_url_description,
			'default'     => ''
		);

		$fail_url_description = '<p class="input-text regular-input robokassa_urls">' . WC_Robokassa()->get_fail_url() . '</p>' . __('The address for the user to go to the site, after payment with an error. Copy the address and enter it in your personal account ROBOKASSA in the technical settings. Notification method: POST. You can specify other addresses of your choice.', 'wc-robokassa');

		$fields['fail_url'] = array
		(
			'title'       => __('Fail Url', 'wc-robokassa'),
			'type'        => 'text',
			'disabled'    => true,
			'description' => $fail_url_description,
			'default'     => ''
		);

		return $fields;
	}

	/**
	 * Add settings for test payments
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_test_payments($fields)
	{
		$fields['test_payments'] = array
		(
			'title'       => __('Parameters for test payments', 'wc-robokassa'),
			'type'        => 'title',
			'description' => __('Passwords and hashing algorithms for test payments differ from those specified for real payments.', 'wc-robokassa'),
		);

		$fields['test'] = array
		(
			'title'       => __('Test mode', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'   => __('Enable test mode?', 'wc-robokassa'),
			'description' => __('When you activate the test mode, no funds will be debited. In this case, the payment gateway will only be displayed when you log in with an administrator account. This is done in order to protect you from false orders.', 'wc-robokassa'),
			'default'     => 'yes'
		);

		$fields['test_sign_method'] = array
		(
			'title'       => __('Hash calculation algorithm', 'wc-robokassa'),
			'description' => __('The algorithm must match the one specified in the personal account of ROBOKASSA.', 'wc-robokassa'),
			'type'        => 'select',
			'options'     => array
			(
				'md5'       => 'MD5',
				'ripemd160' => 'RIPEMD160',
				'sha1'      => 'SHA1',
				'sha256'    => 'SHA256',
				'sha384'    => 'SHA384',
				'sha512'    => 'SHA512'
			),
			'default'     => 'sha256'
		);

		$fields['test_shop_pass_1'] = array
		(
			'title'       => __('Password #1', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Please write Shop pass 1 for testing payments. The pass must match the one specified in the personal account of ROBOKASSA.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['test_shop_pass_2'] = array
		(
			'title'       => __('Password #2', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Please write Shop pass 2 for testing payments. The pass must match the one specified in the personal account of ROBOKASSA.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['test_checkout_notice'] = array
		(
			'title'   => __('Show notice in checkout?', 'wc-robokassa'),
			'type'    => 'checkbox',
			'label'   => __('Show notice for test mode on checkout page.', 'wc-robokassa'),
			'description' => __('Only working in test mode active.', 'wc-robokassa'),
			'default' => 'yes'
		);

		return $fields;
	}

	/**
	 * Add settings for interface
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_interface($fields)
	{
		$fields['interface'] = array
		(
			'title'       => __('Interface', 'wc-robokassa'),
			'type'        => 'title',
			'description' => __('Customize the appearance. Can leave it at that.', 'wc-robokassa'),
		);

		$fields['enable_icon'] = array
		(
			'title'   => __('Show gateway icon?', 'wc-robokassa'),
			'type'    => 'checkbox',
			'label'   => __('Show', 'wc-robokassa'),
			'default' => 'yes'
		);

		$fields['language'] = array
		(
			'title'       => __('Language interface', 'wc-robokassa'),
			'type'        => 'select',
			'options'     => array
			(
				'ru' => __('Russian', 'wc-robokassa'),
				'en' => __('English', 'wc-robokassa')
			),
			'description' => __('What language interface displayed for the customer on Robokassa?', 'wc-robokassa'),
			'default'     => 'ru'
		);

		$fields['language_auto'] = array
		(
			'title'       => __('Language based on the locale?', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'   => __('Enable user language automatic detection?', 'wc-robokassa'),
			'description' => __('Automatic detection of the users language from the WordPress environment.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['page_skipping'] = array
		(
			'title'       => __('Skip the received order page?', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'   => __('Enable skip order page?', 'wc-robokassa'),
			'description' => __('This setting is used to reduce actions when users switch to payment.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['title'] = array
		(
			'title'       => __('Title', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('This is the name that the user sees during the payment.', 'wc-robokassa'),
			'default'     => __('Robokassa', 'wc-robokassa')
		);

		$fields['order_button_text'] = array
		(
			'title'       => __('Order button text', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('This is the button text that the user sees during the payment.', 'wc-robokassa'),
			'default'     => __('Goto pay', 'wc-robokassa')
		);

		$fields['description'] = array
		(
			'title'       => __('Description', 'wc-robokassa'),
			'type'        => 'textarea',
			'description' => __('Description of the method of payment that the customer will see on our website.', 'wc-robokassa'),
			'default'     => __('Payment via Robokassa.', 'wc-robokassa')
		);

		return $fields;
	}

	/**
	 * Add settings for OFD
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_ofd($fields)
	{
		$fields['ofd'] = array
		(
			'title'       => __('Cart content sending (54fz)', 'wc-robokassa'),
			'type'        => 'title',
			'description' => __('These settings are required only for legal entities in the absence of its cash machine.', 'wc-robokassa'),
		);

		$fields['ofd_status'] = array
		(
			'title'       => __('The transfer of goods', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Enable', 'wc-robokassa'),
			'description' => __('When you select the option, a check will be generated and sent to the tax and customer. When used, you must set up the VAT of the items sold. VAT is calculated according to the legislation of the Russian Federation. There may be differences in the amount of VAT with the amount calculated by the store.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['ofd_sno'] = array
		(
			'title'   => __('Taxation system', 'wc-robokassa'),
			'type'    => 'select',
			'default' => '0',
			'options' => array
			(
				'0' => __('General', 'wc-robokassa'),
				'1' => __('Simplified, income', 'wc-robokassa'),
				'2' => __('Simplified, income minus consumption', 'wc-robokassa'),
				'3' => __('Single tax on imputed income', 'wc-robokassa'),
				'4' => __('Single agricultural tax', 'wc-robokassa'),
				'5' => __('Patent system of taxation', 'wc-robokassa'),
			),
		);

		$fields['ofd_nds'] = array
		(
			'title'   => __('Default VAT rate', 'wc-robokassa'),
			'type'    => 'select',
			'default' => '0',
			'options' => array
			(
				'0' => __('Without the vat', 'wc-robokassa'),
				'1' => __('VAT 0%', 'wc-robokassa'),
				'2' => __('VAT 10%', 'wc-robokassa'),
				'3' => __('VAT 20%', 'wc-robokassa'),
				'4' => __('VAT receipt settlement rate 10/110', 'wc-robokassa'),
				'5' => __('VAT receipt settlement rate 20/120', 'wc-robokassa'),
			),
		);

		$fields['ofd_payment_method'] = array
		(
			'title'       => __('Indication of the calculation method', 'wc-robokassa'),
			'description' => __('The parameter is optional. If this parameter is not configured, the check will indicate the default value of the parameter from the Personal account.', 'wc-robokassa'),
			'type'        => 'select',
			'default'     => '',
			'options'     => array
			(
				''                => __('Default in Robokassa', 'wc-robokassa'),
				'full_prepayment' => __('Prepayment 100%', 'wc-robokassa'),
				'prepayment'      => __('Partial prepayment', 'wc-robokassa'),
				'advance'         => __('Advance', 'wc-robokassa'),
				'full_payment'    => __('Full settlement', 'wc-robokassa'),
				'partial_payment' => __('Partial settlement and credit', 'wc-robokassa'),
				'credit'          => __('Transfer on credit', 'wc-robokassa'),
				'credit_payment'  => __('Credit payment', 'wc-robokassa')
			),
		);

		$fields['ofd_payment_object'] = array
		(
			'title'       => __('Sign of the subject of calculation', 'wc-robokassa'),
			'description' => __('The parameter is optional. If this parameter is not configured, the check will indicate the default value of the parameter from the Personal account.', 'wc-robokassa'),
			'type'        => 'select',
			'default'     => '',
			'options'     => array
			(
				''                      => __('Default in Robokassa', 'wc-robokassa'),
				'commodity'             => __('Product', 'wc-robokassa'),
				'excise'                => __('Excisable goods', 'wc-robokassa'),
				'job'                   => __('Work', 'wc-robokassa'),
				'service'               => __('Service', 'wc-robokassa'),
				'gambling_bet'          => __('Gambling rate', 'wc-robokassa'),
				'gambling_prize'        => __('Gambling win', 'wc-robokassa'),
				'lottery'               => __('Lottery ticket', 'wc-robokassa'),
				'lottery_prize'         => __('Winning the lottery', 'wc-robokassa'),
				'intellectual_activity' => __('Results of intellectual activity', 'wc-robokassa'),
				'payment'               => __('Payment', 'wc-robokassa'),
				'agent_commission'      => __('Agency fee', 'wc-robokassa'),
				'composite'             => __('Compound subject of calculation', 'wc-robokassa'),
				'another'               => __('Another object of the calculation', 'wc-robokassa'),
				'property_right'        => __('Property right', 'wc-robokassa'),
				'non-operating_gain'    => __('Extraordinary income', 'wc-robokassa'),
				'insurance_premium'     => __('Insurance premium', 'wc-robokassa'),
				'sales_tax'             => __('Sales tax', 'wc-robokassa'),
				'resort_fee'            => __('Resort fee', 'wc-robokassa')
			),
		);

		return $fields;
	}

	/**
	 * Add settings for order notes
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_order_notes($fields)
	{
		$fields['orders_notes'] = array
		(
			'title'       => __('Orders notes', 'wc-robokassa'),
			'type'        => 'title',
			'description' => __('Settings for adding notes to orders. All are off by default.', 'wc-robokassa'),
		);

		$fields['orders_notes_robokassa_request'] = array
		(
			'title'       => __('Request from Robokassa', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Enable', 'wc-robokassa'),
			'description' => __('All requests from Robokassa for orders will be added to the notes.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_validate_error'] = array
		(
			'title'       => __('Validation errors of requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Enable', 'wc-robokassa'),
			'description' => __('Adding to the notes all the data related to the check for error requests.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_result'] = array
		(
			'title'       => __('Result requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Enable', 'wc-robokassa'),
			'description' => __('Adding payment result data to order notes.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_fail'] = array
		(
			'title'       => __('Failed requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Enable', 'wc-robokassa'),
			'description' => __('Adding customers return data to the cancellation page in the notes.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_success'] = array
		(
			'title'       => __('Success requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Enable', 'wc-robokassa'),
			'description' => __('Adding customers return data to the successful payment page in the notes.', 'wc-robokassa'),
			'default'     => 'no'
		);

		return $fields;
	}

	/**
	 * Add settings for technical
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_technical($fields)
	{
		$fields['technical'] = array
		(
			'title'       => __('Technical details', 'wc-robokassa'),
			'type'        => 'title',
			'description' => __('Setting technical parameters. Used by technical specialists. Can leave it at that.', 'wc-robokassa'),
		);

		$fields['logger'] = array
		(
			'title'       => __('Logging', 'wc-robokassa'),
			'type'        => 'select',
			'description' => __('You can enable gateway logging, specify the level of error that you want to benefit from logging. All sensitive data in the report are deleted. By default, the error rate should not be less than ERROR.', 'wc-robokassa'),
			'default'     => '400',
			'options'     => array
			(
				''    => __('Off', 'wc-robokassa'),
				'100' => 'DEBUG',
				'200' => 'INFO',
				'250' => 'NOTICE',
				'300' => 'WARNING',
				'400' => 'ERROR',
				'500' => 'CRITICAL',
				'550' => 'ALERT',
				'600' => 'EMERGENCY'
			)
		);

		$fields['cart_clearing'] = array
		(
			'title'       => __('Cart clearing', 'wc-robokassa'),
			'type'        => 'select',
			'description' => __('Clean the customers cart if payment is successful? If so, the shopping cart will be cleaned. If not, the goods already purchased will most likely remain in the shopping cart.', 'wc-robokassa'),
			'default'     => 'no',
			'options'     => array
			(
				'yes'    => __('Yes', 'wc-robokassa'),
				'no' => __('No', 'wc-robokassa'),
			)
		);

		$fields['fail_set_order_status_failed'] = array
		(
			'title'       => __('Mark order as cancelled?', 'wc-robokassa'),
			'type'        => 'select',
			'description' => __('Change the status of the order to canceled when the user cancels the payment. The status changes when the user returns to the cancelled payment page.', 'wc-robokassa'),
			'default'     => 'no',
			'options'     => array
			(
				'yes'    => __('Yes', 'wc-robokassa'),
				'no' => __('No', 'wc-robokassa'),
			)
		);

		return $fields;
	}

	/**
	 * Check if this gateway is enabled and available in the user's country
	 */
	public function is_valid_for_use()
	{
		wc_robokassa_logger()->info('is_valid_for_use: start');

		/**
		 * Check allow currency
		 */
		if(!in_array(WC_Robokassa()->get_wc_currency(), $this->currency_all, false))
		{
			wc_robokassa_logger()->alert('is_valid_for_use: currency not support');
			return false;
		}

		/**
		 * Check test mode and admin rights
		 *
		 * @todo сделать возможность тестирования не только админами
		 */
		if($this->get_test() === 'yes' && false === current_user_can('manage_options'))
		{
			wc_robokassa_logger()->alert('is_valid_for_use: test mode only admin');
			return false;
		}

		wc_robokassa_logger()->info('is_valid_for_use: success');

		return true;
	}

	/**
	 * Output settings screen
	 */
	public function admin_options()
	{
		wp_enqueue_style('robokassa-admin-styles', WC_ROBOKASSA_URL . 'assets/css/main.css');

		// hook
		do_action('wc_robokassa_admin_options_before_show');

		echo '<h2>' . esc_html($this->get_method_title());
		wc_back_link(__('Return to payment gateways', 'wc-robokassa'), admin_url('admin.php?page=wc-settings&tab=checkout'));
		echo '</h2>';

		// hook
		do_action('wc_robokassa_admin_options_method_description_before_show');

		echo wp_kses_post(wpautop($this->get_method_description()));

		// hook
		do_action('wc_robokassa_admin_options_method_description_after_show');

		// hook
		do_action('wc_robokassa_admin_options_form_before_show');

		echo '<table class="form-table">' . $this->generate_settings_html($this->get_form_fields(), false) . '</table>';

		// hook
		do_action('wc_robokassa_admin_options_form_after_show');

		// hook
		do_action('wc_robokassa_admin_options_after_show');
	}

	/**
	 * There are no payment fields for sprypay, but we want to show the description if set
	 */
	public function payment_fields()
	{
		// hook
		do_action('wc_robokassa_payment_fields_before_show');

		// hook
		do_action('wc_robokassa_payment_fields_show');

		// hook
		do_action('wc_robokassa_payment_fields_after_show');
	}

	/**
	 * Show description on site
	 */
	public function payment_fields_description_show()
	{
		if($this->description)
		{
			echo wpautop(wptexturize($this->description));
		}
	}

	/**
	 * Show test mode on site
	 *
	 * @return void
	 */
	public function payment_fields_test_mode_show()
	{
		if($this->get_test() === 'yes' && $this->get_option('test_checkout_notice') === 'yes')
		{
			echo '<div style="padding:5px; border-radius:20px; background-color: #ff8982;text-align: center;">';
			echo __('TEST mode is active. Payment will not be charged. After checking, disable this mode.', 'wc-robokassa');
			echo '</div>';
		}
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
		wc_robokassa_logger()->info('process_payment: start');

		$order = wc_get_order($order_id);

		if($order === false)
		{
			wc_robokassa_logger()->error('process_payment: $order === false');

			return array
			(
				'result' => 'failure',
				'redirect' => ''
			);
		}

		// hook
		do_action('wc_robokassa_before_process_payment', $order_id, $order);

		wc_robokassa_logger()->debug('process_payment: order', $order);

		if(method_exists($order, 'add_order_note'))
		{
			$order->add_order_note(__('The client started to pay.', 'wc-robokassa'));
		}

		if($this->get_page_skipping() === 'yes')
		{
			wc_robokassa_logger()->info('process_payment: page skipping, success');

			return array
			(
				'result' => 'success',
				'redirect' => $this->get_url_auto_redirect($order_id)
			);
		}

		wc_robokassa_logger()->info('process_payment: success');

		return array
		(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		);
	}

	/**
	 * Receipt page
	 *
	 * @param $order
	 *
	 * @return void
	 */
	public function receipt_page($order)
	{
		// hook
		do_action('wc_robokassa_receipt_page_before_show', $order);

		// hook
		do_action('wc_robokassa_receipt_page_show', $order);

		// hook
		do_action('wc_robokassa_receipt_page_after_show', $order);
	}

	/**
	 * @param $order
	 *
	 * @return void
	 */
	public function wc_robokassa_receipt_page_show_form($order)
	{
		echo $this->generate_form($order);
	}

	/**
	 * Generate payments form
	 *
	 * @param $order_id
	 *
	 * @return string - payment form
	 **/
	public function generate_form($order_id)
	{
		wc_robokassa_logger()->info('generate_form: start');

		$order = wc_get_order($order_id);
		if(!is_object($order))
		{
			wc_robokassa_logger()->error('generate_form: $order', $order);
			die('Generate form error. Order not found.');
		}

		wc_robokassa_logger()->debug('generate_form: $order', $order);

		$args = array();
		$args['MerchantLogin'] = $this->get_shop_login();

		$out_sum = number_format($order->get_total(), 2, '.', '');
		$args['OutSum'] = $out_sum;

		$args['InvId'] = $order_id;
		$args['InvDesc'] = __('Order number: ' . $order_id, 'wc-robokassa');

		/**
		 * Rewrite currency from order
		 */
		if(WC_Robokassa()->get_wc_currency() !== $order->get_currency('view'))
		{
			wc_robokassa_logger()->info('generate_form: rewrite currency' . $order->get_currency());
			WC_Robokassa()->set_wc_currency($order->get_currency());
		}

		/**
		 * Set currency to Robokassa
		 */
		switch(WC_Robokassa()->get_wc_currency())
		{
			case 'USD':
				$args['OutSumCurrency'] = 'USD';
				break;
			case 'EUR':
				$args['OutSumCurrency'] = 'EUR';
				break;
			case 'KZT':
				$args['OutSumCurrency'] = 'KZT';
				break;
		}

		/**
		 * Test mode
		 */
		if($this->get_test() === 'yes')
		{
			wc_robokassa_logger()->info('generate_form: test mode active');

			$signature_pass = $this->get_test_shop_pass_1();
			$signature_method = $this->get_test_sign_method();

			$args['IsTest'] = 1;
		}
		/**
		 * Real payments
		 */
		else
		{
			wc_robokassa_logger()->info('generate_form: real payments');

			$signature_pass = $this->get_shop_pass_1();
			$signature_method = $this->get_sign_method();
		}

		/**
		 * Billing email
		 */
		$billing_email = $order->get_billing_email();
		if(!empty($billing_email))
		{
			$args['Email'] = $billing_email;
		}

		/**
		 * Receipt
		 */
		$receipt_result = '';
		if($this->is_ofd_status() === true)
		{
			wc_robokassa_logger()->info('generate_form: fiscal active');

			$receipt['sno'] = $this->get_ofd_sno();
			$receipt['items'] = $this->generate_receipt_items($order);

			$receipt_result = json_encode($receipt);

			wc_robokassa_logger()->debug('generate_form: $receipt_result', $receipt_result);
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
		$args['Culture'] = $this->get_user_interface_language();

		/**
		 * Execute filter wc_robokassa_form_args
		 */
		$args = apply_filters('wc_robokassa_payment_form_args', $args);

		/**
		 * Form inputs generic
		 */
		$args_array = array();
		foreach ($args as $key => $value)
		{
			$args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
		}

		wc_robokassa_logger()->info('generate_form: success');

		return '<form action="' . esc_url($this->get_form_url()) . '" method="POST" id="wc_robokassa_payment_form" accept-charset="utf-8">' . "\n" .
		       implode("\n", $args_array) .
		       '<input type="submit" class="button alt" id="submit_wc_robokassa_payment_form" value="' . __('Pay', 'wc-robokassa') .
		       '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel & return to cart', 'wc-robokassa') . '</a>' . "\n" .
		       '</form>';
	}

	/**
	 * Generate receipt
	 *
	 * @since 2.2.0.1
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function generate_receipt_items($order)
	{
		$receipt_items = array();

		wc_robokassa_logger()->info('generate_receipt_items: start');

		/**
		 * Order items
		 */
		foreach($order->get_items() as $receipt_items_key => $receipt_items_value)
		{
			/**
			 * Quantity
			 */
			$item_quantity = $receipt_items_value->get_quantity();

			/**
			 * Total item sum
			 */
			$item_total = $receipt_items_value->get_total();

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
				'tax' => $this->get_ofd_nds(),

				/**
				 * Payment method
				 */
				'payment_method' => $this->get_ofd_payment_method(),

				/**
				 * Payment object
				 */
				'payment_object' => $this->get_ofd_payment_object(),
			);
		}

		/**
		 * Delivery
		 */
		if($order->get_shipping_total() > 0)
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
				'name' => __('Delivery', 'wc-robokassa'),

				/**
				 * Стоимость предмета расчета с учетом скидок и наценок
				 *
				 * Цена в рублях:
				 *  целая часть не более 8 знаков;
				 *  дробная часть не более 2 знаков.
				 */
				'sum' => intval($order->get_shipping_total()),

				/**
				 * Количество/вес
				 *
				 * максимальная длина 128 символов
				 */
				'quantity' => 1,

				/**
				 * Tax
				 */
				'tax' => $this->get_ofd_nds(),

				/**
				 * Payment method
				 */
				'payment_method' => $this->get_ofd_payment_method(),

				/**
				 * Payment object
				 */
				'payment_object' => $this->get_ofd_payment_object(),
			);
		}

		wc_robokassa_logger()->info('generate_receipt_items: success');

		return $receipt_items;
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
	 * Получение ссылки на автоматический редирект в робокассу
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_url_auto_redirect($order_id) // todo: more options
	{
		return get_site_url( null, '/?wc-api=wc_' . $this->id . '&action=redirect&order_id=' . $order_id);
	}

	/**
	 * Автоматический редирект на робокассу методом автоматической отправки формы
	 */
	public function wc_robokassa_input_payment_notifications_redirect_by_form()
	{
		if(false == isset($_GET['action']))
		{
			return;
		}

		if(false == isset($_GET['order_id']))
		{
			return;
		}

		if($_GET['action'] !== 'redirect')
		{
			return;
		}

		if($_GET['order_id'] === '')
		{
			return;
		}

		$order_id = $_GET['order_id'];

		/**
		 * Form data
		 */
		$form_data = $this->generate_form($order_id);

		/**
		 * Page data
		 */
		$page_data = '<html lang="ru"><body style="display: none;" onload="document.forms.wc_robokassa_payment_form.submit()">' . $form_data .'</body></html>';

		/**
		 * Echo form an die :(
		 */
		die($page_data);
	}

	/**
	 * Check instant payment notification
	 *
	 * @return void
	 */
	public function input_payment_notifications()
	{
		wc_robokassa_logger()->info('input_payment_notifications: start');

		// hook
		do_action('wc_robokassa_input_payment_notifications');

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

		$action = '';
		if(isset($_REQUEST['action']))
		{
			$action = $_REQUEST['action'];
		}

		/**
		 * Test mode
		 */
		if($this->get_test() === 'yes' || (array_key_exists('IsTest', $_REQUEST) && $_REQUEST['IsTest'] == '1'))
		{
			$test = true;

			if($action === 'success')
			{
				$signature_pass = $this->get_test_shop_pass_1();
			}
			else
			{
				$signature_pass = $this->get_test_shop_pass_2();
			}

			$signature_method = $this->get_test_sign_method();
		}
		/**
		 * Real payments
		 */
		else
		{
			$test = false;

			if($action === 'success')
			{
				$signature_pass = $this->get_shop_pass_1();
			}
			else
			{
				$signature_pass = $this->get_shop_pass_2();
			}

			$signature_method = $this->get_sign_method();
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
			wc_robokassa_logger()->error('input_payment_notifications: order not found');

			wp_die(__('Order not found.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
		}

		/**
		 * Local signature
		 */
		$signature_payload = $sum.':'.$order_id.':'.$signature_pass;
		$local_signature = $this->get_signature($signature_payload, $signature_method);

		if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request') === 'yes')
		{
			$order->add_order_note(sprintf(__('Robokassa request. Sum: %1$s. Signature: %2$s. Remote signature: %3$s', 'wc-robokassa'), $sum, $local_signature, $signature));
		}

		/**
		 * Result
		 */
		if($action === 'result')
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

				wc_robokassa_logger()->notice('input_payment_notifications: $signature !== $local_signature');

				if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_validate_error') === 'yes')
				{
					$order->add_order_note(sprintf(__('Validate hash error. Local: %1$s Remote: %2$s', 'wc-robokassa'), $local_signature, $signature));
				}
			}

			/**
			 * Validated
			 */
			if($validate === true)
			{
				if($test === true)
				{
					wc_robokassa_logger()->info('input_payment_notifications: Order successfully paid (TEST MODE)');

					if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_result') === 'yes')
					{
						$order->add_order_note(__('Order successfully paid (TEST MODE).', 'wc-robokassa'));
					}
				}
				else
				{
					wc_robokassa_logger()->info('input_payment_notifications: Order successfully paid');

					if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_result') === 'yes')
					{
						$order->add_order_note(__('Order successfully paid.', 'wc-robokassa'));
					}
				}

				/**
				 * Set status is payment
				 */
				$order->payment_complete();
				die('OK'.$order_id);
			}

			wc_robokassa_logger()->error('input_payment_notifications: action result - error');

			/**
			 * Send Service unavailable
			 */
			wp_die(__('Payment error, please pay other time.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
		}
		elseif($action === 'success')
		{
			wc_robokassa_logger()->info('input_payment_notifications: Client return to success page');

			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_success') === 'yes')
			{
				$order->add_order_note(__('Client return to success page.', 'wc-robokassa'));
			}

			/**
			 * Empty cart
			 */
			if($this->get_option('cart_clearing') === 'yes')
			{
				wc_robokassa_logger()->info('input_payment_notifications: clear cart');

				WC()->cart->empty_cart();
			}

			wc_robokassa_logger()->info('input_payment_notifications: redirect to success page');

			/**
			 * Redirect to success
			 */
			wp_redirect($this->get_return_url($order));
			die();
		}
		elseif($action === 'fail')
		{
			wc_robokassa_logger()->info('input_payment_notifications: The order has not been paid');

			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_fail') === 'yes')
			{
				$order->add_order_note(__('The order has not been paid.', 'wc-robokassa'));
			}

			/**
			 * Set status is failed
			 */
			if($this->get_option('fail_set_order_status_failed') === 'yes')
			{
				wc_robokassa_logger()->info('input_payment_notifications: fail_set_order_status_failed');

				$order->update_status('failed');
			}

			wc_robokassa_logger()->info('input_payment_notifications: redirect to order cancel page');

			/**
			 * Redirect to cancel
			 */
			wp_redirect(str_replace('&amp;', '&', $order->get_cancel_order_url()));
			die();
		}

		wc_robokassa_logger()->info('input_payment_notifications: error, action not found');

		/**
		 * Send Service unavailable
		 */
		wp_die(__('Api request error. Action not found.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
	}

	/**
	 * Check if the gateway is available for use
	 *
	 * @since 1.0.0.1
	 *
	 * @return bool
	 */
	public function is_available()
	{
		wc_robokassa_logger()->info('is_available: start');

		$is_available = parent::is_available();

		wc_robokassa_logger()->debug('is_available: parent $is_available', $is_available);

		/**
		 * Change status from external code
		 *
		 * @since 2.4.0
		 * @deprecated 3.1
		 */
		$is_available = apply_filters('wc_robokassa_main_method_get_available', $is_available);
		
		/**
		 * Change status from external code
		 *
		 * @since 3.1
		 */
		$is_available = apply_filters('wc_robokassa_method_get_available', $is_available);

		wc_robokassa_logger()->debug('is_available: $is_available', $is_available);
		wc_robokassa_logger()->info('is_available: success');

		return $is_available;
	}

	/**
	 * @return int
	 */
	public function get_receipt_items_limit()
	{
		return $this->receipt_items_limit;
	}

	/**
	 * @param int $receipt_items_limit
	 */
	public function set_receipt_items_limit($receipt_items_limit)
	{
		$this->receipt_items_limit = $receipt_items_limit;
	}
}