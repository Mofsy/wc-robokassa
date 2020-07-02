<?php
/**
 * Main class
 *
 * @package Mofsy/WC_Robokassa
 */
defined('ABSPATH') || exit;

class WC_Robokassa
{
	/**
	 * The single instance of the class
	 *
	 * @var WC_Robokassa
	 */
	protected static $_instance = null;

	/**
	 * Logger
	 *
	 * @var WC_Robokassa_Logger
	 */
	public $logger = false;

	/**
     * Api Robokassa
     *
	 * @var Wc_Robokassa_Api
	 */
	protected $robokassa_api = false;

	/**
	 * WooCommerce version
	 *
	 * @var
	 */
	protected $wc_version = '';

	/**
	 * WooCommerce currency
	 *
	 * @var string
	 */
	protected $wc_currency = 'RUB';

	/**
     * Result url
     *
	 * @var string
	 */
	private $result_url = '';

	/**
     * Fail url
     *
	 * @var string
	 */
	private $fail_url = '';

	/**
     * Success url
     *
	 * @var string
	 */
	private $success_url = '';

	/**
	 * Available currencies
	 *
	 * @var array
	 */
	private $robokassa_available_currencies = array();

	/**
	 * Current rates by robokassa
	 *
	 * @var array
	 */
	private $robokassa_rates_merchant = array();

	/**
	 * Current currency rates by CBR
	 * @var array
	 */
	private $currency_rates_by_cbr = array();

	/**
	 * Tecodes
	 *
	 * @var null|Tecodes_Local
	 */
	private $tecodes = null;

	/**
	 * WC_Robokassa constructor
	 */
	public function __construct()
	{
		// hook
		do_action('wc_robokassa_loading');

		wc_robokassa_plugin_text_domain();

		$this->init_includes();
		$this->init_hooks();

		// hook
		do_action('wc_robokassa_loaded');
	}

