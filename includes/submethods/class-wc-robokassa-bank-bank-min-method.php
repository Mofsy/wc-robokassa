<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Bank_Bank_Min_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Bank_Bank_Min_Method constructor
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
		$this->id = $this->get_parent_id() . '_bank_bank_min';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('BankMIN');

		/**
		 * Admin title
		 */
		$this->title = __('Moscow Industrial Bank', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}