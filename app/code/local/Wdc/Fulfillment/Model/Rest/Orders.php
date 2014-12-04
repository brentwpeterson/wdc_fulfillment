<?php

class Wdc_Fulfillment_Model_Rest_Orders extends Wdc_Fulfillment_Model_Rest_Client //Mage_Core_Model_Abstract //
{
	
		
	public function sendXMLAPI($orderId)
	{
		$result = array();
		$result['success'] = false;
		$result['error'] = true;
		$result['error_messages'] = '';
		
		$order = Mage::getModel('sales/order')->load($orderId);	
		
		
		$billing = $order->getBillingAddress()->getData();
		$shipping = $order->getShippingAddress()->getData();
		$products = $order->getItemsCollection()->toArray();
//		$quote = Mage::getModel('checkout/session')->getQuote();
		
		$payment = $order->getPayment();
		
		//use this for debugging and resubmitting only
		//if($ccNumber = Mage::getModel('fulfillment/rest_orders')->getTempCC($orderId)){
			//echo $cc;
		//}
		//else{
			$ccNumber = $payment->decrypt($payment->getCcNumberEnc());
		//}
				
		$cctype = $payment->getCcType();
		$ccExpMonth = $payment->getCcExpMonth();
		$ccExpYear = $payment->getCcExpYear();
		//print_r($shipping);
		
		//authentication data
		$user =  Mage::getStoreConfig('fulfillment/apigeneral/serveruser'); //'<<Site Level User Name>>';//production		
		$pwd = Mage::getStoreConfig('fulfillment/apigeneral/serverpass'); //'<<Site Level Password>>';//production
		$olcc_user = Mage::getStoreConfig('fulfillment/apigeneral/apiuser'); //'<<API Level User Name>>';
		$olcc_pwd = Mage::getStoreConfig('fulfillment/apigeneral/apipass'); //'<<API Level Password>>';
		$token = Mage::getStoreConfig('fulfillment/apigeneral/xmltoken'); //'<<Authentication Token>>';
		
		// Static fields for Every Order
		$dnis = Mage::getStoreConfig('fulfillment/apigeneral/apidnis'); //'<<Campaign Assigned DNIS>>';
		$emp_number = Mage::getStoreConfig('fulfillment/apigeneral/apicode'); //'<<Campaign Assigned Emplopyee Code>>';

		// URL
		if(Mage::getStoreConfig('fulfillment/apigeneral/use_production_api') == 1){
			$url = Mage::getStoreConfig('fulfillment/apigeneral/apiaddress');
		}
		else{
			$url = Mage::getStoreConfig('fulfillment/apigeneral/apiaddress_dev');
		}
		//$url = Mage::getStoreConfig('fulfillment/apigeneral/apiaddress');
		//$url = 'https://a2bf.orderlogix.com/training/dotnetoms/orderapi/olcc_api_v3.asmx/ReceiveOrder';
		
		
		$inputs = array();
		$inputs['customer_number'] = 'PINK01-'.$order->increment_id;
		$inputs['bill_first_name'] = $billing['firstname'];
		$inputs['bill_last_name'] = $billing['lastname'];
		$inputs['bill_address1'] = $billing['street'];
		$inputs['bill_city'] = $billing['city'];
		$inputs['bill_state'] = $this->getStateCode($billing['region_id']);
		$inputs['bill_zipcode'] = $billing['postcode'];
		$inputs['country'] = $this->getLongCountry($billing['country_id']);
		$inputs['bill_phone_number'] = $billing['telephone'];
		$inputs['email'] = $order->customer_email;
		$inputs['ship_to_first_name'] = $shipping['firstname'];
		$inputs['ship_to_last_name'] = $shipping['lastname'];
		$inputs['ship_to_address1'] = $shipping['street'];
		$inputs['ship_to_city'] = $shipping['city'];
		$inputs['ship_to_state'] = $this->getStateCode($shipping['region_id']);
		$inputs['ship_to_zipcode'] = $shipping['postcode'];
		$inputs['scountry'] = $this->getLongCountry($shipping['country_id']);
		$inputs['ship_to_phone'] = $shipping['telephone'];
		$inputs['order_date'] = Mage::getModel('core/date')->date('Y-m-d');
		//$inputs['order_date'] = date("m/d/y",  strtotime($order->created_at) ); <-- This procuded a GMT date/time
		$inputs['order_number'] = 'PINK01-'.$order->increment_id;
		$inputs['dnis'] = $dnis;
		$inputs['emp_number'] = $emp_number;
		$inputs['use_prices'] = 'N';
		$inputs['use_shipping'] = 'N';
		$inputs['shipping'] = $order->getShippingAmount();
		$inputs['order_state_sales_tax'] = $order->getTaxAmount();

		if( !empty($ccNumber)) {
			$inputs['cc_type'] = $this->convertCCtype($cctype);
			$inputs['cc_number'] = $ccNumber;
			$inputs['exp_date'] = str_pad($ccExpMonth, 2, '0', STR_PAD_LEFT).'/'.substr($ccExpYear, 2);
			$inputs['cvv_code'] = ''; //$data['cc_cid'];
			$inputs['payment_method'] = 'CC';

			if($data['method'] == 'authorizenet') {
				$inputs['payment_type'] = 'AUTH';
				$inputs['merchant_transaction_id'] = print_r($order->getPayment()->last_trans_id, true); 
				$inputs['amount_already_paid'] = print_r($order->getPayment()->amount_authorized, true);
			}
		} else {
			$inputs['ACH_ACCOUNT_NUMBER'] = '000000';
			$inputs['ACH_BANK_NAME'] = 'NA';
			$inputs['ACH_ROUTING_NUMBER'] = '0000';
			$inputs['payment_method'] = 'CK';
		}
		$inputs['shipping_method']= Mage::getModel('fulfillment/shipment')->shippingCodes($order->getShippingDescription());

		$n = 1;
		foreach($products['items'] as $product) {
			if($product['product_type'] == 'simple'){
				$nString = str_pad($n, 2, '0', STR_PAD_LEFT);
				$inputs['product'.$nString] = $product['sku'];
				$inputs['quantity'.$nString] = $product['qty_ordered'];
				$inputs['price'.$nString] = number_format($product['price'], 2);
				++$n;
			}
		}
		
		//$inputs['use_prices'] = 'N';
		//Build the XML doc
		//<xml version="1.0" encoding="utf-8">
		$xml = '<OrderImport><Order>';
		foreach($inputs as $key => $val) {
			$xml .= "<".strtoupper($key).">{$val}</".strtoupper($key).">";
		}
		$xml .= '</Order></OrderImport>';
			
		if(Mage::getStoreConfig('fulfillment/email_contact/use_email') == 1){			
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";			
			$headers .= 'From: API ORDER <support@a2bf.com>' . "\r\n";			
			mail(Mage::getStoreConfig('fulfillment/email_contact/emailaddress'), 'ORDER FOR PINK', $xml, $headers);
		}
		
		$inputArray['user'] = $olcc_user;
		$inputArray['pwd'] = $olcc_pwd;
		$inputArray['token'] = $token;
		$inputArray['inXMLDoc'] = $xml;
		
		$message = $this->processClient($url, 'POST', 'text/html', $inputArray, $user, $pwd)->execute();
		
		return $message;

	}
	
