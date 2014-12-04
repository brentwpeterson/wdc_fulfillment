<?php

class Wdc_Fulfillment_Adminhtml_FtpController extends Mage_Adminhtml_Controller_Action
{

    public function uploadAction()
    {
        $cnt = Mage::getModel('fulfillment/order')->createCsvFailedOrders();
        $i = 0;

        $p = Mage::getBaseDir('var') . DS . 'orders/export/';

        foreach (Mage::helper('fulfillment')->listFiles() as $file) {


            $filearray = array('path' => $p . '/', 'file' => $file);
            if (Mage::getStoreConfig('fulfillment/ftpgeneral/show_ftp') == '1') {
                if ($filearray) {
                    Mage::getModel('fulfillment/ftp')->uploadFile($filearray);
                    $i++;
                }
                else {
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('There are no files to upload at this time'));
                }
            }
            else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('FTP IS OFF'));
            }

        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('There were ' . $i . ' orders out of ' . $cnt . ' uploaded'));
        //Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('filepath=>'. $p . ' path'));
        $this->_redirect('*/sales_order/index');
    }

    public function indexAction()
    {
        {
            $this->_redirect('*/*/upload');
        }

    }

    public function reportAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog/product')
            ->_addContent($this->getLayout()->createBlock('fulfillment/adminhtml_report')->initForm());
        $this->renderLayout();
    }

    public function sendordersAction()
    {
        Mage::getModel('fulfillment/order')->processFailedOrders(250);
        Mage::log("Manual run of Cron started", null, "fulfillmentcron.log");
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The Cron Ran at ' . Mage::getModel('core/date')->date('Y-m-d H:i:s')));
        $this->_redirect('*/sales_order/index');
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

            $bill = array();
            $i = 0;

            foreach ($collection as $order) {
                $line = array();

                $line['DATE'] = $order->getCreatedAt();
                foreach ($order->getItemsCollection() as $item) {

                    $line['QTY'] = $item->getQtyOrdered();
                    $line['NAME'] = $item->getName();

                    $ar = $item->getProductOptions();
                    $j = 1;
                    foreach ($ar[attributes_info] as $option) {
                        $line['option' . $j] = $option[label] . ':' . $option[value];
                        $j++;
                    }

                }
                $bill[$i] = $line;
                //                foreach ($bill as $page){
                //                    $diff = array_diff($page, $line);
                //                    echo '<pre>';
                //                    print_r($diff);
                //                    echo '</pre>';
                //                    echo '<hr>';
                //                }
                $i++;
            }

            $year = date("Y", time());
            $month = date("m", time());
            $day = date("d", time());
            $minute = date("Hms", time());
            $file = 'dailycsv' . $year . '-' . $month . '-' . $day . '-' . $minute . '.csv';

            $io = new Varien_Io_File();
            $filepath = Mage::getBaseDir('var');
            $filepath .= "/tmp/";
            if ($io->checkAndCreateFolder($filepath)) {
                $io->cd($filepath);
                $io->streamOpen($file);
                foreach ($bill as $line) {
                    $io->streamWriteCsv($line, ',', '"');
                }

                $read = $io->read($filepath.$file);
                $io->streamClose();

                Mage::app()->getResponse()->setHeader('Content-Type', 'application/octet-stream');
                Mage::app()->getResponse()->sendHeaders();
                echo $read;
            }

           echo '</pre>';
        }
    }




}