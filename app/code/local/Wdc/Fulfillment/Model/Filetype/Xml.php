<?php

class Wdc_Fulfillment_Model_Filetype_Xml extends Mage_Core_Model_Abstract
{
	public function readXml($file){
		
		if(file_exists($file)) {
			$xml = simplexml_load_file($file);			
			//print_r($xml);
		} else {
			$xml = 'Failed to open xml.';
		}
		return $xml;
	}
	
	public function generateXML($order)
	{				
				
		$i = 1;
		
		$xml = '<OrderImport><Order>';		
						
			$billingAddress = $order->getBillingAddress();		
			
			$xml.= '<CUSTOMER_NUMBER>' . $order->getCustomerId() . '</CUSTOMER_NUMBER>';
			$xml.= '<BILL_COMPANY>' . $billingAddress->getCompany() . '</BILL_COMPANY>';
			$xml.= '<BILL_FIRST_NAME>' . $billingAddress->getFirstname() . '</BILL_FIRST_NAME>';
			$xml.= '<BILL_LAST_NAME>' . $billingAddress->getLastname() . '</BILL_LAST_NAME>';
			$xml.= '<BILL_ADDRESS1>' . $this->flattenSteet($billingAddress->getStreet()) . '</BILL_ADDRESS1>';
			$xml.= '<BILL_ADDRESS2></BILL_ADDRESS2>';
			$xml.= '<BILL_CITY>' . $billingAddress->getCity(). '</BILL_CITY>';
			$xml.= '<BILL_STATE>' .$billingAddress->getRegion(). '</BILL_STATE>';
			$xml.= '<BILL_ZIPCODE>' . $billingAddress->getPostcode() . '</BILL_ZIPCODE>';
			$xml.= '<COUNTRY>' . $billingAddress->getCountryId() . '</COUNTRY>';
			$xml.= '<BILL_PHONE_NUMBER>' . $billingAddress->getTelephone() . '</BILL_PHONE_NUMBER>';
			$xml.= '<BILL_PHONE_2></BILL_PHONE_2>';
			$xml.= '<EMAIL>' . $billingAddress->getEmail() . '</EMAIL>';
			$xml.= '<NO_SOLICITING>0</NO_SOLICITING>';
			$shipAdress = $order->getShippingAddress();
			$xml.= '<SHIP_TO_COMPANY>' . $shipAdress->getCompany() . '</SHIP_TO_COMPANY>';
			$xml.= '<SHIP_TO_FIRST_NAME>' . $shipAdress->getFirstname() . '</SHIP_TO_FIRST_NAME>';
			$xml.= '<SHIP_TO_LAST_NAME>' . $shipAdress->getLastname() . '</SHIP_TO_LAST_NAME>';
			$xml.= '<SHIP_TO_ADDRESS1>' . $this->flattenSteet($shipAdress->getStreet()) . '</SHIP_TO_ADDRESS1>';
			$xml.= '<SHIP_TO_ADDRESS2></SHIP_TO_ADDRESS2>';
			$xml.= '<SHIP_TO_CITY>' . $shipAdress->getCity() . '</SHIP_TO_CITY>';
			$xml.= '<SHIP_TO_STATE>' . $shipAdress->getRegion() . '</SHIP_TO_STATE>';
			$xml.= '<SHIP_TO_ZIPCODE>' . $shipAdress->getPostcode() . '</SHIP_TO_ZIPCODE>';
			$xml.= '<SCOUNTRY>' . $shipAdress->getCountryId() . '</SCOUNTRY>';
			$xml.= '<SHIP_TO_PHONE>' . $shipAdress->getTelephone() . '</SHIP_TO_PHONE>';
			$xml.= '<SHIP_TO_PHONE2></SHIP_TO_PHONE2>';
		$xml.= '<ORDER_DATE>' . $order->getCreatedAt() . '</ORDER_DATE>';
		$xml.= '<ORDER_NUMBER>PINK01-'. $order->getIncrementId() .'</ORDER_NUMBER>';
		$xml.= '<DNIS>PINKMEWEB</DNIS>';
    $xml.= '<KEY_CODE></KEY_CODE>';
		$xml.= '<EMP_NUMBER>PINK01</EMP_NUMBER>';
    $xml.= '<ORDER_HOLD_DATE></ORDER_HOLD_DATE>';
    $xml.= '<VOICE_RECORDING_ID></VOICE_RECORDING_ID>';
    $xml.= '<PAYMENT_TYPE></PAYMENT_TYPE>';
    $xml.= '<AMOUNT_ALREADY_PAID></AMOUNT_ALREADY_PAID>';
    $xml.= '<MERCHANT_TRANSACTION_ID></MERCHANT_TRANSACTION_ID>';
    $xml.= '<PAYMENT_METHOD></PAYMENT_METHOD>';
    $xml.= '<CC_TYPE></CC_TYPE>';
    $xml.= '<CC_NUMBER></CC_NUMBER>';
    $xml.= '<EXP_DATE></EXP_DATE>';
    $xml.= '<CVV_CODE></CVV_CODE>';
    $xml.= '<ACH_BANK_NAME></ACH_BANK_NAME>';
    $xml.= '<ACH_ROUTING_NUMBER></ACH_ROUTING_NUMBER>';
    $xml.= '<ACH_ACCOUNT_NUMBER></ACH_ACCOUNT_NUMBER>';
    $xml.= '<ACH_CHECK_NUMBER></ACH_CHECK_NUMBER>';
    $xml.= '<ACH_IS_SAVINGS_ACCOUNT></ACH_IS_SAVINGS_ACCOUNT>';
    $xml.= '<SHIPPING_METHOD></SHIPPING_METHOD>';
    $xml.= '<ORDER_ALREADY_FULLFILLED></ORDER_ALREADY_FULLFILLED>';
    $xml.= '<TRACKING_NUM></TRACKING_NUM>';
    $xml.= '<SHIPPING_CARRIER></SHIPPING_CARRIER>';
    $xml.= '<SHIPPING_DATE></SHIPPING_DATE>';
    $xml.= '<CLIENT_TRANSACTION_ID></CLIENT_TRANSACTION_ID>';
    $xml.= '<CONTINUED>N</CONTINUED>';
    $xml.= '<CUSTOM_1></CUSTOM_1>';
    $xml.= '<CUSTOM_2></CUSTOM_2>';
    $xml.= '<CUSTOM_3></CUSTOM_3>';
    $xml.= '<CUSTOM_4></CUSTOM_4>';
    $xml.= '<CUSTOM_5></CUSTOM_5>';
    $xml.= '<CUSTOMER_COMMENTS></CUSTOMER_COMMENTS>';
    $xml.= '<CUSTOMER_AGE></CUSTOMER_AGE>';
    $xml.= '<CUSTOMER_DOB></CUSTOMER_DOB>';
    $xml.= '<CUSTOMER_GENDER></CUSTOMER_GENDER>';
    $xml.= '<CUSTOMER_SSN></CUSTOMER_SSN>';
    $xml.= '<PACKING_SLIP_COMMENTS></PACKING_SLIP_COMMENTS>';
    $xml.= '<ORDER_COMMENTS></ORDER_COMMENTS>';
    $xml.= '<LOCATION_CODE></LOCATION_CODE>';
    $xml.= '<USE_PRICES>Y</USE_PRICES>';
		foreach($order->getItemsCollection() as $item){
			$xml.= '<PRODUCT'. $i .'>' . $item->getSku() .'</PRODUCT'. $i .'>';
			$xml.= '<QUANTITY'. $i .'>' . $item->getQtyOrdered() .'</QUANTITY'. $i .'>';
			$xml.= '<PRICE'. $i .'>' . $item->getRowTotal() . '</PRICE'. $i .'>';
			$xml.= '<DISCOUNT'. $i .'>' . $item->getDiscountAmount() . '</DISCOUNT'. $i .'>';
			$xml.= '<COUPON_CODE'. $i .'>'. $order->getCouponCode() . '</COUPON_CODE'. $i .'>';			
			$i++;
		}
    $xml.= '<USE_SHIPPING>Y</USE_SHIPPING>';
		$xml.= '<SHIPPING>'. $order->getShippingAmount() .'</SHIPPING>';
    $xml.= '<ORDER_STATE_SALES_TAX>'. $order->getTaxAmount() .'</ORDER_STATE_SALES_TAX>';
    $xml.= '<ORDER_FEDERAL_SALES_TAX>0.00</ORDER_FEDERAL_SALES_TAX>';
			
		$xml.='</Order></OrderImport>';
				
		return $xml;		
		
	}
	
	public function flattenSteet($street){
		$str = '';
		foreach($street as $line)
			{
			$str.= $line . ' ';	
			}
		return $str;
	}
}