	public function createCSV($orderId)
	{
		$order = Mage::getModel('sales/order')->load($orderId);				
		$billing = $order->getBillingAddress()->getData();
		$shipping = $order->getShippingAddress()->getData();
		$products = $order->getItemsCollection()->toArray();		
		$payment = $order->getPayment();	
		$ccNumber = $payment->decrypt($payment->getCcNumberEnc());
				
		$cctype = $payment->getCcType();
		$ccExpMonth = $payment->getCcExpMonth();
		$ccExpYear = $payment->getCcExpYear();
		//print_r($shipping);
		
		//authentication data
		$user =  Mage::getStoreConfig('fulfillment/apigeneral/serveruser'); //'<<Site Level User Name>>';//production		
		$pwd = Mage::getStoreConfig('fulfillment/apigeneral/serverpass'); //'<<Site Level Password>>';//production
		$olcc_user = Mage::getStoreConfig('fulfillment/apigeneral/apiuser'); //'<<API Level User Name>>';
		$olcc_pwd = Mage::getStoreConfig('fulfillment/apigeneral/apipass'); //'<<API Level Password>>';
		$token = Mage::getStoreConfig('fulfillment/apigeneral/xmltoken'); //'<<Authentication Token>>';
		
		// Static fields for Every Order
		$dnis = Mage::getStoreConfig('fulfillment/apigeneral/apidnis'); //'<<Campaign Assigned DNIS>>';
		$emp_number = Mage::getStoreConfig('fulfillment/apigeneral/apicode'); //'<<Campaign Assigned Emplopyee Code>>';
		
		$inputs = array();
		$inputs['customer_number'] = $order->increment_id;
		$inputs['bill_first_name'] = $billing['firstname'];
		$inputs['bill_last_name'] = $billing['lastname'];
		$inputs['bill_address1'] = $billing['street'];
		$inputs['bill_city'] = $billing['city'];
		$inputs['bill_state'] = $this->getStateCode($billing['region_id']);
		$inputs['bill_zipcode'] = $billing['postcode'];
		$inputs['country'] = $this->getLongCountry($billing['country_id']);
		$inputs['bill_phone_number'] = $billing['telephone'];
		$inputs['email'] = $order->customer_email;
		$inputs['ship_to_first_name'] = $shipping['firstname'];
		$inputs['ship_to_last_name'] = $shipping['lastname'];
		$inputs['ship_to_address1'] = $shipping['street'];
		$inputs['ship_to_city'] = $shipping['city'];
		$inputs['ship_to_state'] = $this->getStateCode($shipping['region_id']);
		$inputs['ship_to_zipcode'] = $shipping['postcode'];
		$inputs['scountry'] = $this->getLongCountry($shipping['country_id']);
		$inputs['ship_to_phone'] = $shipping['telephone'];
		$inputs['order_date'] = Mage::getModel('core/date')->date('Y-m-d');
		//$inputs['order_date'] = date("m/d/y",  strtotime($order->created_at) ); <-- This procuded a GMT date/time
		$inputs['order_number'] = $order->increment_id;
		$inputs['dnis'] = $dnis;
		$inputs['emp_number'] = $emp_number;
		$inputs['use_prices'] = 'N';
		$inputs['use_shipping'] = 'N';
		$inputs['shipping'] = $order->getShippingAmount();
		$inputs['order_state_sales_tax'] = $order->getTaxAmount();

		if( !empty($ccNumber)) {
			$inputs['cc_type'] = $this->convertCCtype($cctype);
			$inputs['cc_number'] = $ccNumber;
			$inputs['exp_date'] = str_pad($ccExpMonth, 2, '0', STR_PAD_LEFT).'/'.substr($ccExpYear, 2);
			$inputs['cvv_code'] = ''; //$data['cc_cid'];
			$inputs['payment_method'] = 'CC';

			if($data['method'] == 'authorizenet') {
				$inputs['payment_type'] = 'AUTH';
				$inputs['merchant_transaction_id'] = print_r($order->getPayment()->last_trans_id, true); 
				$inputs['amount_already_paid'] = print_r($order->getPayment()->amount_authorized, true);
			}
		} else {
			$inputs['ACH_ACCOUNT_NUMBER'] = '000000';
			$inputs['ACH_BANK_NAME'] = 'NA';
			$inputs['ACH_ROUTING_NUMBER'] = '0000';
			$inputs['payment_method'] = 'CK';
		}
		$inputs['shipping_method']= Mage::getModel('fulfillment/shipment')->shippingCodes($order->getShippingDescription());

		$n = 1;
		foreach($products['items'] as $product) {
			if($product['product_type'] == 'simple'){
				$nString = str_pad($n, 2, '0', STR_PAD_LEFT);
				$inputs['product'.$nString] = $product['sku'];
				$inputs['quantity'.$nString] = $product['qty_ordered'];
				$inputs['price'.$nString] = number_format($product['price'], 2);
				++$n;
			}
		}
		
		$csv = '';

		foreach($inputs as $key => $val) {
			$csv .= '"' . $val . '",';
		}

		return $csv;
	}

