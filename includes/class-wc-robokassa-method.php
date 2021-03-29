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
	public $currency_all =
	[
		'RUB', 'USD', 'EUR', 'KZT'
	];

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
	 * Commission pay by merchant
	 *
	 * @var bool
	 */
	protected $commission_merchant = false;

	/**
	 * Commission calculate by cbr
	 *
	 * @var bool
	 */
	protected $commission_merchant_by_cbr = false;

	/**
	 * Rates merchant
	 *
	 * @var bool
	 */
	protected $rates_merchant = false;

	/**
	 * Available only for shipping
	 *
	 * @var array|false
	 */
	protected $available_shipping = false;

	/**
	 * @var bool
	 */
	protected $submethods_check_available = false;

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
		if(current_user_can('manage_options') && is_admin())
		{
			$this->process_options();
		}

		/**
		 * Gateway allowed?
		 */
		if($this->is_available_front() === false)
		{
			$this->enabled = false;
		}

		if(false === is_admin())
		{
			/**
			 * Receipt page
			 */
			add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'), 10);

			/**
			 * Auto redirect
			 */
			add_action('wc_robokassa_input_payment_notifications', array($this, 'input_payment_notifications_redirect_by_form'), 20);

			/**
			 * Payment listener/API hook
			 */
			add_action('woocommerce_api_wc_' . $this->id, array($this, 'input_payment_notifications'), 10);
		}
	}

	/**
	 * Admin options
	 */
	public function process_options()
	{
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

	/**
	 * Logger
	 */
	public function init_logger()
	{
		if($this->get_option('logger', '') !== '')
		{
			$level = $this->get_option('logger');

			wc_robokassa_logger()->set_level($level);

			$file_name = WC_Robokassa()->get_logger_filename();

			wc_robokassa_logger()->set_name($file_name);
		}
	}

	/**
	 * Initialize filters
	 */
	public function init_filters()
	{
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_tecodes'), 5);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_main'), 10);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_test_payments'), 20);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_interface'), 30);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_ofd'), 40);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_sub_methods'), 45);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_order_notes'), 45);
		add_filter('wc_robokassa_init_form_fields', array($this, 'init_form_fields_technical'), 50);
	}

	/**
	 * Init actions
	 */
	public function init_actions()
	{
		/**
		 * Payment fields description show
		 */
		add_action('wc_robokassa_payment_fields_show', array($this, 'payment_fields_description_show'), 10);

		/**
		 * Payment fields test mode show
		 */
		if($this->get_test() === 'yes' && $this->get_option('test_mode_checkout_notice', 'no') === 'yes')
		{
			add_action('wc_robokassa_payment_fields_after_show', array($this, 'payment_fields_test_mode_show'), 10);
		}

		/**
		 * Receipt form show
		 */
		add_action('wc_robokassa_receipt_page_show', array($this, 'wc_robokassa_receipt_page_show_form'), 10);
	}

	/**
	 * Update plugin version at settings update
	 */
	public function wc_robokassa_last_settings_update_version()
	{
		$result = update_option('wc_robokassa_last_settings_update_version', WC_ROBOKASSA_VERSION);

		if($result)
		{
			wc_robokassa_logger()->info('wc_robokassa_last_settings_update_version: success');
		}
		else
		{
			wc_robokassa_logger()->warning('wc_robokassa_last_settings_update_version: not updated');
		}
	}

	/**
	 * Init gateway options
	 */
	public function init_options()
	{
		/**
		 * Gateway not enabled?
		 */
		if($this->get_option('enabled', 'no') !== 'yes')
		{
			$this->enabled = false;
		}

		/**
		 * Page skipping enabled?
		 */
		if($this->get_option('page_skipping', 'no') === 'yes')
		{
			$this->set_page_skipping('yes');
		}

		/**
		 * Title for user interface
		 */
		$this->title = $this->get_option('title', '');

		/**
		 * Set description
		 */
		$this->description = $this->get_option('description', '');

		/**
		 * Testing?
		 */
		$this->set_test($this->get_option('test', 'yes'));

		/**
		 * Default language for Robokassa interface
		 */
		$this->set_user_interface_language($this->get_option('language'));

		/**
		 * Automatic language
		 */
		if($this->get_option('language_auto', 'no') === 'yes')
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
		if($this->get_option('ofd_status', 'no') === 'yes')
		{
			$this->set_ofd_status(true);
		}

		/**
		 * Ofd sno
		 */
		$ofd_sno_code = $this->get_option('ofd_sno', '');
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
		$ofd_nds_code = $this->get_option('ofd_nds', '');
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
		if($this->get_option('ofd_payment_method', '') !== '')
		{
			$this->set_ofd_payment_method($this->get_option('ofd_payment_method'));
		}

		/**
		 * Set ofd_payment_object
		 */
		if($this->get_option('ofd_payment_object', '') !== '')
		{
			$this->set_ofd_payment_object($this->get_option('ofd_payment_object'));
		}

		/**
		 * Set shop pass 1
		 */
		if($this->get_option('shop_pass_1', '') !== '')
		{
			$this->set_shop_pass_1($this->get_option('shop_pass_1'));
		}

		/**
		 * Set shop pass 2
		 */
		if($this->get_option('shop_pass_2', '') !== '')
		{
			$this->set_shop_pass_2($this->get_option('shop_pass_2'));
		}

		/**
		 * Load shop login
		 */
		$this->set_shop_login($this->get_option('shop_login', ''));

		/**
		 * Load sign method
		 */
		$this->set_sign_method($this->get_option('sign_method'));

		/**
		 * Set shop pass 1 for testing
		 */
		if($this->get_option('test_shop_pass_1', '') !== '')
		{
			$this->set_test_shop_pass_1($this->get_option('test_shop_pass_1'));
		}

		/**
		 * Set shop pass 2 for testing
		 */
		if($this->get_option('test_shop_pass_2', '') !== '')
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
		if($this->get_option('enable_icon', 'no') === 'yes')
		{
			$this->icon = apply_filters('woocommerce_icon_robokassa', WC_ROBOKASSA_URL . 'assets/img/robokassa.png', $this->id);
		}

		if($this->get_option('commission_merchant', 'no') === 'yes')
		{
			$this->set_commission_merchant(true);

			if($this->get_option('commission_merchant_by_cbr', 'no') === 'yes')
			{
				$this->set_commission_merchant_by_cbr(true);
			}
		}

		if($this->get_option('rates_merchant', 'no') === 'yes')
		{
			$this->set_rates_merchant(true);
		}

		if($this->get_option('sub_methods_check_available', 'no') === 'yes')
		{
			$this->set_submethods_check_available(true);
		}

		$available_shipping = $this->get_option('available_shipping', '');
		if(is_array($available_shipping))
		{
			$this->set_available_shipping($available_shipping);
		}
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
		$this->form_fields = apply_filters('wc_robokassa_init_form_fields', []);
	}

	/**
	 * Add fields for tecodes settings
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_tecodes($fields)
	{
		if(WC_Robokassa()->tecodes()->is_valid())
		{
			return $fields;
		}

		$buy_url = esc_url('https://mofsy.ru/market/wc-robokassa-code');

		$fields['tecodes'] = array
		(
			'title' => __('Support activation', 'wc-robokassa'),
			'type' => 'title',
			'class' => WC_Robokassa()->tecodes()->is_valid() ? '' : 'bg-warning p-2 mt-1',
			'description' => __('The code can be obtained from the plugin website:', 'wc-robokassa') . ' <a target="_blank" href="' . $buy_url . '">' . $buy_url . '</a>. ' . __('This section will disappear after enter a valid code before the expiration of the entered code, or its cancellation.', 'wc-robokassa'),
		);

		$fields['tecodes_code'] = array
		(
			'title' => __('Input code', 'wc-robokassa'),
			'type' => 'tecodes_text',
			'class' => 'p-2',
			'description' => __('If enter the correct code, the current environment will be activated. Enter the code only on the actual workstation.', 'wc-robokassa'),
			'default' => ''
		);

		return $fields;
	}

	/**
	 * Generate Tecodes Text Input HTML
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_text_html($key, $data)
	{
		$field_key = $this->get_field_key($key);
		$defaults = array
		(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>
		<tr valign="top">
			<td colspan="2" class="forminp">
				<fieldset>
					<div class="row">
						<div class="col-20 p-0">
							<legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
							<input class="input-text regular-input <?php echo esc_attr($data['class']); ?>"
							       type="<?php echo esc_attr($data['type']); ?>" name="<?php echo esc_attr($field_key); ?>"
							       id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>"
							       value="<?php echo esc_attr($this->get_option($key)); ?>"
							       placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.
							?> />
							<?php echo $this->get_description_html($data); // WPCS: XSS ok.?>
						</div>
						<div class="col-4 p-0">
							<button style="float: right;margin: 0px;height: 90%; width: 90%;" name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php _e('Activate', 'wc-robokassa') ?>"><?php _e('Activate', 'wc-robokassa') ?></button>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
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
			'description' => __('Shop pass #1 must match the one specified in the personal account of Robokassa.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['shop_pass_2'] = array
		(
			'title'       => __('Password #2', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Shop pass #2 must match the one specified in the personal account of Robokassa.', 'wc-robokassa'),
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
			'label'   => __('Select the checkbox to enable this feature. Default is enabled.', 'wc-robokassa'),
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
			'description' => __('Shop pass #1 for testing payments. The pass must match the one specified in the personal account of ROBOKASSA.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['test_shop_pass_2'] = array
		(
			'title'       => __('Password #2', 'wc-robokassa'),
			'type'        => 'text',
			'description' => __('Shop pass #2 for testing payments. The pass must match the one specified in the personal account of ROBOKASSA.', 'wc-robokassa'),
			'default'     => ''
		);

		$fields['test_mode_checkout_notice'] = array
		(
			'title'   => __('Test notification display on the test mode', 'wc-robokassa'),
			'type'    => 'checkbox',
			'label'   => __('Select the checkbox to enable this feature. Default is enabled.', 'wc-robokassa'),
			'description' => __('A notification about the activated test mode will be displayed when the payment.', 'wc-robokassa'),
			'default' => 'yes'
		);

		return $fields;
	}

	/**
	 * Add settings for sub methods
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_sub_methods($fields)
	{
		$fields['title_sub_methods'] = array
		(
			'title' => __('Sub methods', 'wc-robokassa'),
			'type' => 'title',
			'description' => __('General settings for the sub methods of payment.', 'wc-robokassa'),
		);

		$fields['sub_methods'] = array
		(
			'title' => __('Enable sub methods', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Use of all mechanisms add a child of payment methods.', 'wc-robokassa'),
			'default' => 'no'
		);

		$fields['sub_methods_check_available'] = array
		(
			'title' => __('Check available via the API', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Check whether child methods are currently available for payment.', 'wc-robokassa'),
			'default' => 'no'
		);

		$fields['rates_merchant'] = array
		(
			'title' => __('Show the total amount including the fee', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('If you enable this option, the exact amount payable, including fees, will be added to the payment method headers.', 'wc-robokassa'),
			'default' => 'no'
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
			'title'   => __('Show icon?', 'wc-robokassa'),
			'type'    => 'checkbox',
			'label'   => __('Select the checkbox to enable this feature. Default is enabled.', 'wc-robokassa'),
			'default' => 'yes',
			'description' => __('Next to the name of the payment method will display the logo Robokassa.', 'wc-robokassa'),
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
			'label'   => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
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
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
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

		$fields['orders_notes_robokassa_request_validate_error'] = array
		(
			'title'       => __('Errors when verifying the signature of requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Recording a errors when verifying the signature of requests from Robokassa.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_process_payment'] = array
		(
			'title'       => __('Process payments', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Recording information about the beginning of the payment process by the user.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_paid_success'] = array
		(
			'title'       => __('Successful payments', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Recording information about received requests with successful payment.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_result'] = array
		(
			'title'       => __('Background requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Recording information about the background queries about transactions from Robokassa.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_fail'] = array
		(
			'title'       => __('Failed requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Recording information about the clients return to the canceled payment page.', 'wc-robokassa'),
			'default'     => 'no'
		);

		$fields['orders_notes_robokassa_request_success'] = array
		(
			'title'       => __('Success requests', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Recording information about the clients return to the success payment page.', 'wc-robokassa'),
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

		$logger_path = wc_robokassa_logger()->get_path() . '/' . wc_robokassa_logger()->get_name();

		$fields['logger'] = array
		(
			'title'       => __('Logging', 'wc-robokassa'),
			'type'        => 'select',
			'description' => __('You can enable gateway logging, specify the level of error that you want to benefit from logging. All sensitive data in the report are deleted. By default, the error rate should not be less than ERROR.', 'wc-robokassa') . '<br/>' . __('Current file: ', 'wc-robokassa') . '<b>' . $logger_path . '</b>',
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
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Clean the customers cart if payment is successful? If so, the shopping cart will be cleaned. If not, the goods already purchased will most likely remain in the shopping cart.', 'wc-robokassa'),
			'default'     => 'no',
		);

		$fields['fail_set_order_status_failed'] = array
		(
			'title'       => __('Mark order as cancelled?', 'wc-robokassa'),
			'type'        => 'checkbox',
			'label'       => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('Change the status of the order to canceled when the user cancels the payment. The status changes when the user returns to the cancelled payment page.', 'wc-robokassa'),
			'default'     => 'no',
		);

		if(version_compare(wc_robokassa_get_wc_version(), '3.2.0', '>='))
		{
			$options = array();

			try
			{
				$data_store = WC_Data_Store::load('shipping-zone');
			}
			catch(Exception $e)
			{
				return $fields;
			}

			$raw_zones = $data_store->get_zones();

			foreach($raw_zones as $raw_zone)
			{
				$zones[] = new WC_Shipping_Zone($raw_zone);
			}

			$zones[] = new WC_Shipping_Zone(0);

			foreach(WC()->shipping()->load_shipping_methods() as $method)
			{
				$options[$method->get_method_title()] = array();

				// Translators: %1$s shipping method name.
				$options[$method->get_method_title()][$method->id] = sprintf(__('Any &quot;%1$s&quot; method', 'woocommerce'), $method->get_method_title());

				foreach($zones as $zone)
				{
					$shipping_method_instances = $zone->get_shipping_methods();

					foreach($shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance)
					{
						if($shipping_method_instance->id !== $method->id)
						{
							continue;
						}

						$option_id = $shipping_method_instance->get_rate_id();

						// Translators: %1$s shipping method title, %2$s shipping method id.
						$option_instance_title = sprintf(__('%1$s (#%2$s)', 'woocommerce'), $shipping_method_instance->get_title(), $shipping_method_instance_id);

						// Translators: %1$s zone name, %2$s shipping method instance name.
						$option_title = sprintf(__('%1$s &ndash; %2$s', 'woocommerce'), $zone->get_id() ? $zone->get_zone_name() : __('Other locations', 'woocommerce'), $option_instance_title);

						$options[$method->get_method_title()][$option_id] = $option_title;
					}
				}
			}

			$fields['available_shipping'] =  array
			(
				'title' => __('Enable for shipping methods', 'wc-robokassa'),
				'type' => 'multiselect',
				'class' => 'wc-enhanced-select',
				'css' => 'width: 400px;',
				'default' => '',
				'description' => __('If only available for certain methods, set it up here. Leave blank to enable for all methods.', 'wc-robokassa'),
				'options' => $options,
				'custom_attributes' => array
				(
					'data-placeholder' => __('Select shipping methods', 'wc-robokassa'),
				),
			);
		}

		$fields['commission_merchant'] = array
		(
			'title' => __('Payment of the commission for the buyer', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('When you enable this feature, the store will pay all customer Commission costs. Works only when you select a payment method on the site and for stores individuals.', 'wc-robokassa'),
			'default' => 'no'
		);

		$fields['commission_merchant_by_cbr'] = array
		(
			'title' => __('Preliminary conversion of order currency into roubles for commission calculation', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Select the checkbox to enable this feature. Default is disabled.', 'wc-robokassa'),
			'description' => __('If the calculation of the customer commission is included and the order is not in roubles, the order will be converted to roubles based on data from the Central Bank of Russia.
			This is required due to poor Robokassa API.', 'wc-robokassa'),
			'default' => 'no'
		);

		return $fields;
	}

	/**
	 * @return array
	 */
	public function get_currency_all()
	{
		return $this->currency_all;
	}

	/**
	 * @param array $currency_all
	 */
	public function set_currency_all($currency_all)
	{
		$this->currency_all = $currency_all;
	}

	/**
	 * Check currency support
	 *
	 * @param string $currency
	 *
	 * @return bool
	 */
	public function is_support_currency($currency = '')
	{
		if($currency === '')
		{
			$currency = WC_Robokassa()->get_wc_currency();
		}

		if(!in_array($currency, $this->get_currency_all(), false))
		{
			wc_robokassa_logger()->alert('is_support_currency: currency not support');
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function get_available_shipping()
	{
		return $this->available_shipping;
	}

	/**
	 * @param array $available_shipping
	 */
	public function set_available_shipping($available_shipping)
	{
		$this->available_shipping = $available_shipping;
	}

	/**
	 * Check available in front
	 */
	public function is_available_front()
	{
		wc_robokassa_logger()->info('is_available_front: start');

		/**
		 * Check allow currency
		 */
		if($this->is_support_currency() === false)
		{
			wc_robokassa_logger()->alert('is_available_front: is_support_currency');
			return false;
		}

		if($this->is_commission_merchant())
		{
			wc_robokassa_logger()->alert('is_available_front: is_commission_merchant');
			return false;
		}

		/**
		 * Check test mode and admin rights
		 *
		 * @todo сделать возможность тестирования не только админами
		 */
		if($this->get_test() === 'yes' && false === current_user_can('manage_options'))
		{
			wc_robokassa_logger()->alert('is_available_front: test mode only admin');
			return false;
		}

		wc_robokassa_logger()->info('is_available_front: success');

		return true;
	}

	/**
	 * Output settings screen
	 */
	public function admin_options()
	{
		wp_enqueue_style('robokassa-admin-styles', WC_ROBOKASSA_URL . 'assets/css/main.css');

		add_filter('wc_robokassa_widget_status_color', array($this, 'admin_right_widget_status_content_color'), 20);
		add_action('wc_robokassa_widget_status_content', array($this, 'admin_right_widget_status_content_tecodes'), 10);
		add_action('wc_robokassa_widget_status_content', array($this, 'admin_right_widget_status_content_logger'), 10);
		add_action('wc_robokassa_widget_status_content', array($this, 'admin_right_widget_status_content_api'), 20);
		add_action('wc_robokassa_widget_status_content', array($this, 'admin_right_widget_status_content_currency'), 20);
		add_action('wc_robokassa_widget_status_content', array($this, 'admin_right_widget_status_content_test'), 20);

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
		do_action('wc_' . $this->id . '_payment_fields_before_show');

		// hook
		do_action('wc_' . $this->id . '_payment_fields_show');

		// hook
		do_action('wc_' . $this->id . '_payment_fields_after_show');
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
		echo '<div style="padding:5px; border-radius:20px; background-color: #ff8982;text-align: center;">';
		echo __('TEST mode is active. Payment will not be charged. After checking, disable this mode.', 'wc-robokassa');
		echo '</div>';
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

			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_process_payment') === 'yes')
			{
				$order->add_order_note(__('The customer clicked the payment button, but an error occurred while getting the order object.', 'wc-robokassa'));
			}

			return array
			(
				'result' => 'failure',
				'redirect' => ''
			);
		}

		// hook
		do_action('wc_robokassa_before_process_payment', $order_id, $order);

		wc_robokassa_logger()->debug('process_payment: order', $order);

		if($this->get_page_skipping() === 'yes')
		{
			wc_robokassa_logger()->info('process_payment: page skipping, success');

			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_process_payment') === 'yes')
			{
				$order->add_order_note(__('The customer clicked the payment button and was sent to the side of the Robokassa.', 'wc-robokassa'));
			}

			return array
			(
				'result' => 'success',
				'redirect' => $this->get_url_auto_redirect($order_id)
			);
		}

		wc_robokassa_logger()->info('process_payment: success');

		if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_process_payment') === 'yes')
		{
			$order->add_order_note(__('The customer clicked the payment button and was sent to the page of the received order.', 'wc-robokassa'));
		}

		return array
		(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		);
	}

	/**
	 * Validate tecodes code
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function validate_tecodes_code_field($key, $value)
	{
		if($value === '')
		{
			return '';
		}

		WC_Robokassa()->tecodes()->set_code($value);
		WC_Robokassa()->tecodes()->validate();

		if(!WC_Robokassa()->tecodes()->is_valid())
		{
			$errors = WC_Robokassa()->tecodes()->get_errors();

			if(is_array($errors))
			{
				foreach(WC_Robokassa()->tecodes()->get_errors() as $error_key => $error)
				{
					WC_Admin_Settings::add_error($error);
				}
			}
		}

		return '';
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
		do_action('wc_' . $this->id . '_receipt_page_before_show', $order);

		// hook
		do_action('wc_' . $this->id . '_receipt_page_show', $order);

		// hook
		do_action('wc_' . $this->id . '_receipt_page_after_show', $order);
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

		$args = [];
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

		if($this->get_test() === 'yes')
		{
			wc_robokassa_logger()->info('generate_form: test mode active');

			$signature_pass = $this->get_test_shop_pass_1();
			$signature_method = $this->get_test_sign_method();

			$args['IsTest'] = 1;
		}
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
		$receipt_json = '';
		if($this->is_ofd_status() === true)
		{
			wc_robokassa_logger()->info('generate_form: fiscal active');

			$receipt['sno'] = $this->get_ofd_sno();
			$receipt['items'] = $this->generate_receipt_items($order);

			$receipt_json = urlencode(json_encode($receipt, 256));

			wc_robokassa_logger()->debug('generate_form: $receipt_result', $receipt_json);
		}

		/**
		 * Signature
		 */
		$receipt_signature = '';
		if($receipt_json !== '')
		{
			$receipt_signature = ':' . $receipt_json;
			$args['Receipt'] = $receipt_json;
		}

		if(array_key_exists('OutSumCurrency', $args))
		{
			$signature_payload = $args['MerchantLogin'] . ':' . $args['OutSum'] . ':' . $args['InvId'] . ':' . $args['OutSumCurrency'] . $receipt_signature . ':' . $signature_pass;
		}
		else
		{
			$signature_payload = $args['MerchantLogin'] . ':' . $args['OutSum'] . ':' . $args['InvId'] . $receipt_signature . ':' . $signature_pass;
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

		wc_robokassa_logger()->debug('generate_form: final $args', $args);

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
				'sum' => (int) $item_total,

				/**
				 * Количество/вес
				 *
				 * максимальная длина 128 символов
				 */
				'quantity' => (int) $item_quantity,

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
				'sum' => (int) $order->get_shipping_total(),

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
				$signature = hash('ripemd160', $string);
				break;

			case 'sha1':
				$signature = sha1($string);
				break;

			case 'sha256':
				$signature = hash('sha256', $string);
				break;

			case 'sha384':
				$signature = hash('sha384', $string);
				break;

			case 'sha512':
				$signature = hash('sha512', $string);
				break;

			default:
				$signature = md5($string);
		}

		return strtoupper($signature);
	}

	/**
	 * Получение ссылки на автоматический редирект в робокассу
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_url_auto_redirect($order_id)
	{
		return get_site_url( null, '/?wc-api=wc_' . $this->id . '&action=redirect&order_id=' . $order_id);
	}

	/**
	 * Автоматический редирект на робокассу методом автоматической отправки формы
	 *
	 * @since 4.0.0
	 */
	public function input_payment_notifications_redirect_by_form()
	{
		if(false === isset($_GET['action']))
		{
			return;
		}

		if(false === isset($_GET['order_id']))
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

		/**
		 * Result
		 */
		if($action === 'result')
		{
			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_result') === 'yes')
			{
				$order->add_order_note(sprintf(__('Robokassa request. Sum: %1$s. Signature: %2$s. Remote signature: %3$s', 'wc-robokassa'), $sum, $local_signature, $signature));
			}

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

					if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_paid_success') === 'yes')
					{
						$order->add_order_note(__('Order successfully paid (TEST MODE).', 'wc-robokassa'));
					}
				}
				else
				{
					wc_robokassa_logger()->info('input_payment_notifications: Order successfully paid');

					if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_paid_success') === 'yes')
					{
						$order->add_order_note(__('Order successfully paid.', 'wc-robokassa'));
					}
				}

				$order->payment_complete();
				die('OK'.$order_id);
			}

			wc_robokassa_logger()->error('input_payment_notifications: action result - error');

			wp_die(__('Payment error, please pay other time.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
		}
		elseif($action === 'success')
		{
			wc_robokassa_logger()->info('input_payment_notifications: Client return to success page');

			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_success') === 'yes')
			{
				$order->add_order_note(__('The client returned to the payment success page.', 'wc-robokassa'));
			}

			if($this->get_option('cart_clearing') === 'yes')
			{
				wc_robokassa_logger()->info('input_payment_notifications: clear cart');

				WC()->cart->empty_cart();
			}

			wc_robokassa_logger()->info('input_payment_notifications: redirect to success page');

			wp_redirect($this->get_return_url($order));
			die();
		}
		elseif($action === 'fail')
		{
			wc_robokassa_logger()->info('input_payment_notifications: The order has not been paid');

			if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_robokassa_request_fail') === 'yes')
			{
				$order->add_order_note(__('Order cancellation. The client returned to the payment cancellation page.', 'wc-robokassa'));
			}

			if($this->get_option('fail_set_order_status_failed') === 'yes')
			{
				wc_robokassa_logger()->info('input_payment_notifications: fail_set_order_status_failed');

				$order->update_status('failed');
			}

			wc_robokassa_logger()->info('input_payment_notifications: redirect to order cancel page');

			wp_redirect(str_replace('&amp;', '&', $order->get_cancel_order_url()));
			die();
		}

		wc_robokassa_logger()->info('input_payment_notifications: error, action not found');

		wp_die(__('Api request error. Action not found.', 'wc-robokassa'), 'Payment error', array('response' => '503'));
	}

	/**
	 * Is rates merchant
	 *
	 * @return bool
	 */
	public function is_rates_merchant()
	{
		return $this->rates_merchant;
	}

	/**
	 * Set rates merchant
	 *
	 * @param bool $rates_merchant
	 */
	public function set_rates_merchant($rates_merchant)
	{
		$this->rates_merchant = $rates_merchant;
	}

	/**
	 * @return bool
	 */
	public function is_commission_merchant()
	{
		return $this->commission_merchant;
	}

	/**
	 * @param bool $commission_merchant
	 */
	public function set_commission_merchant($commission_merchant)
	{
		$this->commission_merchant = $commission_merchant;
	}

	/**
	 * @return bool
	 */
	public function is_commission_merchant_by_cbr()
	{
		return $this->commission_merchant_by_cbr;
	}

	/**
	 * @param bool $commission_merchant_by_cbr
	 */
	public function set_commission_merchant_by_cbr($commission_merchant_by_cbr)
	{
		$this->commission_merchant_by_cbr = $commission_merchant_by_cbr;
	}

	/**
	 * Set submethods check available
	 *
	 * @param bool $submethods_check_available
	 */
	public function set_submethods_check_available($submethods_check_available)
	{
		$this->submethods_check_available = $submethods_check_available;
	}

	/**
	 * @return bool
	 */
	public function is_submethods_check_available()
	{
		return $this->submethods_check_available;
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
		$is_available = $this->enabled;

		if(WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total())
		{
			$is_available = false;
		}

		wc_robokassa_logger()->debug('is_available: parent $is_available', $is_available);

		if(is_array($this->get_available_shipping()) && !empty($this->get_available_shipping()) && version_compare(wc_robokassa_get_wc_version(), '3.2.0', '>='))
		{
			$order = null;
			$needs_shipping = false;

			// Test if shipping is needed first
			if(WC()->cart && WC()->cart->needs_shipping())
			{
				$needs_shipping = true;
			}
			elseif(is_page(wc_get_page_id('checkout')) && 0 < get_query_var('order-pay'))
			{
				$order_id = absint(get_query_var('order-pay'));
				$order = wc_get_order($order_id);

				// Test if order needs shipping
				if(0 < count($order->get_items()))
				{
					foreach($order->get_items() as $item)
					{
						$_product = $item->get_product();
						if($_product && $_product->needs_shipping())
						{
							$needs_shipping = true;
							break;
						}
					}
				}
			}

			$needs_shipping = apply_filters('woocommerce_cart_needs_shipping', $needs_shipping);

			// Only apply if all packages are being shipped via chosen method
			if($needs_shipping && !empty($this->get_available_shipping()))
			{
				$order_shipping_items = is_object($order) ? $order->get_shipping_methods() : false;
				$chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

				if($order_shipping_items)
				{
					$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids($order_shipping_items);
				}
				else
				{
					$canonical_rate_ids = $this->get_canonical_package_rate_ids($chosen_shipping_methods_session);
				}

				if(!count($this->get_matching_rates($canonical_rate_ids)))
				{
					$is_available = false;
				}
			}
		}

		/**
		 * Change status from external code
		 *
		 * @since 3.1
		 */
		$is_available = apply_filters('wc_robokassa_method_get_available', $is_available);

		wc_robokassa_logger()->debug('is_available: $is_available', $is_available);

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

	/**
	 * Widget status: Tecodes
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function admin_right_widget_status_content_tecodes($content)
	{
		if(WC_Robokassa()->tecodes()->is_valid())
		{
			return '';
		}

		$message = __('The activation was not success. It may be difficult to release new updates.', 'wc-robokassa');
		$color = 'bg-warning';

		$content .= '<li class="list-group-item mb-0 ' . $color . '">' . $message . '</li>';

		return $content;
	}

	/**
	 * Widget status: API
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function admin_right_widget_status_content_api($content)
	{
		$message = __('disconnected', 'wc-robokassa');
		$color = 'text-white bg-danger';

		if(false !== $this->check_robokassa_api())
		{
			$color = 'text-white bg-success';
			$message = __('connected', 'wc-robokassa');
		}

		$content .= '<li class="list-group-item mb-0 ' . $color . '">'
		            . __('API Robokassa: ', 'wc-robokassa') . $message .
		            '</li>';

		return $content;
	}

	/**
	 * Widget status: test mode
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function admin_right_widget_status_content_test($content)
	{
		$message = __('active', 'wc-robokassa');
		$color = 'bg-warning';

		if('yes' !== $this->get_test())
		{
			$color = 'text-white bg-success';
			$message = __('inactive', 'wc-robokassa');
		}

		$content .= '<li class="list-group-item mb-0 ' . $color . '">'
		            . __('Test mode: ', 'wc-robokassa') . $message .
		            '</li>';

		return $content;
	}

	/**
	 * Widget status: currency
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function admin_right_widget_status_content_currency($content)
	{
		$color = 'bg-danger';

		if(true === $this->is_support_currency())
		{
			$color = 'bg-success';
		}

		$content .= '<li class="list-group-item mb-0 text-white ' . $color . '">'
		            . __('Currency: ', 'wc-robokassa') . WC_Robokassa()->get_wc_currency() .
		            '</li>';

		return $content;
	}

	/**
	 * Widget status: logger
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function admin_right_widget_status_content_logger($content)
	{
		if(wc_robokassa_logger()->get_level() < 200)
		{
			$content .= '<li class="list-group-item mb-0 text-white bg-warning">'
			            . __('The logging level is too low. Need to increase the level after debugging.', 'wc-robokassa') .
			            '</li>';
		}

		return $content;
	}

	/**
	 * Check available API Robokassa
	 *
	 * @return bool
	 */
	public function check_robokassa_api()
	{
		$api = WC_Robokassa()->load_robokassa_api();

		if(false !== $api->xml_get_limit($this->get_shop_login()))
		{
			return true;
		}

		return false;
	}

	/**
	 * Widget status: color
	 *
	 * @param $color
	 *
	 * @return string
	 */
	public function admin_right_widget_status_content_color($color)
	{
		if('yes' === $this->get_test())
		{
			$color = 'bg-warning';
		}
		elseif('' === $this->get_shop_login() || '' === $this->get_shop_pass_1() || '' === $this->get_shop_pass_2())
		{
			$color = 'bg-warning';
		}
		elseif(wc_robokassa_logger()->get_level() < 200)
		{
			$color = 'bg-warning';
		}

		if(!WC_Robokassa()->tecodes()->is_valid())
		{
			$color = 'bg-warning';
		}

		if(false === $this->check_robokassa_api() || false === $this->is_support_currency())
		{
			$color = 'bg-danger';
		}

		return $color;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format
	 *
	 * @since 0.9.0
	 *
	 * @param array $order_shipping_items Array of WC_Order_Item_Shipping objects
	 * @return array $canonical_rate_ids Rate IDs in a canonical format
	 */
	protected function get_canonical_order_shipping_item_rate_ids($order_shipping_items)
	{
		$canonical_rate_ids = array();

		foreach($order_shipping_items as $order_shipping_item)
		{
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format
	 *
	 * @since 0.9.0
	 *
	 * @param array $chosen_package_rate_ids Rate IDs as generated by shipping methods.
	 * Can be anything if a shipping method doesn't honor WC conventions.
	 *
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	protected function get_canonical_package_rate_ids($chosen_package_rate_ids)
	{
		$shipping_packages = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();

		if(!empty($chosen_package_rate_ids) && is_array($chosen_package_rate_ids))
		{
			foreach($chosen_package_rate_ids as $package_key => $chosen_package_rate_id)
			{
				if(!empty($shipping_packages[$package_key]['rates'][$chosen_package_rate_id]))
				{
					$chosen_rate = $shipping_packages[$package_key]['rates'][$chosen_package_rate_id];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since 0.9.0
	 *
	 * @param array $rate_ids Rate ids to check
	 *
	 * @return mixed
	 */
	protected function get_matching_rates($rate_ids)
	{
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique(array_merge
        (
            array_intersect($this->get_available_shipping(), $rate_ids),
            array_intersect($this->get_available_shipping(), array_unique(array_map('wc_get_string_before_colon', $rate_ids)))
        ));
	}
}