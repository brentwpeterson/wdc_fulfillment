<?php
/**
 * @category   Wdc
 * @package    Wdc_Fulfillment
 * @copyright  Copyright (c) 2011 Wagento Data Consutling, Inc. (http://www.wagento.com)
 * @author     Brent Peterson
 */
class Wdc_Fulfillment_Model_Order extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('fulfillment/order');
    }


    /**
     * This is method getCompleteOrders
     *
     * @return Array All completed order ID's     *
     */
    public function getCompleteOrders()
    {
        $orders = array();
        if ($this->getCollection()) {
            foreach ($this->getCollection() as $item) {
                $orders[] = $item->getOrderId();
            }
        }
        return $orders;
    }

    public function processOrders()
    {
        $detail = false;

        // Collect all orders
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addAttributeToFilter('status', array(
            'in' => array('Complete', 'Processing'),
        ));

        //check orders against orders that have already been processed
        if (count($this->getCompleteOrders()) > 0) {
            $collection->addFieldToFilter('entity_id', array('nin' => array(
                $this->getCompleteOrders()
            )));
        }

        //Create File Header
        $filename = Mage::helper('fulfillment')->getFile('header', 'csv'); // This will set the type of file
        $io = new Varien_Io_File();
        $io->cd($io->getCleanPath($filename['path']));
        $io->streamOpen($filename['file']);
        // Header of file
        //$io->streamWrite(Mage::getModel('fulfillment/filetype_csv')->writeOnelineAB1header(1));

        $i = 0;
        //Go through list of orders

        foreach ($collection as $order)
        {
            // Save order to table so it won't run again
            $this->saveOrder($order->getId(), $filename['file_id']);
            // Create new line

            $line = Mage::getModel('fulfillment/filetype_csv')->createOnelineMin($order);
            $io->streamWriteCsv($line, ',', '"');
            Mage::log($i . " total test", null, "fulfill.log");

            $i++;
        }
        $io->streamClose();

        // use this section if seperate detail page
        if ($detail) {
            $collection = $this->getCollection();
            $collection->addAttributeToFilter('file_id', $filename['file_id']);

            if (count($collection) > 0) {
                //Create seperate file Detail
                $filename = Mage::helper('fulfillment')->getFile('detail', 'csv', $filename['file_id']);
                $j = 0;
                $io->streamOpen($filename['file']);
                foreach ($collection as $order) // to do get new collection
                {
                    //this file has to be replaced
                    //$io->streamWriteCsv(array(Mage::getModel('fulfillment/filetype_csv')->createDetailFile($filename['file_id'])));
                    $j++;
                }
                $io->streamClose();
            }
            else {
                Mage::log("No orders in your file?", null, "fulfillment.log");
            }
        }


        Mage::log($i . " files loaded for FTP upload", null, "fulfillment.log");

        if ($i == 0) {
            return false;
        }
        else {
            return $filename;
        }
    }

    protected function getMaxItems($collection)
    {
        $highcount = 1;
        foreach ($collection as $order) {
            if ($order->getTotalItemCount() > $highcount) {
                $highcount = $order->getTotalItemCount();
            }

        }
        return $highcount;
    }

    protected function saveOrder($orderId, $fileId)
    {
        $model = Mage::getModel('fulfillment/order');
        $model->setOrderId($orderId);
        $model->setFileId($fileId);
        $model->setOrderType('FTP');
        $model->setUploadedAt(date('Y-m-d_H-i-s'));
        $model->save();
    }

    public function processXMLResponse($order)
    {

        $result = Mage::getModel('fulfillment/rest_orders')->sendXMLAPI($order->getId());
        Mage::log($order->getId(), null, 'event.log');

        $model = Mage::getModel('fulfillment/order');
        $xml = simplexml_load_string($result);
        if (strlen(trim($xml->DATA->ORDER_ID)) > 1) {

            //	Mage::log('log deteail 133-> ' . $xml->DATA->ORDER_ID, null, 'oneoffevent.log');
            $model->setOrderId($order->getId())
                ->setOrderType('API')
                ->setResponse($result)
                ->setFileId($xml->DATA->ORDER_ID)
                ->setStatus(1)
                ->save();

            $this->closeAllStatus($order->getId());

            $order->getPayment()->setCcNumberEnc(0)->save();
            Mage::log('Order-> ' . $order->getIncrementId() . ' complete-Card destroyed', null, 'fulfillment.log');

            //send email on good order
            if (Mage::getStoreConfig('fulfillment/email_contact/use_email') == 1) {
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $headers .= 'From: API ORDER <support@a2bf.com>' . "\r\n";
                mail(Mage::getStoreConfig('fulfillment/email_contact/emailaddress'), 'New API order', 'Order ' . $order->getIncrementId() . ' was successful', $headers);
            }
        }
        elseif (strpos($xml->DATA->ERROR_DETAIL, 'DUPLICATE') === false) {
            $model->setOrderId($order->getId())
                ->setOrderType('API')
                ->setFileId(0)
                ->setResponse($result);
            $model->save();

            if ($this->checkAttemps($order->getId())) {
                $this->closeAllStatus($order->getId());
            }

            //send email on failed order
            if (Mage::getStoreConfig('fulfillment/email_contact/use_email') == 1) {
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $headers .= 'From: API ORDER <support@a2bf.com>' . "\r\n";
                mail(Mage::getStoreConfig('fulfillment/email_contact/emailaddress'), 'ERROR - Failed order', 'Order ' . $order->getIncrementId() . ' FAILED', $headers);
            }

            Mage::log('FAILER Order-> ' . $order->getIncrementId() . ' FAIL', null, 'fulfillment.log');
            ///	Mage::log('log deteail 168-> ' . $xml->DATA->ORDER_ID, null, 'oneoffevent.log');

        }
        else {
            $model->setOrderId($order->getId())
                ->setOrderType('API')
                ->setFileId(0)
                ->setResponse($result)
                ->setStatus(1);
            $model->save();

            $this->closeAllStatus($order->getId());
            $order->getPayment()->setCcNumberEnc(0)->save();
            Mage::log('Order-> ' . $order->getIncrementId() . ' DUPLICATE-Card destroyed', null, 'fulfillment.log');
        }

    }

    public function processXMLSave($order)
    {
        //create XML for order
       //Check if order id in order table already
        $file = $this->saveXML($order->getId());

        //Add save info here
        $io = new Varien_Io_File();
        $filepath = Mage::getBaseDir('var');
        $filepath .= "/orders/export/";
        if ($io->checkAndCreateFolder($filepath)) {
            $io->cd($filepath);
            $io->streamOpen('order-' . $order->increment_id . '.xml');
        }

        $io->streamWrite($file, ',', '"');
        $io->streamClose();

       //add to order table
        $model = Mage::getModel('fulfillment/order');

            $model->setOrderId($order->getId())
                ->setOrderType('API')
                ->setFileId(0)
                ->setResponse('Order' . $order->increment_id . ' Complete')
                ->setStatus(1);
            $model->save();

    }

    public function saveXML($orderId)
    {

        $order = Mage::getModel('sales/order')->load($orderId);
        $billing = $order->getBillingAddress()->getData();
        $products = $order->getItemsCollection();

       // Mage::log($order->getTransactionId(), null, 'xorder.log');

        if($order->getIsVirtual()){

            $shipName = $billing['firstname'] . ' ' . $billing['lastname'];
            $shiptoaddress1 = $billing['street'];
            $shiptocity = $billing['city'];
            $shiptostate = $this->getStateCode($billing['region_id']);
            $shipzip = $billing['postcode'];
            $shipcountry = $this->getLongCountry($billing['country_id']);
            $shiptophone = $billing['telephone'];
        }
        else{
            $shipping = $order->getShippingAddress()->getData();
            $shipName = $shipping['firstname']  . ' ' . $shipping['lastname'];
            $shiptoaddress1  = $shipping['street'];
            $shiptocity = $shipping['city'];
            $shiptostate = $this->getStateCode($shipping['region_id']);
            $shipzip = $shipping['postcode'];
            $shipcountry = $this->getLongCountry($shipping['country_id']);
            $shiptophone = $shipping['telephone'];

        }

        $inputs = array();

        $inputs['orderid'] = $order->getId();
        $inputs['customer_id'] = $order->getCustomerId();
        $inputs['bill_first_last'] = $billing['firstname'] . ' ' . $billing['lastname'];
        $inputs['bill_address1'] = $billing['street'];
        $inputs['bill_city'] = $billing['city'];
        $inputs['bill_state'] = Mage::getModel('directory/region')->load(intval($billing['region_id']))->getCode();
        $inputs['bill_zipcode'] = $billing['postcode'];
        $inputs['country'] = $this->getLongCountry($billing['country_id']);
        $inputs['bill_phone_number'] = $billing['telephone'];
        $inputs['email'] = $order->getCustomerEmail();

        $inputs['ship_to_first_last'] = $shipName;
        $inputs['ship_to_address1'] = $shiptoaddress1;
        $inputs['ship_to_city'] = $shiptocity;
        $inputs['ship_to_state'] = Mage::getModel('directory/region')->load(intval($shipping['region_id']))->getCode();
        $inputs['ship_to_zipcode'] = $shipzip;
        $inputs['scountry'] = $shipcountry;
        $inputs['ship_to_phone'] = $shiptophone;
        $inputs['order_date'] = Mage::getModel('core/date')->date('Y-m-d');
        //$inputs['order_date'] = date("m/d/y",  strtotime($order->created_at) ); <-- This procuded a GMT date/time
        $inputs['order_number'] = $order->increment_id;
        $inputs['shipping'] = $order->getShippingAmount();
        $inputs['order_state_sales_tax'] = $order->getTaxAmount();
        $inputs['order_discount'] = $order->getDiscountAmount();

        $inputs['payment_method'] = $order->getPayment()->getTitle();

//	foreach ($order->getPaymentCollection() as $pay){  //->getPayment()->getAdditionalInformation() as $pay){
		#iforeach ($pay as $sub){
//			Mage::log($pay, null, 'superstuf.log');
		#i}#
//	}    
	Mage::log($order->getPayment()->getId(), null, 'pay.log');	
    

        $inputs['card_name'] = $order->getPayment()->getCcOwner();
        $inputs['cc_last4'] = $order->getPayment()->getCcLast4();
        $inputs['cc_tran_id'] = $order->getPayment()->getCcTransId();
        $inputs['cc_auth'] = $order->getPayment()->getTransactionId();

        $inputs['grant_total'] = $order->getGrandTotal();
        $inputs['po_number'] = $order->getPayment()->getPoNumber();
        $inputs['shipping_method']= Mage::getModel('fulfillment/shipment')->shippingCodes($order->getShippingDescription());

        $subxml = '';
        $n = 1;
//        foreach($products['items'] as $product) {
//
//            if($product['product_type'] == 'simple'){
//                $subxml .= '<row>';
//              //  $nString = str_pad($n, 2, '0', STR_PAD_LEFT);
//                $subxml .= '<line_no>' . $n . '</line_no>';
//                $subxml .= '<order_increment_id>' .  $order->getIncrementId() . '</order_increment_id>';
//                $subxml .= '<productsku>' .  $product['sku'] . '</productsku>';
//                $subxml .= '<quantity>' .  $product['qty_ordered'] . '</quantity>';
//                $subxml .= '<price>' . number_format($product['price'], 2) . '</price>';
//                $subxml .= '</row>';
//                ++$n;
//            }
//        }

        $sku = null;
        foreach ($products as $item){

            if($sku != $item->getSku()){
                $sku = null;
            }

            if($item->getProductType() != 'simple'){
                if($this->isConfigurable($item->getProductId())){
                    $subxml .= $this->formatXMLRow($item, $n, $order->getIncrementId());
                    $sku = $item->getSku();
                     ++$n;
                }
                else{
                    // TODO Catch other types of products when needed
                }
            }
            elseif($sku == null){
                $subxml .= $this->formatXMLRow($item, $n, $order->getIncrementId());
                ++$n;        
            }           
           
        }

        $xml = '<OrderImport><Order><DOCUMENT>';
        foreach($inputs as $key => $val) {
            $xml .= "<".strtoupper($key).">{$val}</".strtoupper($key).">";
        }
        $xml .= '</DOCUMENT><DOCUMENT_ROWS>';
        $xml .= $subxml;
        $xml .= '</DOCUMENT_ROWS></Order></OrderImport>';

        return $xml;
    }

    protected function formatXMLRow($item, $n, $incrementId){
        $row  = '<row>';
        $row .= '<line_no>' . $n . '</line_no>';
        $row .= '<order_increment_id>' .  $incrementId . '</order_increment_id>';
        $row .= '<productsku>' .  $item->getSku() . '</productsku>';
        $row .= '<price>' .  $item->getPrice() . '</price>';
        $row .= '<quantity>' . $item->getQtyOrdered() . '</quantity>';
        $row .= '</row>';
        return $row;
    }

    protected function isConfigurable($productId){

        if(Mage::getModel('catalog/product')->load($productId)->isConfigurable()){
            return true;
        }
        else{
            return false;
        }
    }

    public function processFailedOrders($ordernum = 1000)
    {
        $i = 0;
        $collection = $this->getCollection()->addFieldToFilter('status', 0);
        foreach ($collection as $order) {

            $orderModel = Mage::getModel('sales/order')->load($order->getOrderId());

            $xml = simplexml_load_string($order->getResponse());
            if (strpos($xml->DATA->ERROR_DETAIL, 'DUPLICATE') === false) {
                if ($this->checkAttemps($order->getOrderId())) {
                    $this->closeAllStatus($order->getOrderId());
                }
                else {
                    Mage::getModel('fulfillment/order')->processXMLResponse($orderModel);
                }
            }
            else {
                $order->setStatus(1)->save();
                //$orderModel->getPayment()->setCcNumberEnc(0)->save();
            }
            $i++;
            if ($i > $ordernum) {
                break;
            }
        }
    }

    public function createCsvFailedOrders($limit = 1500)
{
    $j = 0;
    $i = 1;
    $year = date("Y", time());
    $month = date("m", time());
    $day = date("d", time());
    $minute = date("Hms", time());
    $file = 'dailycsv' . $year . '-' . $month . '-' . $day . '-' . $minute;

    $io = new Varien_Io_File();
    $filepath = Mage::getBaseDir('var');
    $filepath .= "/orders/export/";
    if ($io->checkAndCreateFolder($filepath)) {
        $io->cd($filepath);
        $io->streamOpen($file . '.csv');

        $collection = $this->getCollection();
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('file_id', 0);
        $collection->getSelect()->group('order_id');
        $line = '';
        foreach ($collection as $order) {

            if ($order->getOrderId() != 0) {

                $orderModel = Mage::getModel('sales/order')->load($order->getOrderId());
                $line = Mage::getModel('fulfillment/filetype_csv')->createCSV($orderModel, $i);
                $io->streamWriteCsv($line, ',', '"');
                $this->resetFileId($orderModel->getId(), 999);
                $j++;
            }
            else {
                $this->resetFileId(0, 999);
            }
            if ($i > $limit) {
                break;
            }
            $i++;
        }

        //echo system('zip -P a156h ' . realpath(".").'/'.$file .'.zip' . ' ' . realpath(".").'/'.$file .'.csv');
        system('zip -P a156h ' . $file . '.zip' . ' ' . $file . '.csv');
        //$io->rm($file .'.csv');
        $io->streamClose();

    }

    return $j;
}


    public function repostOrders()
    {
        $collection = $this->getCollection()->addFieldToFilter('status', 0);
        $cnt = 0;
        foreach ($collection as $order) {
            $orderModel = Mage::getModel('sales/order')->load($order->getOrderId());
            Mage::getModel('fulfillment/order')->processXMLResponse($orderModel);
            if ($cnt > 6000) {
                break;
            }
            $cnt++;
        }

        return $cnt;
    }

    public function closeAllStatus($orderId)
    {

        //	$response = 'OrderId ' . $orderId . ' Closed ';
        $collection = $this->getCollection()->addFieldToFilter('order_id', $orderId);
        foreach ($collection as $order) {
            $order->setStatus(1)->save();
            $response = $order->getResponse();
        }

        //Mage::getModel('sales/order')->load($orderId)->getPayment()->setCcNumberEnc(0)->save();
        if (Mage::getStoreConfig('fulfillment/email_contact/use_email') == 1) {
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'From: API ORDER <support@a2bf.com>' . "\r\n";
            mail(Mage::getStoreConfig('fulfillment/email_contact/emailaddress'), 'To many Attemps on Order ', $orderId, $headers);
        }
    }

    public function resetFileId($orderId, $fileresetId = 999)
    {
        $collection = $this->getCollection()->addFieldToFilter('order_id', $orderId);
        foreach ($collection as $order) {
            $order->setFileId($fileresetId)->save();
        }
    }

    public function checkAttemps($orderId, $attemps = 5)
    {
        $collection = $this->getCollection()->addFieldToFilter('order_id', $orderId);
        if (count($collection) > $attemps) {
            return true;
        }
        else {
            return false;
        }
    }
}

