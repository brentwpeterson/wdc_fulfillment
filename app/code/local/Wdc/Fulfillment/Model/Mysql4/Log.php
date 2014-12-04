<?php


class Wdc_Fulfillment_Model_Mysql4_Log extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Initialize resource model
	 */
	public function _construct()
	{
		$this->_init('fulfillment/log', 'log_id');
	}	
}