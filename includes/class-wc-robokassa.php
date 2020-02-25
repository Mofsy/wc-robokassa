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
	 * @var WC_Gatework_Logger
	 */
	public $logger = false;

	/**
     * Api Robokassa
     *
	 * @var Wc_Robokassa_Api
	 */
	public $robokassa_api = false;

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
	public $wc_currency;

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
	 * WC_Robokassa constructor
	 */
	public function __construct()
	{
		// hook
		do_action('wc_robokassa_loading');

		/**
		 * Include required files for initializing
		 */
		$this->init_includes();

		/**
		 * Initializing actions & filters
		 */
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
		if (is_null(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning instances is forbidden due to singleton pattern
	 *
	 * @since 2.0.0.1
	 */
	public function __clone()
    {
	    _doing_it_wrong(__FUNCTION__, sprintf('You cannot clone instances of %s.', get_class($this)), '2.0.0.1');
	}

	/**
	 * Un-serializing instances is forbidden due to singleton pattern
	 *
	 * @since 2.0.0.1
	 */
	public function __wakeup()
    {
	    _doing_it_wrong(__FUNCTION__, sprintf('You cannot unserialize instances of %s.', get_class($this)), '2.0.0.1');
	}
	
	/**
	 * Init required files
	 */
	public function init_includes()
	{
		// hook
		do_action('wc_robokassa_includes_start');

		include_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-api.php';
		require_once WC_ROBOKASSA_PLUGIN_DIR . 'includes/class-wc-robokassa-method.php';

		// hook
		do_action('wc_robokassa_includes_end');
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
	 * Hooks (actions & filters)
	 */
	private function init_hooks()
	{
		/**
		 * Init
		 */
		add_action('init', array($this, 'init'), 0);

		/**
		 * Gateway init
		 */
		add_action('woocommerce_init', array($this, 'wc_robokassa_gateway_init'), 10);

		/**
		 * Admin
		 */
		if(is_admin())
		{
			add_action('init', array($this, 'admin_init'), 0);
			add_action('admin_enqueue_scripts', array($this, 'wc_robokassa_admin_styles'), 10);
			add_action('admin_notices', array($this, 'wc_robokassa_admin_notices'), 10);
			add_filter('plugin_action_links_' . WC_ROBOKASSA_PLUGIN_NAME, array($this, 'links_left'), 10);
			add_filter('plugin_row_meta', array($this, 'links_right'), 10, 2);

			$this->page_explode();
		}
	}

	/**
	 * Init plugin gateway
	 */
	public function wc_robokassa_gateway_init()
	{
		// hook
		do_action('wc_robokassa_gateway_init_before');

		if(!class_exists('WC_Payment_Gateway'))
		{
			return;
		}

		add_filter('woocommerce_payment_gateways', array($this, 'add_wc_gateway_method'), 10);

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

		$this->load_plugin_text_domain();
		$this->load_wc_version();
		$this->load_currency();
		$this->load_robokassa_api();

		return true;
	}

	/**
	 * Admin initialization
	 */
	public function admin_init()
	{
		/**
		 * Load URLs for settings
		 */
		$this->load_urls();
	}

	/**
	 * Load robokassa api
	 */
	public function load_robokassa_api()
    {
        $default_class_name = 'Wc_Robokassa_Api';

	    /**
	     * Load API class name from external code
	     */
	    $robokassa_api_class_name = apply_filters('wc_robokassa_api_class_name_load', $default_class_name);

	    /**
	     * Fallback
	     */
	    if(!class_exists($robokassa_api_class_name))
        {
	        $robokassa_api_class_name = $default_class_name;
        }

        $robokassa_api = new $robokassa_api_class_name();

        $this->set_robokassa_api($robokassa_api);
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
	 */
	public function load_currency()
    {
	    /**
	     * WooCommerce Currency Switcher
	     */
	    if(class_exists('WOOCS'))
	    {
		    global $WOOCS;

		    $this->set_wc_currency(strtoupper($WOOCS->storage->get_val('woocs_current_currency')));
	    }
	    else
	    {
		    $this->set_wc_currency(gatework_get_wc_currency());
	    }
    }

	/**
	 * Load current WC version
	 */
	public function load_wc_version()
    {
	    $this->set_wc_version(gatework_get_wc_version());
    }

	/**
	 * Load localisation files
	 */
	public function load_plugin_text_domain()
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

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods - all WooCommerce initialized gateways
	 *
	 * @return array - new WooCommerce initialized gateways
	 */
	public function add_wc_gateway_method($methods)
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
	 * Load logger
	 */
	public function load_logger()
	{
		try
		{
			$logger = new WC_Gatework_Logger();
		}
		catch(Exception $e)
		{
			return false;
		}

		if(function_exists('wp_upload_dir'))
		{
			$wp_dir = wp_upload_dir();

			$logger->set_name('wc-robokassa.boot.log');
			$logger->set_level(50);
			$logger->set_path($wp_dir['basedir']);
		}

		return false;
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
	 * @return WC_Gatework_Logger|null
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
		return array_merge(array('settings' => '<a href="https://mofsy.ru/projects/wc-robokassa-premium" target="_blank">' . __('Premium addon', 'wc-robokassa') . '</a>'), $links);
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
	 * Add admin css styles
	 */
	public function wc_robokassa_admin_styles()
    {
        if(isset($_GET['section']) && $_GET['section'] === 'robokassa')
        {
            wp_enqueue_style('robokassa-admin-styles', WC_ROBOKASSA_URL . 'assets/css/main.css');
        }
	}

	/**
	 * Show admin notices
	 */
	public function wc_robokassa_admin_notices()
    {
        $current_version = '2.5';
        $settings_version = get_option('wc_robokassa_last_settings_update_version');

        /**
         * Global notice: Require update settings
         */
        if(get_option('wc_robokassa_last_settings_update_version') !== false && $settings_version < $current_version && $_GET['section'] !== 'robokassa')
        {
        }
    }

	/**
	 *  Add page explode actions
	 */
	public function page_explode()
	{
		add_action('wc_robokassa_admin_options_form_before_show', array($this, 'page_explode_table_before'));
		add_action('wc_robokassa_admin_options_form_after_show', array($this, 'page_explode_table_after'));
		add_action('wc_robokassa_admin_options_form_right_column_show', array($this, 'admin_right_widget_one'));
		add_action('wc_robokassa_admin_options_form_right_column_show', array($this, 'admin_right_widget_two'));
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
    <li class="list-group-item"><a href="https://mofsy.ru/others/feedback" target="_blank">' . __('Feedback to author', 'wc-robokassa') . '</a></li>
  </ul>
  </div>
  <div class="card-footer text-muted" style="padding: 10px;">
    ' . __('This plugin is sponsored by the premium extension. Technical support and updates are provided on an irregular basis.', 'wc-robokassa') . '
  </div>
</div>';
	}

	/**
	 * Widget two
	 */
	public function admin_right_widget_two()
	{
		echo '<div class="card text-white border-light bg-dark" style="margin-top: 10px;padding: 0;">
  <div class="card-header" style="padding: 10px;">
    <h5 style="margin: 0;padding: 0;">' . __('Extension', 'wc-robokassa') . '</h5>
  </div> <a href="https://mofsy.ru/projects/wc-robokassa-premium" target="_blank">
   	<img src="' . WC_ROBOKASSA_URL . 'assets/img/wc-robokassa-premium-icon.png" class="card-img-top">
   </a>
  <div class="card-body text-center">
    ' . __('Even more opportunities to accept payments. Increase conversion and stability of the algorithms.', 'wc-robokassa') . '
    <p>
    <a href="https://mofsy.ru/projects/wc-robokassa-premium" class="btn btn-secondary" target="_blank">' . __('Official plugin page', 'wc-robokassa') . '</a>
    </p>
  </div></div>';
	}
}