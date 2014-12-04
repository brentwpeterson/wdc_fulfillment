<?php

class Wdc_Fulfillment_Model_File extends Mage_Core_Model_Abstract
{
	public function _construct()
	{		   
		$this->_init('fulfillment/file');		
	}	
	
	public function getNextRecordId()
	{
		return $this->setCreatedAt(date('Y-m-d_H-i-s'))->save()->getId();
	}
	
	

}