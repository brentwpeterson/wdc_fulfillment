<?php
/**
 * User: brent
 * Date: 2/10/12
 * Time: 3:28 PM
 */

class Wdc_Fulfillment_Adminhtml_ReportsController extends Mage_Adminhtml_Controller_Action
{
    public function reviewAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog/product')
            ->_addContent($this->getLayout()->createBlock('fulfillment/adminhtml_report')->initForm());
        $this->renderLayout();
    }

    public function ordersAction(){
        $file = Mage::getModel('fulfillment/order')->processOrders();
        $io = new Varien_Io_File();

      //  echo $file['path'] . $file['file']

        $read = $io->read($file['path'] . $file['file']);
        $io->streamClose();

        Mage::app()->getResponse()->setHeader('Content-Type', 'application/octet-stream');
        Mage::app()->getResponse()->sendHeaders();
        echo $read;


    }

    public function downloadAction()
    {

        if ($data = $this->getRequest()->getPost()) {

            $collection = Mage::getModel('sales/order')->getCollection();
            $collection->addAttributeToFilter('created_at', array(
                'from' => $data['from_date'],
                'to' => $data['to_date'],
                'date' => true,
            ));

            if($data['store_id'] != 0){
            $collection->addAttributeToFilter('store_id', $data['store_id']);
            }


            $year = date("Y", time());
            $month = date("m", time());
            $day = date("d", time());
            $minute = date("Hms", time());
            $file = 'dailyxml' . $year . '-' . $month . '-' . $day . '-' . $minute . '.xml';

            header("Content-type: text/csv");
            header("Cache-Control: no-store, no-cache");
            header('Content-Disposition: attachment; filename="'.$file.'"');


            $outstream = fopen("php://output",'w');

            fputs($outstream, '<BOM xmlns:php="http://php.net/xsl">');


            foreach ($collection as $order) {
                fputs($outstream, Mage::getModel('fulfillment/rest_orders')->createXML($order->getId()));
            }

            fputs($outstream, '</BOM>');
            fclose($outstream);

        }
    }

}