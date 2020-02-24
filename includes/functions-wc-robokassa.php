<?php
/**
 * Run
 *
 * @since 2.5.0
 */
function wc_robokassa_run()
{
	if(is_callable('WC_Robokassa::instance'))
	{
		WC_Robokassa::instance();
	}
}