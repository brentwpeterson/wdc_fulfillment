<?php
/**
 * @category   Wdc
 * @package    Wdc_Fulfillment
 * @copyright  Copyright (c) 2011 Wagento Data Consutling, Inc. (http://www.wagento.com)
 * @author     Brent Peterson
 */
class Wdc_Fulfillment_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getFile($filetype, $type, $fileId=0)
	{
		$year = date("Y",time());
		$month = date("m",time());
		$day = date("d",time());
		if($filetype == 'detail'){	
			$file = $fileId . $filetype . $year . '-' . $month . '-' . $day;	
		}
		else{
			$fileId = Mage::getModel('fulfillment/file')->getNextRecordId();
			//Text file for upload.
			$file = $fileId . $filetype . $year . '-' . $month . '-' . $day;
		}
		
		$io = new Varien_Io_File();
        $filepath = Mage::getBaseDir('var');
        $filepath .= "/orders/export/";
        $filename = $filepath.$file . '.' . $type;
		if($io->checkAndCreateFolder($filepath)){			
			$io->cd($filepath);
			$io->streamOpen($filename);
			$io->streamWrite('');
			$io->streamClose();			
		}		
		return array('path'=> $filepath, 'file'=>$file . '.' . $type, 'file_id'=>$fileId);
	}	

	public function listFiles($dir="orders/export"){		
		$fileArray = array();

        $filepath = Mage::getBaseDir('var') .  DS . 'orders/export/';


		Mage::log($filepath, null, 'filelog.log');		
		if ($handle = opendir($filepath)) {
			while (false !== ($file = readdir($handle))) {
				Mage::log($file, null, 'filelog.log');
				if(strlen($file) > 2 && stripos($file, '.zip') != false){
					$fileArray[] = $file;
				}
			}			
			closedir($handle);
		}		
		return $fileArray;
	}
}