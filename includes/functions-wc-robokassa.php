<?php
/**
 * Main instance of WC_Robokassa
 * @since 2.5.0
 *
 * @return WC_Robokassa|false
 */
function WC_Robokassa()
{
	if(is_callable('Wc1c::instance'))
	{
		return WC_Robokassa::instance();
	}

	return false;
}