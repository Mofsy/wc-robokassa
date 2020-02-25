<?php
/**
 * Main instance of WC_Robokassa
 * @since 3.0.0
 *
 * @return WC_Robokassa|false
 */
function WC_Robokassa()
{
	if(is_callable('WC_Robokassa::instance'))
	{
		return WC_Robokassa::instance();
	}

	return false;
}