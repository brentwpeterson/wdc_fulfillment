<?php
/**
 * This file should only be used for CSV output files. Use an array.
 *
 * @category   Wdc
 * @package    Wdc_Fulfillment
 * @copyright  Copyright (c) 2011 Wagento Data Consutling, Inc. (http://www.wagento.com)
 * @author     Brent Peterson
 */

class Wdc_Fulfillment_Model_Filetype_Csv extends Mage_Core_Model_Abstract
{

    public function createOnelineMin($order)
    {
        $i = 1;
        $inputs = array();

        $inputs['CUSTOMER_NAME'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();

        foreach ($order->getItemsCollection() as $item) {
            //$inputs['ITEMNO'] = $i;
            $inputs['QTY'] = $item->getQtyOrdered();
            $inputs['NAME'] = $item->getName();

            $ar = $item->getProductOptions();
            $j = 1;
            foreach ($ar[attributes_info] as $option) {

                $inputs['option' . $j] = $option[label] . ':' . $option[value];
                $j++;
            }
            $i++;
        }

        return $inputs;
    }


    public function flattenSteet($street)
    {
        $str = '';
        foreach ($street as $line)
        {
            $str .= $line . ' ';
        }
        return $str;
    }


    /**
     * This is method readShipments
     *
     * @param int $col Amount of columns in CSV file
     * @return array Returns tracking number in array
     *
     */
    public function readShipments($col = 2)
    {
        // Set for a two column CSV file
        $tracking = array();
        foreach (Mage::helper('fulfillment')->listFiles() as $file) {
            $filepath = Mage::getBaseDir('var') . DS . 'orders/import/';
            $handle = fopen($filepath . $file, 'r');
            while (($data = fgetcsv($handle, ",")) !== FALSE) {
                $num = count($data);
                //$row++;

                //	Mage::log('number->'.$num.' and col->'.$col, null, 'fulfillment.log');
                if ($num == $col) {
                    $tmp = array();
                    for ($c = 0; $c < $num; $c++) {
                        if ($c == 0) {
                            $tracking[$data[$c]] = $data[$c];
                        }
                        elseif ($c == 1) {
                            $tracking[$data[$c - 1]] = $data[$c];
                        }
                    }
                }
                else {
                    Mage::log('There was an error in the file ' . $file, null, 'fulfillment.log');
                }

            }
        }
        return $tracking;
    }

    public function createCSV($order, $line = 1)
    {
        $billing = $order->getBillingAddress()->getData();
        $shipping = $order->getShippingAddress()->getData();
        $products = $order->getItemsCollection()->toArray();
        $payment = $order->getPayment();
        $ccNumber = $payment->decrypt($payment->getCcNumberEnc());

        $cctype = $payment->getCcType();
        $ccExpMonth = $payment->getCcExpMonth();
        $ccExpYear = $payment->getCcExpYear();
        // Static fields for Every Order
        $dnis = Mage::getStoreConfig('fulfillment/apigeneral/apidnis'); //'<<Campaign Assigned DNIS>>';
        $emp_number = Mage::getStoreConfig('fulfillment/apigeneral/apicode'); //'<<Campaign Assigned Emplopyee Code>>';

        $inputs = array();
        $inputs['RESERVED1'] = ''; //RESERVED 1
        $inputs['customer_number'] = 'PINK01-' . $order->increment_id; //CUSTOMER_NUMBER -2
        $inputs['bill_first_name'] = $billing['firstname']; //BILL_LAST_NAME -3
        $inputs['bill_last_name'] = $billing['lastname']; //BILL_FIRST_NAME -4
        $inputs['bill_company'] = $billing['']; //BILL_COMPANY -5
        $inputs['bill_address1'] = preg_replace('/[\r\n]+/', " ", $billing['street']); //BILL_ADDRESS1 -6
        $inputs['bill_address2'] = ''; //BILL_ADDRESS2 -7
        $inputs['bill_city'] = $billing['city']; //BILL_CITY -8
        $inputs['bill_state'] = $this->getStateCode($billing['region_id']); //BILL_STATE -9
        $inputs['bill_zipcode'] = $billing['postcode']; //BILL_ZIPCODE -10
        $inputs['RESERVED2'] = ''; //RESERVED -11
        //$inputs['country'] = $this->getLongCountry($billing['country_id']); //RESERVED
        $inputs['bill_phone_number'] = $billing['telephone']; //BILL_PHONE_NUMBER -12

        $inputs['ACH_BANK_NAME'] = ''; //ACH_BANK_NAME -13
        $inputs['NO_SOLICITING'] = ''; //NO_SOLICITING -14
        $inputs['RESERVED15'] = ''; //RESERVED -15

        //PAYMENT_TYPE
        //	if( !empty($ccNumber)) {  // This scipt will only have CC

        $inputs['payment_type'] = ''; //16
        $inputs['RESERVED17'] = ''; //RESERVED -17
        $inputs['RESERVED18'] = ''; //RESERVED -18
        $inputs['cc_type'] = $this->convertCCtype($cctype); //PAYMENT_TYPE -19
        //	$inputs['cc_number'] = substr($ccNumber, 12);  //CC_NUMBER -20 Last four
        $inputs['cc_number'] = $ccNumber; //CC_NUMBER -20
        $inputs['exp_date'] = str_pad($ccExpMonth, 2, '0', STR_PAD_LEFT) . '/' . substr($ccExpYear, 2); // -21
        //$inputs['cvv_code'] = ''; //$data['cc_cid'];

        //		} else {
        //			$inputs['ACH_ACCOUNT_NUMBER'] = '000000';
        //			$inputs['ACH_BANK_NAME'] = 'NA';
        //			$inputs['ACH_ROUTING_NUMBER'] = '0000';
        //			$inputs['payment_method'] = 'CK';
        //		}

        $inputs['dnis'] = $dnis; //DNIS 22
        $inputs['KEY_CODE'] = ''; //KEY_CODE 23
        $inputs['emp_number'] = $emp_number; //24
        $inputs['RESERVED25'] = ''; //RESERVED -25
        $inputs['RESERVED26'] = ''; //RESERVED -26
        $inputs['shipping_method'] = Mage::getModel('fulfillment/shipment')->shippingCodes($order->getShippingDescription()); //27
        $inputs['ORDER_ALREADY_FULLFILLED'] = ''; //ORDER_ALREADY_FULLFILLED 28
        $inputs['AMOUNT_ALREADY_PAID'] = ''; //AMOUNT_ALREADY_PAID 29
        $inputs['CONTINUED'] = ''; //CONTINUED 30
        $inputs['order_date'] = Mage::getModel('core/date')->date('Y-m-d'); //31
        //$inputs['order_date'] = date("m/d/y",  strtotime($order->created_at) ); <-- This procuded a GMT date/time
        $inputs['order_number'] = 'PINK01-' . $order->increment_id; // 32

        $price = array();
        $n = 1;
        foreach ($products['items'] as $product) {
            if ($product['product_type'] == 'simple') {
                $nString = str_pad($n, 2, '0', STR_PAD_LEFT);
                $inputs['product' . $nString] = $product['sku'];
                $inputs['quantity' . $nString] = $product['qty_ordered'];
                $price['price' . $nString] = number_format($product['price'], 2);
                ++$n;
            }
        }

        for ($j = $n; $j < 6; $j++) {
            $nString = str_pad($j, 2, '0', STR_PAD_LEFT);
            $inputs['product' . $nString] = '';
            $inputs['quantity' . $nString] = '';
            $price['price' . $nString] = '';
        }

        $inputs['ship_to_last_name'] = $shipping['lastname']; //SHIP_TO_LAST_NAME 43
        $inputs['ship_to_first_name'] = $shipping['firstname']; //SHIP_TO_FIRST_NAME 44

        $inputs['ship_to_company'] = ''; //45
        $inputs['ship_to_address1'] = preg_replace('/[\r\n]+/', " ", $shipping['street']); //46
        $inputs['ship_to_address2'] = preg_replace('/[\r\n]+/', " ", $shipping['street']); //47
        $inputs['ship_to_city'] = $shipping['city']; //48
        $inputs['ship_to_state'] = $this->getStateCode($shipping['region_id']); //49
        $inputs['ship_to_zipcode'] = $shipping['postcode']; //50

        $inputs['ORDER_HOLD_DATE'] = ''; // 51
        $inputs['payment_method'] = 'CC'; //52

        $inputs['ACH_ROUTING_NUMBER'] = ''; //53
        $inputs['ACH_ACCOUNT_NUMBER'] = ''; //54
        $inputs['RESERVED55'] = ''; //RESERVED -55
        //			$inputs['ACH_BANK_NAME'] = 'NA';

        //			$inputs['payment_method'] = 'CK';
        $inputs['use_prices'] = 'N'; //56

        for ($k = 1; $k < 6; $k++) {
            $nString = str_pad($k, 2, '0', STR_PAD_LEFT);
            $inputs['price' . $nString] = $price['price' . $nString];
            $inputs['discount' . $nString] = ''; //.$k;
        }

        $inputs['use_shipping'] = 'N'; //67
        $inputs['shipping'] = $order->getShippingAmount(); //68

        $inputs['email'] = $order->customer_email; //69

        $inputs['RESERVED70'] = ''; //RESERVED -70
        $inputs['country_code'] = $this->getCountryCode($billing['country_id']); //RESERVED -71
        $inputs['RESERVED72'] = ''; //RESERVED -72

        //$inputs['scountry'] = $this->getLongCountry($shipping['country_id']);
        $inputs['bill_to_phone'] = $shipping['telephone']; //73
        $inputs['ship_to_phone'] = $shipping['telephone']; //74

        for ($p = 75; $p < 97; $p++) {
            $inputs['z' . $p] = '';
        }

        $inputs['order_state_sales_tax'] = $order->getTaxAmount(); //97

        for ($p = 98; $p < 108; $p++) {
            $inputs['z' . $p] = '';
        }

        return $inputs;


    }

    protected function convertCCtype($type)
    {
        switch ($type) {
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

    protected function getCountryCode($code)
    {

        if ($code == "US") {
            return "001";
        }
        elseif ($code == "CA") {
            return "034";
        }
        else {
            return "001";
        }
    }

    protected function getLongCountry($code)
    {

        if ($code == "US") {
            return "USA";
        }
        elseif ($code == "CA") {
            return "CAN";
        }
        else {
            return "USA";
        }
    }

    protected function getStateCode($regionId)
    {
        return Mage::getModel('directory/region')->load($regionId)->getCode();
    }
}