<?php
/**
 * Sub method class
 *
 * @package Mofsy/WC_Robokassa/Submethods
 */
defined('ABSPATH') || exit;

class Wc_Robokassa_Mobile_Phone_Tattelecom_Method extends Wc_Robokassa_Sub_Method
{
	/**
	 * Wc_Robokassa_Mobile_Phone_Tattelecom_Method constructor
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
		$this->id = $this->get_parent_id() . '_mobile_phone_tattelecom';

		/**
		 * Alias currency for child method
		 */
		$this->set_current_currency_alias('PhoneTatTelecom');

		/**
		 * Admin title
		 */
		$this->title = __('Tattelecom', 'wc-robokassa-premium');

		/**
		 * Псевдо конструктор
		 */
		$this->init_child_method();
	}
}