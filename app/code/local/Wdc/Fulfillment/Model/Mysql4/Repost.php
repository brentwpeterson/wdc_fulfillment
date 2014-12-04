<?php


class Wdc_Fulfillment_Model_Mysql4_Repost extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Initialize resource model
	 */
	public function _construct()
	{
		$this->_init('fulfillment/repost', 'rp_id');
	}	
	
	public function fetchByOrder($orderId)
	{
		$sql = $this->_getReadAdapter()->select()
			->from($this->getMainTable(), array('number'))		
			->where('order_id=?', $orderId)	;		
		
		$val = $this->_getReadAdapter()->fetchAll($sql);
		
		if($val){
			foreach ($val as $number){
				return $number['number'];
			}
		}
		else{
			return false;
		}
	}   
	
}