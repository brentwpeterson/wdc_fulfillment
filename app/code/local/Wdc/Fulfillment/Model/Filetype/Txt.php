<?php

class Wdc_Fulfillment_Model_Filetype_Txt extends Mage_Core_Model_Abstract
{
	
	
	public function createOnelineAB1($order)
	{	
		$po = 'PO Number';
		$rm = 'Remarks';
		$uom = 'Ea';
		$lid = 'LotId';
		$at = 'ADDRESSTYPE';
			
		$lb = " \n";
		$dl = "|";
		
		$csv = '';
		$csv.= '"' . $order->getIncrementId() . '"'.$dl;
		$csv.= '"' . $po . '"'.$dl;
		$csv.= '"' . $rm . '"'.$dl;
		
		if($order->getAllItems()){
			$i = 1;
			foreach ($order->getAllItems() as $item){
				$csv.= '"' . $i . '"'.$dl;
				$csv.= '"' . $item->getQtyOrdered() . '"'.$dl;
				$csv.= '"' . $uom . '"'.$dl;
				$csv.= '"' . $item->getSku . '"'.$dl;
				$csv.= '"' . $lid . '"'.$dl;			
			}
		}
		
		$csv.= '"' . $order->getShippingDescription() . '"'.$dl;
		$csv.= '"' . $at . '"'.$dl;
		
		$shipAdress = $order->getShippingAddress();
		
		$csv.= '"' . $shipAdress->getCompany() . '"'.$dl;
		$csv.= '"' . $shipAdress->getFirstname() . ' ' . $shipAdress->getLastname() . '"'.$dl;
		
		$csv.= '"' . $this->flattenSteet($shipAdress->getStreet()) . '"'.$dl;
		
		$csv.= '"' . $shipAdress->getCity() . '"'.$dl;
		$csv.= '"' . $shipAdress->getRegion() . '"'.$dl;
		$csv.= '"' . $shipAdress->getPostcode() . '"'.$dl;
		$csv.= '"' . $shipAdress->getCountryId() . '"'.$dl;
		
		
		$csv.= '"' . $order->getGrandTotal() . '"'.$dl;
		$csv.= '"' . $order->getTaxAmount() . '"'.$dl;
		$csv.= '"' . $order->getShippingAmount() . '"'.$dl;
		$csv.= '"' . $order->getSubTotal() . '"'.$dl;
		
		$csv.= '"' . $shipAdress->getTelephone() . '"'.$dl;
		$csv.= '"' . $shipAdress->getEmail() . '"';
		$csv.= $lb;
		
		return $csv;		
			
	}
	
	public function flattenSteet($street){
		$str = '';
		foreach ($street as $line)
			{
			$str.= $line . ' ';	
			}
		return $str;
	}
}