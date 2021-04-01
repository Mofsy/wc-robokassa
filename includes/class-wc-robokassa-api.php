<?php
/**
 * Main API class
 *
 * @package Mofsy/WC_Robokassa/Api
 */
defined('ABSPATH') || exit;

class Wc_Robokassa_Api
{
	/**
	 * Api url
	 *
	 * @var string
	 */
	private $api_url = 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx';

	/**
	 * Api endpoint
	 *
	 * @var string
	 */
	private $api_endpoint = '';

	/**
	 * Last response
	 *
	 * @var WP_Error|array The response or WP_Error on failure.
	 */
	private $last_response;

	/**
	 * Last response body
	 *
	 * @var string
	 */
	private $last_response_body = '';

	/**
	 * Wc_Robokassa_Api constructor
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		if(!defined('LIBXML_VERSION'))
		{
			throw new Exception('LIBXML_VERSION not defined');
		}

		if(!function_exists('libxml_use_internal_errors'))
		{
			throw new Exception('libxml_use_internal_errors');
		}

		if(!function_exists('wp_remote_get') || !function_exists('wp_remote_retrieve_body'))
		{
			throw new Exception('wp_remote_get && wp_remote_retrieve_body is not available');
		}

		if(!class_exists('SimpleXMLElement'))
		{
			throw new Exception('SimpleXMLElement is not exists');
		}

		libxml_use_internal_errors(true);
	}

	/**
	 * Get base api URL
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_api_url()
	{
		return $this->api_url;
	}

	/**
	 * Set base api URL
	 *
	 * @since 4.1.0
	 *
	 * @param string $api_url
	 */
	public function set_api_url($api_url)
	{
		$this->api_url = $api_url;
	}

	/**
	 * Get api endpoint
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_api_endpoint()
	{
		return $this->api_endpoint;
	}

	/**
	 * Set api endpoint
	 *
	 * @since 4.1.0
	 *
	 * @param string $api_endpoint
	 */
	public function set_api_endpoint($api_endpoint)
	{
		$this->api_endpoint = $api_endpoint;
	}

	/**
	 * Get last response
	 *
	 * @since 2.3.0.1
	 *
	 * @return WP_Error|array The response or WP_Error on failure.
	 */
	public function get_last_response()
	{
		return $this->last_response;
	}

	/**
	 * Set last response
	 *
	 * @since 2.3.0.1
	 *
	 * @param $last_response WP_Error|array The response or WP_Error on failure.
	 *
	 */
	public function set_last_response($last_response)
	{
		$this->last_response = $last_response;
	}

	/**
	 * Get last response body
	 *
	 * @since 2.3.0.1
	 *
	 * @return string
	 */
	public function get_last_response_body()
	{
		return $this->last_response_body;
	}

	/**
	 * Set last response body
	 *
	 * @since 2.3.0.1
	 *
	 * @param string $last_response_body
	 */
	public function set_last_response_body($last_response_body)
	{
		$this->last_response_body = $last_response_body;
	}

	/**
	 * Request execute
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	private function execute()
	{
		$url = $this->get_api_url() . $this->get_api_endpoint();

		$response = wp_remote_get($url);
		$this->set_last_response($response);

		$response_body = wp_remote_retrieve_body($this->get_last_response());
		$this->set_last_response_body($response_body);

		if($this->get_last_response_body() === '')
		{
			return false;
		}

		$xml_data = simplexml_load_string($this->get_last_response_body());

		if(!$xml_data)
		{
			throw new Exception('Wc_Robokassa_Api execute: xml errors');
		}

		return $xml_data;
	}

	/**
	 * Интерфейс расчёта суммы к получению магазином
	 *
	 * - Только для физических лиц.
	 *
	 * Позволяет рассчитать сумму к получению, исходя из текущих курсов ROBOKASSA,
	 * по сумме, которую заплатит пользователь.
	 *
	 * @param $IncCurrLabel string Код валюты, для которой нужно произвести расчет суммы к оплате.
	 * @param $IncSum mixed Сумма, которую должен будет заплатить пользователь.
	 * @param $merchantLogin string Логин магазина.
	 *
	 * @return false|string false - error, integer - success
	 */
	public function xml_calc_out_sum($IncCurrLabel, $IncSum, $merchantLogin = 'demo')
	{
		$endpoint = '/CalcOutSumm?MerchantLogin=' . $merchantLogin . '&IncCurrLabel=' . $IncCurrLabel . '&IncSum=' . $IncSum;
		$this->set_api_endpoint($endpoint);

		try
		{
			$response_data = $this->execute();
		}
		catch(Exception $e)
		{
			wc_robokassa_logger()->error('xml_calc_out_sum', $e);
			return false;
		}

		/**
		 * Check error
		 */
		if(!isset($response_data->Result) || $response_data->Result->Code != 0)
		{
			return false;
		}

		/**
		 * OutSum
		 */
		if(isset($response_data->OutSum))
		{
			return (string)$response_data->OutSum;
		}

		return false;
	}

