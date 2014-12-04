<?php
/**
 * @category   Wdc
 * @package    Wdc_Fulfillment
 * @copyright  Copyright (c) 2011 Wagento Data Consutling, Inc. (http://www.wagento.com)
 * @author     Brent Peterson
 */
class Wdc_Fulfillment_Model_Repost extends Mage_Core_Model_Abstract
{
	public function _construct()
	{		   
		$this->_init('fulfillment/repost');		
	}	
}