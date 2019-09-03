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
			'timeout' => 10,
			'body' => ''
		);

		/**
		 * Request execute
		 */
		$response = wp_remote_post($this->get_base_api_url() . '/CalcOutSumm?MerchantLogin=' . $merchantLogin . '&IncCurrLabel=' . $IncCurrLabel . '&IncSum=' . $IncSum, $args);

		/**
		 * Response get
		 */
		$response_body = wp_remote_retrieve_body($response);

		/**
		 * Response is very good
		 */
		if($response_body != '')
		{
			/**
			 * Response normalize
			 */
			$response_data = new SimpleXMLElement($response_body);

			/**
			 * Check error
			 *
			 * @todo refactoring
			 */
			if($response_data->Result->Code != 0)
			{
				return false;
			}

			return $response_data->OutSum;
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
			'timeout' => 10,
			'body' => ''
		);

		/**
		 * Request execute
		 */
		$response = wp_remote_post($this->get_base_api_url() . '/OpState?MerchantLogin=' . $merchantLogin . '&InvoiceID=' . $InvoiceID . '&Signature=' . $Signature, $args);

		/**
		 * Response get
		 */
		$response_body = wp_remote_retrieve_body($response);

		/**
		 * Response is very good
		 */
		if($response_body != '')
		{
			/**
			 * Response normalize
			 */
			$response_data = new SimpleXMLElement($response_body);

			/**
			 * Check error
			 *
			 * @todo refactoring
			 */
			if($response_data->Result->Code != 0)
			{
				return false;
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
			'timeout' => 10,
			'body' => ''
		);

		/**
		 * Request execute
		 */
		$response = wp_remote_post($this->get_base_api_url() . '/GetCurrencies?MerchantLogin=' . $merchantLogin . '&language=' . $language, $args);

		/**
		 * Response get
		 */
		$response_body = wp_remote_retrieve_body($response);

		/**
		 * Response is very good
		 */
		if($response_body != '')
		{
			/**
			 * Response normalize
			 */
			$response_data = new SimpleXMLElement($response_body);

			/**
			 * Check error
			 *
			 * @todo refactoring
			 */
			if($response_data->Result->Code != 0)
			{
				return false;
			}

			/**
			 * Данные валют
			 */
			$currencies_data = array();

			/**
			 * Перебираем данные
			 */
			foreach($response_data->Groups->Group as $xml_group)
			{
				$xml_group_attributes = $xml_group->attributes();

				foreach($xml_group->Items->Currency as $xml_group_item)
				{
					$xml_group_item_attributes = $xml_group_item->attributes();

					$currencies_data[] = array
					(
						'group_code' => (string)$xml_group_attributes['Code'],
						'group_description' => (string)$xml_group_attributes['Description'],
						'currency_label' => (string)$xml_group_item_attributes['Label'],
						'currency_alias' => (string)$xml_group_item_attributes['Alias'],
						'currency_name' => (string)$xml_group_item_attributes['Name'],
						'language' => $language,
					);
				}
			}

			return $currencies_data;
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
			'timeout' => 10,
			'body' => ''
		);

		/**
		 * Request execute
		 */
		$response = wp_remote_post($this->get_base_api_url() . '/GetPaymentMethods?MerchantLogin=' . $merchantLogin . '&language=' . $language, $args);

		/**
		 * Response get
		 */
		$response_body = wp_remote_retrieve_body($response);

		/**
		 * Response is very good
		 */
		if($response_body != '')
		{
			/**
			 * Response normalize
			 */
			$response_data = new SimpleXMLElement($response_body);

			/**
			 * Check error
			 *
			 * @todo refactoring
			 */
			if($response_data->Result->Code != 0)
			{
				return false;
			}

			/**
			 * Данные валют
			 */
			$methods_data = array();

			/**
			 * Перебираем данные
			 */
			foreach($response_data->Methods->Method as $xml_method)
			{
				$xml_method_attributes = $xml_method->attributes();

				$methods_data[(string)$xml_method_attributes['Code']] = array
				(
					'method_code' => (string)$xml_method_attributes['Code'],
					'method_description' => (string)$xml_method_attributes['Description'],
					'language' => $language
				);
			}

			return $methods_data;
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
	 */
	public function xm_get_rates($merchantLogin, $OutSum, $IncCurrLabel = '', $language = 'ru')
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
			'timeout' => 10,
			'body' => ''
		);

		/**
		 * Request execute
		 */
		$response = wp_remote_post($this->get_base_api_url() . '/GetRates?MerchantLogin=' . $merchantLogin . '&IncCurrLabel=' . $IncCurrLabel . '&OutSum=' . $OutSum . '&Language=' . $language, $args);

		/**
		 * Response get
		 */
		$response_body = wp_remote_retrieve_body($response);

		/**
		 * Response is very good
		 */
		if($response_body != '')
		{
			/**
			 * Response normalize
			 */
			$response_data = new SimpleXMLElement($response_body);

			/**
			 * Check error
			 *
			 * @todo refactoring
			 */
			if($response_data->Result->Code != 0)
			{
				return false;
			}

			/**
			 * Данные валют
			 */
			$rates_data = array();

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

					$rates_data[] = array
					(
						'group_code' => (string)$xml_group_attributes['Code'],
						'group_description' => (string)$xml_group_attributes['Description'],
						'currency_label' => (string)$xml_group_item_attributes['Label'],
						'currency_alias' => (string)$xml_group_item_attributes['Alias'],
						'currency_name' => (string)$xml_group_item_attributes['Name'],
						'rate_inc_sum' => (string)$xml_group_item_rate_attributes['IncSum'],
						'language' => $language,
					);
				}
			}

			return $rates_data;
		}

		return false;
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
			'timeout' => 10,
			'body' => ''
		);

		/**
		 * Request execute
		 */
		$response = wp_remote_post($this->get_base_api_url() . '/GetLimit?MerchantLogin=' . $merchantLogin, $args);

		/**
		 * Response get
		 */
		$response_body = wp_remote_retrieve_body($response);

		/**
		 * Response is very good
		 */
		if($response_body != '')
		{
			/**
			 * Response normalize
			 */
			$response_data = new SimpleXMLElement($response_body);

			/**
			 * Check error
			 *
			 * @todo refactoring
			 */
			if($response_data->Result->Code != 0)
			{
				return false;
			}

			return $response_data->Limit;
		}

		return false;
	}
}