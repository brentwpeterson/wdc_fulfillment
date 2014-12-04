<?php


class Wdc_Fulfillment_Model_Mysql4_Shipment extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Initialize resource model
	 */
	public function _construct()
	{
		$this->_init('fulfillment/shipment', 'ship_id');
	}	
	
	public function checkTrackingExist($orderId, $tracking)
	{
		$sql = $this->_getReadAdapter()->select()
			->from($this->getMainTable(), array('ship_id'))		
			->where('order_id=?', $orderId)
			->where('tracking_number=?', $tracking);
		
		$val = $this->_getReadAdapter()->fetchCol($sql);
		
		if($val){
			return true;
		}
		else{
			return false;
		}
	}   
}