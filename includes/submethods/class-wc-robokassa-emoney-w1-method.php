<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Emoney_W1_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Emoney_W1_Method constructor
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
		$this->id = $this->get_parent_id() . '_emoney_w1';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('W1');

		/**
		 * Admin title
		 */
		$this->title = __('RUR W1', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}