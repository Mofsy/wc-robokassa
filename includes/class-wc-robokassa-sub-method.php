<?php
/**
 * Main sub method class
 *
 * @package Mofsy/WC_Robokassa
 */
defined('ABSPATH') || exit;

class Wc_Robokassa_Sub_Method extends Wc_Robokassa_Method
{
	/**
	 * Main method id
	 *
	 * @var false|string
	 */
	protected $parent_id = false;

	/**
	 * Main method settings
	 *
	 * @var false|array
	 */
	protected $parent_settings = false;

	/**
	 * Currency alias
	 *
	 * @var string
	 */
	protected $current_currency_alias = '';

	/**
	 * Wc_Robokassa_Sub_Method constructor
	 */
	public function __construct()
	{
		/**
		 * Main method id
		 */
		$this->parent_id = 'robokassa';

		/**
		 * Logger
		 */
		$this->init_logger();

		/**
		 * Admin title
		 */
		$this->method_title = __('Robokassa', 'wc-robokassa');

		/**
		 * Admin method description
		 */
		$this->method_description = __('Pay via Robokassa. Child method for Robokassa.', 'wc-robokassa');

		/**
		 * Main method options
		 */
		$this->init_options();

		/**
		 * Load available currencies
		 */
		WC_Robokassa()->load_robokassa_available_currencies($this->get_shop_login(), $this->get_user_interface_language());

		/**
		 * Load current rates
		 */
		if($this->is_rates_merchant() && is_admin() === false)
		{
			$sum = WC()->cart->total;
			WC_Robokassa()->load_merchant_rates($this->get_shop_login(), $sum, $this->get_user_interface_language());
		}
	}

	/**
	 * Get current currency alias
	 *
	 * @return string
	 */
	public function get_current_currency_alias()
	{
		return $this->current_currency_alias;
	}

	/**
	 * Set current currency alias
	 *
	 * @param string $current_currency_alias
	 */
	public function set_current_currency_alias( $current_currency_alias )
	{
		$this->current_currency_alias = $current_currency_alias;
	}

	/**
	 * Генерация формы
	 *
	 * - выбор метода после нажатия на оплату в случае включения возможности выбора
	 * способа оплаты на сайте
	 * - редирект на робокассу без выбора способа оплаты на сайте
	 * - редирект на робокассу в случае выбора способа оплаты в корзине.
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function generate_form($order_id)
	{
		$order = wc_get_order($order_id);
		if(!is_object($order))
		{
			die('Generate form error. Order not found.');
		}

		/**
		 * Rewrite currency from order
		 *
		 * todo: setting in admin?
		 */
		WC_Robokassa()->set_wc_currency($order->get_currency());

		/**
		 * Form parameters
		 */
		$args = array();

		/**
		 * Shop login
		 *
		 * Идентификатор магазина в ROBOKASSA, который придуман при создании магазина.
		 */
		$args['MerchantLogin'] = $this->shop_login;

		/**
		 * Sum
		 *
		 * Требуемая к получению сумма (буквально — стоимость заказа, сделанного клиентом). Формат представления — число,
		 * разделитель — точка, например: 123.45.
		 * Сумма должна быть указана в рублях.
		 * Но, если стоимость товаров на сайте указана, например, в долларах, то при выставлении счёта к оплате
		 * необходимо указывать уже пересчитанную сумму из долларов в рубли.
		 * См. необязательный параметр OutSumCurrency.
		 */
		$out_sum = number_format($order->get_total(), 2, '.', '');

		/**
		 * Оплата комиссии магазином
		 *
		 * todo: percentage + -
		 */
		if($this->is_commission_merchant() && $this->get_current_currency_alias() != '' && WC_Robokassa()->get_wc_currency() == 'RUB')
		{
			/**
			 * Считаем сумму к получению
			 */
			$out_sum = WC_Robokassa()->get_robokassa_api()->xml_calc_out_sum
			(
				$this->get_current_currency_alias(),
				$out_sum,
				$this->shop_login
			);
		}

