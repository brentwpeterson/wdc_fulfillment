<?php
/**
 * @category   Wdc
 * @package    Wdc_Fulfillment
 * @copyright  Copyright (c) 2011 Wagento Data Consutling, Inc. (http://www.wagento.com)
 * @author     Brent Peterson
 */
class Wdc_Fulfillment_Model_Ftp extends Mage_Core_Model_Abstract
{
	
	public function uploadFile($filearray, $admin=true){
		
		$ftp_server = Mage::getStoreConfig('fulfillment/ftpgeneral/ftpserver');
		$ftp_user = Mage::getStoreConfig('fulfillment/ftpgeneral/ftpuser');
		$ftp_pass = Mage::getStoreConfig('fulfillment/ftpgeneral/ftppass');
		
		
		$file = $filearray['path'].$filearray['file'];
		$remote_file = $filearray['file'];
		Mage::log($remote_file, null, 'ftplog.log');
		// set up a connection or die
		$conn_id = ftp_connect($ftp_server) or die("Couldn't connect to $ftp_server"); 

		if(@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
			
		} else {			
			if($admin){
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Couldn't connect as ". $ftp_user));
				Mage::log("Couldn't connect as ". $ftp_user, null, "fulfillment.log");
			}
		}
			if(ftp_put($conn_id, $remote_file, $file, FTP_BINARY)) {
				if($admin){
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("You have successfully uploaded ". $filearray['file']));			
				}
					Mage::log("You have successfully uploaded ". $filearray['file'], null, "fulfillment.log");
					
			$io = new Varien_Io_File();					
			$filepath = Mage::getBaseDir('var'); 
			$filepath.= "/orders/export/";
			$io->cd($filepath);	
			$io->rm($filearray['file']);
			
				
			} else {
				if($admin){
					
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("There was a problem while uploading ". $file . " to remote file " . $remote_file));								
				}
				
			Mage::log("There was a problem while uploading ". $filearray['file'], null, "fulfillment.log");
			}
		
		// close the connection
		ftp_close($conn_id); 
		
		return;
	}
	
	public function getFile($admin=false, $deletefile=true){
		
		$ftp_server = Mage::getStoreConfig('fulfillment/ftpgeneral/ftpserver');
		$ftp_user = Mage::getStoreConfig('fulfillment/ftpgeneral/ftpuser');
		$ftp_pass = Mage::getStoreConfig('fulfillment/ftpgeneral/ftppass');
		
		$filepath = Mage::getBaseDir('var') .  DS . 'orders/import';
		if(!is_dir($filepath)) {			
			mkdir(Mage::getBaseDir('var') .  DS . 'orders');
			mkdir(Mage::getBaseDir('var') .  DS . 'orders/import');
		}
		// set up a connection or die
		if(!$conn_id = ftp_connect($ftp_server)){
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Couldn't connect to FTP"));
		} //or die("Couldn't connect to $ftp_server"); 

		if(@ftp_login($conn_id, $ftp_user, $ftp_pass)) {			
		} else {			
			if($admin){
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("Couldn't connect as ". $ftp_user));
				
			}
			Mage::log("Couldn't connect as ". $ftp_user, null, "fulfillment.log");
		}
		
		$list = ftp_nlist($conn_id, '/out/');		
		foreach ($list as $file){
			
		//	print_r($file);
			echo $filepath.$file;
			if(ftp_get($conn_id, $filepath.$file, '/out/'.$file, FTP_BINARY)) {
			
				
			if($admin){
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__("You have successfully downloaded ". $file));									
			}
				Mage::log("You have successfully downloaded ". $file, null, "fulfillment.log");
				if($deletefile){
					if(ftp_delete($conn_id, '/out/'.$file)) {
						Mage::log("Just deleted ". $file, null, "fulfillment.log");
					} else {
						Mage::log("ERROR - Could not delete ". $file, null, "fulfillment.log");
					}
				}
		} else {
			if($admin){
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__("There was a problem while downloading ". $file));	
			}
				Mage::log("There was a problem while downloading ". $file, null, "fulfillment.log");
		}
		}
		// close the connection
		ftp_close($conn_id); 
		
		return;
	}
}