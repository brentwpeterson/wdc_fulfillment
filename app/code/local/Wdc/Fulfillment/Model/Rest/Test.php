<?php

class Wdc_Fulfillment_Model_Rest_Orders extends Mage_Core_Model_Abstract //Wdc_Fulfillment_Model_Rest_Client
{
	
	public function test()
	{
		return 'fun';
	}
		
//	public function sendXMLAPI($orderId)
//	{
//		
//		echo "line 8';
//		$result = array();
//		$result['success'] = false;
//		$result['error'] = true;
//		$result['error_messages'] = '';
//		
//		$order = Mage::getModel('sales/order')->load($orderId);		
//		
//		$billing = $order->getBillingAddress()->getData();
//		$shipping = $order->getShippingAddress()->getData();
//		$products = $order->getItemsCollection()->toArray();
////		$quote = Mage::getModel('checkout/session')->getQuote();
//
//		//authentication data
//		$user =  Mage::getStoreConfig('fulfillment/apigeneral/serveruser'); //'<<Site Level User Name>>';//production
//		$pwd = Mage::getStoreConfig('fulfillment/apigeneral/serverpass'); //'<<Site Level Password>>';//production
//		$olcc_user = Mage::getStoreConfig('fulfillment/apigeneral/apiuser'); //'<<API Level User Name>>';
//		$olcc_pwd = Mage::getStoreConfig('fulfillment/apigeneral/apipass'); //'<<API Level Password>>';
//		$token = Mage::getStoreConfig('fulfillment/apigeneral/xmltoken'); //'<<Authentication Token>>';
//		
//		// Static fields for Every Order
//		$dnis = Mage::getStoreConfig('fulfillment/apigeneral/apidnis'); //'<<Campaign Assigned DNIS>>';
//		$emp_number = Mage::getStoreConfig('fulfillment/apigeneral/apicode'); //'<<Campaign Assigned Emplopyee Code>>';
//
//		// URL
//		$url = Mage::getStoreConfig('fulfillment/apigeneral/apiaddress');
//		
//		
//		
//		$inputs = array();
//		$inputs['customer_number'] = 'NVR-'.$order->increment_id;
//		$inputs['bill_first_name'] = $billing['firstname'];
//		$inputs['bill_last_name'] = $billing['lastname'];
//		$inputs['bill_address1'] = $billing['street'];
//		$inputs['bill_city'] = $billing['city'];
//		$inputs['bill_state'] = self::getShortState($billing['region']);
//		$inputs['bill_zipcode'] = $billing['postcode'];
//		$inputs['country'] = 'USA';
//		$inputs['bill_phone_number'] = $billing['telephone'];
//		$inputs['email'] = $order->customer_email;
//		$inputs['ship_to_first_name'] = $shipping['firstname'];
//		$inputs['ship_to_last_name'] = $shipping['lastname'];
//		$inputs['ship_to_address1'] = $shipping['street'];
//		$inputs['ship_to_city'] = $shipping['city'];
//		$inputs['ship_to_state'] = self::getShortState($shipping['region']);
//		$inputs['ship_to_zipcode'] = $shipping['postcode'];
//		$inputs['scountry'] = 'USA';
//		$inputs['ship_to_phone'] = $shipping['telephone'];
//		$inputs['order_date'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
//		//$inputs['order_date'] = date("m/d/y",  strtotime($order->created_at) ); <-- This procuded a GMT date/time
//		$inputs['order_number'] = 'NVR-'.$order->increment_id;
//		$inputs['dnis'] = $dnis;
//		$inputs['emp_number'] = $emp_number;
//		$inputs['use_prices'] = 'Y';
//		$inputs['use_shipping'] = 'Y';
//
//		if( !empty($data['cc_number'])) {
//			$inputs['cc_type'] = $cctype;
//			$inputs['cc_number'] = $data['cc_number'];
//			$inputs['exp_date'] = str_pad($data['cc_exp_month'], 2, '0', STR_PAD_LEFT).'/'.substr($data['cc_exp_year'], 2);
//			$inputs['cvv_code'] = ''; //$data['cc_cid'];
//			$inputs['payment_method'] = 'CC';
//
//			if($data['method'] == 'authorizenet') {
//				$inputs['payment_type'] = 'AUTH';
//				$inputs['merchant_transaction_id'] = print_r($order->getPayment()->last_trans_id, true); 
//				$inputs['amount_already_paid'] = print_r($order->getPayment()->amount_authorized, true);
//			}
//		} else {
//			$inputs['payment_method'] = 'CK';
//		}
//		$inputs['shipping_method']='REG';
//
//		$n = 1;
//		foreach($products['items'] as $product) {
//			$nString = str_pad($n, 2, '0', STR_PAD_LEFT);
//			$inputs['product'.$nString] = $product['name'];
//			$inputs['quantity'.$nString] = $product['qty'];
//			$inputs['price'.$nString] = number_format($product['price'], 2);
//			++$n;
//		}
//		
//		//Build the XML doc
//		$xml = '<?xml version="1.0" encoding="utf-8" ?><OrderImport><Order>';
//		foreach($inputs as $key => $val) {
//			$xml .= "<".strtoupper($key).">{$val}</".strtoupper($key).">";
//		}
//		$xml .= '</Order></OrderImport>';
//		
//		$inputArray['user'] = $olcc_user;
//		$inputArray['pwd'] = $olcc_pwd;
//		$inputArray['token'] = $token;
//		$inputArray['inXMLDoc'] = $xml;
//		$logVar = print_r($xml, true);
//		
//		// send the xml to the log for review
//		Mage::log($logVar, null, 'fulfillment.log');//Mage::log isnt fond of print_r's in the call, supposedly
//
//		$this->processClient($url, 'POST', 'text/html', $inputArray, $user, $pwd)->execute();
//		
//		//Check response for errors
//		if( !empty($xml->DATA->ERROR_DETAIL) ) {
//			$response['error_messages'] = $xml->DATA->ERROR_DETAIL;
//			$messagebody = wordwrap($logVar, 70,"\n",true);
//			mail("<<email address for failures>>","Failed Order from OrderLogix API",$messagebody);
//		} else {
//			$result['success'] = true;//Proven innocent, hooray!
//			$result['error'] = false;
//		}
//		
//		return $result;
//		//$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
//	}
}