    public function createXML($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $billing = $order->getBillingAddress()->getData();
        $shipping = $order->getShippingAddress()->getData();
        $products = $order->getItemsCollection()->toArray();
        $payment = $order->getPayment();
        $ccNumber = $payment->decrypt($payment->getCcNumberEnc());

        $cctype = $payment->getCcType();
        $ccExpMonth = $payment->getCcExpMonth();
        $ccExpYear = $payment->getCcExpYear();
        //print_r($shipping);

        //authentication data
        $user =  Mage::getStoreConfig('fulfillment/apigeneral/serveruser'); //'<<Site Level User Name>>';//production
        $pwd = Mage::getStoreConfig('fulfillment/apigeneral/serverpass'); //'<<Site Level Password>>';//production
        $olcc_user = Mage::getStoreConfig('fulfillment/apigeneral/apiuser'); //'<<API Level User Name>>';
        $olcc_pwd = Mage::getStoreConfig('fulfillment/apigeneral/apipass'); //'<<API Level Password>>';
        $token = Mage::getStoreConfig('fulfillment/apigeneral/xmltoken'); //'<<Authentication Token>>';

        // Static fields for Every Order
        $dnis = Mage::getStoreConfig('fulfillment/apigeneral/apidnis'); //'<<Campaign Assigned DNIS>>';
        $emp_number = Mage::getStoreConfig('fulfillment/apigeneral/apicode'); //'<<Campaign Assigned Emplopyee Code>>';


        $inputs = array();
        $inputs['OrderID'] = $order->increment_id;
        $inputs['StoreID'] = $order->getStoreid();
        $inputs['Created_At'] = $order->getCreatedAt();
        $inputs['customer_id'] = $order->getCustomerId();
       // $inputs['Company'] = $billing['company'];
        $inputs['FirstName'] = $billing['firstname'];
        $inputs['LastName'] = $billing['lastname'];
        $inputs['Email'] = $order->customer_email;
        $inputs['Ship_Street'] = $shipping['street'];
        $inputs['Ship_City'] = $shipping['city'];
        $inputs['Ship_Region'] = $this->getStateCode($shipping['region_id']);
        $inputs['Ship_PostCode'] = $shipping['postcode'];
        $inputs['Ship_Telephone'] = $shipping['telephone'];
        $inputs['Ship_Fax'] = '';
        $inputs['Bill_Street'] = $billing['street'];
        $inputs['Bill_City'] = $billing['city'];
        $inputs['Bill_Region'] = $this->getStateCode($billing['region_id']);
        $inputs['Bill_PostCode'] = $billing['postcode'];
        $inputs['country'] = $this->getLongCountry($billing['country_id']);
        $inputs['Bill_Telephone'] = $billing['telephone'];
        $inputs['Bill_Fax'] = '';
        $inputs['scountry'] = $this->getLongCountry($shipping['country_id']);
        $inputs['TaxAmount'] = $order->getTaxAmount();
        $inputs['ShippingAmount'] = $order->getShippingAmount();
        $inputs['DiscountAmount'] = '';
        $inputs['SubTotal'] = '';
        $inputs['GrandTotal'] = '';

        if( !empty($ccNumber)) {
            $inputs['cc_type'] = $this->convertCCtype($cctype);
            $inputs['cc_number'] = $ccNumber;
            $inputs['exp_date'] = str_pad($ccExpMonth, 2, '0', STR_PAD_LEFT).'/'.substr($ccExpYear, 2);
            $inputs['cvv_code'] = ''; //$data['cc_cid'];
            $inputs['payment_method'] = 'CC';

        } else {
            $inputs['Payment_Method'] = 'purchaseorder';
            $inputs['PO_Number'] = '';

        }
        $inputs['shipping_method']= Mage::getModel('fulfillment/shipment')->shippingCodes($order->getShippingDescription());


        $detailinputs = array();
        $n = 1;
        foreach($products['items'] as $product) {

            if($product['product_type'] == 'simple'){
                $nString = str_pad($n, 2, '0', STR_PAD_LEFT);
                $detailinputs['product'.$nString] = $product['sku'];
                $detailinputs['quantity'.$nString] = $product['qty_ordered'];
                $detailinputs['price'.$nString] = number_format($product['price'], 2);
                ++$n;
            }
        }

        $xml = '<BO><Documents><row>';

        foreach($inputs as $key => $val) {
            $xml .= "<".strtoupper($key).">{$val}</".strtoupper($key).">";
        }
        $xml .= '</row></Documents><Document_Lines>';

        foreach($detailinputs as $key => $val) {
            $xml .= "<".strtoupper($key).">{$val}</".strtoupper($key).">";
        }

        $xml .= '</Document_Lines></BO>';

        return $xml;
    }

