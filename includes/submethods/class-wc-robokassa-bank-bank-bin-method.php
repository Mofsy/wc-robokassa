<?php
/**
 * Sub method class
 *
 * @package Mofsy/WC_Robokassa/Submethods
 */
defined('ABSPATH') || exit;

class Wc_Robokassa_Bank_Bank_Bin_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Bank_Bank_Bin_Method constructor
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
		$this->id = $this->get_parent_id() . '_bank_bank_bin';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('BankBin');

		/**
		 * Admin title
		 */
		$this->title = __('BINBANK', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}