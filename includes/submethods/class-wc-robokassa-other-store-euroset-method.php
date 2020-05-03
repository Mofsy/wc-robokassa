<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Other_Store_Euroset_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Other_Store_Euroset_Method constructor
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
		$this->id = $this->get_parent_id() . '_other_store_euroset';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('StoreEuroset');

		/**
		 * Admin title
		 */
		$this->title = __('Euroset', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}