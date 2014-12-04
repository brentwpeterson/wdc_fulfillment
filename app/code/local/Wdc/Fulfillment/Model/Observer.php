<?php

class Wdc_Fulfillment_Model_Observer
{
	public function sendAPI($observer){		
		
		$order = $observer->getEvent()->getOrder(); 		
		Mage::getModel('fulfillment/order')->processXMLResponse($order);
	}

    public function addmassbutton($observer) {
        if(get_class($observer->getEvent()->getBlock()) =='Mage_Adminhtml_Block_Widget_Grid_Massaction') {
            if($observer->getEvent()->getBlock()->getRequest()->getControllerName() =='sales_order') {
                $observer->getEvent()->getBlock()->addItem('fulfillment_download', array(
                    'label'=> Mage::helper('sales')->__('Download Selected'),
                    'url'  => Mage::app()->getStore()->getUrl('adminhtml/ftp/sendorders'),
                ));
            }
        }
    }

    public function saveOrderXml($observer){

        $order = $observer->getEvent()->getOrder();
        Mage::getModel('fulfillment/order')->processXMLSave($order);
    }
}
