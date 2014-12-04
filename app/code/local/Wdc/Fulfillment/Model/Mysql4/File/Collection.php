<?php

class Wdc_Fulfillment_Model_Mysql4_File_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('fulfillment/file');
	}
}