		$args['OutSum'] = $out_sum;

		/**
		 * Order id
		 *
		 * Номер счета в магазине. Необязательный параметр, но мы настоятельно рекомендуем его использовать.
		 * Значение этого параметра должно быть уникальным для каждой оплаты. Может принимать значения от 1 до 2147483647 (231-1).
		 * Если значение параметра пустое, или равно 0, или параметр вовсе не указан,
		 * то при создании операции оплаты ему автоматически будет присвоено уникальное значение.
		 *
		 * Используйте данную возможность только в очень простых магазинах, где не требуется какого-либо контроля оплаты.
		 * Если параметр передан, то он должен быть включён в расчёт контрольной суммы (SignatureValue)
		 */
		$args['InvId'] = $order_id;

		/**
		 * Order description
		 *
		 * Описание покупки, можно использовать только символы английского или русского алфавита, цифры и знаки препинания.
		 * Максимальная длина — 100 символов. Эта информация отображается в интерфейсе ROBOKASSA и в Электронной квитанции,
		 * которую мы выдаём клиенту после успешного платежа.
		 * Корректность отображения зависит от необязательного параметра Encoding
		 */
		$args['InvDesc'] = __('Order number: ' . $order_id, 'wc-robokassa');

		/**
		 * Set currency to Robokassa
		 *
		 * Способ указать валюту, в которой магазин выставляет стоимость заказа. Этот параметр нужен для того,
		 * чтобы избавить магазин от самостоятельного пересчета по курсу. Является дополнительным к обязательному параметру OutSum.
		 * Если этот параметр присутствует, то OutSum показывает полную сумму заказа, конвертированную из той валюты,
		 * которая указана в параметре OutSumCurrency, в рубли по курсу ЦБ на момент оплаты. Принимает значения: USD, EUR и KZT.
		 *
		 * Если передается параметр OutSumCurrency, то он должен быть включен в расчет  контрольной суммы (SignatureValue).
		 * В этом случае база для  расчёта будет выглядеть так: MerchantLogin:OutSum:InvId:OutSumCurrency:Пароль#1
		 */
		switch (WC_Robokassa()->get_wc_currency())
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
		if ($this->test === 'yes')
		{
			$signature_pass = $this->test_shop_pass_1;
			$signature_method = $this->test_sign_method;

			$args['IsTest'] = 1;
		}
		/**
		 * Real payments
		 */
		else
		{
			$signature_pass = $this->shop_pass_1;
			$signature_method = $this->sign_method;
		}

		/**
		 * Billing email
		 *
		 * Email покупателя автоматически подставляется в платёжную форму ROBOKASSA.
		 * Пользователь может изменить его в процессе оплаты.
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
		if($this->is_ofd_status() === true)
		{
			WC_Robokassa()->get_logger()->info('generate_form: fiscal active');

			$receipt['sno'] = $this->get_ofd_sno();
			$receipt['items'] = $this->generate_receipt_items($order);

			$receipt_result = json_encode($receipt);

			WC_Robokassa()->get_logger()->debug('generate_form: $receipt_result', $receipt_result);
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
		 *
		 * Кодировка, в которой отображается страница ROBOKASSA. По умолчанию: windows-1251.
		 * Этот же параметр влияет на корректность отображения описания покупки (Description) в интерфейсе ROBOKASSA,
		 * и на правильность передачи Дополнительных пользовательских параметров,
		 * если в их значениях присутствует язык отличный от английского.
		 */
		$args['Encoding'] = 'utf-8';

		/**
		 * Language (culture)
		 *
		 * Язык общения с клиентом (в соответствии с ISO 3166-1). Определяет на каком языке будет страница ROBOKASSA,
		 * на которую попадёт покупатель. Может принимать значения: en, ru.
		 * Если параметр не передан, то используются региональные настройки браузера покупателя.
		 * Для значений отличных от ru или en используется английский язык.
		 */
		$args['Culture'] = $this->user_interface_language;

