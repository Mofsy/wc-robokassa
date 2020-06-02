<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Mobile_Phone_Tele2_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Mobile_Phone_Tele2_Method constructor
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
		$this->id = $this->get_parent_id() . '_mobile_phone_tele2';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('PhoneTele2');

		/**
		 * Admin title
		 */
		$this->title = __('Tele2', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}