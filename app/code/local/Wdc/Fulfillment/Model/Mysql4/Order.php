<?php


class Wdc_Fulfillment_Model_Mysql4_Order extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Initialize resource model
	 */
	public function _construct()
	{
		$this->_init('fulfillment/order', 'fill_id');
	}	
}