		/**
		 * IncCurrLabel
		 *
		 * Предлагаемый способ оплаты. Тот вариант оплаты, который Вы рекомендуете использовать своим покупателям
		 * (если не задано, то по умолчанию открывается оплата Банковской картой). Если параметр указан, то покупатель
		 * при переходе на сайт ROBOKASSA попадёт на страницу оплаты с выбранным способом оплаты.
		 *
		 * Пользователь может изменить его в процессе оплаты.
		 *
		 * Доступные значения для параметра IncCurrLabel — Alias валют, Вы можете получить с использованием
		 * соответствующего интерфейса описанного в разделе: XML интерфейсы. Интерфейс получения списка валют.
		 */
		if($this->get_parent_id() !== false)
		{
			$args['IncCurrLabel'] = $this->get_current_currency_alias();
		}

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
			$args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
		}

		/**
		 * Return full form
		 */
		return '<form action="'.esc_url($this->get_form_url()).'" method="POST" id="wc_robokassa_payment_form" accept-charset="utf-8">'."\n".
		       implode("\n", $args_array).
		       '<input type="submit" class="button alt" id="submit_wc_robokassa_payment_form" value="'.__('Pay', 'wc-robokassa').
		       '" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel & return to cart', 'wc-robokassa').'</a>'."\n".
		       '</form>';
	}

	/**
	 * Load parent settings
	 */
	public function load_parent_settings()
	{
		$parent_settings = WC_Robokassa()->get_method_settings_by_method_id('robokassa');

		if(is_array($parent_settings))
		{
			$this->set_parent_settings($parent_settings);
			WC_Robokassa()->get_logger()->info('load_parent_settings: success');
			return true;
		}

		WC_Robokassa()->get_logger()->info('load_parent_settings: return false');
		return false;
	}

	/**
	 * Псевдо конструктор
	 */
	public function init_child_method()
	{
		/**
		 * Add setting fields
		 */
		add_filter('wc_' . $this->id . '_init_form_fields', array($this, 'init_form_fields_main'), 10);
		add_filter('wc_' . $this->id . '_init_form_fields', array($this, 'init_form_fields_interface'), 20);

		/**
		 * Перезапись локальных настроек
		 */
		$this->init_settings();

		/**
		 * Admin fields
		 */
		$this->init_form_fields();

		/**
		 * Rewrite options
		 */
		$this->init_child_options();

		/**
		 * Save options from admin
		 */
		$this->process_options();

		/**
		 * Auto redirect
		 */
		add_action('wc_' . $this->id . '_input_payment_notifications', array(
			$this,
			'wc_robokassa_input_payment_notifications_redirect'
		));

		/**
		 * Admin title
		 */
		if($this->title !== '')
		{
			$this->method_title .= ' (' . $this->title . ')';
		}

		/**
		 * Add sum included commission to method title
		 */
		if(!is_admin() && $this->is_rates_merchant() === true && $this->is_commission_merchant() === false)
		{
			$current_rates = WC_Robokassa()->get_robokassa_rates_merchant();

			if(is_array($current_rates))
			{
				$current_position = array_search($this->get_current_currency_alias(), array_column($current_rates, 'currency_alias'));

				if(isset($current_rates[$current_position]))
				{
					$this->title .= ' (' . $current_rates[$current_position]['rate_inc_sum'] . ' ' . WC_Robokassa()->get_wc_currency() . ')';
				}
			}
		}
	}

	/**
	 * Перезапись из локальных настроек
	 */
	public function init_child_options()
	{
		/**
		 * Gateway not enabled?
		 */
		if($this->get_option('enabled') !== 'yes')
		{
			$this->enabled = false;
		}

		/**
		 * Title for user interface
		 */
		if($this->get_option('title', '') !== '')
		{
			$this->title = $this->get_option('title');
		}

		/**
		 * Set description
		 */
		if($this->get_option('description', '') !== '')
		{
			$this->description = $this->get_option('description');
		}

		/**
		 * Set order button text
		 */
		if($this->get_option('order_button_text', '') !== '')
		{
			$this->order_button_text = $this->get_option('order_button_text');
		}

		/**
		 * Set icon for child method
		 */
		$this->icon = '';
		if($this->get_option('enable_icon') === 'yes')
		{
			$this->icon = apply_filters('woocommerce_robokassa_icon', WC_ROBOKASSA_URL . 'assets/img/' . $this->id . '.png');
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @filter wc_{id}_init_form_fields
	 *
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = apply_filters('wc_' . $this->id . '_init_form_fields', array());
	}

	/**
	 * Add main settings
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
			'description' => __('Work is impossible without these settings. Carefully specify the correct data.', 'wc-robokassa'),
		);

		$fields['enabled'] = array
		(
			'title' => __('Online / Offline', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Enable display of the payment gateway on the website', 'wc-robokassa'),
			'description' => '',
			'default' => 'off'
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
			'title' => __( 'Interface', 'wc-robokassa' ),
			'type' => 'title',
			'description' => __( 'Customize the appearance. Can leave it at that.', 'wc-robokassa' ),
		);

		$fields['enable_icon'] = array
		(
			'title' => __('Show gateway icon?', 'wc-robokassa'),
			'type' => 'checkbox',
			'label' => __('Show', 'wc-robokassa'),
			'default' => 'no'
		);

		$fields['title'] = array
		(
			'title' => __('Title', 'wc-robokassa'),
			'type' => 'text',
			'description' => __( 'This is the name that the user sees during the payment.', 'wc-robokassa' ),
			'default' => $this->title
		);

		$fields['order_button_text'] = array
		(
			'title' => __('Order button text', 'wc-robokassa'),
			'type' => 'text',
			'description' => __( 'This is the button text that the user sees during the payment.', 'wc-robokassa' ),
			'default' => __('Goto pay', 'wc-robokassa')
		);

		$fields['description'] = array
		(
			'title' => __('Description', 'wc-robokassa'),
			'type' => 'textarea',
			'description' => __('Description of the method of payment that the customer will see on our website.', 'wc-robokassa'),
			'default' => __('Payment via Robokassa.', 'wc-robokassa')
		);

		return $fields;
	}

	/**
	 * Get parent method id
	 *
	 * Use for detect submethods
	 *
	 * @return string
	 */
	public function get_parent_id()
	{
		return $this->parent_id;
	}

	/**
	 * Set parent method id
	 *
	 * @param string $parent_id
	 */
	public function set_parent_id($parent_id)
	{
		$this->parent_id = $parent_id;
	}

	/**
	 * Get parent settings
	 *
	 * @return array|false
	 */
	public function get_parent_settings()
	{
		return $this->parent_settings;
	}

	/**
	 * Set parent settings
	 *
	 * @param array|false $parent_settings
	 */
	public function set_parent_settings($parent_settings)
	{
		$this->parent_settings = $parent_settings;
	}

	/**
	 * @return bool
	 */
	public function is_available()
	{
		$return = parent::is_available();

		if($return && is_array(WC_Robokassa()->get_robokassa_available_currencies()) && count(WC_Robokassa()->get_robokassa_available_currencies()) > 0)
		{
			$return = in_array($this->get_current_currency_alias(), array_unique(array_column(WC_Robokassa()->get_robokassa_available_currencies(), 'currency_alias')));
		}
		else
		{
			return false;
		}

		return $return;
	}

	/**
	 * Check instant payment notification
	 *
	 * @return void
	 */
	public function input_payment_notifications()
	{
		// hook
		do_action('wc_' . $this->id . '_input_payment_notifications');
	}
}