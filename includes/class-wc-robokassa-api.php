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
	 * @return mixed
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
			'timeout' => 20,
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

			$currencies_data = array();

			/**
			 * Перебираем данные
			 */
			foreach($response_data->Groups as $xml_group)
			{
				foreach($xml_group['Items'] as $xml_group_item)
				{
					$currencies_data[] = array
					(
						'group_code' => $xml_group['Code'],
						'group_description' => $xml_group['Description'],
					);
				}
			}

			return $response_data->OutSum;
		}

		return false;
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
}