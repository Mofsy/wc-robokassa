<?php
/*
  +----------------------------------------------------------+
  | Author: Mofsy <support@mofsy.ru>                         |
  | Author website: https://mofsy.ru                         |
  +----------------------------------------------------------+
*/

class Wc_Robokassa_Terminals_Terminals_Elecsnet_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Terminals_Terminals_Elecsnet_Method constructor
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
		$this->id = $this->get_parent_id() . '_terminals_terminals_elecsnet';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('TerminalsElecsnet');

		/**
		 * Admin title
		 */
		$this->title = __('TerminalsElecsnet', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}