	/**
	 * Интерфейс получения состояния оплаты счета
	 *
	 * Возвращает детальную информацию о текущем состоянии и реквизитах оплаты.
	 * Необходимо помнить, что операция инициируется не в момент ухода пользователя на оплату,
	 * а позже – после подтверждения его платежных реквизитов, т.е. Вы вполне можете не находить операцию,
	 * которая по Вашему мнению уже должна начаться.
	 *
	 * @param string $merchantLogin Идентификатор магазина
	 * @param integer $InvoiceID Номер счета магазина, целое число.
	 * @param  string $Signature Контрольная сумма – хэш, число в 16-ричной форме и любом регистре (0-9, A-F),
	 * рассчитанное методом указанным в Технических настройках магазина. Базой для расчёта служат все обязательные
	 * параметры, разделенные символом «:», с добавлением Пароль #2
	 * т. е.: MerchantLogin:InvoiceID:Пароль#2
	 *
	 * @return mixed
	 */
	public function xml_op_state($merchantLogin, $InvoiceID, $Signature)
	{
		$endpoint = '/OpState?MerchantLogin=' . $merchantLogin . '&InvoiceID=' . $InvoiceID . '&Signature=' . $Signature;

		$this->set_api_endpoint($endpoint);

		try
		{
			$response_data = $this->execute();
		}
		catch(Exception $e)
		{
			wc_robokassa_logger()->error('xml_op_state', $e);
			return false;
		}

		$op_state_data = [];

		/**
		 * Check error
		 */
		if(!isset($response_data->Result) || $response_data->Result->Code != 0)
		{
			return false;
		}

		/**
		 * Current payment state
		 */
		if(isset($response_data->State))
		{
			$op_state_data['state'] = array
			(
				'code' => (string)$response_data->State->Code,
				'request_date' => (string)$response_data->State->RequestDate,
				'state_date' => (string)$response_data->State->StateDate,
			);
		}

		/**
		 * Информация об операции оплаты счета
		 */
		if(isset($response_data->Info))
		{
			$op_state_data['info'] = array
			(
				'inc_curr_label' => (string)$response_data->Info->IncCurrLabel,
				'inc_sum' => (string)$response_data->Info->IncSum,
				'inc_account' => (string)$response_data->Info->IncAccount,
				'payment_method_code' => (string)$response_data->Info->PaymentMethod->Code,
				'payment_method_description' => (string)$response_data->Info->PaymentMethod->Description,
				'out_curr_label' => (string)$response_data->Info->OutCurrLabel,
				'out_sum' => (string)$response_data->Info->OutSum,
			);
		}

		return $op_state_data;
	}

	/**
	 * Интерфейс получения списка валют
	 *
	 * Возвращает список валют, доступных для оплаты заказов указанного магазина/сайта.
	 * Используется для указания значений параметра IncCurrLabel, также используется
	 * для отображения доступных вариантов оплаты непосредственно на сайте.
	 *
	 * @param $merchantLogin string Идентификатор магазина
	 * @param $language string Язык для локализованных значений в ответе
	 * (названий валют, методов оплаты и т. д.).
	 * Возможные значения:
	 * ru – русский;
	 * en – английский.
	 *
	 * @return array|false
	 */
	public function xml_get_currencies($merchantLogin, $language)
	{
		$endpoint = '/GetCurrencies?MerchantLogin=' . $merchantLogin . '&language=' . $language;

		$this->set_api_endpoint($endpoint);

		try
		{
			$response_data = $this->execute();
		}
		catch(Exception $e)
		{
			wc_robokassa_logger()->error('xml_get_currencies', $e);
			return false;
		}

		/**
		 * Available currencies
		 */
		$currencies_data = [];

		if(!isset($response_data->Result) || $response_data->Result->Code != 0 || !isset($response_data->Groups))
		{
			return false;
		}

		foreach($response_data->Groups->Group as $xml_group)
		{
			$xml_group_attributes = $xml_group->attributes();

			foreach($xml_group->Items->Currency as $xml_group_item)
			{
				$xml_group_item_attributes = $xml_group_item->attributes();

				$response_item = array
				(
					'group_code' => (string)$xml_group_attributes['Code'],
					'group_description' => (string)$xml_group_attributes['Description'],
					'currency_label' => (string)$xml_group_item_attributes['Label'],
					'currency_alias' => (string)$xml_group_item_attributes['Alias'],
					'currency_name' => (string)$xml_group_item_attributes['Name'],
					'language' => $language,
				);

				if(isset($xml_group_item_attributes['MaxValue']))
				{
					$response_item['sum_max'] = (string)$xml_group_item_attributes['MaxValue'];
				}

				if(isset($xml_group_item_attributes['MinValue']))
				{
					$response_item['sum_min'] = (string)$xml_group_item_attributes['MinValue'];
				}

				$currencies_data[] = $response_item;
			}
		}

		return $currencies_data;
	}