	/**
	 * Main WC_Robokassa instance
	 *
	 * @return WC_Robokassa
	 */
	public static function instance()
	{
		if(is_null(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	
	/**
	 * Init required files
	 */
	private function init_includes()
	{
		/**
		 * @since 3.0.0
		 */
		do_action('wc_robokassa_before_includes');

		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-api.php';
		require_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-sub-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/tecodes-local/bootstrap.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-tecodes.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-tecodes-instance.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-tecodes-storage-code.php';

		/**
		 * Sub methods
		 */
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-alfabank-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-alfabank-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-avb-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-bin-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-fbid-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-inteza-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-min-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-sov-com-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-bank-trust-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bank-vtb4-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bankcard-bank-card-apple-pay-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bankcard-bank-card-halva-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bankcard-bank-card-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-bankcard-bank-card-samsung-pay-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-emoney-elecsnet-wallet-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-emoney-qiwi-wallet-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-emoney-w1-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-emoney-wmr-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-emoney-yandex-money-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-mobile-phone-beeline-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-mobile-phone-megafon-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-mobile-phone-mts-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-mobile-phone-tattelecom-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-mobile-phone-tele2-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-other-biocoin-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-other-store-euroset-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-other-store-svyaznoy-method.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/submethods/class-wc-robokassa-terminals-terminals-elecsnet-method.php';

		/**
		 * @since 3.0.0
		 */
		do_action('wc_robokassa_after_includes');
	}

	/**
     * Get current currency
     *
	 * @return string
	 */
	public function get_wc_currency()
    {
        return $this->wc_currency;
    }

	/**
     * Set current currency
     *
	 * @param $wc_currency
	 */
    public function set_wc_currency($wc_currency)
    {
        $this->wc_currency = $wc_currency;
    }

	/**
     * Get current WooCommerce version installed
     *
	 * @return mixed
	 */
	public function get_wc_version()
    {
		return $this->wc_version;
	}

	/**
     * Set current WooCommerce version installed
     *
	 * @param mixed $wc_version
	 */
	public function set_wc_version($wc_version)
    {
		$this->wc_version = $wc_version;
	}

	/**
	 * Get Tecodes
	 *
	 * @return Tecodes_Local|null
	 */
	public function tecodes()
	{
		return $this->tecodes;
	}

	/**
	 * Set Tecodes
	 *
	 * @param Tecodes_Local|null $tecodes
	 */
	public function set_tecodes($tecodes)
	{
		$this->tecodes = $tecodes;
	}

	/**
	 * Hooks (actions & filters)
	 */
	private function init_hooks()
	{
		add_action('init', array($this, 'init'), 0);
		add_action('init', array($this, 'wc_robokassa_gateway_init'), 5);

		if(is_admin())
		{
			add_action('init', array($this, 'init_admin'), 0);
			add_action('admin_notices', array($this, 'wc_robokassa_admin_notices'), 10);

			add_filter('plugin_action_links_' . WC_ROBOKASSA_PLUGIN_NAME, array($this, 'links_left'), 10);
			add_filter('plugin_row_meta', array($this, 'links_right'), 10, 2);

			$this->page_explode();
		}
	}

	/**
	 * Init plugin gateway
	 *
	 * @return mixed|void
	 */
	public function wc_robokassa_gateway_init()
	{
		// hook
		do_action('wc_robokassa_gateway_init_before');

		if(class_exists('WC_Payment_Gateway') !== true)
		{
			wc_robokassa_logger()->emergency('WC_Payment_Gateway not found');
			return false;
		}

		add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_method'), 10);

		$robokassa_settings = $this->get_method_settings_by_method_id('robokassa');

		if(isset($robokassa_settings['sub_methods']) && $robokassa_settings['sub_methods'] === 'yes')
		{
			add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_submethods'), 10);
		}

		// hook
		do_action('wc_robokassa_gateway_init_after');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		if($this->load_logger() === false)
		{
			return false;
		}

		$this->load_wc_version();
		$this->load_currency();
		$this->load_tecodes();

		return true;
	}

	/**
	 * Admin initialization
	 */
	public function init_admin()
	{
		/**
		 * Load URLs for settings
		 */
		$this->load_urls();
	}

	/**
	 * Load robokassa api
	 *
	 * @param $reload boolean
	 *
	 * @return Wc_Robokassa_Api
	 */
	public function load_robokassa_api($reload = true)
    {
    	if(true === $reload)
	    {
		    $robokassa_api = $this->get_robokassa_api();

		    if(false !== $robokassa_api)
		    {
			    return $robokassa_api;
		    }
	    }

        $default_class_name = 'Wc_Robokassa_Api';

	    /**
	     * Load API class name from external code
	     */
	    $robokassa_api_class_name = apply_filters('wc_robokassa_api_class_name_load', $default_class_name);

	    /**
	     * Fallback
	     */
	    if(class_exists($robokassa_api_class_name) !== true)
        {
	        $robokassa_api_class_name = $default_class_name;
        }

        $robokassa_api = new $robokassa_api_class_name();

        $this->set_robokassa_api($robokassa_api);

        return $this->get_robokassa_api();
    }

	/**
	 * Load Tecodes
	 */
    public function load_tecodes()
    {
	    $options =
	    [
		    'timeout' => 15,
		    'verify_ssl' => false,
		    'version' => 'tecodes/v1'
	    ];

	    $tecodes_local = new Wc_Robokassa_Tecodes('https://mofsy.ru/', $options);

	    /**
	     * Languages
	     */
	    $tecodes_local->status_messages = array
	    (
		    'status_1' => __('This activation code is active.', 'wc-robokassa'),
		    'status_2' => __('Error: This activation code has expired.', 'wc-robokassa'),
		    'status_3' => __('Activation code republished. Awaiting reactivation.', 'wc-robokassa'),
		    'status_4' => __('Error: This activation code has been suspended.', 'wc-robokassa'),
		    'code_not_found' => __('This activation code is not found.', 'wc-robokassa'),
		    'localhost' => __('This activation code is active (localhost).', 'wc-robokassa'),
		    'pending' => __('Error: This activation code is pending review.', 'wc-robokassa'),
		    'download_access_expired' => __('Error: This version of the software was released after your download access expired. Please downgrade software or contact support for more information.', 'wc-robokassa'),
		    'missing_activation_key' => __('Error: The activation code variable is empty.', 'wc-robokassa'),
		    'could_not_obtain_local_code' => __('Error: I could not obtain a new local code.', 'wc-robokassa'),
		    'maximum_delay_period_expired' => __('Error: The maximum local code delay period has expired.', 'wc-robokassa'),
		    'local_code_tampering' => __('Error: The local key has been tampered with or is invalid.', 'wc-robokassa'),
		    'local_code_invalid_for_location' => __('Error: The local code is invalid for this location.', 'wc-robokassa'),
		    'missing_license_file' => __('Error: Please create the following file (and directories if they dont exist already): ', 'wc-robokassa'),
		    'license_file_not_writable' => __('Error: Please make the following path writable: ', 'wc-robokassa'),
		    'invalid_local_key_storage' => __('Error: I could not determine the local key storage on clear.', 'wc-robokassa'),
		    'could_not_save_local_key' => __('Error: I could not save the local key.', 'wc-robokassa'),
		    'code_string_mismatch' => __('Error: The local code is invalid for this activation code.', 'wc-robokassa'),
		    'code_status_delete' => __('Error: This activation code has been deleted.', 'wc-robokassa'),
		    'code_status_draft' => __('Error: This activation code has draft.', 'wc-robokassa'),
		    'code_status_available' => __('Error: This activation code has available.', 'wc-robokassa'),
		    'code_status_blocked' => __('Error: This activation code has been blocked.', 'wc-robokassa'),
	    );

	    $tecodes_local->set_local_code_storage(new Wc_Robokassa_Tecodes_Code_Storage());
	    $tecodes_local->set_instance(new Wc_Robokassa_Tecodes_Instance());

	    $tecodes_local->validate();

	    $this->set_tecodes($tecodes_local);
    }

	/**
	 * Load available currencies from Robokassa
	 *
	 * @param $merchant_login
	 * @param string $language
	 */
	public function load_robokassa_available_currencies($merchant_login, $language = 'ru')
	{
		if(is_array($this->get_robokassa_available_currencies()) && count($this->get_robokassa_available_currencies()) === 0)
		{
			$api = $this->load_robokassa_api();

			$robokassa_available_currencies_result = $api->xml_get_currencies($merchant_login, $language);

			if(is_array($robokassa_available_currencies_result))
			{
				$this->set_robokassa_available_currencies($robokassa_available_currencies_result);
			}
		}
	}

	/**
	 * Load merchant rates
	 *
	 * @param $merchant_login
	 * @param int $out_sum
	 * @param string $language
	 */
	public function load_merchant_rates($merchant_login, $out_sum = 0, $language = 'ru')
	{
		if(is_array($this->get_robokassa_rates_merchant()) && count($this->get_robokassa_rates_merchant()) == 0)
		{
			$api = $this->load_robokassa_api();

			$robokassa_rates_merchant_result = $api->xml_get_rates($merchant_login, $out_sum, '', $language);

			if(is_array($robokassa_rates_merchant_result))
			{
				$this->set_robokassa_rates_merchant($robokassa_rates_merchant_result);
			}
		}
	}

	/**
	 * Get settings by method id for submethods
	 *
	 * @param string $method_id
	 *
	 * @return mixed
	 */
	public function get_method_settings_by_method_id($method_id = 'robokassa')
	{
		return get_option('woocommerce_' . $method_id . '_settings');
	}

	/**
	 * Load currency rates by cbr
	 *
	 * @return mixed
	 */
	public function load_currency_rates_by_cbr()
	{
		$transient_name = 'wc_robokassa_currency_rates_cbr';
		$current_rates = get_transient($transient_name);

		if($current_rates)
		{
			$this->set_currency_rates_by_cbr($current_rates);
			return $current_rates;
		}

		$url = 'https://www.cbr-xml-daily.ru/daily_json.js';

		$result = wp_remote_get($url);
		$result_body = wp_remote_retrieve_body($result);

		if($result_body !== '')
		{
			$rates = json_decode($result_body, true);
			set_transient($transient_name, $rates, 60 * 15);
			$this->set_currency_rates_by_cbr($rates);

			return $rates;
		}

		return false;
	}

	/**
	 * Get merchant rates from Robokassa
	 *
	 * @return array
	 */
	public function get_robokassa_rates_merchant()
	{
		return $this->robokassa_rates_merchant;
	}

	/**
	 * Set merchant rates from Robokassa
	 *
	 * @param array $robokassa_rates_merchant
	 */
	public function set_robokassa_rates_merchant($robokassa_rates_merchant)
	{
		$this->robokassa_rates_merchant = $robokassa_rates_merchant;
	}

	/**
	 * Get merchant currencies available from Robokassa
	 *
	 * @return array
	 */
	public function get_robokassa_available_currencies()
	{
		return $this->robokassa_available_currencies;
	}

	/**
	 * Set merchant currencies available from Robokassa
	 *
	 * @param array $robokassa_available_currencies
	 */
	public function set_robokassa_available_currencies($robokassa_available_currencies)
	{
		$this->robokassa_available_currencies = $robokassa_available_currencies;
	}

	/**
	 * @return array
	 */
	public function get_currency_rates_by_cbr()
	{
		return $this->currency_rates_by_cbr;
	}

	/**
	 * @param array $currency_rates_by_cbr
	 */
	public function set_currency_rates_by_cbr($currency_rates_by_cbr)
	{
		$this->currency_rates_by_cbr = $currency_rates_by_cbr;
	}

	/**
	 * Get Robokassa api
	 *
	 * @return Wc_Robokassa_Api
	 */
	public function get_robokassa_api()
    {
		return $this->robokassa_api;
	}

	/**
	 * Set Robokassa api
	 *
	 * @param Wc_Robokassa_Api $robokassa_api
	 */
	public function set_robokassa_api($robokassa_api)
    {
		$this->robokassa_api = $robokassa_api;
	}

	/**
	 * Load WooCommerce current currency
	 *
	 * @return string
	 */
	public function load_currency()
    {
	    $wc_currency = wc_robokassa_get_wc_currency();

	    /**
	     * WooCommerce Currency Switcher
	     */
	    if(class_exists('WOOCS'))
	    {
		    global $WOOCS;

		    wc_robokassa_logger()->alert('load_currency WooCommerce Currency Switcher detect');

		    $wc_currency = strtoupper($WOOCS->storage->get_val('woocs_current_currency'));
	    }

	    wc_robokassa_logger()->debug('load_currency $wc_version', $wc_currency);

	    $this->set_wc_currency($wc_currency);

	    return $wc_currency;
    }

	/**
	 * Load current WC version
	 *
	 * @return string
	 */
	public function load_wc_version()
    {
    	$wc_version = wc_robokassa_get_wc_version();

	    wc_robokassa_logger()->info('load_wc_version: $wc_version' . $wc_version);

	    $this->set_wc_version($wc_version);
	    
	    return $wc_version;
    }

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods - all WooCommerce initialized gateways
	 *
	 * @return array - new WooCommerce initialized gateways
	 */
	public function add_gateway_method($methods)
	{
	    $default_class_name = 'Wc_Robokassa_Method';

		$robokassa_method_class_name = apply_filters('wc_robokassa_method_class_name_add', $default_class_name);

		if(!class_exists($robokassa_method_class_name))
		{
			$robokassa_method_class_name = $default_class_name;
		}

		$methods[] = $robokassa_method_class_name;

		return $methods;
	}

	/**
	 * Add the submethods gateway to WooCommerce
	 *
	 * @param $methods - all WooCommerce initialized gateways
	 *
	 * @return array - new WooCommerce initialized gateways
	 */
	public function add_gateway_submethods($methods)
	{
		$methods[] = 'Wc_Robokassa_Bank_Alfabank_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Avb_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Bin_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Fbid_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Inteza_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Min_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Sov_Com_Method';
		$methods[] = 'Wc_Robokassa_Bank_Bank_Trust_Method';
		//$methods[] = 'Wc_Robokassa_Bank_Vtb24_Method';
		$methods[] = 'Wc_Robokassa_Bankcard_Bank_Card_Apple_Pay_Method';
		$methods[] = 'Wc_Robokassa_Bankcard_Bank_Card_Halva_Method';
		$methods[] = 'Wc_Robokassa_Bankcard_Bank_Card_Method';
		$methods[] = 'Wc_Robokassa_Bankcard_Bank_Card_Samsung_Pay_Method';
		$methods[] = 'Wc_Robokassa_Emoney_Elecsnet_Wallet_Method';
		$methods[] = 'Wc_Robokassa_Emoney_Qiwi_Wallet_Method';
		$methods[] = 'Wc_Robokassa_Emoney_W1_Method';
		$methods[] = 'Wc_Robokassa_Emoney_Wmr_Method';
		$methods[] = 'Wc_Robokassa_Emoney_Yandex_Money_Method';
		$methods[] = 'Wc_Robokassa_Mobile_Phone_Beeline_Method';
		$methods[] = 'Wc_Robokassa_Mobile_Phone_Megafon_Method';
		$methods[] = 'Wc_Robokassa_Mobile_Phone_Mts_Method';
		$methods[] = 'Wc_Robokassa_Mobile_Phone_Tattelecom_Method';
		$methods[] = 'Wc_Robokassa_Mobile_Phone_Tele2_Method';
		$methods[] = 'Wc_Robokassa_Other_Biocoin_Method';
		$methods[] = 'Wc_Robokassa_Other_Store_Euroset_Method';
		$methods[] = 'Wc_Robokassa_Other_Store_Svyaznoy_Method';
		$methods[] = 'Wc_Robokassa_Terminals_Terminals_Elecsnet_Method';

		return $methods;
	}

	/**
	 * Load logger
	 *
	 * @return boolean
	 */
	protected function load_logger()
	{
		try
		{
			$logger = new WC_Robokassa_Logger();
		}
		catch(Exception $e)
		{
			return false;
		}

		if(function_exists('wp_upload_dir'))
		{
			$wp_dir = wp_upload_dir();

			$logger->set_path($wp_dir['basedir']);
			$logger->set_name('wc-robokassa.boot.log');

			$this->set_logger($logger);

			return true;
		}

		return false;
	}

	/**
	 * Filename for log
	 *
	 * @return mixed
	 */
	public function get_logger_filename()
	{
		$file_name = get_option('wc_robokassa_log_file_name');
		if($file_name === false)
		{
			$file_name = 'wc-robokassa.' . md5(mt_rand(1, 10) . 'MofsyMofsyMofsy' . mt_rand(1, 10)) . '.log';
			update_option('wc_robokassa_log_file_name', $file_name, 'no');
		}

		return $file_name;
	}

	/**
	 * Set logger
	 *
	 * @param $logger
	 *
	 * @return $this
	 */
	public function set_logger($logger)
	{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * Get logger
	 *
	 * @return WC_Robokassa_Logger|null
	 */
	public function get_logger()
	{
		return $this->logger;
	}

	/**
	 * Setup left links
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function links_left($links)
	{
		return array_merge(array('settings' => '<a href="https://mofsy.ru/projects/wc-robokassa" target="_blank">' . __('Official site', 'wc-robokassa') . '</a>'), $links);
	}

	/**
	 * Setup right links
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function links_right($links, $file)
	{
		if($file === WC_ROBOKASSA_PLUGIN_NAME)
		{
			$links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=robokassa') . '">' . __('Settings') . '</a>';
		}

		return $links;
	}

	/**
	 * Show admin notices
	 */
	public function wc_robokassa_admin_notices()
    {
    	$section = '';
    	if(isset($_GET['section']))
	    {
		    $section = $_GET['section'];
	    }

        $settings_version = get_option('wc_robokassa_last_settings_update_version');

        /**
         * Global notice: Require update settings
         */
        if(get_option('wc_robokassa_last_settings_update_version') !== false
           && $settings_version < WC_ROBOKASSA_VERSION
           && $section !== 'robokassa')
        {
	        ?>
	        <div class="notice notice-warning" style="padding-top: 10px; padding-bottom: 10px; line-height: 170%;">
		        <?php
		        echo __('The plugin for accepting payments through ROBOKASSA for WooCommerce has been updated to a version that requires additional configuration.', 'wc-robokassa');
		        echo '<br />';
		        $link = '<a href="'. admin_url('admin.php?page=wc-settings&tab=checkout&section=robokassa') .'">'.__('here', 'wc-robokassa').'</a>';
		        echo sprintf( __( 'Press %s (go to payment gateway settings).', 'wc-robokassa' ), $link ) ?>
	        </div>
	        <?php
        }
    }

	/**
	 *  Add page explode actions
	 */
	public function page_explode()
	{
		add_action('wc_robokassa_admin_options_form_before_show', array($this, 'page_explode_table_before'));
		add_action('wc_robokassa_admin_options_form_after_show', array($this, 'page_explode_table_after'));
		add_action('wc_robokassa_admin_options_form_right_column_show', array($this, 'admin_right_widget_status'));
		add_action('wc_robokassa_admin_options_form_right_column_show', array($this, 'admin_right_widget_one'));
	}

	/**
	 * Page explode before table
	 */
	public function page_explode_table_before()
	{
		echo '<div class="row"><div class="col-24 col-md-17">';
	}

	/**
	 * Load urls:
     * - result
     * - fail
     * - success
	 */
	public function load_urls()
    {
	    $this->set_result_url(get_site_url(null, '/?wc-api=wc_robokassa&action=result'));
	    $this->set_fail_url(get_site_url(null, '/?wc-api=wc_robokassa&action=fail'));
	    $this->set_success_url(get_site_url(null, '/?wc-api=wc_robokassa&action=success'));
    }

	/**
	 * Get result url
	 *
	 * @return string
	 */
	public function get_result_url()
    {
		return $this->result_url;
	}

	/**
	 * Set result url
	 *
	 * @param string $result_url
	 */
	public function set_result_url($result_url)
    {
		$this->result_url = $result_url;
	}

	/**
	 * Get fail url
	 *
	 * @return string
	 */
	public function get_fail_url()
    {
		return $this->fail_url;
	}

	/**
	 * Set fail url
	 *
	 * @param string $fail_url
	 */
	public function set_fail_url($fail_url)
    {
		$this->fail_url = $fail_url;
	}

	/**
	 * Get success url
	 *
	 * @return string
	 */
	public function get_success_url()
    {
		return $this->success_url;
	}

	/**
	 * Set success url
	 *
	 * @param string $success_url
	 */
	public function set_success_url($success_url)
    {
		$this->success_url = $success_url;
	}

	/**
	 * Page explode after table
	 */
	public function page_explode_table_after()
	{
		echo '</div><div class="col-24 d-none d-md-block col-md-6">';

		do_action('wc_robokassa_admin_options_form_right_column_show');

		echo '</div></div>';
	}

	/**
	 * Widget one
	 */
	public function admin_right_widget_one()
	{
		echo '<div class="card border-light" style="margin-top: 0;padding: 0;">
  <div class="card-header" style="padding: 10px;">
    <h5 style="margin: 0;padding: 0;">' . __('Useful information', 'wc-robokassa') . '</h5>
  </div>
    <div class="card-body" style="padding: 0;">
      <ul class="list-group list-group-flush" style="margin: 0;">
    <li class="list-group-item"><a href="https://mofsy.ru/projects/wc-robokassa" target="_blank">' . __('Official plugin page', 'wc-robokassa') . '</a></li>
    <li class="list-group-item"><a href="https://mofsy.ru/blog/tag/robokassa" target="_blank">' . __('Related news: ROBOKASSA', 'wc-robokassa') . '</a></li>
    <li class="list-group-item"><a href="https://mofsy.ru/projects/tag/woocommerce" target="_blank">' . __('Plugins for WooCommerce', 'wc-robokassa') . '</a></li>
  </ul>
  </div>
</div>';
	}

	/**
	 * Widget status
	 */
	public function admin_right_widget_status()
	{
		$color = 'bg-success';
		$content = '';
		$footer = '';

		$color = apply_filters('wc_robokassa_widget_status_color', $color);
		$content = apply_filters('wc_robokassa_widget_status_content', $content);

		if($color === 'bg-success' || $color === 'text-white bg-success')
		{
			$footer = __('Errors not found. Payment acceptance is active.', 'wc-robokassa');
		}
		elseif($color === 'bg-warning' || $color === 'text-white bg-warning')
		{
			$footer = __('Warnings found. They are highlighted in yellow. You should attention to them.', 'wc-robokassa');
		}
		else
		{
			$footer = __('Critical errors were detected. They are highlighted in red. Payment acceptance is not active.', 'wc-robokassa');
		}

		echo '<div class="card mb-3 ' . $color . '" style="margin-top: 0;padding: 0;"><div class="card-header" style="padding: 10px;">
			<h5 style="margin: 0;padding: 0;">' . __('Status', 'wc-robokassa') . '</h5></div>
			<div class="card-body" style="padding: 0;">
      		<ul class="list-group list-group-flush" style="margin: 0;">';
		echo $content;
		echo '</ul></div>';
		echo '<div class="card-footer text-muted bg-light" style="padding: 10px;">';
		echo $footer;
		echo '</div></div>';
	}
}