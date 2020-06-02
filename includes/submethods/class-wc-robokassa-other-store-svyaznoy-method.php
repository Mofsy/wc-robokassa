<?php
/**
 * Sub method class
 *
 * @package Mofsy/WC_Robokassa/Submethods
 */
defined('ABSPATH') || exit;

class Wc_Robokassa_Other_Store_Svyaznoy_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Other_Store_Svyaznoy_Method constructor
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
		$this->id = $this->get_parent_id() . '_other_store_svyaznoy';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('StoreSvyaznoy');

		/**
		 * Admin title
		 */
		$this->title = __('Svyaznoy', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}