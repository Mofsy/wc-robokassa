<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Emoney_Wmr_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Emoney_Wmr_Method constructor
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
		$this->id = $this->get_parent_id() . '_emoney_wmr';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('WMR');

		/**
		 * Admin title
		 */
		$this->title = __('WMR', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}