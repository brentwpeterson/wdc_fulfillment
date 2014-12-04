<?php

class Wdc_Fulfillment_Model_Inventory extends Mage_Core_Model_Abstract
{
	const C = 'complete';

	public function updateInventory(){

		Mage::log('run inventory', null, "inventory.log");
		$filepath = Mage::getBaseDir('var') . DS . 'orders/import/';
		$xmldata = Mage::getModel('fulfillment/filetype_xml')->readXml($filepath . 'B2C_Invty.xml');

		$count = count($xmldata);
		foreach ($xmldata as $row){
			Mage::log('sku->'.$row->ItemCode.'-qty->'.$row->Quantity, null, 'inv.log');
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$row->ItemCode);
			if ($product){
				try{
					$inventory = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
					$inventory->setQty($row->Quantity);

					//simon@bananacode.com.br - here is my hack
					if($row->Quantity > $this->getMinQty($inventory)){
						$inventory->setData('is_in_stock',1);
					}
					//end of banana code

					$inventory->save();
				}
				catch(exception $e){
					Mage::log($e->getMessage(), null, 'honkinerrror.log');
					throw new Exception("insert name again",0,$e);
				}
			}
		}
		return $count;
	}

	// this function is supposed to get minimum qty for a product wether this is set per product or per store
	protected function getMinQty($stockitem){
		if($stockitem->getData('use_config_min_qty')){
			$return = intval(Mage::getStoreConfig('cataloginventory/item_options/min_qty'));
		}
		else{
			$return = intval($stockitem->getData('min_qty'));
		}
		//Mage::log('getMinQty '.$return, null,'stockwiz.log');
		return $return;
	}

	/**
	 * Get the Quantities shipped for the Order, based on an item-level
	 * This method can also be modified, to have the Partial Shipment functionality in place
	 *
	 * @param $order Mage_Sales_Model_Order
	 * @return array
	 */
	protected function _getItemQtys(Mage_Sales_Model_Order $order)
	{
		$qty = array();
		foreach ($order->getAllItems() as $_eachItem) {
			if ($_eachItem->getParentItemId()){
				$qty[$_eachItem->getParentItemId()] = $_eachItem->getQtyOrdered();
			}
			else
			{
				$qty[$_eachItem->getId()] = $_eachItem->getQtyOrdered();
			}
		}
		return $qty;
	}

	public function getCarrierList()
	{
		if (!$carriers)
			require(dirname(__FILE__).'/Carriers.php');
		return $carriers;
	}

	protected function getCarrier($carrier_no)
	{
		$carriers = $this->getCarrierList();
		$carrier_no = intval($carrier_no);
		return $carriers[$carrier_no];
	}

	/**
	 * update order using xml file sent from SAP
	 * author : atheotsky
	 */
	public function updateOrder()
	{
		$filepath = Mage::getBaseDir('var') . DS . 'orders/import/';
		$xmldata = Mage::getModel('fulfillment/filetype_xml')->readXml($filepath . 'B2C_Status_Upload.xml');

		$count = count($xmldata);
		foreach ($xmldata as $row)
		{
			Mage::log(
				'TransStatus->'.$row->TransStatus
				.',U_CES_WebOrderID->'.$row->U_CES_WebOrderID
				.',CreateDate->'.$row->CreateDate
				.',TrnspCode->'.$row->TrnspCode
				.',TrackNo->'.$row->TrackNo
				, null, 'SAPorderUpdate.log');
			if (constant('self::'.$row->TransStatus->__toString()) == Mage_Sales_Model_Order::STATE_COMPLETE)
			{
				try	{
					$order = Mage::getModel('sales/order')
						->loadByIncrementId(intval($row->U_CES_WebOrderID->__toString()));

					if ($order->getId()
						&& $order->getStatus() != Mage_Sales_Model_Order::STATE_COMPLETE){
						// create invoice
						$order = Mage::getModel("sales/order")->loadByIncrementId($order->getIncrementId());
						try {
							if($order->canInvoice()) {
								//Create invoice with pending status
								$invoiceId = Mage::getModel('sales/order_invoice_api')
									->create($order->getIncrementId(), $this->_getItemQtys($order));

								$invoice = Mage::getModel('sales/order_invoice')
									->loadByIncrementId($invoiceId);

								//set invoice status "paid"
								$invoice->save();
							}
						}catch (Mage_Core_Exception $e) {
							Mage::log($e->getMessage(), null, 'honkinerrror.log');
						}

						// add shipment
						$customerEmailComments = "order No. {$row->U_CES_WebOrderID} has been shipped";
						$carrier = $this->getCarrier($row->TrnspCode->__toString());

						try {
							if($order->canShip()) {
								//Create shipment
								$shipmentid = Mage::getModel('sales/order_shipment_api')
									->create($order->getIncrementId(), $this->_getItemQtys($order), $customerEmailComments);
								//Add tracking information
								$ship = Mage::getModel('sales/order_shipment_api')
									->addTrack($shipmentid, $carrier['code'], $carrier['title'], $row->TrackNo->__toString());        
							}
						}catch (Mage_Core_Exception $e) {
							Mage::log($e->getMessage(), null, 'honkinerrror.log');
						}

						// change order status to ‘Completed’
						$order->setData('state', constant('self::'.$row->TransStatus->__toString()));
						$order->setData('status', constant('self::'.$row->TransStatus->__toString()));
						$history = $order->addStatusHistoryComment('Order marked as complete automatically.', false);
						$history->setIsCustomerNotified(false);

						$order->save();
					}
				}
				catch(exception $e) {
					Mage::log($e->getMessage(), null, 'honkinerrror.log');
				}
			}
		}
	}
}