    protected function convertCCtype($type)
	{
		switch($type){
			case 'VI':
				return 'V';
				break;
			case 'MC':
				return 'M';
				break;
			case 'DI':
				return 'D';
				break;
			case 'AX':
				return 'A';
				break;
			default:
				return $type;
				break;
		}
	}
		
	protected function getLongCountry($code) {
		
		if($code == "US"){
			return "USA";
		}
		elseif($code == "CA"){
			return "CAN";
		}
		else{
			return "USA";
		}
		
	}
	
	protected function getStateCode($regionId){
		return  Mage::getModel('directory/region')->load($regionId)->getCode(); 		
	}
	
	protected function getShortState($state) {

		$state = ucwords(strtolower(trim($state)));
		$abbreviation = $state;
		$states = array("AL"=>"Alabama","AK"=>"Alaska","AZ"=>"Arizona","AR"=>"Arkansas","CA"=>"California","CT"=>"Connecticut","DE"=>"Delaware","DC"=>"District Of Columbia","FL"=>"Florida","GA"=>"Georgia","HI"=>"Hawaii","ID"=>"Idaho","IL"=>"Illinois","IN"=>"Indiana","IA"=>"Iowa","KS"=>"Kansas","KY"=>"Kentucky","LA"=>"Louisiana","ME"=>"Maine","MD"=>"Maryland","MA"=>"Massachusetts","MI"=>"Michigan","MN"=>"Minnesota","MS"=>"Mississippi","MO"=>"Missouri","MT"=>"Montana","NE"=>"Nebraska","NV"=>"Nevada","NH"=>"New Hampshire","NM"=>"New Mexico","NJ"=>"New Jersey","NY"=>"New York","NC"=>"North Carolina","ND"=>"North Dakota","OH"=>"Ohio","OK"=>"Oklahoma","OR"=>"Oregon","PA"=>"Pennsylvania","RI"=>"Rhode Island","SC"=>"South Carolina","SD"=>"South Dakota","TN"=>"Tennessee","TX"=>"Texas","UT"=>"Utah","VT"=>"Vermont","VA"=>"Virginia","WA"=>"Washington","WV"=>"West Virginia","WI"=>"Wisconsin","WY"=>"Wyoming","AS"=>"American Samoa","FM"=>"Federated States Of Micronesia","GU"=>"Guam","MH"=>"Marshall Islands","MP"=>"Northern Mariana Islands","PW"=>"Palau","PR"=>"Puerto Rico","VI"=>"Virgin Islands","AE"=>"Armed Forces Africa","AA"=>"Armed Forces Americas","AE"=>"Armed Forces Canada","AE"=>"Armed Forces Europe","AE"=>"Armed Forces Middle East","AP"=>"Armed Forces Pacific");

		if(strlen($state) > 2) {
			foreach($states as $key => $value) {
				if($state == $value) {
					$abbreviation = $key;
				}
			}
		}
		return $abbreviation;
	}
	

}