<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Bankcard_Bank_Card_Halva_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Bankcard_Bank_Card_Halva_Method constructor
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
		$this->id = $this->get_parent_id() . '_bank_card_halva';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('BankCardHalva');

		/**
		 * Admin title
		 */
		$this->title = __('Card Halva', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}