<?php

class Wdc_Fulfillment_Model_Payment_Types extends Mage_Core_Model_Abstract
{
	public function getCCcode($code)
	{
		if( isset($data['cc_type']) ) {
			switch($data['cc_type']) {
				case 'VI':
					$cctype = 'VS'; break;
				case 'MC':
					$cctype = 'MC'; break;
				case 'AE':
					$cctype = 'A'; break;
				case 'DI':
					$cctype = 'D'; break;
				default:
					$cctype = ''; break;
			}
		}
		return $cctype;	
	}
}