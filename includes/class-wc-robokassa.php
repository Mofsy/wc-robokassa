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
	 * The single instance of the class.
	 *
	 * @var WC_Robokassa
	 */
	protected static $_instance = null;

	/**
	 * Logger
	 *
	 * @var WC_Gatework_Logger
	 */
	public $logger;

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
		// hook
		do_action( 'wc_robokassa_loading' );

		$this->includes();
		$this->hooks();

		// hook
		do_action( 'wc_robokassa_loaded' );
	}

	/**
	 * Main WC_Robokassa Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @static
	 *
	 * @return WC_Robokassa - Main instance.
	 */
	public static function instance()
	{
		if ( is_null( self::$_instance ) )
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Include required files.
	 */
	public function includes()
	{
		// hook
		do_action('wc_robokassa_includes_start');

		include_once WC_ROBOKASSA_PLUGIN_DIR . '/includes/class-wc-robokassa-api.php';
		include_once WC_ROBOKASSA_PLUGIN_DIR . '/includes/class-wc-robokassa-method.php';

		// hook
		do_action('wc_robokassa_includes_end');
	}

	/**
     * Get current currency
     *
	 * @return string
	 */
	public function get_currency()
    {
        return $this->currency;
    }

	/**
     * Set current currency
     *
	 * @param $currency
	 */
    public function set_currency($currency)
    {
        $this->currency = $currency;
    }

	/**
	 * Hook into actions and filters
	 */
	private function hooks()
	{
		/**
		 * Init
		 */
		add_action( 'woocommerce_init', array( $this, 'init' ), 0 );

		/**
		 * Add payment method
		 */
		add_filter('woocommerce_payment_gateways',  array( $this, 'woocommerce_gateway_method_add' ));

		/**
		 * Admin
		 */
		if(is_admin())
		{
			add_action('admin_enqueue_scripts', array( $this, 'admin_style' ));
			add_filter( 'plugin_action_links_' . WC_ROBOKASSA_PLUGIN_NAME, array( $this, 'links_left' ) );
			add_filter( 'plugin_row_meta', array( $this, 'links_right' ), 10, 2 );
		}
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		/**
		 * Load logger
		 */
	    $this->load_logger();

		/**
		 * Load languages
		 */
		$this->load_plugin_textdomain();

		/**
		 * Set WooCommerce version
		 */
		$this->wc_version = gatework_wc_get_version_active();

		/**
		 * Logger debug
		 */
		$this->get_logger()->addDebug('WooCommerce version: ' . $this->wc_version);

		/**
		 * Get currency
		 */
		$this->currency = gatework_get_wc_currency();

		/**
		 * Logger debug
		 */
		$this->get_logger()->addDebug('Current currency: ' . $this->currency);
	}

	/**
	 * Load localisation files
	 */
	public function load_plugin_textdomain()
	{
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'wc-robokassa' );

		unload_textdomain( 'wc-robokassa' );
		load_textdomain( 'wc-robokassa', WP_LANG_DIR . '/wc-robokassa/wc-robokassa-' . $locale . '.mo' );
		load_textdomain( 'wc-robokassa', dirname( plugin_basename( __FILE__ ) ) . '/languages/wc-robokassa-' . $locale . '.mo' );
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	public function woocommerce_gateway_method_add($methods)
	{
		/**
		 * Default method
		 */
		$methods[] = 'Wc_Robokassa_Method';

		return $methods;
	}

	/**
	 * Load logger
	 */
	public function load_logger()
	{
		if(function_exists('wp_upload_dir'))
		{
			$wp_dir = wp_upload_dir();
			$level = 50;

			if($level == false || $level == '')
			{
				$level = 50;
			}

			$this->set_logger(new WC_Gatework_Logger( $wp_dir['basedir'] . '/wc1c.txt', $level));
		}
	}

	/**
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
		return array_merge(array('settings' => '<a href="https://mofsy.ru/about/help">' . __('Donate for author', 'wc-robokassa') . '</a>'), $links);
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
		if ( $file === WC_ROBOKASSA_PLUGIN_NAME )
		{
			$links[] = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=robokassa').'">' . __('Settings') . '</a>';
		}

		return $links;
	}

	/**
	 * Css styles
	 */
	public function admin_style()
    {
		wp_enqueue_style('robokassa-admin-styles', WC_ROBOKASSA_PLUGIN_DIR . '/assets/css/main.css');
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