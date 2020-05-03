<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Bankcard_Bank_Card_Samsung_Pay_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Bankcard_Bank_Card_Samsung_Pay_Method constructor
	 */
	public function __construct()
	{
		/**
		 * Main constructor
		 */
		parent::__construct();

		/**
		 * Child method id
		 */
		$this->id = $this->get_parent_id() . '_bank_card_samsung_pay';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('SamsungPay');

		/**
		 * Admin title
		 */
		$this->title = __('Samsung Pay', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}