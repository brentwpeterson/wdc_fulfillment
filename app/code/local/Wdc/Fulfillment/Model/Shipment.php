<?php

class Wdc_Fulfillment_Model_Shipment extends Mage_Core_Model_Abstract
{
	public function _construct()
	{		   
		$this->_init('fulfillment/shipment');		
	}	
	
	public function processShipments()
	{
		// currently method only supports two columns
		$data = Mage::getModel('fulfillment/filetype_csv')->readShipments();
		
		foreach ($data as $id => $tracking){
			$order = Mage::getModel('sales/order')->loadByIncrementId($id);
			if($order->getId() != 0 ){
				if(!Mage::getResourceModel('fulfillment/shipment')->checkTrackingExist($order->getId(), $tracking)){
				$this->saveShipment($order->getId(), $tracking, $order->getShippingMethod());	
				$this->addTracking($order->getId(), $tracking, 'custom', $order->getShippingMethod());
			}	
			}	
		}	
	}	
	
	protected function addTracking($orderId, $tracking, $shipTitle='', $shipcode='NA'){
		
		$collection = Mage::getResourceModel('sales/order_shipment_collection');
		$collection->addAttributeToFilter('order_id', $orderId);	
		
		foreach($collection as $_ship) {
			$shipment = Mage::getModel('sales/order_shipment')->load($_ship->getId());		
			if($shipment->getId() != '') { 
				$track = Mage::getModel('sales/order_shipment_track')
					->setShipment($shipment)
					->setData('title', $shipTitle)
					->setData('number', $tracking)
					->setData('carrier_code', $shipcode)
					->setData('order_id', $shipment->getData('order_id'))
					->save();
			}
		}
		
		$order = Mage::getSingleton('sales/order')->load($orderId);
		if($order->getState() != 'complete'){
			$order->setStatus('complete');
			$order->save();
		}
	}
	
	protected function saveShipment($orderId, $tracking, $shipcode='NA')
	{		
		$model = Mage::getModel('fulfillment/shipment');
		$model->setOrderId($orderId);
		$model->setShipperCode($shipcode);	
		$model->setTrackingNumber($tracking);		
		$model->setCreatedAt(date('Y-m-d_H-i-s'));
		$model->save();					
	}
	
	public function shippingCodes($code){
		
		// This will have to be configured be client
			//BAS	UPS Basic	
			//U01	Next Day Air
			//U04	2nd Day Air
			//U06	3 Day Select
			//U07	Ground
			//USPFCM	First Class Mail Intl
			//USPPI	Priority Mail Intl
			//USPPM	Priority Mail
			//LTL	Pallet Shipping
			
		if(strripos($code, "Standard") > 0){
			return "Standard";
		}
		elseif(strripos($code, "Overnight") > 0){
			return "Overnight";
		}
		elseif(strripos($code, "Express") > 0){
			return "Express";
		}
		elseif(strripos($code, "Economy") > 0){
			return "BAS";
		}
		else{
			return "BAS";
		}
	}
}