	/**
	 * Интерфейс получения списка доступных методов оплаты
	 *
	 * Возвращает список методов оплаты, доступных для оплаты заказов указанного магазина/сайта.
	 * Используется для отображения доступных методов оплаты непосредственно на сайте,
	 * если Вы желаете дать больше информации своим клиентам. Основное отличие от Списка валют – здесь
	 * не показывается детальная информация по всем вариантам оплаты, здесь отображаются группы/методы оплаты.
	 *
	 * @param $merchantLogin string Идентификатор магазина
	 * @param $language string Язык для локализованных значений в ответе
	 * (названий валют, методов оплаты и т. д.).
	 * Возможные значения:
	 * ru – русский;
	 * en – английский.
	 *
	 * @return array|false - error, array - success
	 */
	public function xml_get_payment_methods($merchantLogin = 'demo', $language = 'ru')
	{
		$endpoint = '/GetPaymentMethods?MerchantLogin=' . $merchantLogin . '&language=' . $language;

		$this->set_api_endpoint($endpoint);

		try
		{
			$response_data = $this->execute();
		}
		catch(Exception $e)
		{
			wc_robokassa_logger()->error('xml_get_payment_methods', $e);
			return false;
		}

		/**
		 * Available methods
		 */
		$methods_data = [];

		/**
		 * Check error
		 */
		if(!isset($response_data->Result) || $response_data->Result->Code != 0)
		{
			return false;
		}

		foreach($response_data->Methods->Method as $xml_method)
		{
			$xml_method_attributes = $xml_method->attributes();

			$methods_data[ (string) $xml_method_attributes['Code'] ] = array
			(
				'method_code' => (string) $xml_method_attributes['Code'],
				'method_description' => (string) $xml_method_attributes['Description'],
				'language' => $language
			);
		}

		return $methods_data;
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
	 *
	 * @since 2.3.0.1
	 *
	 * @param string $merchantLogin Идентификатор магазина, строка. Подробнее см. Создание Магазина.
	 * @param string $OutSum Сумма, которую хочет получить магазин. Исходя из этой суммы и текущих курсов валют для каждой валюты/варианта
	 * оплаты в списке будет рассчитана сумма, которую должен будет заплатить клиент.
	 * @param string $IncCurrLabel Код валюты, для которой нужно произвести расчет суммы к оплате. Если оставить этот параметр пустым,
	 * расчет будет произведен для всех доступных валют.
	 * @param string $language Язык для локализованных значений в ответе (названий валют, методов оплаты и т. д.).
	 *
	 * @return mixed
	 */
	public function xml_get_rates($merchantLogin, $OutSum, $IncCurrLabel = '', $language = 'ru')
	{
		$endpoint = '/GetRates?MerchantLogin=' . $merchantLogin . '&IncCurrLabel=' . $IncCurrLabel . '&OutSum=' . $OutSum . '&Language=' . $language;

		$this->set_api_endpoint($endpoint);

		try
		{
			$response_data = $this->execute();
		}
		catch(Exception $e)
		{
			wc_robokassa_logger()->error('xml_get_rates', $e);
			return false;
		}

		/**
		 * Rates
		 */
		$rates_data = [];

		/**
		 * Check error
		 */
		if(!isset($response_data->Result) || $response_data->Result->Code != 0)
		{
			return false;
		}

		foreach($response_data->Groups->Group as $xml_group)
		{
			$xml_group_attributes = $xml_group->attributes();

			foreach($xml_group->Items->Currency as $xml_group_item)
			{
				$xml_group_item_attributes = $xml_group_item->attributes();
				$xml_group_item_rate_attributes = $xml_group_item->Rate->attributes();

				$rates_item =  array
				(
					'group_code' => (string)$xml_group_attributes['Code'],
					'group_description' => (string)$xml_group_attributes['Description'],
					'currency_label' => (string)$xml_group_item_attributes['Label'],
					'currency_alias' => (string)$xml_group_item_attributes['Alias'],
					'currency_name' => (string)$xml_group_item_attributes['Name'],
					'rate_inc_sum' => (string)$xml_group_item_rate_attributes['IncSum'],
					'language' => $language,
				);

				if(isset($xml_group_item_attributes['MaxValue']))
				{
					$rates_item['currency_sum_max'] = (string)$xml_group_item_attributes['MaxValue'];
				}

				if(isset($xml_group_item_attributes['MinValue']))
				{
					$rates_item['currency_sum_min'] = (string)$xml_group_item_attributes['MinValue'];
				}

				$rates_data[] = $rates_item;
			}
		}

		return $rates_data;
	}

	/**
	 * Получение информации о доступном лимите платежей
	 *
	 * @param string $merchantLogin
	 *
	 * @return string|false
	 */
	public function xml_get_limit($merchantLogin = 'demo')
	{
		$endpoint = '/GetLimit?MerchantLogin=' . $merchantLogin;

		$this->set_api_endpoint($endpoint);

		try
		{
			$response_data = $this->execute();
		}
		catch(Exception $e)
		{
			wc_robokassa_logger()->error('xml_get_limit', $e);
			return false;
		}

		/**
		 * Check error
		 */
		if(!isset($response_data->Result) || $response_data->Result->Code != 0)
		{
			return false;
		}

		/**
		 * Limit exists
		 */
		if(isset($response_data->Limit))
		{
			return (string)$response_data->Limit;
		}

		return false;
	}
}