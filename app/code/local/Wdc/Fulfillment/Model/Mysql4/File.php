<?php


class Wdc_Fulfillment_Model_Mysql4_File extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Initialize resource model
	 */
	public function _construct()
	{
		$this->_init('fulfillment/file', 'file_id');
	}	
}