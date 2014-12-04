<?php

class Wdc_Fulfillment_Model_Cron extends Mage_Core_Model_Abstract
{
	public function writeorders(){
		$filearray = Mage::getModel('fulfillment/order')->processOrders();			
		if(Mage::getStoreConfig('fulfillment/ftpgeneral/show_ftp') == '1'){
			if($filearray){
				Mage::getModel('fulfillment/ftp')->uploadFile($filearray, false);
			}
		}
	}
	
	public function failedorders()
	{
		Mage::getModel('fulfillment/order')->processFailedOrders();
		Mage::log("Cron ran", null, "fulfillmentcron.log");
	}
	
	public function sendzip(){
		Mage::getModel('fulfillment/order')->createCsvFailedOrders();
		$i=0;

        $filepath = Mage::getBaseDir('var') .  DS . '/orders/export/';

		foreach (Mage::helper('fulfillment')->listFiles() as $file){		
			$filearray = array('path' => $filepath, 'file' => $file);
			Mage::log('Path to get files is '.$filearray['path'].$filearray['file'], null, 'ziplog.log');
			if(Mage::getStoreConfig('fulfillment/ftpgeneral/show_ftp') == '1'){
				if($filearray){
					Mage::getModel('fulfillment/ftp')->uploadFile($filearray);
					$i++;
				}
			}
		}
		Mage::log('There were ' .$i. ' zip file uploaded', null, 'ziplog.log');
	}
}