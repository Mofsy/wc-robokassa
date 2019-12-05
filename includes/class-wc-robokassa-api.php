<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Api
{
	/**
	 * Base Api url
	 *
	 * @var string
	 */
	private $base_api_url = 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx';

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
	 */
	public function __construct()
	{
	}

	/**
	 * @return string
	 */
	public function get_base_api_url()
	{
		return $this->base_api_url;
	}

	/**
	 * @param string $base_api_url
	 */
	public function set_base_api_url($base_api_url)
	{
		$this->base_api_url = $base_api_url;
	}

	/**
	 * @return WP_Error|array The response or WP_Error on failure.
	 *
	 * @since 2.3.0.1
	 */
	public function get_last_response()
	{
		return $this->last_response;
	}

	/**
	 * @param $last_response WP_Error|array The response or WP_Error on failure.
	 *
	 * @since 2.3.0.1
	 */
	public function set_last_response($last_response)
	{
		$this->last_response = $last_response;
	}

	/**
	 * @return string
	 *
	 * @since 2.3.0.1
	 */
	public function get_last_response_body()
	{
		return $this->last_response_body;
	}

	/**
	 * @param string $last_response_body
	 *
	 * @since 2.3.0.1
	 */
	public function set_last_response_body($last_response_body)
	{
		$this->last_response_body = $last_response_body;
	}

	/**
	 * Available API
	 *
	 * @return int
	 */
	public function is_available()
	{
		/**
		 * Check WP
		 */
		if(!function_exists('wp_remote_get') || !function_exists('wp_remote_retrieve_body'))
		{
			return 0;
		}

		/**
		 * Check SimpleXMLElement installed
		 */
		if(class_exists('SimpleXMLElement'))
		{
			return 1;
		}

		return 0;
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
	 * @return mixed false - error, integer - success
	 */
	public function xml_calc_out_sum($IncCurrLabel, $IncSum, $merchantLogin = 'demo')
	{
		/**
		 * Check available
		 */
		$is_available = $this->is_available();
		if($is_available === 0) { return false; }

		/**
		 * URL
		 */
		$url = $this->get_base_api_url() . '/CalcOutSumm?MerchantLogin=' . $merchantLogin . '&IncCurrLabel=' . $IncCurrLabel . '&IncSum=' . $IncSum;

		/**
		 * Request execute
		 */
		$this->set_last_response(wp_remote_get($url));

		/**
		 * Last response set body
		 */
		$this->set_last_response_body(wp_remote_retrieve_body($this->get_last_response()));

		/**
		 * Response is very good
		 */
		if($this->get_last_response_body() != '')
		{
			/**
			 * SimpleXMl
			 */
			if($is_available === 1)
			{
				/**
				 * Response normalize
				 */
				try
				{
					$response_data = new SimpleXMLElement($this->get_last_response_body());
				}
				catch (Exception $e)
				{
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
		/**
		 * Check available
		 */
		$is_available = $this->is_available();
		if($is_available === 0) { return false; }

		/**
		 * URL
		 */
		$url = $this->get_base_api_url() . '/OpState?MerchantLogin=' . $merchantLogin . '&InvoiceID=' . $InvoiceID . '&Signature=' . $Signature;

		/**
		 * Request execute
		 */
		$this->set_last_response(wp_remote_get($url));

		/**
		 * Last response set body
		 */
		$this->set_last_response_body(wp_remote_retrieve_body($this->get_last_response()));

		/**
		 * Response is very good
		 */
		if($this->get_last_response_body() != '')
		{
			$op_state_data = array();

			/**
			 * SimpleXML
			 */
			if($is_available === 1)
			{
				/**
				 * Response normalize
				 */
				try
				{
					$response_data = new SimpleXMLElement($this->get_last_response_body());
				}
				catch (Exception $e)
				{
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
				 * Текущее состояние оплаты.
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
		}

		return false;
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
	 * @return mixed false - error, array - success
	 */
	public function xml_get_currencies($merchantLogin, $language)
	{
		/**
		 * Check available
		 */
		$is_available = $this->is_available();
		if($is_available === 0) { return false; }

		/**
		 * URL
		 */
		$url = $this->get_base_api_url() . '/GetCurrencies?MerchantLogin=' . $merchantLogin . '&language=' . $language;

		/**
		 * Request execute
		 */
		$this->set_last_response(wp_remote_get($url));

		/**
		 * Last response set body
		 */
		$this->set_last_response_body(wp_remote_retrieve_body($this->get_last_response()));

		/**
		 * Response is very good
		 */
		if($this->get_last_response_body() != '')
		{
			/**
			 * Данные валют
			 */
			$currencies_data = array();

			/**
			 * SimpleXML
			 */
			if($is_available === 1)
			{
				/**
				 * Response normalize
				 */
				try
				{
					$response_data = new SimpleXMLElement($this->get_last_response_body());
				}
				catch (Exception $e)
				{
					return false;
				}

				/**
				 * Check error
				 */
				if(!isset($response_data->Result) || $response_data->Result->Code != 0 || !isset($response_data->Groups))
				{
					return false;
				}

				/**
				 * Перебираем данные
				 */
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
		}

		return false;
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
	 * @return mixed false - error, array - success
	 */
	public function xml_get_payment_methods($merchantLogin, $language)
	{
		/**
		 * Check available
		 */
		$is_available = $this->is_available();
		if($is_available === 0) { return false; }

		/**
		 * URL
		 */
		$url = $this->get_base_api_url() . '/GetPaymentMethods?MerchantLogin=' . $merchantLogin . '&language=' . $language;

		/**
		 * Request execute
		 */
		$this->set_last_response(wp_remote_get($url));

		/**
		 * Last response set body
		 */
		$this->set_last_response_body(wp_remote_retrieve_body($this->get_last_response()));

		/**
		 * Response is very good
		 */
		if($this->get_last_response_body() != '')
		{
			/**
			 * Данные валют
			 */
			$methods_data = array();

			/**
			 * SimpleXML
			 */
			if($is_available === 1)
			{
				/**
				 * Response normalize
				 */
				try
				{
					$response_data = new SimpleXMLElement($this->get_last_response_body());
				}
				catch (Exception $e)
				{
					return false;
				}

				/**
				 * Check error
				 */
				if (!isset($response_data->Result) || $response_data->Result->Code != 0)
				{
					return false;
				}

				/**
				 * Перебираем данные
				 */
				foreach ( $response_data->Methods->Method as $xml_method )
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
		}

		return false;
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
	 * @param string $merchantLogin Идентификатор магазина, строка. Подробнее см. Создание Магазина.
	 * @param string $OutSum Сумма, которую хочет получить магазин. Исходя из этой суммы и текущих курсов валют для каждой валюты/варианта
	 * оплаты в списке будет рассчитана сумма, которую должен будет заплатить клиент.
	 * @param string $IncCurrLabel Код валюты, для которой нужно произвести расчет суммы к оплате. Если оставить этот параметр пустым,
	 * расчет будет произведен для всех доступных валют.
	 * @param string $language Язык для локализованных значений в ответе (названий валют, методов оплаты и т. д.).
	 *
	 * @return mixed
	 *
	 * @since 2.3.0.1
	 */
	public function xml_get_rates($merchantLogin, $OutSum, $IncCurrLabel = '', $language = 'ru')
	{
		/**
		 * Check available
		 */
		$is_available = $this->is_available();
		if($is_available === 0) { return false; }

		/**
		 * URL
		 */
		$url = $this->get_base_api_url() . '/GetRates?MerchantLogin=' . $merchantLogin . '&IncCurrLabel=' . $IncCurrLabel . '&OutSum=' . $OutSum . '&Language=' . $language;

		/**
		 * Request execute
		 */
		$this->set_last_response(wp_remote_get($url));

		/**
		 * Last response set body
		 */
		$this->set_last_response_body(wp_remote_retrieve_body($this->get_last_response()));

		/**
		 * Response is very good
		 */
		if($this->get_last_response_body() != '')
		{
			/**
			 * Данные валют
			 */
			$rates_data = array();

			/**
			 * SimpleXML
			 */
			if($is_available === 1)
			{
				/**
				 * Response normalize
				 */
				try
				{
					$response_data = new SimpleXMLElement($this->get_last_response_body());
				}
				catch (Exception $e)
				{
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
				 * Перебираем данные
				 */
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
		}

		return false;
	}

	/**
	 * @deprecated 2.3.0.1
	 *
	 * @param $merchantLogin
	 * @param $OutSum
	 * @param string $IncCurrLabel
	 * @param string $language
	 *
	 * @return mixed
	 */
	public function xm_get_rates($merchantLogin, $OutSum, $IncCurrLabel = '', $language = 'ru')
	{
		return $this->xml_get_rates($merchantLogin, $OutSum, $IncCurrLabel, $language);
	}

	/**
	 * Получение информации о доступном лимите платежей
	 *
	 * @param string $merchantLogin
	 *
	 * @return mixed
	 */
	public function xml_get_limit($merchantLogin)
	{
		/**
		 * Check available
		 */
		$is_available = $this->is_available();
		if($is_available === 0) { return false; }

		/**
		 * URL
		 */
		$url = $this->get_base_api_url() . '/GetLimit?MerchantLogin=' . $merchantLogin;

		/**
		 * Request execute
		 */
		$this->set_last_response(wp_remote_get($url));

		/**
		 * Last response set body
		 */
		$this->set_last_response_body(wp_remote_retrieve_body($this->get_last_response()));

		/**
		 * Response is very good
		 */
		if($this->get_last_response_body() != '')
		{
			/**
			 * SimpleXMl
			 */
			if($is_available === 1)
			{
				/**
				 * Response normalize
				 */
				try
				{
					$response_data = new SimpleXMLElement($this->get_last_response_body());
				}
				catch (Exception $e)
				{
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

		return false;
	}
}